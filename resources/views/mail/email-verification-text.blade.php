Hi {{ $user->name }},

Thanks for creating your {{ config('app.name') }} account. Please confirm your email address so we know it really is you.

To confirm your email address, open this link in your browser:

{{ $url }}

This confirmation link expires in 5 minutes.

You received this email because an account was registered on {{ config('app.name') }} with this email address. If this wasn't you, you can safely ignore this message.

--
{{ config('mail.from.name') }}
{{ config('mail.from.address') }}