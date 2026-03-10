<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\MagicLinkMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class MagicLinkController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function sendLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'regex:/^.+@north-star\.network$/i'],
        ], [
            'email.regex' => 'Only @north-star.network emails are allowed.',
        ]);

        $url = URL::temporarySignedRoute(
            'magic-link.authenticate',
            now()->addMinutes(15),
            ['email' => $request->email]
        );

        Mail::to($request->email)->send(new MagicLinkMail($url));

        return back()->with('success', 'Check your email — a login link has been sent.');
    }

    public function authenticate(Request $request)
    {
        $user = User::firstOrCreate(
            ['email' => $request->query('email')],
            ['name' => explode('@', $request->query('email'))[0], 'password' => '']
        );

        Auth::login($user, remember: true);

        return redirect()->route('dashboard');
    }
}
