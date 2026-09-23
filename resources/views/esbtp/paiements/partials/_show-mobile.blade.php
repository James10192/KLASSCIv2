{{--
    Fiche d'un paiement — rendu MOBILE (shell m-*), maquettes S['caissier:recu']
    et S['comptable:detail']. Inclus par esbtp/paiements/show.blade.php quand le
    shell mobile est actif ; le DOM de bureau reste dans .m-only-desktop.

    Namespace CSS propre a l'ecran : psm- (payment-show-mobile).
    Toute mutation passe par fetch JSON (regle ajax-no-reload-premium) ; les
    deux seules navigations sont celles ou le versement n'existe plus.
--}}
@php
    $mUser = auth()->user();
    $mEcole = \App\Helpers\SettingsHelper::getSchoolInfo();
    $mEtudiant = $paiement->etudiant;
    $mInscription = $paiement->inscription;
    $mClasse = $mInscription?->classe;

    // LMD : la classe s'ancre sur un parcours (ou un reflet de mention) ; on
    // affiche ce libelle-la sous la classe. BTS : niveau + filiere suffisent.
    $mEstLmd = ($mClasse?->systeme_academique ?? null) === \App\Services\FraisScopeResolver::SYSTEME_LMD;
    $mClasseLabel = $mClasse?->name
        ?: trim(($mInscription?->niveauEtude?->name ?? '') . ' ' . ($mInscription?->filiere?->name ?? ''));
    $mParcoursLabel = $mEstLmd
        ? ($mClasse?->parcours?->name ?? $mInscription?->filiere?->name)
        : null;

    // Un versement peut couvrir plusieurs frais : on liste la ventilation, et
    // seulement a defaut la categorie designee au guichet.
    $mAllocations = $paiement->allocations ?? collect();
    $mFrais = $mAllocations->isNotEmpty()
        ? $mAllocations->map(fn ($a) => [
            'name' => $a->fraisCategory->name ?? ('Frais #' . $a->frais_category_id),
            'montant' => (float) $a->montant,
        ])->values()
        : collect([[
            'name' => $paiement->fraisCategory->name ?? 'Frais non défini',
            'montant' => (float) $paiement->montant,
        ]]);

    [$mStatutLabel, $mStatutTone] = match ($paiement->status) {
        'validé' => ['Validé', 'ok'],
        'en_attente' => ['À valider', 'warn'],
        'rejeté' => ['Rejeté', 'bad'],
        default => [$paiement->status ?: '—', 'mute'],
    };

    $mMontant = number_format((float) $paiement->montant, 0, ',', ' ');
    $mNumero = (string) $paiement->numero_recu;
    $mEstAvoir = $paiement->isAvoir();
    $mNomEtudiant = $mEtudiant?->nom_complet ?? ($mEtudiant?->user?->name ?? '—');

    // Le recu s'imprime souvent avant la validation (cf. EtatRecuPaiement) :
    // seul un versement rejete n'a plus de recu a montrer.
    $mRecuDisponible = $paiement->status !== 'rejeté';

    // Contacts pour l'envoi : numero de l'etudiant, puis du tuteur (ou du
    // contact d'urgence), et l'adresse e-mail. Rien d'invente : pas de SMS
    // ni de passerelle e-mail cote bureau, donc wa.me et mailto: seulement.
    $mWaEtudiant = \App\Domain\Notifications\PhoneNormalizer::toWhatsAppId($mEtudiant?->telephone);
    $mTuteur = $mEtudiant?->tuteur;
    $mTelTuteur = $mTuteur?->telephone ?: $mEtudiant?->urgence_contact_telephone;
    $mWaTuteur = \App\Domain\Notifications\PhoneNormalizer::toWhatsAppId($mTelTuteur);
    if ($mWaTuteur !== null && $mWaTuteur === $mWaEtudiant) {
        $mWaTuteur = null;
    }
    $mEmail = $mEtudiant?->email_personnel ?: ($mEtudiant?->email ?: $mEtudiant?->user?->email);

    $mTexteRecu = sprintf(
        "Bonjour, voici votre reçu de paiement N° %s : %s FCFA (%s), reçu le %s. — %s",
        $mNumero,
        $mMontant,
        $mFrais->pluck('name')->implode(', '),
        $paiement->date_paiement ? $paiement->date_paiement->format('d/m/Y') : '—',
        $mEcole['name'] ?? config('app.name')
    );
    $mSujetRecu = 'Reçu de paiement N° ' . $mNumero;

    // Fenetre d'annulation de sa propre saisie : meme calcul que le bureau.
    $mPeutAnnulerMien = $mUser?->can('cancelOwnRecent', $paiement) ?? false;
    $mMinutesRestantes = 0;
    if ($mPeutAnnulerMien) {
        $mFenetre = (int) \App\Helpers\SettingsHelper::get('comptabilite.cancel_own_window_minutes', 5);
        $mEcheance = $paiement->created_at->copy()->addMinutes($mFenetre);
        $mMinutesRestantes = (int) max(1, ceil(max(0, $mEcheance->getTimestamp() - now()->getTimestamp()) / 60));
    }

    // Les memes conditions que le bureau, pour decider s'il y a une feuille
    // d'actions a ouvrir. Chaque entree reste sous sa propre garde de
    // permission plus bas.
    $mPeutValider = $paiement->status === 'en_attente' && ($mUser?->can('paiements.validate') ?? false);
    $mPeutCorrigerMode = $paiement->status === 'validé' && ! $mEstAvoir && ! $paiement->reconciliation_locked_at
        && ($mUser?->can('paiements.correct_mode') ?? false);
    $mPeutReventiler = ! $mEstAvoir && ($mUser?->can('paiements.reventiler') ?? false);
    $mAvoirDisponible = (float) $paiement->avoir_disponible;
    $mPeutAvoir = $paiement->status === 'validé' && ! $mEstAvoir && $mAvoirDisponible > 0
        && ($mUser?->can('paiements.avoir') ?? false);
    // Le droit `paiements.delete` suffit : l'etablissement l'accorde au role qu'il veut.
    $mPeutSupprimer = $mUser?->can('paiements.delete') ?? false;
    $mADesActions = $mPeutValider || $mRecuDisponible || $mPeutCorrigerMode || $mPeutReventiler
        || $mPeutAnnulerMien || $mPeutAvoir || $mPeutSupprimer;

    $mModes = \App\Http\Controllers\Comptabilite\ModeReglementController::MODES;
    $mModeActuel = (string) $paiement->mode_paiement;
    $mModeLabel = $mModes[$mModeActuel] ?? ($mModeActuel !== '' ? ucfirst(str_replace('_', ' ', $mModeActuel)) : '—');

    $mFlash = session('success') ?: (request()->boolean('ok') ? 'Paiement enregistré — reçu N° ' . $mNumero : null);

    $mConfig = [
        'numero' => $mNumero,
        'statut' => $paiement->status,
        'mode' => $mModeActuel,
        'modes' => $mModes,
        'avoirDisponible' => $mAvoirDisponible,
        'flash' => $mFlash,
        'urls' => [
            'valider' => route('esbtp.paiements.valider', $paiement->id),
            'rejeter' => route('esbtp.paiements.rejeter', $paiement->id),
            'mode' => route('esbtp.paiements.mode-reglement.update', $paiement->id),
            'avoir' => route('esbtp.paiements.avoir.store', $paiement->id),
            'avoirPdf' => route('esbtp.paiements.avoir.pdf', ['paiement' => '__ID__', 'inline' => 1]),
            'annulerMien' => route('esbtp.paiements.cancel-own', $paiement->id),
            'supprimer' => route('esbtp.paiements.destroy', $paiement->id),
            'index' => route('esbtp.paiements.index'),
        ],
    ];
