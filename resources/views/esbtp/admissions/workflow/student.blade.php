@extends('layouts.app')

@section('title', 'Finaliser mon inscription - KLASSCI')

@push('styles')
<style>
.mws{max-width:980px;margin:0 auto;padding:24px}.mws-hero{background:linear-gradient(135deg,#0a3d8f,#0453cb);color:#fff;border-radius:18px;padding:26px}.mws-hero h1{font-size:1.55rem;margin:0 0 7px}.mws-hero p{margin:0;opacity:.88}.mws-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:16px}.mws-stat{background:#fff;border:1px solid #e6ebf3;border-radius:13px;padding:15px}.mws-stat small{color:#64748b}.mws-stat strong{display:block;margin-top:4px;color:#172033}.mws-card{margin-top:18px;background:#fff;border:1px solid #e6ebf3;border-radius:16px;padding:22px;box-shadow:0 8px 28px rgba(15,23,42,.05)}.mws-classes{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-top:14px}.mws-class{border:1px solid #dce4ef;border-radius:12px;padding:15px}.mws-class label{display:flex;gap:10px;cursor:pointer}.mws-class strong{color:#172033}.mws-muted{font-size:.82rem;color:#64748b}.mws-btn{margin-top:16px;border:0;border-radius:11px;padding:11px 16px;background:#0453cb;color:#fff;font-weight:800}.mws-lock{padding:12px;border-radius:11px;background:#fff7e8;color:#855300;margin-top:12px;font-size:.85rem}@media(max-width:720px){.mws{padding:14px}.mws-grid,.mws-classes{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div class="mws">
    <section class="mws-hero">
        <h1>Finaliser mon inscription</h1>
        <p>{{ $workflow->candidature?->prenoms }} {{ $workflow->candidature?->nom }} · {{ $workflow->candidature?->reference_publique }}</p>
    </section>

    @if(session('success'))<div class="alert alert-success mt-3">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger mt-3"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="mws-grid">
        <div class="mws-stat"><small>Préinscription</small><strong>{{ $workflow->paymentRecorded() ? 'Payée':'En attente' }}</strong></div>
        <div class="mws-stat"><small>Pièces physiques</small><strong>{{ $workflow->documentsValidated() ? 'Contrôlées':'En attente' }}</strong></div>
        <div class="mws-stat"><small>Espace KLASSCI</small><strong>{{ $workflow->accessActivated() ? 'Activé':'À activer' }}</strong></div>
    </div>

    @if($workflow->final_inscription_id)
        <section class="mws-card">
            <h2 style="font-size:1.15rem">Inscription finalisée</h2>
            <p>Votre inscription académique est terminée. Votre classe est <strong>{{ $workflow->selectedClass?->name }}</strong>.</p>
            <div class="mws-muted">Référence inscription : #{{ $workflow->final_inscription_id }}</div>
        </section>
    @elseif(!$workflow->paymentRecorded() || !$workflow->documentsValidated())
        <section class="mws-card">
            <h2 style="font-size:1.15rem">Votre dossier est encore en traitement</h2>
            <p>La classe pourra être choisie après le paiement de préinscription et le contrôle physique des pièces par l'établissement.</p>
        </section>
    @else
        <section class="mws-card">
            <h2 style="font-size:1.15rem">Choisissez votre classe</h2>
            <p class="mws-muted">Seules les classes correspondant à votre filière et votre niveau, avec une place disponible, sont proposées.</p>
            @if($classChoiceOnce)
                <div class="mws-lock"><strong>Choix unique.</strong> Après confirmation, vous ne pourrez plus modifier vous-même la classe. Une correction reste possible par l'administration avec motif.</div>
            @endif

            @if($workflow->classIsLocked())
                <p class="mt-3">Classe confirmée : <strong>{{ $workflow->selectedClass?->name }}</strong>.</p>
            @else
                <form method="POST" action="{{ route('esbtp.admissions.workflow.student.choose-class') }}">
                    @csrf
                    <div class="mws-classes">
                        @forelse($classes as $classe)
                            <div class="mws-class">
                                <label>
                                    <input type="radio" name="classe_id" value="{{ $classe->id }}" required>
                                    <span><strong>{{ $classe->name }}</strong><br><span class="mws-muted">{{ $classe->filiere?->name }} · {{ $classe->niveau?->name }}</span></span>
                                </label>
                            </div>
                        @empty
                            <div class="mws-muted">Aucune classe disponible pour le moment. Contactez la scolarité.</div>
                        @endforelse
                    </div>
                    @if($classes->isNotEmpty())
                        <button class="mws-btn" type="submit">Confirmer ma classe et finaliser mon inscription</button>
                    @endif
                </form>
            @endif
        </section>
    @endif
</div>
@endsection
