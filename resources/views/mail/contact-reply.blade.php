<x-mail::message>
Hello {{ $contactMessage->name }},

{{ $contactMessage->reply }}

<x-mail::panel>
**You wrote:**

{{ $contactMessage->message }}
</x-mail::panel>

Thank you,<br>
The {{ \App\Support\Branding::name() }} team
</x-mail::message>
