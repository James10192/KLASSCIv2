@extends('layouts.app')

@section('title', 'Dossier '.($candidature->reference_publique ?: '#'.$candidature->id).' - KLASSCI')

@push('styles')
<style>
.mwf{max-width:1180px;margin:0 auto;padding:24px}
.mwf-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap}
.mwf-head h1{font-size:1.5rem;font-weight:700;color:#0f172a;margin:0}
.mwf-head p{margin:4px 0 0;color:#64748b;font-size:.9rem}
.mwf-ref{font-family:ui-monospace,monospace;font-size:.82rem;color:#0453cb;background:rgba(4,83,203,.07);padding:4px 10px;border-radius:8px}
.mwf-next{margin-top:16px;padding:14px 16px;border-radius:12px;background:#f1f6ff;border:1px solid rgba(4,83,203,.18);color:#0f172a;display:flex;gap:10px;align-items:center}
.mwf-next i{color:#0453cb}
.mwf-steps{display:flex;gap:6px;margin-top:16px;overflow-x:auto;padding-bottom:4px}
.mwf-step{flex:1 0 120px;font-size:.76rem;font-weight:600;color:#64748b;border-top:3px solid #e2e8f0;padding-top:8px}
.mwf-step.faite{color:#14763d;border-color:#10b981}
.mwf-step.en_cours{color:#0453cb;border-color:#0453cb}
.mwf-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:18px}
.mwf-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px}
.mwf-card h2{font-size:1rem;font-weight:700;color:#0f172a;margin:0 0 12px}
.mwf-full{grid-column:1/-1}
.mwf-kv{display:grid;grid-template-columns:140px 1fr;gap:8px;font-size:.88rem;padding:6px 0;border-bottom:1px solid #f1f5f9}
.mwf-kv span{color:#64748b}
.mwf-form{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.mwf-field{display:flex;flex-direction:column;gap:5px}
.mwf-field label{font-size:.78rem;font-weight:600;color:#475569}
.mwf-field input{border:1px solid #dce3ed;border-radius:10px;padding:10px;background:#fff;min-height:44px}
.mwf-help{font-size:.78rem;color:#64748b}
.mwf-btn{border:0;border-radius:10px;padding:11px 16px;font-weight:600;cursor:pointer;min-height:44px}
.mwf-btn.primary{background:#0453cb;color:#fff}
.mwf-btn.soft{background:#eef4ff;color:#0453cb}
.mwf-btn.success{background:#10b981;color:#fff}
.mwf-btn.danger{background:#fff;color:#dc2626;border:1px solid #fecaca}
.mwf-alerte{border:1px solid #fcd9a6;background:#fff8ed;color:#7c4a03;border-radius:12px;padding:12px 14px;font-size:.9rem}
.mwf-ok{display:inline-flex;gap:6px;align-items:center;font-size:.8rem;font-weight:600;color:#14763d;background:#ecfdf5;border-radius:999px;padding:5px 10px}
.mwf-wait{display:inline-flex;gap:6px;align-items:center;font-size:.8rem;font-weight:600;color:#a35c00;background:#fffbeb;border-radius:999px;padding:5px 10px}
.mwf-progress{font-size:.86rem;color:#0f172a;margin-bottom:10px}
.mwf-piece{display:flex;justify-content:space-between;gap:12px;align-items:center;padding:12px 0;border-bottom:1px solid #f1f5f9;flex-wrap:wrap}
.mwf-piece:last-child{border-bottom:0}
.mwf-piece strong{color:#0f172a;font-size:.92rem}
.mwf-inline{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.mwf-inline input{border:1px solid #dce3ed;border-radius:9px;padding:8px;min-height:40px}
.mwf-muted{font-size:.84rem;color:#64748b}
@media(max-width:850px){.mwf{padding:14px}.mwf-grid,.mwf-form{grid-template-columns:1fr}.mwf-kv{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
@php
    $user = auth()->user();
    $canCash = $user?->can('paiements.create') && $user?->can('paiements.validate');
    $canPieces = $user?->can('pieces_dossier.suivre');
    $canDecide = $user?->can('inscriptions.validate');
    $finalisee = (bool) $workflow->final_inscription_id;
    // L'ordre choisi par l'établissement : en « caisse puis pièces », le
    // guichet des pièces n'agit qu'après le paiement (le serveur refuse aussi).
    $piecesOuvertes = \App\Services\Admissions\ManagedWorkflowPresenter::piecesOuvertes($workflow);
    $canPieces = $canPieces && $piecesOuvertes;
    $piecesSatisfaites = $pieces->where('satisfaite', true)->count();
    $fraisOptions = $fraisEncaissables->mapWithKeys(fn ($f) => [$f['category_id'] => $f['name'].' — '.number_format($f['amount'], 0, ',', ' ').' FCFA'])->all();
    $modeOptions = collect($paymentModes)->mapWithKeys(fn ($meta, $key) => [$key => $meta['label'] ?? $key])->all();
    $classOptions = $eligibleClasses->mapWithKeys(fn ($c) => [$c->id => $c->name.($c->places_restantes !== null ? ' · '.$c->places_restantes.' place(s)' : '')])->all();
@endphp
<div class="mwf">
    <div class="mwf-head">
        <div>
            <h1>{{ $candidature->prenoms }} {{ $candidature->nom }}</h1>
            <p>{{ $candidature->filiere?->name ?: 'Filière à confirmer' }} · {{ $candidature->niveau?->name ?: 'Niveau à confirmer' }} · {{ $candidature->anneeUniversitaire?->name ?: 'Année non précisée' }}</p>
        </div>
        <span class="mwf-ref">{{ $candidature->reference_publique ?: 'Dossier #'.$candidature->id }}</span>
    </div>

    <div class="mwf-next" role="status"><i class="fas fa-arrow-right" aria-hidden="true"></i><strong>{{ $prochaineEtape }}</strong></div>

    <ol class="mwf-steps" aria-label="Avancement du dossier" style="list-style:none;padding-left:0">
        @foreach($etapes as $etape)
            <li class="mwf-step {{ $etape['statut'] }}">{{ $etape['libelle'] }}</li>
        @endforeach
    </ol>

    @if(session('success'))<div class="alert alert-success mt-3">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger mt-3"><strong>Rien n'a été enregistré.</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="mwf-grid">
        <section class="mwf-card" aria-labelledby="mwf-caisse">
            <h2 id="mwf-caisse">Préinscription à la caisse</h2>
            @if($workflow->paymentRecorded())
                <span class="mwf-ok"><i class="fas fa-check" aria-hidden="true"></i> Payée</span>
                <div class="mwf-kv"><span>Reçu</span><strong>{{ $workflow->paiement?->numero_recu ?: '—' }}</strong></div>
                <div class="mwf-kv"><span>Frais</span><strong>{{ $workflow->paiement?->fraisCategory?->name ?: '—' }}</strong></div>
                <div class="mwf-kv"><span>Montant</span><strong>{{ number_format((float) ($workflow->paiement?->montant ?? 0), 0, ',', ' ') }} FCFA</strong></div>
                <div class="mwf-kv"><span>Date</span><strong>{{ optional($workflow->paid_at)->format('d/m/Y H:i') }}</strong></div>
                @if($workflow->paiement && $workflow->paiement->inscription_id)
                    <div class="mt-2"><a class="mwf-btn soft" href="{{ route('esbtp.paiements.show', $workflow->paiement) }}"><i class="fas fa-receipt" aria-hidden="true"></i> Voir le paiement et le reçu</a></div>
                @endif
            @elseif($canCash)
                @if($fraisOptions === [])
                    <p class="mwf-muted">Aucun frais n'est configuré pour cette filière, ce niveau et cette année. Configurez les frais avant d'encaisser.</p>
                @else
                    <form method="POST" action="{{ route('esbtp.admissions.workflow.pay', $candidature) }}" class="mwf-form">
                        @csrf
                        <div class="mwf-field mwf-full">
                            <label>Frais encaissé</label>
                            <x-au-select name="frais_category_id" :value="old('frais_category_id', array_key_first($fraisOptions))" :options="$fraisOptions" :placeholder-is-first-option="false" />
                            <span class="mwf-help">Le montant indiqué est le tarif configuré ; un acompte inférieur est accepté.</span>
                        </div>
                        <div class="mwf-field">
                            <label for="mwf-montant">Montant reçu (FCFA)</label>
                            <input id="mwf-montant" name="montant" type="number" min="1" required inputmode="numeric" value="{{ old('montant', (int) ($fraisEncaissables->first()['amount'] ?? 0)) }}">
                        </div>
                        <div class="mwf-field">
                            <label>Mode de paiement</label>
                            <x-au-select name="mode_paiement" :value="old('mode_paiement')" :options="$modeOptions" placeholder="Choisir" />
                        </div>
                        <div class="mwf-field mwf-full">
                            <label for="mwf-ref">N° de transaction (Mobile Money, virement)</label>
                            <input id="mwf-ref" name="numero_transaction" value="{{ old('numero_transaction') }}">
                        </div>
                        <div class="mwf-full"><button class="mwf-btn primary" type="submit"><i class="fas fa-cash-register" aria-hidden="true"></i> Encaisser</button></div>
                    </form>
                @endif
            @else
                <span class="mwf-wait">En attente de la caisse</span>
            @endif
        </section>

        <section class="mwf-card" aria-labelledby="mwf-identite">
            <h2 id="mwf-identite">Identité (reprise de la candidature)</h2>
            <div class="mwf-kv"><span>Téléphone</span><strong>{{ $candidature->telephone ?: '—' }} @if($candidature->telephone_verifie_at)<span class="mwf-ok">vérifié</span>@endif</strong></div>
            <div class="mwf-kv"><span>E-mail</span><strong>{{ $candidature->email ?: '—' }} @if($candidature->email_verifie_at)<span class="mwf-ok">vérifié</span>@endif</strong></div>
            <div class="mwf-kv"><span>Naissance</span><strong>{{ optional($candidature->date_naissance)->format('d/m/Y') ?: '—' }} · {{ $candidature->lieu_naissance ?: '—' }}</strong></div>
            @php $rdv = $candidature->reservations->sortByDesc('id')->first(); @endphp
            <div class="mwf-kv"><span>Rendez-vous</span><strong>{{ $rdv?->creneau ? $rdv->creneau->date?->format('d/m/Y').' à '.substr((string) $rdv->creneau->heure_debut, 0, 5) : ($rdv ? 'Réservé' : 'Aucun') }}</strong></div>
            <div class="mwf-kv"><span>Matricule provisoire</span><strong>{{ $workflow->etudiant?->matricule ?: 'Créé au premier passage au guichet' }}</strong></div>
        </section>

        <section class="mwf-card mwf-full" aria-labelledby="mwf-pieces">
            <h2 id="mwf-pieces">Contrôle physique des pièces</h2>
            @if($workflow->documentsValidated())
                <span class="mwf-ok"><i class="fas fa-check" aria-hidden="true"></i> Dossier physique validé le {{ optional($workflow->documents_validated_at)->format('d/m/Y à H:i') }}</span>
            @endif
            @if(!$piecesOuvertes && !$finalisee)
                <p class="mwf-muted">Le contrôle des pièces s'ouvre après le paiement de préinscription.</p>
            @endif
            <div class="mwf-progress"><strong>{{ $piecesSatisfaites }} / {{ $pieces->count() }}</strong> pièce(s) complète(s)</div>
            @forelse($pieces as $row)
                <div class="mwf-piece">
                    <div>
                        <strong>{{ $row['piece']->libelle }}</strong>{!! $row['piece']->is_obligatoire ? '' : ' <span class="mwf-muted">(facultative)</span>' !!}
                        <div class="mwf-muted">{{ $row['depose'] }} reçu(s) sur {{ $row['requis'] }} requis{{ $row['manquant'] > 0 ? ' · '.$row['manquant'].' manquant(s)' : '' }}</div>
                    </div>
                    <div class="mwf-inline">
                        @if($row['satisfaite'])<span class="mwf-ok">Complet</span>@else<span class="mwf-wait">Manquant</span>@endif
                        @if($canPieces && !$finalisee && !$row['satisfaite'])
                            <form class="mwf-inline" method="POST" action="{{ route('esbtp.admissions.workflow.pieces.receive', $workflow) }}">
                                @csrf
                                <input type="hidden" name="piece_id" value="{{ $row['piece']->id }}">
                                <label class="visually-hidden" for="q-{{ $row['piece']->id }}">Nombre d'exemplaires reçus</label>
                                <input id="q-{{ $row['piece']->id }}" style="width:72px" type="number" name="quantite" min="1" max="50" value="{{ max(1, $row['manquant']) }}">
                                <button class="mwf-btn soft" type="submit">Reçu</button>
                            </form>
                        @endif
                    </div>
                    @if($canPieces && !$finalisee)
                        @foreach($row['depots'] as $depot)
                            @if($depot->etat?->value === 'deposee')
                                <form class="mwf-inline mwf-full" method="POST" action="{{ route('esbtp.admissions.workflow.pieces.decide', [$workflow, $depot]) }}">
                                    @csrf
                                    <span class="mwf-muted">{{ $depot->quantite_deposee }} exemplaire(s) à relire</span>
                                    <button class="mwf-btn success" name="decision" value="valider">Valider</button>
                                    <label class="visually-hidden" for="motif-{{ $depot->id }}">Motif du refus</label>
                                    <input id="motif-{{ $depot->id }}" name="motif" placeholder="Motif si refus">
                                    <button class="mwf-btn danger" name="decision" value="refuser">Refuser</button>
                                </form>
                            @elseif($depot->etat?->value === 'refusee')
                                <div class="mwf-muted mwf-full">Refusé : {{ $depot->motif }}</div>
                            @endif
                        @endforeach
                    @endif
                </div>
            @empty
                <p class="mwf-muted">Aucune pièce n'est configurée pour cette filière et ce niveau.</p>
            @endforelse

            @if($canPieces && !$workflow->documentsValidated() && !$finalisee)
                <form method="POST" action="{{ route('esbtp.admissions.workflow.pieces.validate', $workflow) }}" class="mt-3">
                    @csrf
                    <button class="mwf-btn success" type="submit"><i class="fas fa-check-double" aria-hidden="true"></i> Valider le dossier physique</button>
                </form>
            @endif
        </section>

        <section class="mwf-card" aria-labelledby="mwf-espace">
            <h2 id="mwf-espace">Espace étudiant</h2>
            <div class="mwf-kv"><span>Envoi du lien</span><strong>{{ $activationStep === 'after_payment' ? 'Après le paiement' : 'Après le contrôle des pièces' }}</strong></div>
            <div class="mwf-kv"><span>Identifiant</span><strong>{{ $workflow->etudiant?->user?->username ?: 'Pas encore créé' }}</strong></div>
            <div class="mwf-kv"><span>Compte</span><strong>{{ $workflow->accessActivated() ? 'Activé' : 'En attente d\'activation' }}</strong></div>
            <div class="mwf-kv"><span>Informations</span><strong>{{ $workflow->profileCompleted() ? 'Complétées' : 'À compléter par l\'étudiant' }}</strong></div>
            @if($workflow->etudiant_id && !$workflow->accessActivated() && ($user?->can('inscriptions.validate') || $user?->can('pieces_dossier.suivre')))
                @if(! $contactJoignable || $emailEnAttente)
                    <div class="mwf-alerte mt-2" role="status">
                        @if(! $contactJoignable)
                            <strong>Le lien n'est pas parti.</strong>
                            Il ne part que vers un contact vérifié, et celui de ce dossier ne l'est pas.
                        @else
                            <strong>Le lien ne part que par WhatsApp :</strong> l'e-mail de ce dossier n'est pas vérifié.
                            Si l'étudiant n'a rien reçu, confirmez son e-mail : le lien partira aussi par e-mail.
                        @endif
                        Relisez-les avec l'étudiant :
                        <div class="mwf-kv"><span>E-mail</span><strong>{{ $candidature->email ?: '—' }}</strong></div>
                        <div class="mwf-kv"><span>Téléphone</span><strong>{{ $candidature->telephone ?: '—' }}</strong></div>
                        <form method="POST" action="{{ route('esbtp.admissions.workflow.activation.confirm-contact', $workflow) }}" class="mt-2">
                            @csrf
                            <input type="hidden" name="empreinte" value="{{ $candidature->empreinteContact() }}">
                            <button class="mwf-btn primary" type="submit">Contact confirmé avec l'étudiant : envoyer le lien</button>
                        </form>
                    </div>
                @endif
                <form method="POST" action="{{ route('esbtp.admissions.workflow.activation.resend', $workflow) }}" class="mt-2">
                    @csrf
                    <button class="mwf-btn soft" type="submit">Renvoyer le lien d'activation</button>
                </form>
                <p class="mwf-muted mt-2">Le lien part uniquement vers un e-mail ou un numéro vérifié. Renvoyer annule les liens précédents.</p>
            @endif
        </section>

        <section class="mwf-card" aria-labelledby="mwf-classe">
            <h2 id="mwf-classe">Classe et inscription</h2>
            <div class="mwf-kv"><span>Choix</span><strong>{{ $classChoiceActor === \App\Services\Admissions\InscriptionWorkflowSettings::CLASS_ACTOR_STUDENT ? "Par l'étudiant" : "Par l'administration" }}</strong></div>
            <div class="mwf-kv"><span>Classe</span><strong>{{ $workflow->selectedClass?->name ?: 'Non choisie' }}</strong></div>
            @if($finalisee)
                <div class="mt-2"><a class="mwf-btn primary" href="{{ route('esbtp.inscriptions.show', $workflow->final_inscription_id) }}">Ouvrir l'inscription</a></div>
            @elseif($canDecide)
                @if($classChoiceActor === \App\Services\Admissions\InscriptionWorkflowSettings::CLASS_ACTOR_ADMIN)
                    <form method="POST" action="{{ route('esbtp.admissions.workflow.class.choose-admin', $workflow) }}" class="mwf-inline mt-2">
                        @csrf
                        <x-au-select name="classe_id" :value="$workflow->selected_class_id" :options="$classOptions" placeholder="Choisir une classe" />
                        <button class="mwf-btn soft" type="submit">Affecter</button>
                    </form>
                @endif
                @if($workflow->state === \App\Models\ESBTPCandidatureWorkflow::STATE_READY_TO_FINALIZE)
                    <form method="POST" action="{{ route('esbtp.admissions.workflow.finalize', $workflow) }}" class="mt-2">
                        @csrf
                        <button class="mwf-btn primary" type="submit">Finaliser l'inscription</button>
                    </form>
                @else
                    <p class="mwf-muted mt-2">La finalisation s'ouvre quand paiement, pièces, activation, informations et classe sont tous faits.</p>
                @endif
            @endif
        </section>
    </div>
</div>
@endsection
