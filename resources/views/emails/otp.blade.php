<x-mail::message>
    # Your One-Time Password (OTP)

    Your One-Time Password (OTP) for {{ $maskedIdentifier }} is:

    <div
        style="text-align: center; font-size: 24px; font-weight: bold; padding: 20px; border: 1px solid #ddd; border-radius: 5px; margin: 20px 0;">
        {{ $otp }}
    </div>

    This code is valid for 10 minutes.

    @if ($resetUrl)
        If you are trying to reset your password, you can also use this link:
        <x-mail::button :url="$resetUrl">
            Reset Password
        </x-mail::button>
    @else
        Please enter this code on the verification page to continue with your password reset.
    @endif

    If you did not request this, please ignore this email.

    Thanks,
    {{ config('app.name') }}
</x-mail::message>
