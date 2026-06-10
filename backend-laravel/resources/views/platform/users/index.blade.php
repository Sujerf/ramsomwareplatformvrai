@extends('layouts.soc')

@section('title', 'RansomShield — Gestion des utilisateurs')
@section('page_title', 'Utilisateurs')
@section('page_subtitle', 'Comptes SOC — administrateurs et analystes')

@section('content')
    @include('platform.partials.network-visual-style')

    @php
        $roleClass = fn($r) => match($r) {
            'admin'   => 'badge-critical',
            'analyst' => 'badge-normal',
            default   => 'badge',
        };
        $roleLabel = fn($r) => match($r) {
            'admin'   => 'Administrateur',
            'analyst' => 'Analyste',
            default   => $r,
        };
        $roleIcon = fn($r) => match($r) {
            'admin'   => 'fa-user-shield',
            'analyst' => 'fa-user-magnifying-glass',
            default   => 'fa-user',
        };
    @endphp

    <style>
        /* ── HERO ─────────────────────────────────────────────────── */
        .users-hero {
            position: relative;
            overflow: hidden;
            padding: 28px;
            border-radius: 32px;
            border: 1px solid var(--border-soft);
            background:
                radial-gradient(circle at 12% 20%, color-mix(in srgb, var(--accent) 14%, transparent), transparent 26%),
                radial-gradient(circle at 85% 14%, color-mix(in srgb, #a855f7 10%, transparent), transparent 30%),
                var(--bg-card);
            box-shadow: var(--shadow-soft);
            margin-bottom: 22px;
        }

        .users-hero h2 {
            margin: 10px 0 0;
            font-size: clamp(36px, 4.5vw, 60px);
            line-height: .93;
            letter-spacing: -.07em;
            font-weight: 950;
        }

        .users-hero p {
            color: var(--text-muted);
            line-height: 1.75;
            max-width: 700px;
            margin-top: 12px;
        }

        /* ── STAT STRIP ────────────────────────────────────────────── */
        .stat-strip {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 18px;
        }

        .stat-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 800;
            border: 1px solid var(--border-soft);
            background: color-mix(in srgb, var(--bg-panel-soft) 70%, transparent);
        }

        .stat-chip-accent {
            background: color-mix(in srgb, var(--accent) 13%, transparent);
            border-color: color-mix(in srgb, var(--accent) 25%, transparent);
            color: var(--accent);
        }

        .stat-chip-admin {
            background: rgba(239, 68, 68, 0.10);
            border-color: rgba(239, 68, 68, 0.22);
            color: #ef4444;
        }

        .stat-chip-analyst {
            background: rgba(34, 197, 94, 0.10);
            border-color: rgba(34, 197, 94, 0.22);
            color: #22c55e;
        }

        /* ── TABLE SECTION ─────────────────────────────────────────── */
        .users-table-wrap {
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            border-radius: 22px;
            box-shadow: var(--shadow-soft);
            overflow: hidden;
        }

        .users-table-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 18px 20px;
            border-bottom: 1px solid var(--border-soft);
        }

        .users-table-title {
            font-size: 15px;
            font-weight: 900;
            letter-spacing: -.02em;
        }

        /* ── CREATE FORM ───────────────────────────────────────────── */
        .create-form-wrap {
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            border-radius: 22px;
            box-shadow: var(--shadow-soft);
            padding: 24px;
            margin-top: 20px;
        }

        .create-form-title {
            font-size: 15px;
            font-weight: 900;
            letter-spacing: -.02em;
            margin: 0 0 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .create-form-title i {
            color: var(--accent);
        }

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

        .invalid-feedback {
            font-size: 12px;
            color: #ef4444;
            font-weight: 700;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 6px;
        }

        select.form-control {
            cursor: pointer;
        }

        /* ── TABLE ACTIONS ─────────────────────────────────────────── */
        .tbl-action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 6px 11px;
            border-radius: 10px;
            border: 1px solid var(--border-soft);
            font-size: 12px;
            font-weight: 800;
            background: color-mix(in srgb, var(--bg-panel-soft) 76%, transparent);
            color: var(--text-muted);
            text-decoration: none;
            cursor: pointer;
            transition: all .18s ease;
        }

        .tbl-action-btn:hover {
            color: var(--text-main);
            background: color-mix(in srgb, var(--accent) 11%, transparent);
            border-color: color-mix(in srgb, var(--accent) 22%, transparent);
        }

        .tbl-action-btn.tbl-action-danger:hover {
            color: #ef4444;
            background: rgba(239, 68, 68, 0.10);
            border-color: rgba(239, 68, 68, 0.25);
        }

        .tbl-action-btn.tbl-action-accent {
            color: var(--accent);
            background: color-mix(in srgb, var(--accent) 11%, transparent);
            border-color: color-mix(in srgb, var(--accent) 22%, transparent);
        }

        /* ── YOU PILL ──────────────────────────────────────────────── */
        .you-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 900;
            background: color-mix(in srgb, var(--accent) 14%, transparent);
            border: 1px solid color-mix(in srgb, var(--accent) 25%, transparent);
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        @media (max-width: 820px) {
            .form-grid { grid-template-columns: 1fr; }
            .form-grid .col-full { grid-column: auto; }
        }

        @keyframes pageFadeUp {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .anim { animation: pageFadeUp .45s ease both; }
        .anim-1 { animation-delay: .05s; }
        .anim-2 { animation-delay: .1s; }
    </style>

    {{-- ── HERO ────────────────────────────────────────────────────────── --}}
    <div class="users-hero anim">
        <span class="hero-kicker"><i class="fa-solid fa-users-gear"></i> &nbsp;Comptes SOC</span>
        <h2>Gestion des<br>utilisateurs</h2>
        <p>Créez et gérez les comptes d'accès à la console SOC. Les administrateurs ont accès
            à toutes les fonctionnalités ; les analystes gèrent les alertes, incidents et approbations.</p>

        <div class="stat-strip">
            <span class="stat-chip stat-chip-accent">
                <i class="fa-solid fa-users"></i>
                {{ $stats['total'] }} utilisateur{{ $stats['total'] > 1 ? 's' : '' }}
            </span>
            <span class="stat-chip stat-chip-admin">
                <i class="fa-solid fa-user-shield"></i>
                {{ $stats['admins'] }} admin{{ $stats['admins'] > 1 ? 's' : '' }}
            </span>
            <span class="stat-chip stat-chip-analyst">
                <i class="fa-solid fa-user-magnifying-glass"></i>
                {{ $stats['analysts'] }} analyste{{ $stats['analysts'] > 1 ? 's' : '' }}
            </span>
        </div>
    </div>

    {{-- ── TABLE UTILISATEURS ──────────────────────────────────────────── --}}
    <div class="users-table-wrap anim anim-1">
        <div class="users-table-header">
            <span class="users-table-title">
                <i class="fa-solid fa-list" style="color: var(--accent); margin-right: 8px;"></i>
                Comptes actifs
            </span>
        </div>

        @if($users->isEmpty())
            <div style="padding: 30px 20px;">
                <div class="empty-state">
                    <i class="fa-solid fa-users-slash" style="font-size: 22px; margin-bottom: 10px; display: block;"></i>
                    Aucun utilisateur enregistré.
                </div>
            </div>
        @else
            <div class="table-wrap">
                <table class="soc-table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>E-mail</th>
                            <th>Rôle</th>
                            <th>Compte créé</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $u)
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <span style="font-weight: 800;">{{ $u->name }}</span>
                                        @if($u->id === auth()->id())
                                            <span class="you-pill"><i class="fa-solid fa-circle-check"></i> vous</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="muted mono" style="font-size: 12px;">{{ $u->email }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $roleClass($u->role) }}">
                                        <i class="fa-solid {{ $roleIcon($u->role) }}" style="margin-right: 5px;"></i>
                                        {{ $roleLabel($u->role) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="muted" style="font-size: 12px;">{{ $u->created_at->format('d/m/Y') }}</span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 6px; justify-content: flex-end; flex-wrap: wrap;">
                                        <a href="{{ route('platform.users.edit', $u) }}" class="tbl-action-btn tbl-action-accent">
                                            <i class="fa-solid fa-pen-to-square"></i> Modifier
                                        </a>
                                        @if($u->id !== auth()->id())
                                            <form method="POST" action="{{ route('platform.users.destroy', $u) }}"
                                                  onsubmit="return confirm('Supprimer « {{ addslashes($u->name) }} » ?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="tbl-action-btn tbl-action-danger">
                                                    <i class="fa-solid fa-trash-can"></i> Supprimer
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ── ERREURS GLOBALES (delete, etc.) ────────────────────────────── --}}
    @if($errors->has('delete'))
        <div class="flash flash-error" style="margin-top: 16px;">
            <i class="fa-solid fa-circle-xmark" style="margin-right: 8px;"></i>
            {{ $errors->first('delete') }}
        </div>
    @endif

    {{-- ── FORMULAIRE CRÉER UTILISATEUR ───────────────────────────────── --}}
    <div class="create-form-wrap anim anim-2">
        <h3 class="create-form-title">
            <i class="fa-solid fa-user-plus"></i>
            Créer un utilisateur
        </h3>

        <form method="POST" action="{{ route('platform.users.store') }}">
            @csrf

            <div class="form-grid">
                {{-- Nom --}}
                <div class="form-group">
                    <label class="form-label" for="name">Nom complet</label>
                    <input type="text" id="name" name="name"
                           class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}"
                           value="{{ old('name') }}"
                           placeholder="Jean Dupont"
                           required>
                    @error('name')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                {{-- E-mail --}}
                <div class="form-group">
                    <label class="form-label" for="email">Adresse e-mail</label>
                    <input type="email" id="email" name="email"
                           class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                           value="{{ old('email') }}"
                           placeholder="jean@example.com"
                           required>
                    @error('email')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Rôle --}}
                <div class="form-group">
                    <label class="form-label" for="role">Rôle</label>
                    <select id="role" name="role"
                            class="form-control {{ $errors->has('role') ? 'is-invalid' : '' }}"
                            required>
                        <option value="">— Choisir un rôle —</option>
                        <option value="analyst" @selected(old('role') === 'analyst')>Analyste SOC</option>
                        <option value="admin"   @selected(old('role') === 'admin')>Administrateur</option>
                    </select>
                    @error('role')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Mot de passe --}}
                <div class="form-group">
                    <label class="form-label" for="password">Mot de passe</label>
                    <input type="password" id="password" name="password"
                           class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                           placeholder="Min. 8 caract., lettres + chiffres"
                           required>
                    @error('password')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Confirmation mot de passe --}}
                <div class="form-group">
                    <label class="form-label" for="password_confirmation">Confirmer le mot de passe</label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           class="form-control"
                           placeholder="Répétez le mot de passe"
                           required>
                </div>

                {{-- Bouton --}}
                <div class="form-group" style="justify-content: flex-end;">
                    <label class="form-label">&nbsp;</label>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" style="min-width: 160px;">
                            <i class="fa-solid fa-user-plus" style="margin-right: 8px;"></i>
                            Créer le compte
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

@endsection
