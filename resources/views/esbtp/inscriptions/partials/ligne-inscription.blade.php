{{--
    Partial : UNE ligne d'inscription dans la table.
    Inclus par :
      - esbtp.inscriptions.partials.results (render initial + AJAX filtre)
      - ESBTPInscriptionController::refreshLigne (AJAX refresh après action)

    Design premium namespace ii-*, monochrome bleu KLASSCI.
    - Photo étudiant + HSL fallback
    - Row cliquable via data-row-href (JS delegation, pas stretched-link)
    - Badge statut via Blade component <x-inscription-status-badge>
    - Actions inline + kebab menu pour secondaires
    - Modals globaux pilotés par data-bs-target + data-inscription-id

    Paramètres requis :
      - $inscription : ESBTPInscription eager-loaded (etudiant, filiere, niveau, anneeUniversitaire)
--}}
@php
    $hasProbleme = session('inscriptions_problemes') && isset(session('inscriptions_problemes')[$inscription->id]);
    $problemeInfo = $hasProbleme ? session('inscriptions_problemes')[$inscription->id] : null;
    $problemeClass = $hasProbleme ? ($problemeInfo['type'] === 'error' ? 'ii-row--error' : 'ii-row--warn') : '';

    $etudiant = $inscription->etudiant;
    $nomComplet = $etudiant ? trim(($etudiant->nom ?? '').' '.($etudiant->prenoms ?? '')) : 'Étudiant inconnu';
    $initials = $etudiant
        ? strtoupper(mb_substr($etudiant->nom ?? '', 0, 1).mb_substr($etudiant->prenoms ?? '', 0, 1))
        : '?';
    $avatarHue = hexdec(substr(md5($nomComplet ?: (string) $inscription->id), 0, 4)) % 360;

    $raison = $problemeInfo['message'] ?? '';
    $isPaiementNonValide = $hasProbleme && str_contains($raison, 'paiement') && str_contains($raison, 'validé');
    $isClassePleine = $hasProbleme && (str_contains($raison, 'Classe pleine') || str_contains($raison, 'classe pleine'));
    $isSansPaiement = $hasProbleme && (str_contains($raison, 'Aucun paiement') || str_contains($raison, 'sans paiement'));

    $showHref = auth()->user()->can('inscriptions.view')
        ? route('esbtp.inscriptions.show', $inscription->id)
        : null;

    $canValidate = auth()->user()->can('inscriptions.validate');
    $isNonValidee = $inscription->status === 'en_attente'
        || $inscription->status === 'pending'
        || ($inscription->status === 'active' && $inscription->workflow_step !== 'etudiant_cree');
