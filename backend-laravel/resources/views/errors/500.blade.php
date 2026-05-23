@extends('errors.layout')

@section('title', '500 — Erreur serveur')

@section('content')
    <div class="error-icon red">
        <i class="fa-solid fa-triangle-exclamation"></i>
    </div>

    <div class="error-code danger">500</div>

    <h1 class="error-title">Erreur serveur</h1>

    <p class="error-desc">
        Une erreur interne s'est produite sur le serveur RansomShield.
        L'équipe a été notifiée. Réessayez dans quelques instants.
    </p>

    <div class="divider"></div>

    <div class="error-actions">
        <a href="{{ url('/console/dashboard') }}" class="btn btn-primary">
            <i class="fa-solid fa-house"></i> Dashboard
        </a>
        <a onclick="location.reload()" href="#" class="btn btn-soft">
            <i class="fa-solid fa-rotate"></i> Réessayer
        </a>
    </div>
@endsection
