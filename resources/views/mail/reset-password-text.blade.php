Hello, {{ $user->name }},

You are receiving this email because we received a password reset request for your account.

To reset your password, open this link in your browser:

{{ $url }}

This password reset link will expire in {{ $count }} minutes.

If you did not request a password reset, no further action is required.

Thanks,
{{ config('mail.from.name') }}