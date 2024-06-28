@component('mail::message')
# Verify Your Account

شكرًا على تسجيلك {{ $admin->name }}! الرجاء الضغط على الزر أدناه للتحقق من حسابك:

@component('mail::button', ['url' => url('api/verifyaccountadmin/'. $admin->id)])
Verify Account
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
