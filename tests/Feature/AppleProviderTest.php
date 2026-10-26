<?php

namespace Tests\Feature;

use App\Services\Socialite\AppleProvider;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Tests\TestCase;
use UnexpectedValueException;

class AppleProviderTest extends TestCase
{
    /** @var array<int, array<string, mixed>> */
    private array $sentRequests = [];

    /**
     * Apple's provider fed with a canned token answer, signed by a test key that stands in for Apple's.
     *
     * @param  array<string, mixed>  $claims
     */
    private function provider(array $claims): AppleProvider
    {
        $appleKey = openssl_pkey_get_private((string) file_get_contents(base_path('tests/Fixtures/apple/id-token-signing.pem')));
        $rsa = openssl_pkey_get_details($appleKey)['rsa'];
        $jwks = ['keys' => [['kty' => 'RSA', 'kid' => 'apple-test', 'alg' => 'RS256', 'use' => 'sig',
            'n' => JWT::urlsafeB64Encode($rsa['n']), 'e' => JWT::urlsafeB64Encode($rsa['e'])]]];
        $idToken = JWT::encode([...$claims, 'iat' => time(), 'exp' => time() + 600], $appleKey, 'RS256', 'apple-test');

        $handler = HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode(['access_token' => 'at', 'id_token' => $idToken])),
            new Response(200, [], json_encode($jwks)),
        ]));
        $handler->push(Middleware::history($this->sentRequests));

        $request = Request::create('/auth/apple/callback', 'GET', ['code' => 'the-code', 'state' => 'the-state', 'user' => '{"name":{"firstName":"Ama","lastName":"Mensah"}}']);
        $request->setLaravelSession(app('session.store'));
        $request->session()->put('state', 'the-state');

        return Socialite::buildProvider(AppleProvider::class, ['client_id' => 'com.caresenda.web', 'client_secret' => null, 'redirect' => '/auth/apple/callback'])
            ->usingSigningKey('TEAM123', 'KEY123', base_path('tests/Fixtures/apple/AuthKey_TEST.p8'))
            ->setRequest($request)
            ->setHttpClient(new Client(['handler' => $handler]));
    }

    public function test_reads_the_person_from_a_verified_id_token(): void
    {
        $user = $this->provider(['iss' => 'https://appleid.apple.com', 'aud' => 'com.caresenda.web', 'sub' => 'apple-1', 'email' => 'ama@privaterelay.appleid.com', 'email_verified' => 'true'])->user();

        $this->assertSame('apple-1', $user->getId());
        $this->assertSame('Ama Mensah', $user->getName());
        $this->assertSame('ama@privaterelay.appleid.com', $user->getEmail());

        // The client secret is a JWT signed with the team's .p8 key.
        parse_str((string) $this->sentRequests[0]['request']->getBody(), $form);
        $ecKey = openssl_pkey_get_details(openssl_pkey_get_private((string) file_get_contents(base_path('tests/Fixtures/apple/AuthKey_TEST.p8'))))['key'];
        $secret = JWT::decode($form['client_secret'], new Key($ecKey, 'ES256'));
        $this->assertSame(['TEAM123', 'com.caresenda.web', 'https://appleid.apple.com'], [$secret->iss, $secret->sub, $secret->aud]);
    }

    public function test_rejects_a_token_issued_for_another_app(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->provider(['iss' => 'https://appleid.apple.com', 'aud' => 'com.someone.else', 'sub' => 'apple-1'])->user();
    }
}
