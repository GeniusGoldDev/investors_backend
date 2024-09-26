@component('mail::message')
# Password rest

Reset or change your password.
click on the link below to reset your password.
If you did not request for password reset kindly ignore this mail.

@component('mail::button', ['url' => $token, 'color' => 'success'])
Change Password
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
