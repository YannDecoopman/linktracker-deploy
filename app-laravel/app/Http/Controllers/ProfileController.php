<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * STORY-046 : Page profil utilisateur — changement de mot de passe
 */
class ProfileController extends Controller
{
    public function show()
    {
        $user = auth()->user();

        if (! $user) {
            return redirect('/dashboard')->with('error', 'Aucun utilisateur configuré.');
        }

        return view('pages.profile.show', ['user' => $user]);
    }

    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        if (! $user) {
            return back()->with('error', 'Aucun utilisateur configuré.');
        }

        $request->validate([
            'current_password' => ['required', function ($attribute, $value, $fail) use ($user) {
                if (!Hash::check($value, $user->password)) {
                    $fail('Le mot de passe actuel est incorrect.');
                }
            }],
            'password' => 'required|min:8|confirmed',
        ], [
            'password.min'       => 'Le nouveau mot de passe doit comporter au moins 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Mot de passe mis à jour avec succès.');
    }
}
