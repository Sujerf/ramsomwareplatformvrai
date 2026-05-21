@php
    $activeStatus = $activeStatus ?? request('status', 'active');
    $filters = $filters ?? [
        'active' => 'Actifs',
        'resolved' => 'Résolus',
        'false_positive' => 'Faux positifs',
        'all' => 'Tous',
    ];
@endphp

<div class="section-gap" style="display:flex; gap:8px; flex-wrap:wrap;">
    @foreach($filters as $key => $label)
        <a href="{{ request()->url() }}?status={{ $key }}"
           class="action-btn {{ $activeStatus === $key ? 'primary' : '' }}">
            {{ $label }}
        </a>
    @endforeach
</div>
