@component('mail::message')
# Email Verification

Your verification code is:

@component('mail::panel')
<h1 style="text-align: center; font-size: 32px;">{{ $code }}</h1>
@endcomponent

This code will expire in 60 minutes.

Thanks,<br>
{{ config('app.name') }}
@endcomponent