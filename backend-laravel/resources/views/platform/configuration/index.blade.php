@extends('layouts.soc')

@section('title', 'RansomShield — Centre de configuration')
@section('page_title', 'Centre de configuration')
@section('page_subtitle', 'Liaison réelle entre extensions, règles, seuils, politiques et paramètres')

@section('content')
    @include('platform.partials.page-tools-style')
    @include('platform.partials.network-visual-style')

    @php
        $summary = $configuration['summary'];
        $health = $configuration['health'];
        $pipeline = $configuration['pipeline'];
        $recommendations = $configuration['recommendations'];
        $riskMatrix = $configuration['risk_matrix'];
        $extensionGroups = $configuration['extension_groups'];
        $ruleChain = $configuration['rule_chain'];
        $safetySettings = $configuration['safety_settings'];
        $simulation = $configuration['simulation'];

        $healthClass = match ($health['level']) {
            'stable' => 'badge-normal',
            'correct' => 'badge-suspect',
            'incomplet' => 'badge-high',
            default => 'badge-critical',
        };

        $riskClass = function ($risk) {
            return match ($risk) {
                'critical' => 'badge-critical',
                'high' => 'badge-high',
                'suspect' => 'badge-suspect',
                default => 'badge-normal',
            };
        };
    @endphp

    <style>
        .config-pipeline {
            display: grid;
            gap: 16px;
            margin-top: 18px;
        }

        .config-step {
            display: grid;
            grid-template-columns: 70px 1fr auto;
            gap: 16px;
            align-items: center;
            padding: 18px;
            border-radius: 24px;
            border: 1px solid var(--border-soft);
            background: var(--bg-card);
            box-shadow: var(--shadow-soft);
            animation: pageFadeUp .55s ease both;
        }

        .config-step-number {
            width: 58px;
            height: 58px;
            display: grid;
            place-items: center;
            border-radius: 20px;
            background: color-mix(in srgb, var(--accent) 15%, transparent);
            color: var(--accent);
            font-weight: 950;
            border: 1px solid color-mix(in srgb, var(--accent) 25%, transparent);
        }

        .config-step h3,
        .link-card h3 {
            margin: 0;
            font-size: 17px;
            font-weight: 950;
            letter-spacing: -.03em;
        }

        .config-step p,
        .link-card p {
            margin: 6px 0 0;
            color: var(--text-muted);
            line-height: 1.6;
            font-size: 13px;
        }

        .config-impact {
            margin-top: 10px;
            padding: 10px 12px;
            border-radius: 16px;
            background: color-mix(in srgb, var(--accent-2) 8%, transparent);
            border: 1px solid color-mix(in srgb, var(--accent-2) 18%, transparent);
            color: var(--text-muted);
            font-size: 13px;
            line-height: 1.55;
        }

        .link-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .link-card {
            padding: 18px;
            border-radius: 24px;
            border: 1px solid var(--border-soft);
            background: var(--bg-card);
            box-shadow: var(--shadow-soft);
        }

        .link-row {
            margin-top: 12px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .simulation-flow {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 12px;
            margin-top: 18px;
        }

        .sim-node {
            border-radius: 20px;
            border: 1px solid var(--border-soft);
            background: color-mix(in srgb, var(--bg-panel-soft) 60%, transparent);
            padding: 14px;
            min-height: 120px;
        }

        .sim-label {
            font-size: 11px;
            font-weight: 950;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--text-muted);
        }

        .sim-value {
            margin-top: 8px;
            font-size: 20px;
            font-weight: 950;
            letter-spacing: -.04em;
        }

        .sim-hint {
            margin-top: 8px;
            color: var(--text-muted);
            font-size: 12px;
            line-height: 1.45;
        }

        @media (max-width: 1100px) {
            .simulation-flow,
            .link-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 760px) {
            .config-step {
                grid-template-columns: 1fr;
            }

            .config-step-number {
                width: 48px;
                height: 48px;
            }
        }
    </style>

    <div class="animated-page">
        <section class="analysis-hero">
            <div class="analysis-hero-content">
                <div>
                    <div class="analysis-kicker">
                        <span class="analysis-dot"></span>
                        Configuration liée
                    </div>

                    <h2>Voir comment chaque réglage influence la détection.</h2>

                    <p>
                        Cette page ne montre plus seulement des liens visuels. Elle lit les vraies données de la base :
                        extensions, règles, seuils, politiques et paramètres de sécurité.
                    </p>

                    <div class="section-gap" style="display:flex; gap:8px; flex-wrap:wrap;">
                        <span class="badge {{ $healthClass }}">État : {{ $health['level'] }}</span>
                        <span class="badge">Score cohérence : {{ $health['score'] }}%</span>
                        <span class="badge">Politiques validation : {{ $summary['approval_policies'] }}</span>
                    </div>

                    <div class="btn-row">
                        <form method="POST" action="{{ route('platform.configuration.reset-defaults') }}">
                            @csrf
                            <button type="submit" class="btn btn-primary">Réinitialiser défauts</button>
                        </form>

                        <a href="{{ route('platform.system-settings.index') }}" class="btn btn-soft">Paramètres</a>
                        <a href="{{ route('platform.detection-rules.index') }}" class="btn btn-soft">Règles</a>
                    </div>
                </div>

                <div class="network-orbit">
                    <div class="orbit-ring"></div>
                    <div class="orbit-ring"></div>
                    <div class="orbit-node n1"></div>
                    <div class="orbit-node n2"></div>
                    <div class="orbit-node n3"></div>
                    <div class="orbit-core">LINK</div>
                </div>
            </div>
        </section>

        <section class="smart-stats">
            <div class="smart-stat">
                <div class="smart-stat-label">Extensions actives</div>
                <div class="smart-stat-value">{{ $summary['extensions_enabled'] }}</div>
                <div class="smart-stat-hint">{{ $summary['extensions_critical'] }} critique(s).</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Règles actives</div>
                <div class="smart-stat-value">{{ $summary['rules_enabled'] }}</div>
                <div class="smart-stat-hint">{{ $summary['rules_total'] }} règle(s) au total.</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Seuils actifs</div>
                <div class="smart-stat-value">{{ $summary['thresholds_enabled'] }}</div>
                <div class="smart-stat-hint">{{ $summary['thresholds_total'] }} seuil(s) configuré(s).</div>
            </div>

            <div class="smart-stat">
                <div class="smart-stat-label">Politiques actives</div>
                <div class="smart-stat-value">{{ $summary['policies_enabled'] }}</div>
                <div class="smart-stat-hint">{{ $summary['manual_policies'] }} en manuel.</div>
            </div>
        </section>

        <section class="grid grid-2 section-gap">
            <div class="smart-card">
                <h3 class="smart-card-title">État de cohérence</h3>
                <p class="smart-card-subtitle">Analyse automatique de la chaîne de configuration.</p>

                <div class="recommendation-box section-gap">
                    <strong>{{ $health['level'] }} — {{ $health['score'] }}%</strong>
                    <br>
                    {{ $health['message'] }}
                </div>
            </div>

            <div class="smart-card">
                <h3 class="smart-card-title">Recommandations</h3>
                <p class="smart-card-subtitle">À vérifier avant les tests.</p>

                <div class="recommendation-box section-gap">
                    @foreach($recommendations as $recommendation)
                        <div>• {{ $recommendation }}</div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="soc-card section-gap">
            <div class="soc-card-header">
                <div>
                    <h3 class="soc-card-title">Simulation de chaîne de détection</h3>
                    <p class="soc-card-subtitle">Exemple calculé avec l’extension active la plus risquée.</p>
                </div>
            </div>

            <div class="simulation-flow">
                <div class="sim-node">
                    <div class="sim-label">Extension</div>
                    <div class="sim-value">{{ $simulation['extension'] }}</div>
                    <div class="sim-hint">Score extension : {{ $simulation['extension_score'] }}</div>
                </div>

                <div class="sim-node">
                    <div class="sim-label">Règle</div>
                    <div class="sim-value">{{ $simulation['rule_score'] }}</div>
                    <div class="sim-hint">{{ $simulation['rule'] }}</div>
                </div>

                <div class="sim-node">
                    <div class="sim-label">Score final</div>
                    <div class="sim-value">{{ $simulation['final_score'] }}</div>
                    <div class="sim-hint">Extension + règle</div>
                </div>

                <div class="sim-node">
                    <div class="sim-label">Niveau</div>
                    <div class="sim-value">{{ $simulation['risk_level'] }}</div>
                    <div class="sim-hint">{{ $simulation['threshold'] }}</div>
                </div>

                <div class="sim-node">
                    <div class="sim-label">Sécurité</div>
                    <div class="sim-value">{{ $simulation['safety']['human_approval'] === '1' ? 'OK' : '⚠' }}</div>
                    <div class="sim-hint">
                        Isolation réelle : {{ $simulation['safety']['real_isolation'] === '1' ? 'activée' : 'désactivée' }}
                        <br>
                        Approbation : {{ $simulation['safety']['human_approval'] === '1' ? 'oui' : 'non' }}
                    </div>
                </div>
            </div>

            <div class="link-row section-gap">
                @forelse($simulation['policies'] as $policy)
                    <span class="badge">
                        {{ $policy['action_type'] }} / {{ $policy['execution_mode'] }}
                    </span>
                @empty
                    <span class="badge badge-suspect">Aucune politique correspondante</span>
                @endforelse
            </div>
        </section>

        <section class="soc-card section-gap">
            <div class="soc-card-header">
                <div>
                    <h3 class="soc-card-title">Matrice seuils → politiques</h3>
                    <p class="soc-card-subtitle">Chaque niveau de risque doit déclencher une réponse cohérente.</p>
                </div>
            </div>

            <div class="link-grid">
                @foreach($riskMatrix as $row)
                    <article class="link-card">
                        <h3>
                            {{ $row['label'] }}
                            <span class="badge {{ $riskClass($row['risk_level']) }}">{{ $row['risk_level'] }}</span>
                        </h3>

                        <p>
                            Score :
                            {{ $row['min_score'] }}
                            -
                            {{ $row['max_score'] ?? '∞' }}
                        </p>

                        <div class="link-row">
                            @forelse($row['policies'] as $policy)
                                <span class="badge {{ $policy['is_enabled'] ? 'badge-normal' : 'badge-suspect' }}">
                                    {{ $policy['action_type'] }} / {{ $policy['execution_mode'] }}
                                </span>
                            @empty
                                <span class="badge badge-suspect">Aucune politique liée</span>
                            @endforelse
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="soc-card section-gap">
            <div class="soc-card-header">
                <div>
                    <h3 class="soc-card-title">Extensions sensibles par niveau</h3>
                    <p class="soc-card-subtitle">Les extensions alimentent la règle “Extension sensible détectée”.</p>
                </div>
            </div>

            <div class="link-grid">
                @foreach(['critical' => 'Critiques', 'high' => 'High', 'suspect' => 'Suspectes'] as $level => $title)
                    <article class="link-card">
                        <h3>{{ $title }} <span class="badge {{ $riskClass($level) }}">{{ $level }}</span></h3>

                        <div class="link-row">
                            @forelse($extensionGroups[$level] as $extension)
                                <span class="badge">
                                    .{{ $extension->extension }} / {{ $extension->score_weight }}
                                </span>
                            @empty
                                <span class="badge">Aucune extension</span>
                            @endforelse
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="soc-card section-gap">
            <div class="soc-card-header">
                <div>
                    <h3 class="soc-card-title">Chaîne règles → seuils → politiques</h3>
                    <p class="soc-card-subtitle">Chaque règle est évaluée contre les seuils actifs.</p>
                </div>
            </div>

            <div class="link-grid">
                @foreach($ruleChain as $rule)
                    <article class="link-card">
                        <h3>
                            {{ $rule['name'] }}
                            <span class="badge {{ $rule['is_enabled'] ? 'badge-normal' : 'badge-suspect' }}">
                                {{ $rule['is_enabled'] ? 'active' : 'inactive' }}
                            </span>
                        </h3>

                        <p>
                            Code : <span class="mono">{{ $rule['code'] }}</span>
                            <br>
                            Poids : {{ $rule['score_weight'] }}
                        </p>

                        <div class="config-impact">
                            @if($rule['threshold'])
                                Seuil correspondant :
                                <strong>{{ $rule['threshold']['risk_level'] }}</strong>
                                —
                                {{ $rule['threshold']['range'] }}
                            @else
                                Aucun seuil correspondant.
                            @endif
                        </div>

                        <div class="link-row">
                            @forelse($rule['policies'] as $policy)
                                <span class="badge">
                                    {{ $policy['action_type'] }} / {{ $policy['execution_mode'] }}
                                </span>
                            @empty
                                <span class="badge badge-suspect">Aucune politique</span>
                            @endforelse
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="soc-card section-gap">
            <div class="soc-card-header">
                <div>
                    <h3 class="soc-card-title">Chaîne globale RansomShield</h3>
                    <p class="soc-card-subtitle">Chaque étape influence directement la suivante.</p>
                </div>
            </div>

            <div class="config-pipeline">
                @foreach($pipeline as $step)
                    <article class="config-step">
                        <div class="config-step-number">{{ $step['step'] }}</div>

                        <div>
                            <h3>{{ $step['title'] }}</h3>
                            <p>{{ $step['description'] }}</p>

                            <div class="config-impact">
                                <strong>Impact :</strong> {{ $step['impact'] }}
                            </div>
                        </div>

                        <a href="{{ route($step['route']) }}" class="action-btn primary">Configurer</a>
                    </article>
                @endforeach
            </div>
        </section>
    </div>
@endsection
