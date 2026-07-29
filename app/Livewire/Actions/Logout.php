<?php

namespace App\Livewire\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Shared logout logic, invoked from any full-page Livewire component's own
 * logout() action (see Terminal::logout(), Dashboard::logout()) — this
 * class only clears the auth session; each caller decides where to redirect.
 */
class Logout
{
    public function __invoke(): void
    {
        Auth::guard('web')->logout();

        Session::invalidate();
        Session::regenerateToken();
    }
}
