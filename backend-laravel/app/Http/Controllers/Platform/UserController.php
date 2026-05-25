<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    // ── Liste des utilisateurs ─────────────────────────────────────────────

    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::orderBy('role')->orderBy('name')->get();

        return view('platform.users.index', [
            'users' => $users,
            'stats' => [
                'total'    => $users->count(),
                'admins'   => $users->where('role', 'admin')->count(),
                'analysts' => $users->where('role', 'analyst')->count(),
            ],
        ]);
    }

    // ── Créer un utilisateur ───────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'role'     => ['required', 'in:admin,analyst'],
            'password' => ['required', Password::min(8)->letters()->numbers(), 'confirmed'],
        ], [
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
            'email.unique'       => 'Cette adresse e-mail est déjà utilisée.',
        ]);

        User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'role'     => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('platform.users.index')
            ->with('success', "Utilisateur « {$validated['name']} » créé avec succès.");
    }

    // ── Fiche édition ──────────────────────────────────────────────────────

    public function edit(User $user): View
    {
        $this->authorize('view', $user);

        return view('platform.users.edit', compact('user'));
    }

    // ── Mettre à jour nom / email / rôle ──────────────────────────────────

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $rules = [
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ];

        // Seul un admin peut changer le rôle d'un autre utilisateur
        if (Auth::user()->isAdmin() && Auth::id() !== $user->id) {
            $rules['role'] = ['required', 'in:admin,analyst'];
        }

        // Bag nommé pour distinguer les erreurs du formulaire profil vs mot de passe
        $validated = $request->validateWithBag('updateBag', $rules, [
            'email.unique' => 'Cette adresse e-mail est déjà utilisée.',
        ]);

        // Protéger le rôle : on ne peut pas se rétrograder soi-même
        if (isset($validated['role']) && Auth::id() === $user->id) {
            unset($validated['role']);
        }

        // Protéger le dernier admin
        if (
            isset($validated['role'])
            && $validated['role'] !== 'admin'
            && $user->role === 'admin'
            && User::where('role', 'admin')->count() <= 1
        ) {
            return back()->withErrors(
                ['role' => 'Impossible de rétrograder le dernier administrateur.'],
                'updateBag'
            );
        }

        $user->update($validated);

        $backRoute = Auth::id() === $user->id
            ? 'platform.users.edit'
            : 'platform.users.index';

        return redirect()
            ->route($backRoute, Auth::id() === $user->id ? $user : [])
            ->with('success', 'Profil mis à jour.');
    }

    // ── Changer le mot de passe ────────────────────────────────────────────

    public function updatePassword(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $rules = [
            'new_password' => ['required', Password::min(8)->letters()->numbers(), 'confirmed'],
        ];

        // L'utilisateur qui modifie son propre compte doit saisir l'ancien mdp
        if (Auth::id() === $user->id) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        // Bag nommé pour distinguer les erreurs du formulaire mot de passe vs profil
        $request->validateWithBag('passwordBag', $rules, [
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
            'new_password.confirmed'             => 'Les nouveaux mots de passe ne correspondent pas.',
        ]);

        $user->update(['password' => Hash::make($request->new_password)]);

        // Régénérer la session si l'utilisateur change son propre mot de passe
        if (Auth::id() === $user->id) {
            $request->session()->regenerate();
        }

        return back()->with('success', 'Mot de passe mis à jour avec succès.');
    }

    // ── Supprimer un utilisateur ──────────────────────────────────────────

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if (Auth::id() === $user->id) {
            return back()->withErrors(['delete' => 'Vous ne pouvez pas supprimer votre propre compte.']);
        }

        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return back()->withErrors(['delete' => 'Impossible de supprimer le dernier administrateur.']);
        }

        $name = $user->name;
        $user->delete();

        return redirect()
            ->route('platform.users.index')
            ->with('success', "Utilisateur « {$name} » supprimé.");
    }
}
