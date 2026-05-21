@extends('layouts.soc')

@section('title', 'RansomShield — Politiques de protection')
@section('page_title', 'Politiques de protection')
@section('page_subtitle', 'Réponses proposées selon le niveau de risque')

@section('content')
    @include('platform.partials.page-tools-style')
    @include('platform.partials.config-premium-style')

    @php
        $items = method_exists($policies, 'items') ? collect($policies->items()) : collect($policies);

        $riskClass = fn ($risk) => match ($risk) {
            'critical' => 'badge-critical',
            'high' => 'badge-high',
            'suspect' => 'badge-suspect',
            default => 'badge-normal',
        };

        $modeClass = fn ($mode) => match ($mode) {
            'automatic' => 'badge-normal',
            'approval_required' => 'badge-high',
            'manual' => 'badge-suspect',
            default => 'badge',
        };

        $enabled = $items->where('is_enabled', true)->count();
        $approval = $items->where('execution_mode', 'approval_required')->count();
        $manual = $items->where('execution_mode', 'manual')->count();
    @endphp

    <div class="animated-page">
        <section class="config-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Réponse automatisée contrôlée
            </div>

            <h2>Décider quoi faire après détection.</h2>

            <p>
                Les politiques déterminent la réponse proposée selon le niveau de risque :
                notification, restriction, isolation, action manuelle. Les actions sensibles doivent rester sous validation humaine.
            </p>

            <div class="btn-row">
                <a href="{{ route('platform.configuration.index') }}" class="btn btn-primary">Centre configuration</a>
                <a href="{{ route('platform.approval-queue.index') }}" class="btn btn-soft">File d’approbation</a>
            </div>
        </section>

        <section class="config-mini-grid section-gap">
            <div class="config-mini"><small>Politiques</small><strong>{{ $items->count() }}</strong></div>
            <div class="config-mini"><small>Actives</small><strong>{{ $enabled }}</strong></div>
            <div class="config-mini"><small>Approbation</small><strong>{{ $approval }}</strong></div>
            <div class="config-mini"><small>Manuelles</small><strong>{{ $manual }}</strong></div>
        </section>

        <section class="config-grid section-gap">
            @forelse($items as $policy)
                <article class="config-card">
                    <div class="config-card-head">
                        <div>
                            <h3 class="config-title">{{ $policy->name }}</h3>
                            <div class="config-subtitle mono">{{ $policy->code }}</div>
                        </div>

                        <span class="badge {{ $riskClass($policy->risk_level) }}">
                            {{ $policy->risk_level }}
                        </span>
                    </div>

                    <div class="config-impact">
                        <strong>Impact :</strong>
                        si le risque est
                        <span class="badge {{ $riskClass($policy->risk_level) }}">{{ $policy->risk_level }}</span>,
                        le système propose
                        <strong>{{ $policy->action_type }}</strong>
                        en mode
                        <span class="badge {{ $modeClass($policy->execution_mode) }}">{{ $policy->execution_mode }}</span>.
                    </div>

                    <form method="POST" action="{{ route('platform.protection-policies.update', $policy) }}" class="config-form">
                        @csrf
                        @method('PUT')

                        <div class="config-form-row">
                            <div class="config-field">
                                <label>Risque</label>
                                <select class="form-control" name="risk_level">
                                    <option value="normal" @selected($policy->risk_level === 'normal')>normal</option>
                                    <option value="suspect" @selected($policy->risk_level === 'suspect')>suspect</option>
                                    <option value="high" @selected($policy->risk_level === 'high')>high</option>
                                    <option value="critical" @selected($policy->risk_level === 'critical')>critical</option>
                                </select>
                            </div>

                            <div class="config-field">
                                <label>Mode</label>
                                <select class="form-control" name="execution_mode">
                                    <option value="automatic" @selected($policy->execution_mode === 'automatic')>automatic</option>
                                    <option value="approval_required" @selected($policy->execution_mode === 'approval_required')>approval_required</option>
                                    <option value="manual" @selected($policy->execution_mode === 'manual')>manual</option>
                                </select>
                            </div>

                            <div class="config-field">
                                <label>État</label>
                                <select class="form-control" name="is_enabled">
                                    <option value="1" @selected($policy->is_enabled)>active</option>
                                    <option value="0" @selected(!$policy->is_enabled)>inactive</option>
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
                    'title' => 'Aucune politique.',
                    'message' => 'Restaure les valeurs par défaut pour recréer les politiques.'
                ])
            @endforelse
        </section>

        @if(method_exists($policies, 'links'))
            <div class="pagination-wrap">{{ $policies->links() }}</div>
        @endif
    </div>
@endsection
