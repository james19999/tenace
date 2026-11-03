<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountSettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user()->load('primaryStockLocation');
        $roleLabel = match ($user->user_type) {
            'ADMINUSER' => 'Administrateur',
            'MNG' => 'Manager',
            'SCR' => 'Secrétaire',
            'VDS' => 'Commercial',
            'CSA' => 'Caissier',
            'LVS' => 'Livreur',
            'CALLCENTER' => 'SAV',
            'COMPTABLE' => 'Comptable',
            'RCM' => 'Responsable commercial',
            'GRO' => 'Grossiste',
            'INFO' => 'Informaticien / ingénieur informatique',
            'PT' => 'Partenaire',
            default => 'Utilisateur',
        };

        return view('account.settings', compact('user', 'roleLabel'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:32'],
        ]);

        $user->fill($data)->save();

        return back()->with('profile_saved', 'Tes informations personnelles ont été mises à jour.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
            'password.confirmed' => 'La confirmation du nouveau mot de passe ne correspond pas.',
        ]);

        $request->user()->forceFill(['password' => Hash::make($data['password'])])->save();

        return back()->with('password_saved', 'Ton mot de passe a été modifié.');
    }
}
