@extends('errors.layout')

@section('title', '404 — Page introuvable')

@section('content')
    <div class="error-icon blue">
        <i class="fa-solid fa-map-location-dot"></i>
    </div>

    <div class="error-code">404</div>

    <h1 class="error-title">Page introuvable</h1>

    <p class="error-desc">
        La page que vous cherchez n'existe pas ou a été déplacée.
        Vérifiez l'URL ou revenez au dashboard.
    </p>

    <div class="divider"></div>

    <div class="error-actions">
        <a href="{{ url('/console/dashboard') }}" class="btn btn-primary">
            <i class="fa-solid fa-house"></i> Dashboard
        </a>
        <a href="{{ url('/console/networks') }}" class="btn btn-soft">
            <i class="fa-solid fa-network-wired"></i> Réseaux
        </a>
        <a onclick="history.back()" href="#" class="btn btn-soft">
            <i class="fa-solid fa-arrow-left"></i> Retour
        </a>
    </div>
@endsection
