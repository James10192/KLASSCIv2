@extends('layouts.app')

@section('title', 'Finaliser mon inscription - KLASSCI')

@push('styles')
<style>
.mws{max-width:1040px;margin:0 auto;padding:24px}.mws-hero{background:linear-gradient(135deg,#0a3d8f,#0453cb);color:#fff;border-radius:18px;padding:26px}.mws-hero h1{font-size:1.55rem;margin:0 0 7px}.mws-hero p{margin:0;opacity:.88}.mws-timeline{list-style:none;padding:0;margin:18px 0 0;display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:10px}.mws-t{display:flex;gap:8px;align-items:flex-start;font-size:.86rem;font-weight:600;color:#94a3b8}.mws-t small{display:block;font-weight:400;font-size:.75rem}.mws-dot{flex:0 0 22px;height:22px;border-radius:50%;border:2px solid #cbd5e1;display:flex;align-items:center;justify-content:center;font-size:.65rem;color:#fff}.mws-t.faite{color:#14763d}.mws-t.faite .mws-dot{background:#10b981;border-color:#10b981}.mws-t.en_cours{color:#0453cb}.mws-t.en_cours .mws-dot{border-color:#0453cb}.mws-card{margin-top:18px;background:#fff;border:1px solid #e6ebf3;border-radius:16px;padding:22px;box-shadow:0 8px 28px rgba(15,23,42,.05)}.mws-card h2{font-size:1.15rem;margin:0 0 8px;color:#172033}.mws-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:16px}.mws-field{display:flex;flex-direction:column;gap:5px}.mws-field.full{grid-column:1/-1}.mws-field label{font-size:.78rem;font-weight:800;color:#475569}.mws-field input,.mws-field select,.mws-field textarea{border:1px solid #d9e1ec;border-radius:10px;padding:11px;background:#fff;width:100%}.mws-classes{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-top:14px}.mws-class{border:1px solid #dce4ef;border-radius:12px;padding:15px}.mws-class label{display:flex;gap:10px;cursor:pointer}.mws-class strong{color:#172033}.mws-muted{font-size:.82rem;color:#64748b}.mws-btn{margin-top:16px;border:0;border-radius:11px;padding:11px 16px;min-height:44px;background:#0453cb;color:#fff;font-weight:800}.mws-lock{padding:12px;border-radius:11px;background:#fff7e8;color:#855300;margin-top:12px;font-size:.85rem}.mws-ok{padding:12px;border-radius:11px;background:#eaf7ef;color:#14763d;margin-top:12px;font-size:.85rem}.mws-wait{padding:12px;border-radius:11px;background:#eef4ff;color:#2453a6;margin-top:12px;font-size:.85rem}@media(max-width:800px){.mws{padding:14px}.mws-timeline{grid-template-columns:1fr}.mws-form,.mws-classes{grid-template-columns:1fr}.mws-field.full{grid-column:auto}}
</style>
@endpush

@section('content')
<div class="mws">
    <section class="mws-hero">
        <h1>Mon inscription</h1>
        <p>{{ $workflow->candidature?->prenoms }} {{ $workflow->candidature?->nom }} · {{ $workflow->candidature?->reference_publique }}</p>
    </section>

    @if(session('success'))<div class="alert alert-success mt-3">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger mt-3"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <ol class="mws-timeline" aria-label="Avancement de mon inscription">
        @foreach($etapes as $etape)
            <li class="mws-t {{ $etape['statut'] }}">
                <span class="mws-dot" aria-hidden="true">@if($etape['statut'] === 'faite')<i class="fas fa-check"></i>@endif</span>
                <span>{{ $etape['libelle'] }}<small>{{ $etape['statut'] === 'faite' ? 'Terminé' : ($etape['statut'] === 'en_cours' ? 'Action en cours' : 'À venir') }}</small></span>
            </li>
        @endforeach
    </ol>

    @if($workflow->final_inscription_id)
        <section class="mws-card">
            <h2>Inscription finalisée</h2>
            <p>Votre inscription académique est terminée. Votre classe est <strong>{{ $workflow->selectedClass?->name }}</strong>.</p>
            <a class="mws-btn" style="display:inline-block;text-decoration:none" href="{{ url('/') }}">Aller à mon espace étudiant</a>
        </section>
    @else
        @if($workflow->accessActivated() && !$workflow->profileCompleted())
            <section class="mws-card">
                <h2>1. Compléter mes informations</h2>
                <p class="mws-muted">Les informations de candidature sont déjà reprises. Renseignez uniquement les éléments nécessaires à votre dossier étudiant.</p>
                <form method="POST" action="{{ route('esbtp.admissions.workflow.student.profile') }}" class="mws-form">
                    @csrf
                    <div class="mws-field full"><label>Adresse actuelle *</label><textarea name="adresse" rows="2" required>{{ old('adresse', $workflow->etudiant?->adresse) }}</textarea></div>
                    <div class="mws-field"><label>Ville *</label><input name="ville" required value="{{ old('ville', $workflow->etudiant?->ville) }}"></div>
                    <div class="mws-field"><label>Commune</label><input name="commune" value="{{ old('commune', $workflow->etudiant?->commune) }}"></div>
                    <div class="mws-field"><label>Téléphone *</label><input name="telephone" required value="{{ old('telephone', $workflow->etudiant?->telephone) }}"></div>
                    <div class="mws-field"><label>E-mail personnel</label><input type="email" name="email_personnel" value="{{ old('email_personnel', $workflow->etudiant?->email_personnel) }}"></div>
                    <div class="mws-field"><label>Groupe sanguin</label><input name="groupe_sanguin" value="{{ old('groupe_sanguin', $workflow->etudiant?->groupe_sanguin) }}"></div>
                    <div class="mws-field"><label>Situation matrimoniale</label><input name="situation_matrimoniale" value="{{ old('situation_matrimoniale', $workflow->etudiant?->situation_matrimoniale) }}"></div>
                    <div class="mws-field"><label>Nombre d'enfants</label><input type="number" min="0" max="30" name="nombre_enfants" value="{{ old('nombre_enfants', $workflow->etudiant?->nombre_enfants ?? 0) }}"></div>
                    <div class="mws-field"><label>Contact d'urgence *</label><input name="urgence_contact_nom" required value="{{ old('urgence_contact_nom', $workflow->etudiant?->urgence_contact_nom) }}"></div>
                    <div class="mws-field"><label>Téléphone d'urgence *</label><input name="urgence_contact_telephone" required value="{{ old('urgence_contact_telephone', $workflow->etudiant?->urgence_contact_telephone) }}"></div>
                    <div class="mws-field"><label>Lien avec le contact</label><input name="urgence_contact_relation" value="{{ old('urgence_contact_relation', $workflow->etudiant?->urgence_contact_relation) }}"></div>
                    <div class="mws-field full"><button class="mws-btn" type="submit">Enregistrer mes informations</button></div>
                </form>
            </section>
        @elseif($workflow->profileCompleted())
            <section class="mws-card">
                <h2>1. Informations personnelles</h2>
                <div class="mws-ok"><strong>Dossier complété.</strong> Vos informations complémentaires ont été enregistrées.</div>
            </section>
        @endif

        @if(!$workflow->paymentRecorded() || !$workflow->documentsValidated())
            <section class="mws-card">
                <h2>2. Dossier en traitement</h2>
                <p>Votre inscription académique ne peut pas encore être finalisée.</p>
                <div class="mws-wait">
                    @if(!$workflow->paymentRecorded()) Préinscription à la caisse en attente. @endif
                    @if(!$workflow->documentsValidated()) Contrôle physique des pièces en attente auprès de l'administration. @endif
                </div>
            </section>
        @elseif(!$workflow->profileCompleted())
            <section class="mws-card">
                <h2>2. Classe</h2>
                <p class="mws-muted">Complétez d'abord vos informations ci-dessus avant de poursuivre.</p>
            </section>
        @elseif($classChoiceActor === \App\Services\Admissions\InscriptionWorkflowSettings::CLASS_ACTOR_ADMIN)
            <section class="mws-card">
                <h2>2. Affectation de classe</h2>
                @if($workflow->selectedClass)
                    <div class="mws-ok">Votre classe a été affectée par l'administration : <strong>{{ $workflow->selectedClass->name }}</strong>.</div>
                @else
                    <div class="mws-wait">Votre établissement a choisi une affectation de classe par l'administration. Aucune action n'est requise de votre part pour cette étape.</div>
                @endif
            </section>
        @else
            <section class="mws-card">
                <h2>2. Choisir ma classe</h2>
                <p class="mws-muted">Seules les classes correspondant à votre filière et votre niveau, avec une place disponible, sont proposées.</p>
                @if($classChoiceOnce)
                    <div class="mws-lock"><strong>Choix définitif.</strong> Confirmer votre classe termine votre inscription : vous ne pourrez plus la changer vous-même.</div>
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
                                        <span><strong>{{ $classe->name }}</strong><br><span class="mws-muted">{{ $classe->filiere?->name }} · {{ $classe->niveau?->name }}@if($classe->places_restantes !== null) · {{ $classe->places_restantes }} place(s) restante(s)@endif</span></span>
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
    @endif
</div>
@endsection
