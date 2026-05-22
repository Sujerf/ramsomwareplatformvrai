@extends('layouts.soc')

@section('title', "RansomShield — Seuils d'analyse")
@section('page_title', "Seuils d'analyse")
@section('page_subtitle', 'Transformation du score en niveau de risque')

@section('content')
    @include('platform.partials.page-tools-style')
    @include('platform.partials.network-visual-style')
    @include('platform.partials.config-premium-style')

    @php
        $items = method_exists($thresholds, 'items') ? collect($thresholds->items()) : collect($thresholds);

        $riskClass = fn ($risk) => match ($risk) {
            'critical' => 'badge-critical',
            'high' => 'badge-high',
            'suspect' => 'badge-suspect',
            default => 'badge-normal',
        };

        $enabled = $items->where('is_enabled', true)->count();
    @endphp

    <div class="animated-page">
        <section class="config-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Score vers risque
            </div>

            <h2>Calibrer la gravité des événements.</h2>

            <p>
                Les seuils transforment le score calculé par les règles et extensions en niveau :
                normal, suspect, high ou critical. Ce niveau déclenche ensuite les politiques.
            </p>

            <div class="btn-row">
                <a href="{{ route('platform.configuration.index') }}" class="action-btn primary">
                    <i class="fa-solid fa-diagram-project"></i> Centre configuration
                </a>
                <a href="{{ route('platform.detection-rules.index') }}" class="action-btn">
                    <i class="fa-solid fa-list-check"></i> Règles de détection
                </a>
                <form method="POST" action="{{ route('platform.configuration.reset-defaults') }}" style="display:contents">
                    @csrf
                    <button class="action-btn warning" type="submit">
                        <i class="fa-solid fa-rotate-left"></i> Restaurer défauts
                    </button>
                </form>
            </div>
        </section>

        <section class="config-mini-grid section-gap">
            <div class="config-mini"><small>Seuils visibles</small><strong>{{ $items->count() }}</strong></div>
            <div class="config-mini"><small>Actifs</small><strong>{{ $enabled }}</strong></div>
            <div class="config-mini"><small>Min global</small><strong>{{ $items->min('min_score') ?? 0 }}</strong></div>
            <div class="config-mini"><small>Max critique</small><strong>∞</strong></div>
        </section>

        <section class="config-grid section-gap">
            @forelse($items as $threshold)
                <article class="config-card">
                    <div class="config-card-head">
                        <div>
                            <h3 class="config-title">{{ $threshold->label ?? $threshold->name ?? $threshold->code }}</h3>
                            <div class="config-subtitle mono">{{ $threshold->code ?? $threshold->key }}</div>
                        </div>

                        <span class="badge {{ $riskClass($threshold->risk_level) }}">
                            {{ $threshold->risk_level }}
                        </span>
                    </div>

                    <div class="config-impact">
                        <strong>Impact :</strong>
                        un score entre
                        <strong>{{ $threshold->min_score }}</strong>
                        et
                        <strong>{{ $threshold->max_score ?? '∞' }}</strong>
                        devient
                        <span class="badge {{ $riskClass($threshold->risk_level) }}">{{ $threshold->risk_level }}</span>.
                    </div>

                    <form method="POST" action="{{ route('platform.detection-thresholds.update', $threshold) }}" class="config-form">
                        @csrf
                        @method('PUT')

                        <div class="config-form-row">
                            <div class="config-field">
                                <label>Score min</label>
                                <input class="form-control" type="number" name="min_score" value="{{ $threshold->min_score }}" min="0" max="1000">
                            </div>

                            <div class="config-field">
                                <label>Score max</label>
                                <input class="form-control" type="number" name="max_score" value="{{ $threshold->max_score }}" min="0" max="1000" placeholder="∞">
                            </div>

                            <div class="config-field">
                                <label>État</label>
                                <select class="form-control" name="is_enabled">
                                    <option value="1" @selected($threshold->is_enabled)>active</option>
                                    <option value="0" @selected(!$threshold->is_enabled)>inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="config-actions">
                            <button class="action-btn primary" type="submit">Enregistrer</button>
                        </div>
                    </form>
                </article>
            @empty
                @include('platform.partials.empty-state', [
                    'title' => 'Aucun seuil.',
                    'message' => 'Restaure les valeurs par défaut pour recréer les seuils.'
                ])
            @endforelse
        </section>

        @if(method_exists($thresholds, 'links'))
            <div class="pagination-wrap">{{ $thresholds->links() }}</div>
        @endif
    </div>
@endsection
