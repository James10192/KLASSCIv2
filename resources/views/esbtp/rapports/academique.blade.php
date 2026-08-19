@extends('layouts.app')

@section('title', $title.' - KLASSCI')

@section('content')
@php
    $pdfRoute = match($kind ?? $report['kind'] ?? null) {
        'rentree' => 'esbtp.rapports.rentree.pdf',
        'trimestre' => 'esbtp.rapports.trimestre.pdf',
        'annuel' => 'esbtp.rapports.annuel.pdf',
        default => null,
    };
@endphp
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1">{{ $title }}</h1>
            <p class="text-muted mb-0">Effectifs et pilotage pédagogique. Aucun montant n'apparaît ici.</p>
        </div>
        @if($pdfRoute)
            <a class="btn btn-primary" href="{{ route($pdfRoute) }}">
                <i class="fas fa-file-pdf me-1"></i>Télécharger le PDF
            </a>
        @endif
    </div>
    <div class="row g-3">
        <div class="col-md-3"><div class="border rounded p-3"><div class="small text-muted">Inscrits valides</div><div class="fs-3 fw-bold">{{ $report['inscrits_valides'] }}</div></div></div>
        <div class="col-md-3"><div class="border rounded p-3"><div class="small text-muted">Classes</div><div class="fs-3 fw-bold">{{ $report['classes'] }}</div></div></div>
        <div class="col-md-3"><div class="border rounded p-3"><div class="small text-muted">Enseignants</div><div class="fs-3 fw-bold">{{ $report['enseignants'] }}</div></div></div>
        <div class="col-md-3"><div class="border rounded p-3"><div class="small text-muted">EDT manquants</div><div class="fs-3 fw-bold">{{ $report['edt_manquants'] }}</div></div></div>
        <div class="col-md-3"><div class="border rounded p-3"><div class="small text-muted">Non soldés</div><div class="fs-3 fw-bold">{{ $report['etudiants_non_soldes'] }}</div></div></div>
        <div class="col-md-3"><div class="border rounded p-3"><div class="small text-muted">Notes manquantes</div><div class="fs-3 fw-bold">{{ $report['notes_manquantes'] }}</div></div></div>
    </div>
</div>
@endsection
