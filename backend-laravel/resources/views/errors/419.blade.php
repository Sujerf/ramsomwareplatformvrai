@extends('errors.layout')

@section('title', '419 — Session expirée')

@section('content')
    <div class="error-icon orange">
        <i class="fa-solid fa-clock-rotate-left"></i>
    </div>

    <div class="error-code warning">419</div>

    <h1 class="error-title">Session expirée</h1>

    <p class="error-desc">
        Votre session a expiré ou le jeton de sécurité (CSRF) n'est plus valide.
        Rechargez la page et réessayez.
    </p>

    <div class="divider"></div>

    <div class="error-actions">
        <a href="{{ url()->previous() ?: url('/console/dashboard') }}" class="btn btn-primary">
            <i class="fa-solid fa-rotate"></i> Recharger la page
        </a>
        <a href="{{ route('platform.login') }}" class="btn btn-soft">
            <i class="fa-solid fa-right-to-bracket"></i> Se reconnecter
        </a>
    </div>
@endsection
