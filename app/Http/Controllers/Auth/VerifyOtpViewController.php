<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\VerificationType;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class VerifyOtpViewController extends Controller
{
    /**
     * Display the OTP verification form.
     *
     * @endpoint GET /verify-otp
     */
    public function __invoke(Request $request): View
    {
        $email = $request->get('email');
        $phoneNumber = $request->get('phoneNumber');
        $identifier = $request->get('identifier');
        $resetType = $request->get('resetType', 'email');
        $verificationTypeValue = $request->get('verificationType', 'password_reset');

        $verificationType = VerificationType::tryFrom($verificationTypeValue)
            ?? VerificationType::PASSWORD_RESET();

        if (! $identifier) {
            $identifier = $resetType === 'email' ? $email : $phoneNumber;
        }

        return view('auth.verify-otp', [
            'email' => $email,
            'phoneNumber' => $phoneNumber,
            'resetType' => $resetType,
            'identifier' => $identifier,
            'verificationType' => $verificationType,
        ]);
    }
}