@endphp

<div class="m-only-mobile m-screen psm-screen" x-data="psmRecu({{ \Illuminate\Support\Js::from($mConfig) }})">

    <x-m.appbar title="Reçu"
                :sub="'N° ' . $mNumero"
                :back="route('esbtp.paiements.index')"
                :action="$mADesActions ? 'more' : null"
                action-label="Actions"
                x-on:click="ouvrir('psm-actions')">
        @if($mRecuDisponible)
            <a href="{{ route('esbtp.paiements.recu', $paiement->id) }}" class="m-ib" aria-label="Télécharger le reçu PDF">
                <x-m.icon name="dl" />
            </a>
        @endif
    </x-m.appbar>

    <div class="m-body" data-m-ptr="reload">

        @if($paiement->status === 'en_attente')
            <div class="psm-note" role="status">
                <x-m.icon name="clock" />
                <span>Ce paiement attend sa validation : il sera comptabilisé une fois validé.</span>
            </div>
        @elseif($paiement->status === 'rejeté')
            <div class="psm-note bad" role="status">
                <x-m.icon name="alert" />
                <span>Paiement rejeté@if($paiement->commentaire) : {{ $paiement->commentaire }}@endif</span>
            </div>
        @endif

        {{-- Le recu --}}
        <div class="m-recu">
            <div class="hd">
                <b>{{ $mEcole['name'] ?? config('app.name') }}</b>
                <span>{{ $mEstAvoir ? 'Avoir' : 'Reçu de paiement' }} · N° {{ $mNumero }}</span>
                @if($paiement->creator)
                    <span>Émis {{ $paiement->created_at->format('d/m/Y H:i') }} · Caisse {{ $paiement->creator->name }}</span>
                @endif
            </div>
            <dl>
                <dt>Étudiant</dt>
                <dd>{{ $mNomEtudiant }}</dd>
                @if($mEtudiant?->matricule)
                    <dt>Matricule</dt>
                    <dd>{{ $mEtudiant->matricule }}</dd>
                @endif
                <dt>Classe</dt>
                <dd>{{ $mClasseLabel !== '' ? $mClasseLabel : '—' }}</dd>
                @if($mParcoursLabel)
                    <dt>@rang('parcours')</dt>
                    <dd>{{ $mParcoursLabel }}</dd>
                @endif
                @if($mInscription?->anneeUniversitaire)
                    <dt>Année</dt>
                    <dd>{{ $mInscription->anneeUniversitaire->libelle ?: ($mInscription->anneeUniversitaire->annee_debut . '-' . $mInscription->anneeUniversitaire->annee_fin) }}</dd>
                @endif
                <dt>{{ $mFrais->count() > 1 ? 'Frais couverts' : 'Frais' }}</dt>
                <dd>
                    @foreach($mFrais as $mLigne)
                        <span class="psm-frais">{{ $mLigne['name'] }} · {{ number_format($mLigne['montant'], 0, ',', ' ') }}</span>
                    @endforeach
                </dd>
                <dt>Mode</dt>
                <dd x-text="libelleMode(mode)">{{ $mModeLabel }}</dd>
                @if($paiement->reference_paiement)
                    <dt>Référence</dt>
                    <dd>{{ $paiement->reference_paiement }}</dd>
                @endif
                <dt>Date</dt>
                <dd>{{ $paiement->date_paiement ? $paiement->date_paiement->format('d/m/Y H:i') : '—' }}</dd>
                <dt>Caissier</dt>
                <dd>{{ $paiement->creator?->name ?? '—' }}</dd>
                <dt>Statut</dt>
                <dd>
                    <span class="m-chip" x-bind:class="toneStatut()" x-text="libelleStatut()">{{ $mStatutLabel }}</span>
                </dd>
                @if($paiement->validatedBy && $paiement->date_validation)
                    <dt>{{ $paiement->status === 'rejeté' ? 'Rejeté par' : 'Validé par' }}</dt>
                    <dd>{{ $paiement->validatedBy->name }} · {{ $paiement->date_validation->format('d/m/Y H:i') }}</dd>
                @endif
            </dl>
            <div class="tot">
                <span>Montant</span>
                <span>{{ $mMontant }} FCFA</span>
            </div>
        </div>

        @if($paiement->commentaire && $paiement->status !== 'rejeté')
            <dl class="m-dl">
                <dt>Commentaire</dt>
                <dd class="psm-wrap">{{ $paiement->commentaire }}</dd>
            </dl>
        @endif

        {{-- Actions courantes du recu --}}
        @if($mRecuDisponible)
            <div class="m-list one">
                <x-m.row icon="share"
                         title="Envoyer le reçu"
                         :sub="collect(['WhatsApp' => $mWaEtudiant || $mWaTuteur, 'e-mail' => (bool) $mEmail])->filter()->keys()->implode(' · ') ?: 'Aucun contact enregistré'"
                         role="button" tabindex="0"
                         x-on:click="ouvrir('psm-envoyer')"
                         x-on:keydown.enter.prevent="ouvrir('psm-envoyer')" />
                <x-m.row :href="route('esbtp.paiements.preview', $paiement->id)"
                         target="_blank" rel="noopener"
                         icon="print"
                         title="Imprimer"
                         sub="Ouvre le reçu, puis « Imprimer » depuis le navigateur" />
                @if($mEstAvoir)
                    <x-m.row :href="route('esbtp.paiements.avoir.pdf', [$paiement->id, 'inline' => 1])"
                             target="_blank" rel="noopener"
                             icon="file"
                             title="PDF de l'avoir"
                             :sub="'Avoir N° ' . ($paiement->numero_avoir ?: $mNumero)" />
                @endif
            </div>
        @endif

        <template x-if="dernierAvoirUrl">
            <div class="m-list one">
                <a class="m-row" x-bind:href="dernierAvoirUrl" target="_blank" rel="noopener">
                    <div class="av ic" aria-hidden="true"><x-m.icon name="file" /></div>
                    <div class="tt"><b>Voir l'avoir émis</b><span>PDF de l'avoir qui vient d'être enregistré</span></div>
                    <span class="tr" aria-hidden="true"><x-m.icon name="chr" class="m-ic ch" /></span>
                </a>
            </div>
        </template>

        <div class="m-list one">
            <x-m.row :href="route('esbtp.etudiants.show', $paiement->etudiant_id)"
                     :av="mb_substr($mEtudiant?->prenoms ?? 'E', 0, 1) . mb_substr($mEtudiant?->nom ?? '', 0, 1)"
                     :title="$mNomEtudiant"
                     :sub="'Profil étudiant' . ($mEtudiant?->matricule ? ' · ' . $mEtudiant->matricule : '')" />
        </div>
    </div>

    @if($mPeutAnnulerMien || ($mUser?->can('paiements.create')))
        <x-m.actionbar :row="$mPeutAnnulerMien && ($mUser?->can('paiements.create') ?? false)">
            @if($mPeutAnnulerMien)
                <button type="button" class="m-btn {{ ($mUser?->can('paiements.create') ?? false) ? 'g' : 'p' }}" x-on:click="ouvrir('psm-annuler')">
                    <x-m.icon name="clock" />Annuler ma saisie
                </button>
            @endif
            @can('paiements.create')
                <a href="{{ route('esbtp.paiements.create') }}" class="m-btn p">
                    {{-- Deux boutons côte à côte : 170 px chacun, le libellé long passait sur deux lignes. --}}
                    <x-m.icon name="plus" />{{ $mPeutAnnulerMien ? 'Encaisser' : 'Nouvel encaissement' }}
                </a>
            @endcan
        </x-m.actionbar>
    @endif

    {{-- ============ Feuille « Actions » ============ --}}
    @if($mADesActions)
    <x-m.sheet id="psm-actions" title="Actions" :sub="'Reçu N° ' . $mNumero . ' · ' . $mMontant . ' FCFA'">
        <div class="m-menu">
            @if($paiement->status === 'en_attente')
                @can('paiements.validate')
                    <button type="button" x-show="statut === 'en_attente'" x-on:click="valider()" x-bind:disabled="occupe">
                        <x-m.icon name="check" />Valider le paiement<span class="ch"><x-m.icon name="chr" /></span>
                    </button>
                    <button type="button" x-show="statut === 'en_attente'" x-on:click="ouvrir('psm-rejeter')">
                        <x-m.icon name="x" />Rejeter le paiement<span class="ch"><x-m.icon name="chr" /></span>
                    </button>
                @endcan
            @endif
            @if($mRecuDisponible)
                <button type="button" x-on:click="ouvrir('psm-envoyer')">
                    <x-m.icon name="share" />Renvoyer le reçu<span class="ch"><x-m.icon name="chr" /></span>
                </button>
            @endif
            @can('paiements.correct_mode')
                @if($paiement->status === 'validé' && ! $mEstAvoir && ! $paiement->reconciliation_locked_at)
                    <button type="button" x-on:click="ouvrir('psm-mode')">
                        <x-m.icon name="refresh" />Corriger le mode de règlement<span class="ch"><x-m.icon name="chr" /></span>
                    </button>
                @endif
            @endcan
            @can('paiements.reventiler')
                @if(! $mEstAvoir)
                    <a href="{{ route('esbtp.paiements.ventilation.edit', $paiement->id) }}">
                        <x-m.icon name="list" />Réimputer sur d'autres frais<span class="ch"><x-m.icon name="chr" /></span>
                    </a>
                @endif
            @endcan
            @can('cancelOwnRecent', $paiement)
                <button type="button" x-on:click="ouvrir('psm-annuler')">
                    <x-m.icon name="clock" />Annuler ma saisie<span class="ch"><x-m.icon name="chr" /></span>
                </button>
            @endcan
        </div>

        @can('paiements.avoir')
            @if($paiement->status === 'validé' && ! $mEstAvoir && $mAvoirDisponible > 0)
                <button type="button" class="m-btn d" x-show="avoirDisponible > 0" x-on:click="ouvrir('psm-avoir')">
                    <x-m.icon name="file" />Émettre un avoir
                </button>
            @endif
        @endcan

        @can('paiements.delete')
                <div class="psm-sep" aria-hidden="true"></div>
                <button type="button" class="psm-danger-link" x-on:click="ouvrir('psm-supprimer')">
                    <x-m.icon name="x" />Supprimer définitivement ce paiement
                </button>
            @endcan

    </x-m.sheet>
    @endif

    {{-- ============ Feuille « Envoyer le reçu » ============ --}}
    @if($mRecuDisponible)
    <x-m.sheet id="psm-envoyer" title="Envoyer le reçu" :sub="'N° ' . $mNumero . ' · ' . $mNomEtudiant">
        @if($mWaEtudiant || $mWaTuteur || $mEmail)
            <div class="m-menu">
                @if($mWaEtudiant)
                    <a href="https://wa.me/{{ $mWaEtudiant }}?text={{ rawurlencode($mTexteRecu) }}" target="_blank" rel="noopener" x-on:click="hide()">
                        <x-m.icon name="msg" />
                        <span class="psm-menu-tt">WhatsApp de l'étudiant<small>{{ \App\Domain\Notifications\PhoneFormatter::toReadable($mEtudiant?->telephone) ?? '—' }}</small></span>
                        <span class="ch"><x-m.icon name="chr" /></span>
                    </a>
                @endif
                @if($mWaTuteur)
                    <a href="https://wa.me/{{ $mWaTuteur }}?text={{ rawurlencode($mTexteRecu) }}" target="_blank" rel="noopener" x-on:click="hide()">
                        <x-m.icon name="users" />
                        <span class="psm-menu-tt">WhatsApp du parent{{ $mTuteur?->nom ? ' · ' . trim(($mTuteur->prenoms ?? '') . ' ' . $mTuteur->nom) : '' }}<small>{{ \App\Domain\Notifications\PhoneFormatter::toReadable($mTelTuteur) ?? '—' }}</small></span>
                        <span class="ch"><x-m.icon name="chr" /></span>
                    </a>
                @endif
                @if($mEmail)
                    <a href="mailto:{{ $mEmail }}?subject={{ rawurlencode($mSujetRecu) }}&body={{ rawurlencode($mTexteRecu) }}" x-on:click="hide()">
                        <x-m.icon name="inbox" />
                        <span class="psm-menu-tt">E-mail<small>{{ $mEmail }}</small></span>
                        <span class="ch"><x-m.icon name="chr" /></span>
                    </a>
                @endif
            </div>
            <p class="psm-hint">Le message contient le numéro, le montant et les frais couverts. Joignez le PDF depuis « Télécharger » si besoin.</p>
        @else
            <x-m.empty icon="phone" title="Aucun contact enregistré" text="Ajoutez un téléphone ou une adresse e-mail sur la fiche de l'étudiant pour lui envoyer le reçu." />
        @endif
        <a href="{{ route('esbtp.paiements.recu', $paiement->id) }}" class="m-btn g">
            <x-m.icon name="dl" />Télécharger le PDF
        </a>
    </x-m.sheet>
    @endif

    {{-- ============ Feuille « Rejeter » ============ --}}
    @if($paiement->status === 'en_attente')
    @can('paiements.validate')
    <x-m.sheet id="psm-rejeter" title="Rejeter le paiement" :sub="$mMontant . ' FCFA · N° ' . $mNumero">
        <form x-on:submit.prevent="rejeter()" class="psm-form">
            <div class="psm-note bad">
                <x-m.icon name="alert" />
                <span>Cette action est irréversible. Le caissier verra le motif pour corriger sa saisie.</span>
            </div>
            <div class="m-field">
                <label for="psm-rejet-motif">Motif du rejet (10 caractères minimum)</label>
                <textarea id="psm-rejet-motif" class="m-in ta" rows="3" minlength="10" maxlength="500" required
                          x-model="formRejet.motif" placeholder="Ex : montant incorrect, doit être 50 000 FCFA."></textarea>
                <small class="psm-count" x-text="formRejet.motif.length + ' / 500'"></small>
            </div>
            <button type="submit" class="m-btn d" x-bind:disabled="occupe || formRejet.motif.trim().length < 10">
                <span x-show="!occupe">Confirmer le rejet</span>
                <span x-show="occupe" x-cloak>Envoi…</span>
            </button>
            <button type="button" class="m-btn g" x-on:click="hide()">Garder le paiement</button>
        </form>
    </x-m.sheet>
    @endcan
    @endif

    {{-- ============ Feuille « Corriger le mode » ============ --}}
    @can('paiements.correct_mode')
    @if($paiement->status === 'validé' && ! $mEstAvoir && ! $paiement->reconciliation_locked_at)
    <x-m.sheet id="psm-mode" title="Corriger le mode de règlement" :sub="'Reçu N° ' . $mNumero">
        <form x-on:submit.prevent="corrigerMode()" class="psm-form">
            <p class="psm-hint">Seule l'étiquette du règlement change : le montant ({{ $mMontant }} FCFA), le frais et la date restent intacts. L'ancienne et la nouvelle valeur sont inscrites au journal d'audit.</p>
            <div class="m-field">
                <label>Mode réel</label>
                <div class="m-opt">
                    @foreach($mModes as $mCle => $mLibelle)
                        <label x-bind:class="formMode.mode === @js($mCle) ? 'on' : ''">
                            <span class="rd" aria-hidden="true"></span>
                            <span>{{ $mLibelle }}@if($mCle === $mModeActuel) <small class="psm-actuel">(enregistré)</small>@endif</span>
                            <input type="radio" name="psm_mode" value="{{ $mCle }}" x-model="formMode.mode" class="psm-radio">
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="m-field">
                <label for="psm-mode-motif">Motif (10 caractères minimum)</label>
                <textarea id="psm-mode-motif" class="m-in ta" rows="3" minlength="10" maxlength="500" required
                          x-model="formMode.motif" placeholder="Pourquoi cette correction ?"></textarea>
            </div>
            <button type="submit" class="m-btn p" x-bind:disabled="occupe || formMode.mode === mode || formMode.motif.trim().length < 10">
                <span x-show="!occupe">Enregistrer la correction</span>
                <span x-show="occupe" x-cloak>Enregistrement…</span>
            </button>
        </form>
    </x-m.sheet>
    @endif
    @endcan

    {{-- ============ Feuille « Émettre un avoir » ============ --}}
    @can('paiements.avoir')
    @if($paiement->status === 'validé' && ! $mEstAvoir && $mAvoirDisponible > 0)
    <x-m.sheet id="psm-avoir" title="Émettre un avoir" :sub="'Reçu N° ' . $mNumero . ' · ' . $mNomEtudiant">
        <form x-on:submit.prevent="emettreAvoir()" class="psm-form">
            <dl class="m-dl">
                <dt>Reste annulable sur ce reçu</dt>
                <dd x-text="format(avoirDisponible) + ' FCFA'">{{ number_format($mAvoirDisponible, 0, ',', ' ') }} FCFA</dd>
            </dl>
            <div class="m-field">
                <label>Que devient l'argent ?</label>
                <div class="m-opt">
                    <label x-bind:class="formAvoir.kind === 'credit' ? 'on' : ''">
                        <span class="rd" aria-hidden="true"></span>
                        <span><b>Crédit sur compte</b><small>L'argent reste à l'école, réutilisable sur un autre frais.</small></span>
                        <input type="radio" name="psm_avoir_kind" value="credit" x-model="formAvoir.kind" class="psm-radio">
                    </label>
                    <label x-bind:class="formAvoir.kind === 'refund' ? 'on' : ''">
                        <span class="rd" aria-hidden="true"></span>
                        <span><b>Remboursement</b><small>L'argent est rendu ; la sortie va au journal de caisse du jour.</small></span>
                        <input type="radio" name="psm_avoir_kind" value="refund" x-model="formAvoir.kind" class="psm-radio">
                    </label>
                </div>
            </div>
            <div class="m-field">
                <label for="psm-avoir-montant">Montant à annuler (FCFA)</label>
                <input id="psm-avoir-montant" type="number" inputmode="numeric" class="m-in" min="1" x-bind:max="avoirDisponible" required
                       x-model.number="formAvoir.montant">
                <button type="button" class="psm-link" x-on:click="formAvoir.montant = avoirDisponible">Tout annuler</button>
            </div>
            <div class="m-field">
                <label for="psm-avoir-motif">Motif (10 caractères minimum)</label>
                <textarea id="psm-avoir-motif" class="m-in ta" rows="3" minlength="10" maxlength="500" required
                          x-model="formAvoir.motif" placeholder="Pourquoi ce versement est-il annulé ?"></textarea>
            </div>
            <p class="psm-hint">L'opération est inscrite au journal d'audit avec votre nom et l'heure. Elle ne s'efface pas.</p>
            <button type="submit" class="m-btn d" x-bind:disabled="occupe || !avoirValide()">
                <span x-show="!occupe" x-text="'Annuler ' + format(formAvoir.montant) + ' FCFA'"></span>
                <span x-show="occupe" x-cloak>Enregistrement…</span>
            </button>
        </form>
    </x-m.sheet>
    @endif
    @endcan

    {{-- ============ Feuille « Annuler mon encaissement » ============ --}}
    @can('cancelOwnRecent', $paiement)
    <x-m.sheet id="psm-annuler" title="Annuler mon encaissement ?" :sub="$mMontant . ' FCFA · N° ' . $mNumero">
        <p class="psm-hint">Vous venez de saisir ce paiement : vous pouvez encore l'annuler pendant environ {{ $mMinutesRestantes }} min. Vous pourrez en saisir un nouveau ensuite. Cette action est tracée.</p>
        <button type="button" class="m-btn d" x-on:click="annulerMien()" x-bind:disabled="occupe">
            <span x-show="!occupe">Oui, annuler ma saisie</span>
            <span x-show="occupe" x-cloak>Annulation…</span>
        </button>
        <button type="button" class="m-btn g" x-on:click="hide()">Garder</button>
    </x-m.sheet>
    @endcan

    {{-- ============ Feuille « Supprimer » ============ --}}
    @can('paiements.delete')
    <x-m.sheet id="psm-supprimer" title="Supprimer ce versement" :sub="'Motif obligatoire · N° ' . $mNumero">
        <form x-on:submit.prevent="supprimer()" class="psm-form">
            <div class="psm-note bad">
                <x-m.icon name="alert" />
                <span>Le versement de {{ $mMontant }} FCFA sera retiré des comptes de l'étudiant. Votre nom, la date et le motif restent lisibles au journal d'audit. Pour confirmer, retapez le numéro du reçu.</span>
            </div>
            <div class="m-field">
                <label for="psm-suppr-motif">Motif de la suppression (10 caractères minimum)</label>
                <textarea id="psm-suppr-motif" class="m-in" rows="3" maxlength="500"
                          x-model="formSuppr.motif" placeholder="Ex : Encaissé par erreur, le paquet de rames a été déposé en nature."></textarea>
            </div>
            <div class="m-field">
                <label for="psm-suppr-numero">Numéro du reçu : {{ $mNumero }}</label>
                <input id="psm-suppr-numero" type="text" class="m-in" autocomplete="off" autocapitalize="characters"
                       x-model="formSuppr.saisie" placeholder="{{ $mNumero }}">
            </div>
            <button type="submit" class="m-btn d" x-bind:disabled="occupe || formSuppr.motif.trim().length < 10 || formSuppr.saisie.trim().toUpperCase() !== numero.toUpperCase()">
                <span x-show="!occupe">Supprimer définitivement</span>
                <span x-show="occupe" x-cloak>Suppression…</span>
            </button>
            <button type="button" class="m-btn g" x-on:click="hide()">Annuler</button>
        </form>
    </x-m.sheet>
    @endcan
