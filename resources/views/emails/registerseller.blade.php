@component('mail::message')
# Verify Your Account

شكرًا على تسجيلك {{ $seller->name }}! الرجاء الضغط على الزر أدناه للتحقق من حسابك:

@component('mail::button', ['url' => url('api/verifyaccountseller/'. $seller->id)])
Verify Account
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
