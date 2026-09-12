<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    /**
     * Log the authenticated customer out.
     */
    public function store(Request $request): RedirectResponse
    {
        Auth::logout();

        /*
         * Invalidate the authenticated session completely.
         */
        $request->session()->invalidate();

        /*
         * Generate a fresh CSRF token for the new guest session.
         */
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}