</div>

@push('styles')
<style>
    /* Fiche paiement mobile — namespace psm- */
    .psm-screen .m-recu dd .psm-frais { display: block; }
    .psm-screen .m-dl .psm-wrap { text-align: left; font-weight: 500; white-space: pre-wrap; }
    .psm-note { display: grid; grid-template-columns: auto 1fr; gap: 10px; align-items: center; background: #fff3df; color: #8a5200; border: 1px solid #f6dfb3; border-radius: 14px; padding: 12px 14px; font-size: 13px; font-weight: 600; font-family: var(--m-font); }
    .psm-note svg { width: 20px; height: 20px; }
    .psm-note.bad { background: #fdecea; color: #a12016; border-color: #f5c6c0; }
    .psm-form { display: grid; gap: 12px; }
    .psm-hint { margin: 0; font-size: 12.5px; color: #64748b; line-height: 1.45; font-family: var(--m-font); }
    .psm-count { font-size: 11.5px; color: #94a3b8; text-align: right; }
    .psm-actuel { color: #64748b; font-weight: 500; font-size: 11.5px; }
    .psm-radio { position: absolute; opacity: 0; width: 1px; height: 1px; pointer-events: none; }
    .m-opt label { position: relative; }
    .m-opt label > span:nth-child(2) { display: grid; gap: 2px; font-size: 14px; color: #0f172a; font-weight: 600; }
    .m-opt label > span:nth-child(2) small { font-size: 12px; color: #64748b; font-weight: 500; }
    .m-opt label.on .rd { border-color: #0453cb; box-shadow: inset 0 0 0 5px #0453cb; }
    .psm-link { background: none; border: 0; padding: 0; justify-self: start; font-size: 12.5px; font-weight: 700; color: #0453cb; cursor: pointer; font-family: inherit; }
    .psm-sep { height: 1px; background: #e6eaf2; margin: 4px 0; }
    .psm-danger-link { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; min-height: 48px; border: 0; background: transparent; color: #a12016; font-weight: 700; font-size: 14px; font-family: inherit; cursor: pointer; border-radius: 12px; }
    .psm-danger-link svg { width: 18px; height: 18px; }
    .psm-menu-tt { display: grid; gap: 1px; min-width: 0; }
    .psm-menu-tt small { font-size: 12px; color: #64748b; font-weight: 500; }
    .m-menu button[disabled] { opacity: .6; cursor: wait; }
</style>
@endpush

@push('scripts')
<script>
    if (typeof window.psmRecu !== 'function') {
        window.psmRecu = function (cfg) {
            return {
                numero: cfg.numero,
                statut: cfg.statut,
                mode: cfg.mode,
                modes: cfg.modes || {},
                avoirDisponible: Number(cfg.avoirDisponible || 0),
                urls: cfg.urls || {},
                occupe: false,
                dernierAvoirUrl: null,
                formRejet: { motif: '' },
                formMode: { mode: cfg.mode, motif: '' },
                formAvoir: { kind: 'credit', montant: Number(cfg.avoirDisponible || 0), motif: '' },
                formSuppr: { saisie: '', motif: '' },

                init() {
                    if (cfg.flash) {
                        this.toast(cfg.flash, 'success');
                    }
                },

                ouvrir(id) {
                    window.dispatchEvent(new CustomEvent('m-sheet:close', { detail: {} }));
                    window.dispatchEvent(new CustomEvent('m-sheet:open', { detail: { id: id } }));
                },
                fermerTout() {
                    window.dispatchEvent(new CustomEvent('m-sheet:close', { detail: {} }));
                },
                toast(message, type) {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { type: type || 'success', message: message } }));
                },
                format(n) {
                    return new Intl.NumberFormat('fr-FR').format(Number(n) || 0);
                },
                libelleMode(cle) {
                    return this.modes[cle] || cle || '—';
                },
                libelleStatut() {
                    return { 'validé': 'Validé', 'en_attente': 'À valider', 'rejeté': 'Rejeté' }[this.statut] || this.statut || '—';
                },
                toneStatut() {
                    return { 'validé': 'ok', 'en_attente': 'warn', 'rejeté': 'bad' }[this.statut] || 'mute';
                },
                avoirValide() {
                    var m = Number(this.formAvoir.montant);
                    return (this.formAvoir.kind === 'credit' || this.formAvoir.kind === 'refund')
                        && m > 0 && m <= this.avoirDisponible
                        && this.formAvoir.motif.trim().length >= 10;
                },

                async appeler(url, method, body) {
                    var res = await fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: body ? JSON.stringify(body) : undefined,
                        credentials: 'same-origin',
                    });
                    var data = await res.json().catch(function () { return {}; });
                    if (!res.ok || data.success === false) {
                        var msg = data.message;
                        if (!msg && data.errors) {
                            msg = Object.values(data.errors).flat().join(' ');
                        }
                        throw new Error(msg || ('Erreur ' + res.status));
                    }
                    return data;
                },

                async executer(action) {
                    if (this.occupe) { return; }
                    this.occupe = true;
                    try {
                        await action();
                    } catch (err) {
                        this.toast(err.message || 'Une erreur est survenue.', 'error');
                    } finally {
                        this.occupe = false;
                    }
                },

                valider() {
                    var self = this;
                    return this.executer(async function () {
                        var data = await self.appeler(self.urls.valider, 'POST', {});
                        self.statut = 'validé';
                        self.fermerTout();
                        self.toast(data.message || 'Paiement validé.', 'success');
                    });
                },

                rejeter() {
                    var self = this;
                    return this.executer(async function () {
                        var data = await self.appeler(self.urls.rejeter, 'POST', { motif_rejet: self.formRejet.motif.trim() });
                        self.statut = 'rejeté';
                        self.fermerTout();
                        self.toast(data.message || 'Paiement rejeté.', 'success');
                    });
                },

                corrigerMode() {
                    var self = this;
                    return this.executer(async function () {
                        var data = await self.appeler(self.urls.mode, 'PATCH', {
                            mode_paiement: self.formMode.mode,
                            motif: self.formMode.motif.trim(),
                        });
                        self.mode = data.mode_paiement || self.formMode.mode;
                        self.formMode.motif = '';
                        self.fermerTout();
                        self.toast(data.message || 'Mode de règlement corrigé.', 'success');
                    });
                },

                emettreAvoir() {
                    var self = this;
                    return this.executer(async function () {
                        var montant = Number(self.formAvoir.montant);
                        var data = await self.appeler(self.urls.avoir, 'POST', {
                            montant: montant,
                            avoir_kind: self.formAvoir.kind,
                            motif: self.formAvoir.motif.trim(),
                        });
                        self.avoirDisponible = Math.max(0, self.avoirDisponible - montant);
                        self.formAvoir = { kind: 'credit', montant: self.avoirDisponible, motif: '' };
                        if (data.avoir_id && self.urls.avoirPdf) {
                            self.dernierAvoirUrl = self.urls.avoirPdf.replace('__ID__', String(data.avoir_id));
                        }
                        self.fermerTout();
                        self.toast(data.message || 'Avoir enregistré.', 'success');
                    });
                },

                annulerMien() {
                    var self = this;
                    return this.executer(async function () {
                        var data = await self.appeler(self.urls.annulerMien, 'POST', {});
                        self.fermerTout();
                        self.toast(data.message || 'Paiement annulé.', 'success');
                        // EXCEPTION ajax-no-reload-premium : le versement n'existe plus, la fiche n'a plus d'objet.
                        setTimeout(function () { window.location.assign(self.urls.index); }, 600);
                    });
                },

                supprimer() {
                    var self = this;
                    return this.executer(async function () {
                        var data = await self.appeler(self.urls.supprimer, 'DELETE', { motif: self.formSuppr.motif.trim() });
                        self.fermerTout();
                        self.toast(data.message || 'Paiement supprimé.', 'success');
                        // EXCEPTION ajax-no-reload-premium : le versement n'existe plus, la fiche n'a plus d'objet.
                        setTimeout(function () { window.location.assign(self.urls.index); }, 600);
                    });
                },
            };
        };
    }
</script>
@endpush
