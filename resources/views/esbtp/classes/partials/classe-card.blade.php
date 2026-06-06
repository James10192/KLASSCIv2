{{--
    Carte d'UNE classe — design premium namespace ci-*
    - Card cliquable via Bootstrap 5 .stretched-link (pas de JS stopPropagation)
    - Menu kebab BS5 dropdown pour actions secondaires
    - 1 seul CTA visible (btn edit) si edit perm, sinon la card entière = CTA view

    Paramètres requis :
    - $classe : ESBTPClasse avec relations (filiere, niveau, annee)
    - $canAdmin, $canEditClasse, $canDeleteClasse, $canManageSchool, $canTeach :
      booléens hoisted depuis items.blade.php (un seul calcul pour la liste entière)
--}}
@php
    $occupation = $classe->places_totales > 0
        ? min(100, round(($classe->nombre_etudiants / $classe->places_totales) * 100))
        : 0;
    $occLevel = $occupation >= 95 ? 'full' : ($occupation >= 75 ? 'high' : ($occupation >= 40 ? 'mid' : 'low'));
    $showUrl = route('esbtp.classes.show', array_merge(['classe' => $classe->id], request()->query()));

    // Badges Tronc Commun / Spécialité (cf rule classes-universelles-pas-annee + parent_id).
    // `isTroncCommun()` et `isSpecialite()` sont des helpers ESBTPClasse qui inspectent
    // la filière (is_tronc_commun + parent_id). Le parent TC est résolu en priorité via
    // un mapping manuel esbtp_classe_orientation_targets (override admin), avec fallback
    // automatique via la hiérarchie filière. Voir ESBTPClasse::classeTroncCommunParent().
    $bIsTC = $classe->isTroncCommun();
    $bIsSpe = $classe->isSpecialite();
    $bParentTC = $bIsSpe ? $classe->classeTroncCommunParent() : null;
@endphp

