@extends('layouts.app')

@section('title', 'Préinscriptions à encaisser - KLASSCI')

@push('styles')
<style>
.miw{max-width:1180px;margin:0 auto;padding:24px}.miw-hero{background:linear-gradient(135deg,#0a3d8f,#0453cb);color:#fff;border-radius:18px;padding:28px;box-shadow:0 16px 45px rgba(4,83,203,.18)}.miw-hero h1{font-size:1.65rem;margin:0 0 8px}.miw-hero p{margin:0;opacity:.88}.miw-card{margin-top:20px;background:#fff;border:1px solid #e8edf5;border-radius:16px;box-shadow:0 8px 28px rgba(15,23,42,.06);overflow:hidden}.miw-row{display:grid;grid-template-columns:1.4fr 1fr 1fr auto;gap:16px;align-items:center;padding:16px 20px;border-bottom:1px solid #edf1f7}.miw-row:last-child{border-bottom:0}.miw-name{font-weight:800;color:#172033}.miw-muted{font-size:.83rem;color:#6b7280;margin-top:3px}.miw-badge{display:inline-flex;padding:5px 9px;border-radius:999px;background:#eef4ff;color:#0453cb;font-size:.76rem;font-weight:800}.miw-btn{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border-radius:10px;background:#0453cb;color:#fff;text-decoration:none;font-weight:800}.miw-empty{text-align:center;padding:44px;color:#64748b}@media(max-width:800px){.miw{padding:14px}.miw-row{grid-template-columns:1fr}.miw-btn{justify-content:center}}
</style>
@endpush

@section('content')
<div class="miw">
    <section class="miw-hero">
        <div class="miw-badge" style="background:rgba(255,255,255,.14);color:#fff;margin-bottom:10px">Workflow configurable · {{ $mode }}</div>
        <h1>Candidatures prêtes pour la caisse</h1>
        <p>Les informations viennent directement de la candidature en ligne : aucune ressaisie de l'étudiant n'est nécessaire.</p>
    </section>

    @if(session('success'))
        <div class="alert alert-success mt-3">{{ session('success') }}</div>
    @endif

    <section class="miw-card">
        @forelse($candidatures as $candidature)
            <div class="miw-row">
                <div>
                    <div class="miw-name">{{ $candidature->nom }} {{ $candidature->prenoms }}</div>
                    <div class="miw-muted">{{ $candidature->reference_publique ?: 'Dossier #'.$candidature->id }} · {{ $candidature->telephone ?: 'Téléphone non renseigné' }}</div>
                </div>
                <div>
                    <div class="miw-badge">{{ $candidature->filiere?->name ?: 'Filière à confirmer' }}</div>
                    <div class="miw-muted">{{ $candidature->niveau?->name ?: 'Niveau à confirmer' }}</div>
                </div>
                <div>
                    <div class="miw-muted">Rendez-vous / candidature</div>
                    <strong>{{ optional($candidature->traite_at)->format('d/m/Y H:i') ?: 'Acceptée' }}</strong>
                </div>
                <a class="miw-btn" href="{{ route('esbtp.admissions.workflow.show', $candidature) }}">
                    <i class="fas fa-cash-register" aria-hidden="true"></i> Ouvrir
                </a>
            </div>
        @empty
            <div class="miw-empty">
                <i class="fas fa-check-circle fa-2x mb-3" aria-hidden="true"></i>
                <div><strong>Aucune candidature en attente d'encaissement.</strong></div>
                <div class="miw-muted">Les dossiers déjà payés poursuivent automatiquement vers le contrôle des pièces.</div>
            </div>
        @endforelse
    </section>
</div>
@endsection
