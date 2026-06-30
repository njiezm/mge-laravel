<?php

namespace App\Http\Controllers;

use App\Models\Utilisateur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = Utilisateur::query()
            ->where('email', $credentials['email'])
            ->where('actif', true)
            ->first();

        if (! $user || ! password_verify($credentials['password'], $user->mot_de_passe)) {
            return back()->withErrors(['email' => 'Identifiants incorrects.'])->withInput();
        }

        $request->session()->put([
            'user_id' => $user->id,
            'user_nom' => $user->code_utilisateur,
            'user_role' => $user->role,
        ]);

        if ($user->premiere_connexion) {
            try {
                if (!empty($user->email)) {
                    Mail::raw(
                        "Vous venez de vous connecter pour la premiere fois a MG Planner.\n\n" .
                        "Merci de modifier votre mot de passe afin de finaliser l'activation de votre compte.",
                        function ($message) use ($user): void {
                            $message->to((string) $user->email)->subject('MG Planner - Premiere connexion');
                        }
                    );
                }
            } catch (\Throwable) {
                // email reminder is best effort only
            }

            return redirect()->route('password.change');
        }

        return $user->role === 'admin'
            ? redirect()->route('admin.dashboard')
            : redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
