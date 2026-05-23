@extends('errors.layout')

@section('title', '403 — Accès refusé')

@section('content')
    <div class="error-icon red">
        <i class="fa-solid fa-shield-halved"></i>
    </div>

    <div class="error-code danger">403</div>

    <h1 class="error-title">Accès refusé</h1>

    <p class="error-desc">
        Vous n'avez pas les permissions nécessaires pour accéder à cette page.
        @if(isset($exception) && $exception->getMessage())
            <br><span style="color:rgba(239,68,68,.7); font-size:13px; margin-top:6px; display:block;">{{ $exception->getMessage() }}</span>
        @endif
    </p>

    <div class="divider"></div>

    <div class="error-actions">
        <a href="{{ url('/console/dashboard') }}" class="btn btn-primary">
            <i class="fa-solid fa-house"></i> Dashboard
        </a>
        <a onclick="history.back()" href="#" class="btn btn-soft">
            <i class="fa-solid fa-arrow-left"></i> Retour
        </a>
    </div>
@endsection