<article class="ci-card {{ $classe->is_active ? '' : 'ci-card--inactive' }}" data-classe-id="{{ $classe->id }}">
    {{-- Ribbon statut --}}
    <span class="ci-card-ribbon ci-card-ribbon--{{ $classe->is_active ? 'active' : 'inactive' }}" aria-hidden="true"></span>

    {{-- Header carte --}}
    <header class="ci-card-header">
        <div class="ci-card-identity">
            <div class="ci-card-icon">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="ci-card-titles">
                <h3 class="ci-card-title">
                    {{-- Stretched-link : toute la card devient cliquable, les boutons internes avec position:relative + z-index prennent le dessus --}}
                    <a href="{{ $showUrl }}" class="stretched-link ci-card-link" title="Voir les détails">{{ $classe->name }}</a>
                </h3>
                <span class="ci-card-code">{{ $classe->code }}</span>
                @if($bIsTC || $bIsSpe)
                    <div class="ci-card-badges">
                        @if($bIsTC)
                            <span class="ci-tc-badge ci-tc-badge--tc" title="Classe rattachée à une filière marquée tronc commun"><i class="fas fa-sitemap"></i>Tronc commun</span>
                        @elseif($bIsSpe)
                            <span class="ci-tc-badge ci-tc-badge--spe" title="Classe issue d'une spécialité d'un tronc commun"><i class="fas fa-graduation-cap"></i>Spécialité</span>
                        @endif
                    </div>
                    @if($bIsSpe && $bParentTC)
                        <div class="ci-card-from" title="Classe TC d'origine pour cette spécialité">
                            <i class="fas fa-arrow-up-from-bracket"></i>
                            <span>Sort de <strong>{{ $bParentTC->name }}</strong></span>
                        </div>
                    @endif
                @endif
            </div>
        </div>

        {{-- Badge statut + menu kebab --}}
        <div class="ci-card-header-right">
            <span class="ci-card-status ci-card-status--{{ $classe->is_active ? 'active' : 'inactive' }}">
                {{ $classe->is_active ? 'Active' : 'Inactive' }}
            </span>
            @if($canManageSchool || $canTeach || $canEditClasse || $canDeleteClasse)
                <div class="dropdown ci-card-menu">
                    <button type="button"
                            class="ci-card-kebab"
                            data-bs-toggle="dropdown"
                            data-bs-strategy="fixed"
                            data-bs-boundary="viewport"
                            data-bs-display="dynamic"
                            aria-expanded="false"
                            aria-label="Actions supplémentaires">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end ci-dropdown">
                        @if($canManageSchool)
                            <li>
                                <a class="dropdown-item" href="{{ route('esbtp.classes.matieres', ['classe' => $classe->id]) }}">
                                    <i class="fas fa-book"></i>Gérer les matières
                                </a>
                            </li>
                        @endif
                        @if($canTeach)
                            <li>
                                <a class="dropdown-item" href="{{ route('esbtp.classes.liste-appel', ['classe' => $classe->id]) }}" target="_blank">
                                    <i class="fas fa-clipboard-list"></i>Liste d'appel
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('esbtp.classes.liste-complete', ['classe' => $classe->id]) }}" target="_blank">
                                    <i class="fas fa-users"></i>Liste complète
                                </a>
                            </li>
                        @endif
                        @if($canEditClasse || $canDeleteClasse)
                            <li><hr class="dropdown-divider"></li>
                            @if($canEditClasse)
                                <li>
                                    <button type="button"
                                            class="dropdown-item btn-open-edit-modal"
                                            data-classe-id="{{ $classe->id }}">
                                        <i class="fas fa-edit"></i>Modifier
                                    </button>
                                </li>
                            @endif
                            @if($canDeleteClasse)
                                <li>
                                    @if($classe->nombre_etudiants == 0)
                                        <button type="button"
                                                class="dropdown-item ci-dropdown-item--danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteModal{{ $classe->id }}">
                                            <i class="fas fa-archive"></i>Archiver la classe
                                        </button>
                                    @else
                                        <button type="button" class="dropdown-item" disabled title="Classe avec historique d'inscriptions — archivage désactivé">
                                            <i class="fas fa-lock"></i>Archivage désactivé
                                        </button>
                                    @endif
                                </li>
                            @endif
                        @endif
                    </ul>
                </div>
            @endif
        </div>
    </header>

    {{-- Meta : filière + niveau (BTS) ou tree premium hiérarchie LMD (Domaine → Mention → Parcours).
         Le tree compact s'affiche pour les classes LMD avec parcours OU mention rattachée
         (cas tronc commun où classe.filiere_id stocke en réalité mention_id, cf rule
         classe-lmd-filiere-as-mention). --}}
    @php
        $cardIsLmd = ($classe->systeme_academique ?? '') === 'LMD';
        $cardLmdParcours = $cardIsLmd && $classe->parcours && $classe->parcours->mention && $classe->parcours->mention->domaine
            ? $classe->parcours
            : null;
    @endphp

    @if($cardLmdParcours)
        <div class="ci-card-meta">
            <x-lmd-hierarchy-tree :parcours="$cardLmdParcours" compact />
            @if($classe->niveau)
                <div class="ci-card-meta-line" style="margin-top:.5rem;">
                    <i class="fas fa-level-up-alt"></i>
                    <span>{{ $classe->niveau->name }}</span>
                </div>
            @endif
        </div>
    @else
        <div class="ci-card-meta">
            @if($classe->filiere)
                <div class="ci-card-meta-line">
                    <i class="fas fa-layer-group"></i>
                    <span>
                        <strong>{{ $classe->filiere->name }}</strong>
                        @if($classe->filiere->parent)
                            <span class="ci-card-meta-parent"> · Option de {{ $classe->filiere->parent->name }}</span>
                        @endif
                        @if($cardIsLmd)
                            <span class="ci-card-meta-parent">· Mention LMD (tronc commun)</span>
                        @endif
                    </span>
                </div>
            @endif
            @if($classe->niveau)
                <div class="ci-card-meta-line">
                    <i class="fas fa-level-up-alt"></i>
                    <span>{{ $classe->niveau->name }}</span>
                </div>
            @endif
        </div>
    @endif

    {{-- Stats : capacité + barre d'occupation --}}
    <div class="ci-card-stats">
        <div class="ci-card-stat">
            <span class="ci-card-stat-value">{{ $classe->nombre_etudiants }}</span>
            <span class="ci-card-stat-label">Inscrits</span>
        </div>
        <div class="ci-card-stat ci-card-stat--separator"></div>
        <div class="ci-card-stat">
            <span class="ci-card-stat-value">{{ $classe->places_totales }}</span>
            <span class="ci-card-stat-label">Capacité</span>
        </div>
        <div class="ci-card-stat ci-card-stat--separator"></div>
        <div class="ci-card-stat">
            <span class="ci-card-stat-value ci-card-stat-value--{{ $classe->places_disponibles > 0 ? 'ok' : 'warn' }}">{{ $classe->places_disponibles }}</span>
            <span class="ci-card-stat-label">Disponibles</span>
        </div>
    </div>

    <div class="ci-card-bar" aria-label="Taux d'occupation : {{ $occupation }}%">
        <div class="ci-card-bar-fill ci-card-bar-fill--{{ $occLevel }}" style="width: {{ $occupation }}%"></div>
        <span class="ci-card-bar-pct">{{ $occupation }}%</span>
    </div>

    {{-- Footer : année --}}
    @if($classe->annee)
        <footer class="ci-card-footer">
            <i class="fas fa-calendar"></i>{{ $classe->annee->name }}
        </footer>
    @endif
</article>

{{-- Modal suppression/archivage (monochrome avec bouton rouge outline pour confirmation Q3a) --}}
@if($canDeleteClasse && $classe->nombre_etudiants == 0)
    <div class="modal fade" id="deleteModal{{ $classe->id }}" tabindex="-1" aria-labelledby="deleteModalLabel{{ $classe->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header ci-modal-header">
                    <h5 class="modal-title" id="deleteModalLabel{{ $classe->id }}">
                        <i class="fas fa-archive me-2"></i>Archiver la classe
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir archiver la classe <strong>{{ $classe->name }}</strong> ?</p>
                    <div class="ci-info-box">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            <strong>L'historique des inscriptions est préservé</strong> pour les rapports et statistiques des années passées. La classe ne sera simplement plus visible dans la liste active.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <form action="{{ route('esbtp.classes.destroy', $classe->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="fas fa-archive me-1"></i>Archiver
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endif
