@extends('layouts.app')

@section('title', 'Planning LMD')

@section('content')
<div class="lp-page" style="padding:2rem;">
    <h1>Planning LMD — Test minimal</h1>
    <p>Si tu vois cette page, le bug Blade est résolu (mode minimal).</p>
    <p>KPIs : {{ $kpis['ue_count'] ?? 0 }} UE · {{ $kpis['ecue_count'] ?? 0 }} ECUE · {{ $kpis['cect_total'] ?? 0 }} CECT</p>
    <p>Parcours actifs : {{ $parcours->count() }}</p>
    @if($parcoursSelected)
        <p>Parcours sélectionné : {{ $parcoursSelected->name }}</p>
    @else
        <p>Aucun parcours sélectionné</p>
    @endif
</div>
@endsection
