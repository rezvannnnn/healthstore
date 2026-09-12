<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render(
            'Account/Profile/Index',
            [
                'user' => $request->user()->only([
                    'id',
                    'name',
                    'email',
                    'phone',
                    'phone_verified_at',
                ]),
            ]
        );
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'nullable',
                'string',
                'lowercase',
                'email',
                'max:255',
                'unique:users,email,' . $request->user()->id,
            ],
        ]);

        $request->user()->update([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
        ]);

        return back()->with(
            'status',
            'اطلاعات پروفایل با موفقیت به‌روزرسانی شد.'
        );
    }
}