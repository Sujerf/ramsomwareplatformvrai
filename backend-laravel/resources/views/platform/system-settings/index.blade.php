@extends('layouts.soc')

@section('title', 'RansomShield — Paramètres système')
@section('page_title', 'Paramètres système')
@section('page_subtitle', 'Configuration dynamique de la plateforme SOC')

@section('content')
    @include('platform.partials.page-tools-style')
    @include('platform.partials.network-visual-style')

    @php
        $settingsFlat = collect($settings);
        $settingsTotal = $stats['total'] ?? $settingsFlat->count();

        $enabledBooleans = $settingsFlat
            ->where('value_type', 'boolean')
            ->filter(fn ($setting) => (string) $setting->value === '1')
            ->count();

        $disabledBooleans = $settingsFlat
            ->where('value_type', 'boolean')
            ->filter(fn ($setting) => (string) $setting->value !== '1')
            ->count();

        $groupLabels = [
            'protection' => 'Protection',
            'detection' => 'Détection',
            'notifications' => 'Notifications',
            'notification' => 'Notifications',
            'ui' => 'Interface',
            'system' => 'Système',
            'security' => 'Sécurité',
        ];

        $impactText = function ($setting) {
            return match ($setting->key) {
                'enable_real_isolation' => 'Contrôle si RansomShield peut réellement isoler une machine. À garder désactivé pendant les tests.',
                'require_human_approval_for_sensitive_actions' => 'Force la validation humaine pour les actions sensibles comme isolation ou kill process.',
                'min_risk_level_for_incident' => 'Détermine à partir de quel niveau de risque un incident est créé.',
                'min_risk_level_for_action' => 'Détermine à partir de quel niveau une action de protection est proposée.',
                'notification_ui_enabled' => "Active ou désactive les notifications visibles dans l'interface.",
                'notification_sound_enabled' => "Active ou désactive l'alarme sonore navigateur.",
                'ui_theme' => 'Définit le thème par défaut de la console.',
                default => 'Paramètre global utilisé par la plateforme RansomShield.',
            };
        };

        $valueLabel = function ($setting) {
            if (($setting->value_type ?? 'string') === 'boolean') {
                return (string) $setting->value === '1' ? 'Activé' : 'Désactivé';
            }

            return is_array($setting->value)
                ? json_encode($setting->value, JSON_UNESCAPED_UNICODE)
                : (string) $setting->value;
        };
    @endphp

    <style>
        .settings-filter-bar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 18px;
        }

        .settings-filter-bar a {
            text-decoration: none;
        }

        .setting-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .setting-card {
            position: relative;
            overflow: hidden;
            padding: 18px;
            border-radius: 24px;
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            box-shadow: var(--shadow-soft);
            animation: pageFadeUp .45s ease both;
        }

        .setting-card::after {
            content: "";
            position: absolute;
            right: -50px;
            top: -50px;
            width: 130px;
            height: 130px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--accent) 8%, transparent);
        }

        .setting-card-head {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 14px;
        }

        .setting-title {
            margin: 0;
            font-size: 17px;
            font-weight: 950;
            letter-spacing: -.03em;
        }

        .setting-key {
            margin-top: 6px;
            color: var(--text-muted);
            font-size: 12px;
            word-break: break-all;
        }

        .setting-description {
            position: relative;
            z-index: 1;
            color: var(--text-muted);
            line-height: 1.65;
            font-size: 13px;
            margin: 12px 0;
        }

        .setting-impact {
            position: relative;
            z-index: 1;
            padding: 12px;
            border-radius: 16px;
            background: color-mix(in srgb, var(--accent-2) 7%, transparent);
            border: 1px solid color-mix(in srgb, var(--accent-2) 18%, transparent);
            color: var(--text-muted);
            line-height: 1.55;
            font-size: 13px;
            margin: 12px 0;
        }

        .setting-form {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 10px;
            margin-top: 14px;
        }

        .boolean-choice {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .boolean-choice label {
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 42px;
            padding: 10px 12px;
            border-radius: 15px;
            border: 1px solid var(--border-soft);
            background: color-mix(in srgb, var(--bg-panel-soft) 62%, transparent);
            color: var(--text-muted);
            font-weight: 850;
            font-size: 13px;
        }

        .boolean-choice input {
            accent-color: var(--accent);
        }

        .setting-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .setting-actions form,
        .setting-actions button,
        .setting-actions a {
            flex: 1 1 auto;
        }

        .setting-current {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 10px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--accent) 10%, transparent);
            border: 1px solid color-mix(in srgb, var(--accent) 18%, transparent);
            color: var(--text-main);
            font-size: 12px;
            font-weight: 900;
        }

        @media (max-width: 1050px) {
            .setting-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 700px) {
            .boolean-choice {
                grid-template-columns: 1fr;
            }

            .setting-actions {
                display: grid;
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="animated-page">
        <section class="analysis-hero">
            <div class="analysis-hero-content">
                <div>
                    <div class="analysis-kicker">
                        <span class="analysis-dot"></span>
                        Paramètres dynamiques
                    </div>

                    <h2>Contrôler le comportement global de RansomShield.</h2>

                    <p>
                        Cette page relie les paramètres aux comportements réels de la plateforme :
                        détection, création d'incident, actions sensibles, notifications et interface.
                    </p>

                    <div class="settings-filter-bar">
                        <a class="action-btn {{ !$activeGroup ? 'primary' : '' }}" href="{{ route('platform.system-settings.index') }}">
                            Tous
                        </a>

                        @foreach($groups as $group)
                            <a class="action-btn {{ $activeGroup === $group ? 'primary' : '' }}" href="{{ route('platform.system-settings.index', ['group' => $group]) }}">
                                {{ $groupLabels[$group] ?? ucfirst($group) }}
                            </a>
                        @endforeach

                        <form method="POST" action="{{ route('platform.configuration.reset-defaults') }}">
                            @csrf
                            <button class="action-btn warning" type="submit">Réinitialiser défauts</button>
                        </form>
                    </div>
                </div>

                <div class="network-orbit">
                    <div class="orbit-ring"></div>
                    <div class="orbit-ring"></div>
                    <div class="orbit-node n1"></div>
                    <div class="orbit-node n2"></div>
                    <div class="orbit-node n3"></div>
                    <div class="orbit-core">CFG</div>
                </div>
            </div>
        </section>

        <section class="smart-stats">
            <div class="smart-stat">
                <div class="smart-stat-label">Paramètres</div>
                <div class="smart-stat-value">{{ $settingsTotal }}</div>
                <div class="smart-stat-hint">Réglages enregistrés.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Protection</div>
                <div class="smart-stat-value">{{ $stats['protection'] ?? 0 }}</div>
                <div class="smart-stat-hint">Actions et sécurité.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Booléens activés</div>
                <div class="smart-stat-value">{{ $enabledBooleans }}</div>
                <div class="smart-stat-hint">{{ $disabledBooleans }} désactivé(s).</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Interface</div>
                <div class="smart-stat-value">{{ $stats['ui'] ?? 0 }}</div>
                <div class="smart-stat-hint">Thème et affichage.</div>
            </div>
        </section>

        <section class="grid grid-2 section-gap">
            <div class="smart-card">
                <h3 class="smart-card-title">Recommandation</h3>
                <p class="smart-card-subtitle">Mode conseillé pour ton mémoire et tes tests.</p>

                <div class="recommendation-box section-gap">
                    <strong>Garde les actions sensibles sous validation humaine.</strong>
                    <br>
                    Pour les tests, `enable_real_isolation` doit rester désactivé et
                    `require_human_approval_for_sensitive_actions` doit rester activé.
                </div>
            </div>

            <div class="smart-card">
                <h3 class="smart-card-title">Chaîne de liaison</h3>
                <p class="smart-card-subtitle">Comment les paramètres influencent le système.</p>

                <div class="recommendation-box section-gap">
                    Paramètres → seuils → politiques → actions → notifications.
                    <br>
                    Les paramètres système limitent les comportements dangereux même si une politique propose une action.
                </div>
            </div>
        </section>

        <section class="soc-card section-gap">
            <div class="soc-card-header">
                <div>
                    <h3 class="soc-card-title">Paramètres configurables</h3>
                    <p class="soc-card-subtitle">Chaque carte est directement liée à une valeur enregistrée en base.</p>
                </div>
            </div>

            @if($settingsFlat->count())
                <div class="setting-grid">
                    @foreach($settingsFlat as $setting)
                        @php
                            $type = $setting->value_type ?? 'string';
                            $group = $setting->group ?? 'system';
                            $defaultValue = data_get($setting->metadata, 'default_value');
                            $currentValue = $valueLabel($setting);
                        @endphp

                        <article class="setting-card">
                            <div class="setting-card-head">
                                <div>
                                    <h3 class="setting-title">{{ $setting->label ?? $setting->key }}</h3>
                                    <div class="setting-key mono">{{ $setting->key }}</div>
                                </div>

                                <span class="badge">{{ $groupLabels[$group] ?? $group }}</span>
                            </div>

                            <div class="setting-current">
                                Valeur actuelle : {{ $currentValue === '' ? '—' : $currentValue }}
                            </div>

                            <p class="setting-description">
                                {{ $setting->description ?? 'Aucune description disponible.' }}
                            </p>

                            <div class="setting-impact">
                                <strong>Impact :</strong> {{ $impactText($setting) }}
                            </div>

                            <form method="POST" action="{{ route('platform.system-settings.update', $setting) }}" class="setting-form">
                                @csrf
                                @method('PUT')

                                @if($type === 'boolean')
                                    <div class="boolean-choice">
                                        <label>
                                            <input type="radio" name="value" value="1" @checked((string) $setting->value === '1')>
                                            Activé
                                        </label>

                                        <label>
                                            <input type="radio" name="value" value="0" @checked((string) $setting->value !== '1')>
                                            Désactivé
                                        </label>
                                    </div>
                                @elseif($setting->key === 'ui_theme')
                                    <select name="value" class="form-control">
                                        <option value="soc_dark"   @selected($setting->value === 'soc_dark')>🌑 Dark SOC (défaut)</option>
                                        <option value="soc_light"  @selected($setting->value === 'soc_light')>☀️ Light SOC</option>
                                        <option value="cyber_blue" @selected($setting->value === 'cyber_blue')>🔵 Cyber Blue</option>
                                        <option value="oled_black" @selected($setting->value === 'oled_black')>⬛ OLED Black</option>
                                    </select>
                                @elseif(in_array($setting->key, ['min_risk_level_for_incident', 'min_risk_level_for_action'], true))
                                    <select name="value" class="form-control">
                                        <option value="normal" @selected($setting->value === 'normal')>normal</option>
                                        <option value="suspect" @selected($setting->value === 'suspect')>suspect</option>
                                        <option value="high" @selected($setting->value === 'high')>high</option>
                                        <option value="critical" @selected($setting->value === 'critical')>critical</option>
                                    </select>
                                @elseif($type === 'integer')
                                    <input class="form-control" type="number" name="value" value="{{ $setting->value }}">
                                @elseif($type === 'json')
                                    <textarea class="form-control" name="value" rows="4">{{ is_array($setting->value) ? json_encode($setting->value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $setting->value }}</textarea>
                                @else
                                    <input class="form-control" type="text" name="value" value="{{ $setting->value }}">
                                @endif

                                <div class="setting-actions">
                                    <button class="action-btn primary" type="submit">
                                        <i class="fa-solid fa-floppy-disk"></i> Enregistrer
                                    </button>
                                </div>
                            </form>

                            <form method="POST" action="{{ route('platform.system-settings.reset-one', $setting) }}" style="margin-top:8px">
                                @csrf
                                @method('PATCH')
                                <button class="action-btn warning" type="submit" style="width:100%" @disabled($defaultValue === null)>
                                    <i class="fa-solid fa-rotate-left"></i> Restaurer défaut
                                </button>
                            </form>
                        </article>
                    @endforeach
                </div>
            @else
                @include('platform.partials.empty-state', [
                    'title' => 'Aucun paramètre.',
                    'message' => "Clique sur 'Réinitialiser défauts' pour restaurer les paramètres de base."
                ])
            @endif
        </section>
    </div>
@endsection
