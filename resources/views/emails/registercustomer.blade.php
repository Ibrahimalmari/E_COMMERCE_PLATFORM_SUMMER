@component('mail::message')
# Verify Your Account

<p>Your verification code is: {{ $verificationCode }}</p>

Thanks,<br>
{{ config('app.name') }}
@endcomponent