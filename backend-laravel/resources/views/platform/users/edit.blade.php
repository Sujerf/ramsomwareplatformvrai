@extends('layouts.soc')

@section('title', 'RansomShield — Profil : ' . $user->name)
@section('page_title', 'Profil utilisateur')
@section('page_subtitle', $user->name . ' — Modifier les informations et le mot de passe')

@section('content')

    @php
        $isOwnProfile = auth()->id() === $user->id;
        $isAdmin      = auth()->user()->isAdmin();
        $roleLabel    = match($user->role) {
            'admin'   => 'Administrateur',
            'analyst' => 'Analyste SOC',
            default   => $user->role,
        };
        $roleClass = match($user->role) {
            'admin'   => 'badge-critical',
            'analyst' => 'badge-normal',
            default   => 'badge',
        };
    @endphp

    <style>
        .profile-header {
            display: flex;
            align-items: center;
            gap: 18px;
            padding: 24px 28px;
            border-radius: 28px;
            border: 1px solid var(--border-soft);
            background:
                radial-gradient(circle at 10% 25%, color-mix(in srgb, var(--accent) 13%, transparent), transparent 28%),
                var(--bg-card);
            box-shadow: var(--shadow-soft);
            margin-bottom: 22px;
        }

        .profile-avatar {
            width: 64px;
            height: 64px;
            border-radius: 20px;
            background: color-mix(in srgb, var(--accent) 18%, transparent);
            border: 1px solid color-mix(in srgb, var(--accent) 28%, transparent);
            display: grid;
            place-items: center;
            font-size: 26px;
            color: var(--accent);
            flex-shrink: 0;
        }

        .profile-meta h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 950;
            letter-spacing: -.04em;
        }

        .profile-meta p {
            margin: 5px 0 0;
            color: var(--text-muted);
            font-size: 13px;
        }

        .profile-meta .badge {
            margin-top: 8px;
        }

        .profile-back-link {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 13px;
            font-weight: 700;
            color: var(--text-muted);
            text-decoration: none;
            padding: 7px 12px;
            border-radius: 10px;
            border: 1px solid var(--border-soft);
            background: color-mix(in srgb, var(--bg-panel-soft) 70%, transparent);
            transition: all .18s ease;
            margin-left: auto;
        }

        .profile-back-link:hover {
            color: var(--text-main);
            background: color-mix(in srgb, var(--accent) 10%, transparent);
            border-color: color-mix(in srgb, var(--accent) 20%, transparent);
        }

        /* ── SECTION CARD ──────────────────────────────────────────── */
        .profile-section {
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            border-radius: 22px;
            box-shadow: var(--shadow-soft);
            padding: 24px;
            margin-bottom: 18px;
        }

        .profile-section-title {
            font-size: 15px;
            font-weight: 900;
            letter-spacing: -.02em;
            margin: 0 0 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .profile-section-title i {
            color: var(--accent);
        }

        .profile-section-divider {
            height: 1px;
            background: var(--border-soft);
            margin: 20px 0;
            opacity: 0.5;
        }

        /* ── FORM ELEMENTS ─────────────────────────────────────────── */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }

        .form-grid .col-full {
            grid-column: 1 / -1;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-label {
            font-size: 12px;
            font-weight: 800;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.07em;
        }

        .form-control {
            width: 100%;
            padding: 11px 13px;
            background: color-mix(in srgb, var(--bg-panel-soft) 80%, transparent);
            border: 1px solid var(--border-soft);
            border-radius: 13px;
            color: var(--text-main);
            font-size: 14px;
            font-weight: 600;
            outline: none;
            transition: border-color .18s ease, box-shadow .18s ease;
        }

        .form-control:focus {
            border-color: color-mix(in srgb, var(--accent) 55%, transparent);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 12%, transparent);
        }

        .form-control.is-invalid {
            border-color: rgba(239, 68, 68, 0.55);
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.10);
        }

        .form-control:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }

        .invalid-feedback {
            font-size: 12px;
            color: #ef4444;
            font-weight: 700;
        }

        select.form-control { cursor: pointer; }

        .form-hint {
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.5;
        }

        @media (max-width: 820px) {
            .form-grid { grid-template-columns: 1fr; }
            .form-grid .col-full { grid-column: auto; }
            .profile-header { flex-direction: column; align-items: flex-start; }
            .profile-back-link { margin-left: 0; }
        }

        @keyframes pageFadeUp {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .anim { animation: pageFadeUp .45s ease both; }
        .anim-1 { animation-delay: .07s; }
        .anim-2 { animation-delay: .14s; }
    </style>

    {{-- ── EN-TÊTE PROFIL ──────────────────────────────────────────────── --}}
    <div class="profile-header anim">
        <div class="profile-avatar">
            <i class="fa-solid {{ $user->role === 'admin' ? 'fa-user-shield' : 'fa-user-magnifying-glass' }}"></i>
        </div>
        <div class="profile-meta">
            <h2>{{ $user->name }}</h2>
            <p class="mono" style="font-size: 12px;">{{ $user->email }}</p>
            <div style="margin-top:6px; display:flex; align-items:center; flex-wrap:wrap; gap:6px;">
                <span class="badge {{ $roleClass }}">{{ $roleLabel }}</span>
                @if($isOwnProfile)
                    <span class="badge" style="color: var(--accent); background: color-mix(in srgb, var(--accent) 12%, transparent); border-color: color-mix(in srgb, var(--accent) 25%, transparent);">
                        <i class="fa-solid fa-circle-check" style="margin-right: 4px;"></i> Votre compte
                    </span>
                @endif
                @if($user->hasTwoFactorEnabled())
                    <span class="badge" style="color:#22c55e; background:rgba(34,197,94,.1); border-color:rgba(34,197,94,.25);">
                        <i class="fa-solid fa-mobile-screen-button" style="margin-right:4px;"></i>2FA active
                    </span>
                @endif
            </div>
            @if($user->last_login_at)
            <p style="margin-top:6px; font-size:11px; color:var(--text-muted);">
                <i class="fa-solid fa-clock" style="margin-right:4px; opacity:.6;"></i>
                Dernière connexion : {{ $user->last_login_at->format('d/m/Y à H:i') }}
            </p>
            @endif
            <p style="margin-top:3px; font-size:11px; color:var(--text-muted);">
                <i class="fa-solid fa-calendar-plus" style="margin-right:4px; opacity:.6;"></i>
                Compte créé le {{ $user->created_at->format('d/m/Y') }}
            </p>
        </div>
        @if($isAdmin && !$isOwnProfile)
            <a href="{{ route('platform.users.index') }}" class="profile-back-link">
                <i class="fa-solid fa-arrow-left"></i> Retour aux utilisateurs
            </a>
        @endif
    </div>

    {{-- ── SECTION 1 : INFOS GÉNÉRALES ────────────────────────────────── --}}
    <div class="profile-section anim anim-1">
        <h3 class="profile-section-title">
            <i class="fa-solid fa-id-card"></i>
            Informations du compte
        </h3>

        <form method="POST" action="{{ route('platform.users.update', $user) }}">
            @csrf
            @method('PATCH')

            <div class="form-grid">
                {{-- Nom --}}
                <div class="form-group">
                    <label class="form-label" for="name">Nom complet</label>
                    <input type="text" id="name" name="name"
                           class="form-control {{ $errors->updateBag->has('name') ? 'is-invalid' : '' }}"
                           value="{{ old('name', $user->name) }}"
                           required>
                    @if($errors->updateBag->has('name'))
                        <span class="invalid-feedback">{{ $errors->updateBag->first('name') }}</span>
                    @endif
                </div>

                {{-- E-mail --}}
                <div class="form-group">
                    <label class="form-label" for="email">Adresse e-mail</label>
                    <input type="email" id="email" name="email"
                           class="form-control {{ $errors->updateBag->has('email') ? 'is-invalid' : '' }}"
                           value="{{ old('email', $user->email) }}"
                           required>
                    @if($errors->updateBag->has('email'))
                        <span class="invalid-feedback">{{ $errors->updateBag->first('email') }}</span>
                    @endif
                </div>

                {{-- Rôle (admin seulement, et pas pour soi-même) --}}
                @if($isAdmin && !$isOwnProfile)
                <div class="form-group">
                    <label class="form-label" for="role">Rôle</label>
                    <select id="role" name="role"
                            class="form-control {{ $errors->updateBag->has('role') ? 'is-invalid' : '' }}"
                            required>
                        <option value="analyst" @selected(old('role', $user->role) === 'analyst')>Analyste SOC</option>
                        <option value="admin"   @selected(old('role', $user->role) === 'admin')>Administrateur</option>
                    </select>
                    @if($errors->updateBag->has('role'))
                        <span class="invalid-feedback">{{ $errors->updateBag->first('role') }}</span>
                    @endif
                </div>
                @else
                <div class="form-group">
                    <label class="form-label">Rôle</label>
                    <input type="text" class="form-control" value="{{ $roleLabel }}" disabled>
                    @if($isOwnProfile)
                        <span class="form-hint">Vous ne pouvez pas modifier votre propre rôle.</span>
                    @endif
                </div>
                @endif

                {{-- Bouton --}}
                <div class="form-group" style="justify-content: flex-end;">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary" style="min-width: 180px;">
                        <i class="fa-solid fa-floppy-disk" style="margin-right: 8px;"></i>
                        Enregistrer
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- ── SECTION 2 : CHANGER LE MOT DE PASSE ───────────────────────── --}}
    <div class="profile-section anim anim-2">
        <h3 class="profile-section-title">
            <i class="fa-solid fa-key"></i>
            Changer le mot de passe
        </h3>

        <form method="POST" action="{{ route('platform.users.update-password', $user) }}">
            @csrf
            @method('PATCH')

            <div class="form-grid">
                {{-- Mot de passe actuel (uniquement pour l'utilisateur lui-même) --}}
                @if($isOwnProfile)
                <div class="form-group col-full">
                    <label class="form-label" for="current_password">Mot de passe actuel</label>
                    <input type="password" id="current_password" name="current_password"
                           class="form-control {{ $errors->passwordBag->has('current_password') ? 'is-invalid' : '' }}"
                           placeholder="Entrez votre mot de passe actuel"
                           required>
                    @if($errors->passwordBag->has('current_password'))
                        <span class="invalid-feedback">{{ $errors->passwordBag->first('current_password') }}</span>
                    @endif
                </div>
                @endif

                {{-- Nouveau mot de passe --}}
                <div class="form-group">
                    <label class="form-label" for="new_password">Nouveau mot de passe</label>
                    <input type="password" id="new_password" name="new_password"
                           class="form-control {{ $errors->passwordBag->has('new_password') ? 'is-invalid' : '' }}"
                           placeholder="Min. 8 caract., lettres + chiffres"
                           required>
                    @if($errors->passwordBag->has('new_password'))
                        <span class="invalid-feedback">{{ $errors->passwordBag->first('new_password') }}</span>
                    @endif
                </div>

                {{-- Confirmation --}}
                <div class="form-group">
                    <label class="form-label" for="new_password_confirmation">Confirmer le nouveau mot de passe</label>
                    <input type="password" id="new_password_confirmation" name="new_password_confirmation"
                           class="form-control"
                           placeholder="Répétez le nouveau mot de passe"
                           required>
                </div>

                {{-- Bouton --}}
                <div class="form-group col-full">
                    <button type="submit" class="btn btn-primary" style="min-width: 200px;">
                        <i class="fa-solid fa-lock-keyhole" style="margin-right: 8px;"></i>
                        Mettre à jour le mot de passe
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- ── AUTHENTIFICATION À DEUX FACTEURS (profil propre uniquement) ── --}}
    @if($isOwnProfile)
    <div class="profile-section anim anim-3">
        <h3 class="profile-section-title">
            <i class="fa-solid fa-mobile-screen-button"></i>
            Authentification à deux facteurs (2FA)
        </h3>

        @if(auth()->user()->hasTwoFactorEnabled())
            @php $remaining = auth()->user()->recoveryCodesRemaining(); @endphp
            <p style="color: var(--text-muted); font-size: 13px; margin: 0 0 16px; line-height: 1.6;">
                <span style="color:#22c55e; font-weight:600;"><i class="fa-solid fa-circle-check" style="margin-right:6px;"></i>Active</span>
                — Votre compte est protégé par une application TOTP.
            </p>
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:16px; padding:12px 16px; background:{{ $remaining <= 2 ? 'rgba(245,158,11,.08)' : 'rgba(34,197,94,.07)' }}; border-radius:10px; border:1px solid {{ $remaining <= 2 ? 'rgba(245,158,11,.25)' : 'rgba(34,197,94,.2)' }};">
                <i class="fa-solid fa-key" style="color:{{ $remaining <= 2 ? '#f59e0b' : '#22c55e' }}; font-size:16px;"></i>
                <div style="flex:1; font-size:12px; color:var(--text-muted);">
                    <strong style="color:var(--text-primary);">{{ $remaining }} code(s) de secours</strong> restant(s)
                    @if($remaining <= 2) — <span style="color:#f59e0b;">Régénérez-en bientôt.</span> @endif
                </div>
                <a href="{{ route('platform.two-factor.recovery-codes') }}" style="font-size:11px; color:var(--accent); text-decoration:none; white-space:nowrap;">
                    Gérer <i class="fa-solid fa-arrow-right" style="font-size:10px;"></i>
                </a>
            </div>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <a href="{{ route('platform.two-factor.setup') }}" class="btn btn-soft" style="text-decoration:none;">
                    <i class="fa-solid fa-shield-halved"></i> Gérer la 2FA
                </a>
                <a href="{{ route('platform.two-factor.recovery-codes') }}" class="btn btn-soft" style="text-decoration:none;">
                    <i class="fa-solid fa-key"></i> Codes de secours
                </a>
            </div>
        @else
            <p style="color: var(--text-muted); font-size: 13px; margin: 0 0 16px; line-height: 1.6;">
                <span style="color:#f59e0b; font-weight:600;"><i class="fa-solid fa-triangle-exclamation" style="margin-right:6px;"></i>Désactivée</span>
                — Renforcez la sécurité de votre compte en activant la vérification en deux étapes.
            </p>
            <a href="{{ route('platform.two-factor.setup') }}" class="btn btn-primary" style="min-width:200px; text-decoration:none;">
                <i class="fa-solid fa-shield-halved"></i> Activer la 2FA
            </a>
        @endif
    </div>
    @endif

    {{-- ── ZONE DANGER (admin seulement, pas pour soi-même) ───────────── --}}
    @if($isAdmin && !$isOwnProfile)
    <div class="profile-section" style="border-color: rgba(239,68,68,0.25); background: color-mix(in srgb, rgba(239,68,68,0.05) 100%, var(--bg-card));">
        <h3 class="profile-section-title" style="color: #ef4444;">
            <i class="fa-solid fa-circle-exclamation" style="color: #ef4444;"></i>
            Zone dangereuse
        </h3>

        @if($errors->has('delete'))
            <div class="flash flash-error" style="margin-bottom: 14px;">
                {{ $errors->first('delete') }}
            </div>
        @endif

        <p style="color: var(--text-muted); font-size: 13px; margin: 0 0 14px;">
            La suppression du compte est irréversible. Les données liées (audit, historique) sont conservées.
        </p>

        <form method="POST" action="{{ route('platform.users.destroy', $user) }}"
              onsubmit="return confirm('Supprimer définitivement le compte de « {{ addslashes($user->name) }} » ?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn"
                    style="background: rgba(239,68,68,0.12); border-color: rgba(239,68,68,0.25); color: #ef4444; min-width: 180px;">
                <i class="fa-solid fa-trash-can" style="margin-right: 8px;"></i>
                Supprimer ce compte
            </button>
        </form>
    </div>
    @endif

@endsection
