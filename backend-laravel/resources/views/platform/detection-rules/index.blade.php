@extends('layouts.soc')

@section('title', 'RansomShield — Règles de détection')
@section('page_title', 'Règles de détection')
@section('page_subtitle', 'Règles utilisées par le moteur dynamique pour calculer le score de risque')

@section('content')
    @include('platform.partials.page-tools-style')
    @include('platform.partials.config-premium-style')

    @php
        $items = method_exists($rules, 'items') ? collect($rules->items()) : collect($rules);

        $riskClass = fn ($risk) => match ($risk) {
            'critical' => 'badge-critical',
            'high' => 'badge-high',
            'suspect' => 'badge-suspect',
            default => 'badge-normal',
        };

        $enabled = $items->where('is_enabled', true)->count();
        $critical = $items->where('risk_level', 'critical')->count();
        $high = $items->where('risk_level', 'high')->count();
        $suspect = $items->where('risk_level', 'suspect')->count();

        $ruleIcon = function ($code) {
            return match ($code) {
                'rule_sensitive_extension' => '🧬',
                'rule_mass_rename' => '🔁',
                'rule_ransom_note' => '📝',
                'rule_fast_write_activity' => '⚡',
                'rule_simulation_marker' => '🧪',
                default => '🧠',
            };
        };

        $ruleImpact = function ($rule) {
            return match ($rule->code) {
                'rule_sensitive_extension' => "Cette règle vérifie si l'extension du fichier est présente dans la table des extensions sensibles.",
                'rule_mass_rename' => "Cette règle détecte les renommages ou déplacements suspects, typiques d'un chiffrement massif.",
                'rule_ransom_note' => 'Cette règle détecte les fichiers de type README, RECOVER, DECRYPT ou instructions de rançon.',
                'rule_fast_write_activity' => "Cette règle augmente le score lors d'une activité rapide de création ou modification de fichiers.",
                'rule_simulation_marker' => 'Cette règle permet de tester le moteur sans déclencher une vraie attaque.',
                default => 'Cette règle participe au calcul dynamique du score de risque.',
            };
        };
    @endphp

    <style>
        .rule-card {
            display: grid;
            grid-template-columns: 58px minmax(0, 1fr);
            gap: 14px;
            align-items: flex-start;
        }

        .rule-icon {
            width: 58px;
            height: 58px;
            display: grid;
            place-items: center;
            border-radius: 20px;
            background: color-mix(in srgb, var(--accent) 11%, transparent);
            border: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);
            font-size: 25px;
        }

        .score-meter {
            margin-top: 12px;
            height: 12px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--text-muted) 10%, transparent);
            overflow: hidden;
        }

        .score-meter span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
        }

        .rule-tags {
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        @media (max-width: 720px) {
            .rule-card {
                grid-template-columns: 1fr;
            }

            .rule-icon {
                width: 52px;
                height: 52px;
            }
        }
    </style>

    <div class="animated-page">
        <section class="config-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Moteur de détection
            </div>

            <h2>Transformer les événements en score.</h2>

            <p>
                Les règles de détection analysent les événements reçus des agents.
                Chaque règle active ajoute un poids au score final, puis les seuils convertissent ce score en niveau de risque.
            </p>

            <div class="btn-row">
                <a href="{{ route('platform.configuration.index') }}" class="btn btn-primary">
                    Centre configuration
                </a>

                <a href="{{ route('platform.detection-thresholds.index') }}" class="btn btn-soft">
                    Voir seuils
                </a>

                <form method="POST" action="{{ route('platform.configuration.reset-defaults') }}">
                    @csrf
                    <button class="btn btn-soft" type="submit">Restaurer défauts</button>
                </form>
            </div>
        </section>

        <section class="config-mini-grid section-gap">
            <div class="config-mini">
                <small>Règles visibles</small>
                <strong>{{ $items->count() }}</strong>
            </div>

            <div class="config-mini">
                <small>Actives</small>
                <strong>{{ $enabled }}</strong>
            </div>

            <div class="config-mini">
                <small>Critical</small>
                <strong>{{ $critical }}</strong>
            </div>

            <div class="config-mini">
                <small>High / suspect</small>
                <strong>{{ $high + $suspect }}</strong>
            </div>
        </section>

        <section class="soc-card section-gap">
            <div class="soc-card-header">
                <div>
                    <h3 class="soc-card-title">Chaîne logique</h3>
                    <p class="soc-card-subtitle">Comment une règle influence réellement la réponse du système.</p>
                </div>
            </div>

            <div class="config-impact">
                Événement agent → règle active → score ajouté → seuil atteint → niveau de risque → politique de protection → action proposée.
            </div>
        </section>

        <section class="config-grid section-gap">
            @forelse($items as $rule)
                @php
                    $scorePercent = min(100, max(0, (int) $rule->score_weight));
                @endphp

                <article class="config-card">
                    <div class="rule-card">
                        <div class="rule-icon">{{ $ruleIcon($rule->code) }}</div>

                        <div>
                            <div class="config-card-head">
                                <div>
                                    <h3 class="config-title">{{ $rule->name }}</h3>
                                    <div class="config-subtitle mono">{{ $rule->code }}</div>
                                </div>

                                <span class="badge {{ $riskClass($rule->risk_level) }}">
                                    {{ $rule->risk_level }}
                                </span>
                            </div>

                            <div class="rule-tags">
                                <span class="score-pill">Score : {{ $rule->score_weight }}</span>
                                <span class="badge">{{ $rule->event_type ?? 'event générique' }}</span>
                                <span class="badge {{ $rule->is_enabled ? 'badge-normal' : 'badge-suspect' }}">
                                    {{ $rule->is_enabled ? 'active' : 'inactive' }}
                                </span>
                            </div>

                            <div class="score-meter">
                                <span style="width: {{ $scorePercent }}%"></span>
                            </div>

                            <div class="config-impact">
                                <strong>Impact :</strong> {{ $ruleImpact($rule) }}
                                <br>
                                Si cette règle correspond à l'événement, elle ajoute
                                <strong>{{ $rule->score_weight }}</strong> points au score dynamique.
                            </div>

                            <form method="POST" action="{{ route('platform.detection-rules.update', $rule) }}" class="config-form">
                                @csrf
                                @method('PUT')

                                <div class="config-form-row">
                                    <div class="config-field">
                                        <label>Risque</label>
                                        <select class="form-control" name="risk_level">
                                            <option value="normal" @selected($rule->risk_level === 'normal')>normal</option>
                                            <option value="suspect" @selected($rule->risk_level === 'suspect')>suspect</option>
                                            <option value="high" @selected($rule->risk_level === 'high')>high</option>
                                            <option value="critical" @selected($rule->risk_level === 'critical')>critical</option>
                                        </select>
                                    </div>

                                    <div class="config-field">
                                        <label>Poids score</label>
                                        <input class="form-control"
                                               type="number"
                                               name="score_weight"
                                               value="{{ $rule->score_weight }}"
                                               min="0"
                                               max="1000">
                                    </div>

                                    <div class="config-field">
                                        <label>État</label>
                                        <select class="form-control" name="is_enabled">
                                            <option value="1" @selected($rule->is_enabled)>active</option>
                                            <option value="0" @selected(!$rule->is_enabled)>inactive</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="config-actions">
                                    <button class="action-btn primary" type="submit">Enregistrer</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                @include('platform.partials.empty-state', [
                    'title' => 'Aucune règle de détection.',
                    'message' => 'Restaure les valeurs par défaut pour recréer les règles.'
                ])
            @endforelse
        </section>

        @if(method_exists($rules, 'links'))
            <div class="pagination-wrap">{{ $rules->links() }}</div>
        @endif
    </div>
@endsection
