Hi {{ $user->name }},

Your instructor application has been {{ $status }}.

@if ($feedback)
{{ $feedback }}
@endif

--
{{ config('mail.from.name') }}
{{ config('mail.from.address') }}