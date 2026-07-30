<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Handles the signed link from the VerifyEmail notification. Not a
 * Livewire component — it must be a plain route since it's hit directly
 * from an email client, never from an in-app request.
 */
class VerifyEmailController extends Controller
{
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $alreadyVerified = $request->user()->hasVerifiedEmail();

        $request->fulfill();

        return redirect()->route('pos.terminal')->with(
            'status',
            $alreadyVerified ? 'Email kamu sudah terverifikasi.' : 'Email kamu berhasil diverifikasi!'
        );
    }
}
