@extends('layouts.app')

@section('title', 'Dossier '.$candidature->reference_publique.' - KLASSCI')

@push('styles')
<style>
.mwf{max-width:1220px;margin:0 auto;padding:24px}.mwf-hero{background:linear-gradient(135deg,#0a3d8f,#0453cb);color:#fff;border-radius:18px;padding:26px;display:flex;justify-content:space-between;gap:22px;align-items:flex-start}.mwf-hero h1{font-size:1.55rem;margin:0 0 6px}.mwf-hero p{margin:0;opacity:.86}.mwf-pill{display:inline-flex;padding:6px 10px;border-radius:999px;background:rgba(255,255,255,.14);font-size:.78rem;font-weight:800}.mwf-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-top:18px}.mwf-card{background:#fff;border:1px solid #e5eaf2;border-radius:16px;padding:20px;box-shadow:0 8px 28px rgba(15,23,42,.05)}.mwf-card h2{font-size:1.05rem;color:#172033;margin:0 0 14px}.mwf-kv{display:grid;grid-template-columns:150px 1fr;gap:8px;font-size:.9rem;padding:6px 0;border-bottom:1px solid #f1f4f8}.mwf-kv span{color:#64748b}.mwf-steps{display:grid;grid-template-columns:repeat(5,1fr);gap:8px;margin-top:16px}.mwf-step{padding:10px 8px;border-radius:10px;background:#f3f6fb;text-align:center;font-size:.73rem;font-weight:800;color:#64748b}.mwf-step.done{background:#eaf7ef;color:#14763d}.mwf-step.current{background:#eaf1ff;color:#0453cb}.mwf-form{display:grid;grid-template-columns:1fr 1fr;gap:10px}.mwf-field{display:flex;flex-direction:column;gap:5px}.mwf-field label{font-size:.78rem;font-weight:800;color:#475569}.mwf-field input,.mwf-field select,.mwf-field textarea{border:1px solid #dce3ed;border-radius:10px;padding:10px;background:#fff}.mwf-btn{border:0;border-radius:10px;padding:10px 14px;font-weight:800;cursor:pointer}.mwf-btn.primary{background:#0453cb;color:#fff}.mwf-btn.soft{background:#eef4ff;color:#0453cb}.mwf-btn.success{background:#137c48;color:#fff}.mwf-btn.danger{background:#fff0f0;color:#b42318}.mwf-piece{border:1px solid #edf1f6;border-radius:12px;padding:13px;margin-bottom:10px}.mwf-piece-top{display:flex;justify-content:space-between;gap:10px}.mwf-piece strong{color:#1f2937}.mwf-status{font-size:.76rem;font-weight:800;border-radius:999px;padding:5px 8px;background:#f3f6fb}.mwf-status.ok{background:#eaf7ef;color:#14763d}.mwf-status.warn{background:#fff7e6;color:#a35c00}.mwf-full{grid-column:1/-1}.mwf-muted{font-size:.82rem;color:#64748b}.mwf-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}@media(max-width:850px){.mwf{padding:14px}.mwf-grid{grid-template-columns:1fr}.mwf-steps{grid-template-columns:1fr 1fr}.mwf-form{grid-template-columns:1fr}.mwf-kv{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
@php
    $states = [
        'awaiting_payment' => 1,
        'awaiting_documents' => 2,
        'awaiting_activation' => 3,
        'awaiting_student' => 4,
        'ready_to_finalize' => 5,
        'completed' => 6,
    ];
    $current = $states[$workflow->state] ?? 0;
@endphp
<div class="mwf">
    <section class="mwf-hero">
        <div>
            <div class="mwf-pill">{{ $candidature->reference_publique ?: 'Candidature #'.$candidature->id }}</div>
            <h1>{{ $candidature->nom }} {{ $candidature->prenoms }}</h1>
            <p>{{ $candidature->filiere?->name }} · {{ $candidature->niveau?->name }} · parcours {{ $mode }}</p>
        </div>
        <div class="mwf-pill">État : {{ str_replace('_', ' ', $workflow->state) }}</div>
    </section>

    <div class="mwf-steps">
        @foreach(['Caisse','Pièces','Activation','Espace étudiant','Classe'] as $i => $label)
            @php $n=$i+1; @endphp
            <div class="mwf-step {{ $current > $n ? 'done' : ($current === $n ? 'current' : '') }}">{{ $n }} · {{ $label }}</div>
        @endforeach
    </div>

    @if(session('success'))<div class="alert alert-success mt-3">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger mt-3"><strong>Le dossier n'a pas été modifié.</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="mwf-grid">
        <section class="mwf-card">
            <h2>Identité reprise de la candidature</h2>
            <div class="mwf-kv"><span>Téléphone</span><strong>{{ $candidature->telephone ?: '—' }}</strong></div>
            <div class="mwf-kv"><span>E-mail</span><strong>{{ $candidature->email ?: '—' }}</strong></div>
            <div class="mwf-kv"><span>Naissance</span><strong>{{ optional($candidature->date_naissance)->format('d/m/Y') ?: '—' }} · {{ $candidature->lieu_naissance ?: '—' }}</strong></div>
            <div class="mwf-kv"><span>Filière / niveau</span><strong>{{ $candidature->filiere?->name ?: '—' }} / {{ $candidature->niveau?->name ?: '—' }}</strong></div>
            <div class="mwf-kv"><span>Dossier provisoire</span><strong>{{ $workflow->etudiant?->matricule ?: 'Créé au premier geste physique' }}</strong></div>
        </section>

        <section class="mwf-card">
            <h2>1. Préinscription à la caisse</h2>
            @if($workflow->paymentRecorded())
                <div class="mwf-status ok">Paiement validé</div>
                <div class="mwf-kv"><span>Reçu</span><strong>{{ $workflow->paiement?->numero_recu ?: '—' }}</strong></div>
                <div class="mwf-kv"><span>Montant</span><strong>{{ number_format((float)($workflow->paiement?->montant ?? 0),0,',',' ') }} FCFA</strong></div>
                <div class="mwf-kv"><span>Date</span><strong>{{ optional($workflow->paid_at)->format('d/m/Y H:i') }}</strong></div>
            @else
                @can('inscriptions.create')
                <form method="POST" action="{{ route('esbtp.admissions.workflow.pay', $candidature) }}" class="mwf-form">
                    @csrf
                    <div class="mwf-field"><label>Montant de préinscription</label><input name="montant" type="number" min="1" required value="{{ old('montant') }}"></div>
                    <div class="mwf-field"><label>Mode de paiement</label><select name="mode_paiement" required><option value="">Choisir</option><option value="especes">Espèces</option><option value="mobile_money">Mobile Money</option><option value="carte">Carte</option><option value="virement">Virement</option></select></div>
                    <div class="mwf-field"><label>Référence externe</label><input name="reference_paiement" value="{{ old('reference_paiement') }}"></div>
                    <div class="mwf-field"><label>N° transaction</label><input name="numero_transaction" value="{{ old('numero_transaction') }}"></div>
                    <div class="mwf-full"><button class="mwf-btn primary" type="submit"><i class="fas fa-cash-register"></i> Encaisser sans ressaisie</button></div>
                </form>
                @else
                    <p class="mwf-muted">En attente du passage à la caisse.</p>
                @endcan
            @endif
        </section>

        <section class="mwf-card mwf-full">
            <h2>2. Contrôle physique des pièces</h2>
            <p class="mwf-muted">Même catalogue « Pièces du dossier » que le reste de KLASSCI. Ici les dépôts sont rattachés au dossier étudiant provisoire ; aucune seconde liste de pièces n'est créée.</p>
            @forelse($pieces as $row)
                <div class="mwf-piece">
                    <div class="mwf-piece-top">
                        <div><strong>{{ $row['piece']->libelle }}</strong><div class="mwf-muted">{{ $row['depose'] }}/{{ $row['requis'] }} exemplaire(s) validé(s){{ $row['piece']->is_obligatoire ? ' · obligatoire' : '' }}</div></div>
                        <div class="mwf-status {{ $row['satisfaite'] ? 'ok':'warn' }}">{{ $row['satisfaite'] ? 'Complet':'À fournir' }}</div>
                    </div>
                    @can('pieces_dossier.suivre')
                    <form class="mwf-actions" method="POST" action="{{ route('esbtp.admissions.workflow.pieces.receive', $workflow) }}">
                        @csrf
                        <input type="hidden" name="piece_id" value="{{ $row['piece']->id }}">
                        <input style="width:80px;border:1px solid #dce3ed;border-radius:9px;padding:8px" type="number" name="quantite" min="1" max="50" value="1">
                        <button class="mwf-btn soft" type="submit">Enregistrer la remise</button>
                    </form>
                    @foreach($row['depots'] as $depot)
                        @if($depot->etat?->value === 'deposee')
                        <form class="mwf-actions" method="POST" action="{{ route('esbtp.admissions.workflow.pieces.decide', [$workflow,$depot]) }}">
                            @csrf
                            <span class="mwf-muted">Dépôt #{{ $depot->id }} à relire</span>
                            <button class="mwf-btn success" name="decision" value="valider">Valider</button>
                            <input name="motif" placeholder="Motif si refus" style="border:1px solid #dce3ed;border-radius:9px;padding:8px">
                            <button class="mwf-btn danger" name="decision" value="refuser">Refuser</button>
                        </form>
                        @endif
                    @endforeach
                    @endcan
                </div>
            @empty
                <div class="mwf-muted">Aucun catalogue de pièces n'est configuré pour ce périmètre.</div>
            @endforelse

            @can('pieces_dossier.suivre')
            @if(!$workflow->documentsValidated())
            <form method="POST" action="{{ route('esbtp.admissions.workflow.pieces.validate', $workflow) }}">
                @csrf
                <button class="mwf-btn success" type="submit">Confirmer le contrôle physique complet</button>
            </form>
            @else
                <div class="mwf-status ok">Dossier physique validé le {{ optional($workflow->documents_validated_at)->format('d/m/Y H:i') }}</div>
            @endif
            @endcan
        </section>

        <section class="mwf-card">
            <h2>3. Activation de l'espace étudiant</h2>
            <div class="mwf-kv"><span>Déclenchement</span><strong>{{ $activationStep === 'after_payment' ? 'Après paiement':'Après contrôle des pièces' }}</strong></div>
            <div class="mwf-kv"><span>Compte</span><strong>{{ $workflow->etudiant?->user?->username ?: 'Pas encore préparé' }}</strong></div>
            <div class="mwf-kv"><span>Activation</span><strong>{{ $workflow->accessActivated() ? 'Activé':'En attente' }}</strong></div>
            @can('inscriptions.create')
            @if($workflow->etudiant_id && !$workflow->accessActivated())
            <form method="POST" action="{{ route('esbtp.admissions.workflow.activation.resend', $workflow) }}" class="mwf-actions">
                @csrf
                <button class="mwf-btn soft" type="submit">Régénérer / renvoyer le lien</button>
            </form>
            @endif
            @endcan
            <p class="mwf-muted">Aucun mot de passe permanent n'est envoyé : l'étudiant choisit le sien depuis un lien à usage unique valable 48 h.</p>
        </section>

        <section class="mwf-card">
            <h2>4. Classe et inscription définitive</h2>
            <div class="mwf-kv"><span>Classe choisie</span><strong>{{ $workflow->selectedClass?->name ?: 'En attente de l’étudiant' }}</strong></div>
            <div class="mwf-kv"><span>Inscription finale</span><strong>{{ $workflow->final_inscription_id ? '#'.$workflow->final_inscription_id : 'Non créée' }}</strong></div>
            @can('inscriptions.create')
            @if($workflow->selected_class_id && !$workflow->final_inscription_id)
            <form method="POST" action="{{ route('esbtp.admissions.workflow.finalize', $workflow) }}" class="mwf-actions">
                @csrf
                <button class="mwf-btn primary" type="submit">Finaliser l'inscription académique</button>
            </form>
            @endif
            @endcan
        </section>
    </div>
</div>
@endsection
