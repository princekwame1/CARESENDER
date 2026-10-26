<?php

namespace App\Services\Socialite;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User;
use UnexpectedValueException;

/**
 * "Sign in with Apple" for Socialite.
 *
 * Apple posts the result back to the site; SocialLoginController turns that POST into a GET
 * so the session cookie comes along and the usual state check still runs.
 */
class AppleProvider extends AbstractProvider
{
    private const ISSUER = 'https://appleid.apple.com';

    protected $scopes = ['name', 'email'];

    protected $scopeSeparator = ' ';

    private string $teamId = '';

    private string $keyId = '';

    private string $privateKey = '';

    /** The Apple Developer team, key id and .p8 key (its contents or a file path) used to sign the client secret. */
    public function usingSigningKey(string $teamId, string $keyId, string $privateKey): static
    {
        $this->teamId = $teamId;
        $this->keyId = $keyId;
        $this->privateKey = is_file($privateKey) ? (string) file_get_contents($privateKey) : str_replace('\n', "\n", $privateKey);

        return $this;
    }

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase(self::ISSUER.'/auth/authorize', $state);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getCodeFields($state = null): array
    {
        // Apple only sends the name and email when the answer is posted back.
        return [...parent::getCodeFields($state), 'response_mode' => 'form_post'];
    }

    protected function getTokenUrl(): string
    {
        return self::ISSUER.'/auth/token';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getTokenFields($code): array
    {
        return [...parent::getTokenFields($code), 'client_secret' => $this->clientSecret()];
    }

    public function user(): User
    {
        if ($this->user) {
            return $this->user;
        }

        if ($this->hasInvalidState()) {
            throw new InvalidStateException;
        }

        $response = $this->getAccessTokenResponse($this->getCode());

        return $this->userInstance($response, $this->claimsFromIdToken((string) Arr::get($response, 'id_token')));
    }

    /**
     * Apple has no profile endpoint: everything comes from the ID token.
     *
     * @return array<string, mixed>
     */
    protected function getUserByToken($token): array
    {
        return $this->claimsFromIdToken($token);
    }

    /**
     * @param  array<string, mixed>  $user
     */
    protected function mapUserToObject(array $user): User
    {
        // Apple sends the person's name once, on their very first sign-in, next to the token.
        $name = json_decode((string) $this->request->input('user'), true)['name'] ?? [];
        $fullName = trim(($name['firstName'] ?? '').' '.($name['lastName'] ?? ''));

        return (new User)->setRaw($user)->map([
            'id' => $user['sub'],
            'name' => $fullName !== '' ? $fullName : null,
            'email' => $user['email'] ?? null,
        ]);
    }

    /**
     * Checks the ID token's signature against Apple's published keys, then its issuer and audience.
     *
     * @return array<string, mixed>
     */
    private function claimsFromIdToken(string $idToken): array
    {
        $keys = Cache::remember('socialite.apple.jwks', now()->addHour(), fn () => json_decode(
            (string) $this->getHttpClient()->get(self::ISSUER.'/auth/keys')->getBody(), true,
        ));

        $claims = (array) JWT::decode($idToken, JWK::parseKeySet($keys, 'RS256'));

        if (($claims['iss'] ?? null) !== self::ISSUER || ($claims['aud'] ?? null) !== $this->clientId) {
            throw new UnexpectedValueException('The Apple ID token was not issued for this site.');
        }

        return $claims;
    }

    /** Apple wants a short-lived ES256 token in place of a fixed client secret. */
    private function clientSecret(): string
    {
        return JWT::encode([
            'iss' => $this->teamId,
            'iat' => time(),
            'exp' => time() + 300,
            'aud' => self::ISSUER,
            'sub' => $this->clientId,
        ], $this->privateKey, 'ES256', $this->keyId);
    }
}
