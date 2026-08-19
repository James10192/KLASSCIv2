@extends('layouts.app')

@section('title', $title.' - KLASSCI')

@section('content')
<div class="container-fluid py-4">
    <h1 class="h3 mb-3">{{ $title }}</h1>
    <p class="text-muted">Effectifs et pilotage pedagogique. Aucun montant n apparait ici.</p>
    <div class="row g-3">
        <div class="col-md-3"><div class="border rounded p-3"><div class="small text-muted">Inscrits valides</div><div class="fs-3 fw-bold">{{ $report['inscrits_valides'] }}</div></div></div>
        <div class="col-md-3"><div class="border rounded p-3"><div class="small text-muted">Classes</div><div class="fs-3 fw-bold">{{ $report['classes'] }}</div></div></div>
        <div class="col-md-3"><div class="border rounded p-3"><div class="small text-muted">Enseignants</div><div class="fs-3 fw-bold">{{ $report['enseignants'] }}</div></div></div>
        <div class="col-md-3"><div class="border rounded p-3"><div class="small text-muted">EDT manquants</div><div class="fs-3 fw-bold">{{ $report['edt_manquants'] }}</div></div></div>
        <div class="col-md-3"><div class="border rounded p-3"><div class="small text-muted">Non soldes</div><div class="fs-3 fw-bold">{{ $report['etudiants_non_soldes'] }}</div></div></div>
        <div class="col-md-3"><div class="border rounded p-3"><div class="small text-muted">Notes manquantes</div><div class="fs-3 fw-bold">{{ $report['notes_manquantes'] }}</div></div></div>
    </div>
</div>
@endsection