@endphp
<tr class="ii-row {{ $problemeClass }}"
    data-inscription-id="{{ $inscription->id }}"
    data-matricule="{{ $etudiant->matricule ?? '' }}"
    data-nom="{{ $nomComplet }}"
    @if($showHref) data-row-href="{{ $showHref }}" @endif>

    @can('inscriptions.validate')
        <td class="ii-col-check" data-no-row-click>
            @if($isNonValidee)
                <input type="checkbox"
                       class="form-check-input inscription-checkbox"
                       value="{{ $inscription->id }}"
                       data-inscription-id="{{ $inscription->id }}"
                       aria-label="Sélectionner l'inscription {{ $nomComplet }}">
            @endif
        </td>
    @endcan

    {{-- Étudiant : photo + nom + matricule + N° inscription --}}
    <td class="ii-col-etu">
        <div class="ii-etu-cell">
            <span class="ii-avatar"
                  @if($etudiant && $etudiant->photo_url)
                      style="background:transparent;padding:0;overflow:hidden;"
                  @else
                      style="background: hsl({{ $avatarHue }}, 55%, 92%); color: hsl({{ $avatarHue }}, 50%, 35%);"
                  @endif>
                @if($etudiant && $etudiant->photo_url)
                    <img src="{{ $etudiant->photo_url }}"
                         alt="{{ $nomComplet }}"
                         width="36" height="36"
                         loading="lazy"
                         style="width:100%;height:100%;object-fit:cover;border-radius:50%;"
                         onerror="this.onerror=null;this.parentElement.style.background='hsl({{ $avatarHue }}, 55%, 92%)';this.parentElement.style.color='hsl({{ $avatarHue }}, 50%, 35%)';this.outerHTML='{{ $initials }}';">
                @else
                    {{ $initials }}
                @endif
            </span>
            <div class="ii-etu-body">
                <div class="ii-etu-name">{{ $nomComplet }}</div>
                <div class="ii-etu-meta">
                    <span class="ii-matricule">{{ $etudiant->matricule ?? 'N/A' }}</span>
                    @if(!empty($inscription->numero_inscription))
                        <span class="ii-separator">·</span>
                        <span class="ii-numero">{{ $inscription->numero_inscription }}</span>
                    @endif
                </div>
            </div>
        </div>
    </td>

    {{-- Filière / Niveau (fusionnés) — LMD-aware.
         En LMD avec parcours : Mention en haut + Parcours en sous-texte (chip "LMD").
         En LMD tronc commun : Mention + "Tronc commun" en sous-texte.
         En BTS : filiere->name + niveau->name (pattern legacy). --}}
    <td class="ii-col-filiere">
        @php
            $rowIsLmd = ($inscription->classe?->systeme_academique ?? '') === 'LMD';
            $rowParcours = $rowIsLmd ? $inscription->classe?->parcours : null;
            $rowMention = $rowParcours?->mention;
            // Cas tronc commun : pas de parcours mais classe.filiere_id stocke mention_id (Option A)
            // → l'inscription.filiere_id est la même valeur. Affichage : récupérer la mention via classe.filiere_id.
            $rowTroncCommunMention = ($rowIsLmd && !$rowParcours && $inscription->classe?->filiere)
                ? $inscription->classe->filiere->name  // legacy : si filiere_id pointe vers une vraie filière
                : null;
        @endphp
        <div class="ii-filiere-cell">
            @if($rowParcours && $rowMention)
                {{-- LMD avec parcours --}}
                <div class="ii-filiere-name" style="display:flex;align-items:center;gap:.4rem;">
                    <span>{{ $rowMention->name }}</span>
                    <span style="font-size:.62rem;font-weight:700;color:#0453cb;background:rgba(4,83,203,.1);border:1px solid rgba(4,83,203,.25);padding:.1rem .35rem;border-radius:4px;letter-spacing:.4px;">LMD</span>
                </div>
                <div class="ii-filiere-niveau">
                    <i class="fas fa-route" style="font-size:.65rem;opacity:.7;margin-right:.2rem;"></i>{{ $rowParcours->name }}
                    @if($inscription->niveau)<span style="opacity:.55;"> · {{ $inscription->niveau->name }}</span>@endif
                </div>
            @elseif($rowIsLmd)
                {{-- LMD tronc commun mention --}}
                <div class="ii-filiere-name" style="display:flex;align-items:center;gap:.4rem;">
                    <span>{{ $rowTroncCommunMention ?? ($inscription->filiere->name ?? 'N/A') }}</span>
                    <span style="font-size:.62rem;font-weight:700;color:#0453cb;background:rgba(4,83,203,.1);border:1px solid rgba(4,83,203,.25);padding:.1rem .35rem;border-radius:4px;letter-spacing:.4px;">LMD</span>
                </div>
                <div class="ii-filiere-niveau">
                    <span style="opacity:.7;">Tronc commun</span>
                    @if($inscription->niveau)<span style="opacity:.55;"> · {{ $inscription->niveau->name }}</span>@endif
                </div>
            @else
                {{-- BTS legacy --}}
                <div class="ii-filiere-name">{{ $inscription->filiere->name ?? ($inscription->filiere->nom ?? 'N/A') }}</div>
                <div class="ii-filiere-niveau">{{ $inscription->niveau->name ?? ($inscription->niveau->nom ?? '') }}</div>
            @endif
            @if(!empty($inscription->bts_journey_ui))
                <div style="margin-top:.45rem;">
                    @include('esbtp.partials.bts-journey-badge', ['btsJourney' => $inscription->bts_journey_ui])
                </div>
            @endif
        </div>
    </td>

    {{-- Année --}}
    <td class="ii-col-annee">
        {{ $inscription->anneeUniversitaire->name ?? ($inscription->anneeUniversitaire->annee_scolaire ?? 'N/A') }}
    </td>

    {{-- Statut via Blade component --}}
    <td class="ii-col-status">
        <x-inscription-status-badge :inscription="$inscription" />
        @if($hasProbleme)
            <div class="ii-probleme-chip" data-no-row-click>
                <i class="fas {{ $problemeInfo['type'] === 'error' ? 'fa-exclamation-circle' : 'fa-exclamation-triangle' }}"></i>
                <span>{{ \Illuminate\Support\Str::limit($problemeInfo['message'], 60) }}</span>
            </div>
            @if($isPaiementNonValide)
                @can('paiements.validate')
                    <button type="button" class="ii-btn-quick"
                            onclick="event.stopPropagation(); ouvrirModalValiderPaiement({{ $inscription->id }})"
                            data-no-row-click>
                        <i class="fas fa-check-circle"></i>Valider paiement
                    </button>
                @endcan
            @elseif($isClassePleine)
                @can('inscriptions.validate')
                    <button type="button" class="ii-btn-quick"
                            onclick="event.stopPropagation(); ouvrirModalChangerClasse({{ $inscription->id }})"
                            data-no-row-click>
                        <i class="fas fa-exchange-alt"></i>Changer classe
                    </button>
                @endcan
            @elseif($isSansPaiement)
                @can('paiements.create')
                    <button type="button" class="ii-btn-quick"
                            onclick="event.stopPropagation(); ouvrirModalCreerPaiement({{ $inscription->id }})"
                            data-no-row-click>
                        <i class="fas fa-plus-circle"></i>Créer paiement
                    </button>
                @endcan
            @endif
        @endif
    </td>

    {{-- Date inscription --}}
    <td class="ii-col-date">{{ $inscription->created_at->format('d/m/Y') }}</td>

    {{-- Actions : inline + kebab pour secondaires --}}
    <td class="ii-col-actions" data-no-row-click>
        <div class="inscription-actions-wrapper" data-inscription-actions="{{ $inscription->id }}">
            <div class="inscription-actions-buttons ii-actions">
                @if($inscription->status === 'pending' || $inscription->status === 'en_attente')
                    @can('inscriptions.validate')
                        <button type="button"
                                class="ii-btn ii-btn--primary valider-btn"
                                data-id="{{ $inscription->id }}"
                                title="Valider l'inscription">
                            <i class="fas fa-check"></i>
                        </button>
                        <form id="valider-form-{{ $inscription->id }}"
                              action="{{ route('esbtp.inscriptions.valider', $inscription->id) }}"
                              method="POST" style="display:none;">
                            @csrf
                            @method('PUT')
                        </form>
                    @endcan
                @endif

                {{-- Menu kebab pour actions secondaires --}}
                <div class="dropdown">
                    <button type="button"
                            class="ii-btn ii-btn--ghost"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            aria-label="Actions supplémentaires"
                            title="Actions supplémentaires">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end ii-dropdown">
                        @can('inscriptions.view')
                            <li>
                                <a class="dropdown-item" href="{{ route('esbtp.inscriptions.show', $inscription->id) }}">
                                    <i class="fas fa-eye"></i>Voir les détails
                                </a>
                            </li>
                        @endcan
                        @can('inscriptions.edit')
                            @if($inscription->status === 'pending' || $inscription->status === 'en_attente')
                                <li>
                                    <a class="dropdown-item" href="{{ route('esbtp.inscriptions.edit', $inscription->id) }}">
                                        <i class="fas fa-edit"></i>Modifier
                                    </a>
                                </li>
                            @endif
                        @endcan
                        @if($inscription->status === 'pending' || $inscription->status === 'en_attente')
                            @can('inscriptions.cancel')
                                <li>
                                    <button type="button"
                                            class="dropdown-item"
                                            data-bs-toggle="modal"
                                            data-bs-target="#ii-modal-annuler"
                                            data-inscription-id="{{ $inscription->id }}"
                                            data-student-name="{{ $nomComplet }}">
                                        <i class="fas fa-times"></i>Annuler l'inscription
                                    </button>
                                </li>
                            @endcan
                        @endif
                        @can('inscriptions.delete')
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <button type="button"
                                        class="dropdown-item ii-dropdown-item--danger"
                                        data-bs-toggle="modal"
                                        data-bs-target="#ii-modal-delete"
                                        data-inscription-id="{{ $inscription->id }}"
                                        data-student-name="{{ $nomComplet }}">
                                    <i class="fas fa-trash"></i>Supprimer
                                </button>
                            </li>
                        @endcan
                    </ul>
                </div>
            </div>
            <div class="inscription-actions-spinner" aria-hidden="true">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
            </div>
        </div>
    </td>
</tr>
