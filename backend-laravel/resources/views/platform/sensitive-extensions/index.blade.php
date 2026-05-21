@extends('layouts.soc')

@section('title', 'RansomShield — Extensions sensibles')
@section('page_title', 'Extensions sensibles')
@section('page_subtitle', 'Extensions à surveiller pour détecter les comportements ransomware')

@section('content')
    @include('platform.partials.page-tools-style')
    @include('platform.partials.config-premium-style')

    @php
        $items = method_exists($extensions, 'items') ? collect($extensions->items()) : collect($extensions);

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
    @endphp

    <div class="animated-page">
        <section class="config-hero">
            <div class="analysis-kicker">
                <span class="analysis-dot"></span>
                Extensions ransomware
            </div>

            <h2>Définir les extensions à risque.</h2>

            <p>
                Ces extensions alimentent directement la règle “Extension sensible détectée”.
                Plus le poids est élevé, plus l’événement contribue au score final.
            </p>

            <div class="btn-row">
                <a href="{{ route('platform.configuration.index') }}" class="btn btn-primary">Centre configuration</a>
                <form method="POST" action="{{ route('platform.configuration.reset-defaults') }}">
                    @csrf
                    <button class="btn btn-soft" type="submit">Restaurer défauts</button>
                </form>
            </div>
        </section>

        <section class="config-mini-grid section-gap">
            <div class="config-mini"><small>Actives</small><strong>{{ $enabled }}</strong></div>
            <div class="config-mini"><small>Critical</small><strong>{{ $critical }}</strong></div>
            <div class="config-mini"><small>High</small><strong>{{ $high }}</strong></div>
            <div class="config-mini"><small>Suspect</small><strong>{{ $suspect }}</strong></div>
        </section>

        <section class="soc-card section-gap">
            <div class="soc-card-header">
                <div>
                    <h3 class="soc-card-title">Ajouter une extension</h3>
                    <p class="soc-card-subtitle">Exemple : locked, encrypted, crypt, enc.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('platform.sensitive-extensions.store') }}" class="config-form">
                @csrf

                <div class="config-form-row">
                    <div class="config-field">
                        <label>Extension</label>
                        <input class="form-control" type="text" name="extension" placeholder="locked" required>
                    </div>

                    <div class="config-field">
                        <label>Niveau</label>
                        <select class="form-control" name="risk_level">
                            <option value="suspect">suspect</option>
                            <option value="high">high</option>
                            <option value="critical">critical</option>
                        </select>
                    </div>

                    <div class="config-field">
                        <label>Poids score</label>
                        <input class="form-control" type="number" name="score_weight" value="50" min="0" max="1000" required>
                    </div>
                </div>

                <div class="config-actions">
                    <button class="action-btn primary" type="submit">Ajouter / Mettre à jour</button>
                </div>
            </form>
        </section>

        <section class="config-grid section-gap">
            @forelse($items as $extension)
                <article class="config-card">
                    <div class="config-card-head">
                        <div>
                            <h3 class="config-title">.{{ $extension->extension }}</h3>
                            <div class="config-subtitle">
                                Extension surveillée par le moteur dynamique.
                            </div>
                        </div>

                        <span class="badge {{ $riskClass($extension->risk_level) }}">
                            {{ $extension->risk_level }}
                        </span>
                    </div>

                    <div class="config-impact">
                        <strong>Impact :</strong>
                        si un fichier reçoit l’extension <strong>.{{ $extension->extension }}</strong>,
                        le moteur ajoute <strong>{{ $extension->score_weight }}</strong> points au score.
                    </div>

                    <form method="POST" action="{{ route('platform.sensitive-extensions.update', $extension) }}" class="config-form">
                        @csrf
                        @method('PUT')

                        <div class="config-form-row">
                            <div class="config-field">
                                <label>Niveau</label>
                                <select class="form-control" name="risk_level">
                                    <option value="suspect" @selected($extension->risk_level === 'suspect')>suspect</option>
                                    <option value="high" @selected($extension->risk_level === 'high')>high</option>
                                    <option value="critical" @selected($extension->risk_level === 'critical')>critical</option>
                                </select>
                            </div>

                            <div class="config-field">
                                <label>Poids</label>
                                <input class="form-control" type="number" name="score_weight" value="{{ $extension->score_weight }}" min="0" max="1000">
                            </div>

                            <div class="config-field">
                                <label>État</label>
                                <select class="form-control" name="is_enabled">
                                    <option value="1" @selected($extension->is_enabled)>active</option>
                                    <option value="0" @selected(!$extension->is_enabled)>inactive</option>
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
                    'title' => 'Aucune extension.',
                    'message' => 'Ajoute une extension ou restaure les valeurs par défaut.'
                ])
            @endforelse
        </section>

        @if(method_exists($extensions, 'links'))
            <div class="pagination-wrap">{{ $extensions->links() }}</div>
        @endif
    </div>
@endsection
