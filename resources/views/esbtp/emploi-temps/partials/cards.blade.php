@php
    // Palette monochrome bleue KLASSCI — stripe gauche stable par filière
    $etStripePalette = ['#0a3d8f', '#0453cb', '#1e5fc4', '#2d6dc8', '#3b7ddb', '#4a8bd9', '#5e91de', '#7aa8e4'];
@endphp

@if(!empty($timetableShortcut) && ($timetableShortcut['show'] ?? false))
    <div class="emploi-card emploi-shortcut-card" id="raccourciEmploisTemps">
        <div class="emploi-card-body">
            <div class="emploi-shortcut-title">
                <i class="fas fa-calendar-exclamation me-2"></i>Raccourci emplois du temps
            </div>
            <div class="emploi-shortcut-meta">
                Créez rapidement les emplois du temps pour les classes sans planning ou en fin de validité.
                <button type="button" class="btn btn-link btn-sm p-0 ms-1" data-bs-toggle="modal" data-bs-target="#quickGenerateHelpModal">
                    <i class="fas fa-info-circle me-1"></i>Voir le fonctionnement
                </button>
            </div>
            <div class="emploi-shortcut-stats">
                @if($timetableShortcut['missing'] > 0)
                    <span class="emploi-shortcut-chip">{{ $timetableShortcut['missing'] }} sans emploi du temps</span>
                @endif
                @if($timetableShortcut['expired'] > 0)
                    <span class="emploi-shortcut-chip">{{ $timetableShortcut['expired'] }} expiré(s)</span>
                @endif
                @if($timetableShortcut['expiring_soon'] > 0)
                    <span class="emploi-shortcut-chip">{{ $timetableShortcut['expiring_soon'] }} expire(nt) bientôt</span>
                @endif
            </div>
            @if(auth()->user()->hasAnyPermission(['admin.access', 'identity.school_manager']) || auth()->user()->can('timetables.create'))
                <div class="emploi-actions" style="border-top: none; padding-top: 0;">
                    <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#quickGenerateModal">
                        <i class="fas fa-bolt me-1"></i>Créer maintenant
                    </button>
                </div>
            @endif
        </div>
    </div>
@endif

@forelse($emploisTemps as $emploiTemps)
    @php
        $today = \Carbon\Carbon::today();
        $startDate = $emploiTemps->date_debut ? \Carbon\Carbon::parse($emploiTemps->date_debut) : null;
        $endDate = $emploiTemps->date_fin ? \Carbon\Carbon::parse($emploiTemps->date_fin) : null;
        $isExpired = $endDate && $endDate->lt($today);
        $isUpcoming = $startDate && $startDate->gt($today);
        $isCurrentPeriod = $startDate && $endDate && $today->between($startDate, $endDate);
        $isExpiringSoon = $endDate && $endDate->gte($today) && $endDate->diffInDays($today) <= 3;

        // Statut pour les chips (aligne sur statusFilter côté Alpine)
        $cardStatus = $isExpired ? 'expired' : ($isCurrentPeriod ? 'active' : 'upcoming');

        // Couleur stripe stable par filière (hash monochrome bleu)
        $filiereName = $emploiTemps->classe->filiere->name ?? '—';
        $stripeIdx = hexdec(substr(md5($filiereName), 0, 4)) % count($etStripePalette);
        $stripeColor = $etStripePalette[$stripeIdx];

        // Meta : séances + heures (eager loaded, pas de N+1)
        $seancesCount = $emploiTemps->seances_count ?? ($emploiTemps->seances?->count() ?? 0);
        $totalMinutes = ($emploiTemps->seances ?? collect())->sum(function ($s) {
            if (!$s->heure_debut || !$s->heure_fin) return 0;
            return \Carbon\Carbon::parse($s->heure_debut)->diffInMinutes(\Carbon\Carbon::parse($s->heure_fin));
        });
        $totalHours = intdiv((int) $totalMinutes, 60);
        $totalRemMinutes = (int) $totalMinutes % 60;

        // Haystack pour la recherche inline (classe + filière + niveau)
        $searchHaystack = strtolower(trim(
            ($emploiTemps->classe->name ?? '') . ' ' .
            ($emploiTemps->classe->filiere->name ?? '') . ' ' .
            ($emploiTemps->classe->niveau->name ?? '')
        ));

        $canEdit = auth()->user()->hasAnyPermission(['admin.access', 'identity.school_manager']) || auth()->user()->can('timetables.edit');
        $canDelete = auth()->user()->can('admin.access') && auth()->user()->can('timetables.delete');
    @endphp

    <div class="et-card et-card--{{ $cardStatus }}"
         data-status="{{ $cardStatus }}"
         data-search="{{ $searchHaystack }}"
         x-show="(statusFilter === 'all' || statusFilter === '{{ $cardStatus }}')
                 && (searchQuery.trim() === '' || $el.dataset.search.includes(searchQuery.toLowerCase()))"
         x-transition.opacity>

        <span class="et-card__stripe" style="background: {{ $stripeColor }};" aria-hidden="true"></span>

        <div class="et-card__inner">
            <div class="et-card__head">
                <div class="et-card__titles">
                    <h6 class="et-card__title">{{ $emploiTemps->classe->name ?? 'Classe inconnue' }}</h6>
                    <div class="et-card__subtitle">
                        <span>{{ $filiereName }}</span>
                        <span class="et-card__sep">·</span>
                        <span>{{ $emploiTemps->classe->niveau->name ?? 'Niveau inconnu' }}</span>
                    </div>
                </div>
                <div class="et-card__badges">
                    @if($isExpired)
                        <span class="et-card__badge et-card__badge--danger">
                            <i class="fas fa-circle-exclamation"></i>Expiré
                        </span>
                    @elseif($isCurrentPeriod)
                        <span class="et-card__badge et-card__badge--success">
                            <i class="fas fa-circle-check"></i>Actif
                        </span>
                    @elseif($isUpcoming)
                        <span class="et-card__badge et-card__badge--info">
                            <i class="fas fa-calendar"></i>À venir
                        </span>
                    @endif
                    @if($isExpiringSoon && !$isExpired)
                        <span class="et-card__badge et-card__badge--warning">
                            <i class="fas fa-clock"></i>Expire bientôt
                        </span>
                    @endif
                </div>
            </div>

            <div class="et-card__meta">
                @if($startDate && $endDate)
                    <span class="et-card__meta-item">
                        <i class="fas fa-calendar-day"></i>
                        {{ $startDate->isoFormat('D MMM') }} → {{ $endDate->isoFormat('D MMM YYYY') }}
                    </span>
                @endif
                <span class="et-card__meta-item">
                    <i class="fas fa-layer-group"></i>
                    {{ $seancesCount }} séance{{ $seancesCount > 1 ? 's' : '' }}
                </span>
                @if($totalMinutes > 0)
                    <span class="et-card__meta-item">
                        <i class="fas fa-clock"></i>
                        {{ $totalHours }}h{{ $totalRemMinutes > 0 ? str_pad((string) $totalRemMinutes, 2, '0', STR_PAD_LEFT) : '' }}
                    </span>
                @endif
                @if($emploiTemps->semestre && $emploiTemps->semestre !== 'Année')
                    <span class="et-card__meta-item et-card__meta-item--muted">
                        {{ $emploiTemps->semestre === 'Semestre 1' ? 'S1' : ($emploiTemps->semestre === 'Semestre 2' ? 'S2' : $emploiTemps->semestre) }}
                    </span>
                @endif
            </div>

            @if($emploiTemps->updatedBy || $emploiTemps->updated_at)
                <div class="et-card__footer">
                    <i class="fas fa-user-pen"></i>
                    Modifié {{ $emploiTemps->updated_at?->diffForHumans() }}
                    @if($emploiTemps->updatedBy)
                        par {{ $emploiTemps->updatedBy->name }}
                    @endif
                </div>
            @endif

            <div class="et-card__actions">
                <a href="{{ route('esbtp.emploi-temps.export-pdf', $emploiTemps->id) }}"
                   class="et-card-btn et-card-btn--ghost"
                   title="Exporter en PDF">
                    <i class="fas fa-file-pdf"></i>
                    <span>PDF</span>
                </a>
                <a href="{{ route('esbtp.emploi-temps.show', $emploiTemps->id) }}"
                   class="et-card-btn et-card-btn--primary"
                   title="Ouvrir l'emploi du temps">
                    <span>Ouvrir</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
                @if($canEdit || $canDelete)
                    <div class="et-card__menu" x-data="{ open: false }" @click.outside="open = false">
                        <button type="button"
                                class="et-card-btn et-card-btn--icon"
                                @click="open = !open"
                                :aria-expanded="open.toString()"
                                aria-label="Plus d'actions">
                            <i class="fas fa-ellipsis-vertical"></i>
                        </button>
                        <div class="et-card__menu-pop"
                             x-show="open"
                             x-transition.opacity.duration.150ms
                             style="display: none;">
                            @if($canEdit)
                                <a href="{{ route('esbtp.emploi-temps.edit', $emploiTemps->id) }}" class="et-card__menu-item">
                                    <i class="fas fa-edit"></i>Modifier
                                </a>
                            @endif
                            @if($canDelete)
                                <form action="{{ route('esbtp.emploi-temps.destroy', $emploiTemps->id) }}"
                                      method="POST"
                                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet emploi du temps ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="et-card__menu-item et-card__menu-item--danger">
                                        <i class="fas fa-trash"></i>Supprimer
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@empty
    @php
        // L'utilisateur filtre sur une semaine ?semaine=X|Y qui est vide, et la semaine
        // précédente contient des plannings → on propose la duplication.
        $requestedWeek = request('semaine');
        $canDuplicate = ! empty($previousWeekValue ?? null)
            && ($previousWeekPlanningCount ?? 0) > 0
            && ! empty($requestedWeek)
            && (auth()->user()->hasAnyPermission(['admin.access', 'identity.school_manager']) || auth()->user()->can('timetables.create'));
    @endphp
    @if($canDuplicate)
        @php
            [$prevStart, $prevEnd] = array_map('trim', explode('|', $previousWeekValue));
            [$targetStart, $targetEnd] = array_map('trim', explode('|', $requestedWeek));
            $prevLabel = \Carbon\Carbon::parse($prevStart)->isoFormat('D MMM') . ' → ' . \Carbon\Carbon::parse($prevEnd)->isoFormat('D MMM YYYY');
            $targetLabel = \Carbon\Carbon::parse($targetStart)->isoFormat('D MMM') . ' → ' . \Carbon\Carbon::parse($targetEnd)->isoFormat('D MMM YYYY');
        @endphp
        <div class="et-duplicate-empty">
            <div class="et-duplicate-empty__icon">
                <i class="fas fa-copy"></i>
            </div>
            <div class="et-duplicate-empty__body">
                <h5 class="et-duplicate-empty__title">Semaine vide</h5>
                <p class="et-duplicate-empty__text">
                    Aucun emploi du temps pour la semaine du <strong>{{ $targetLabel }}</strong>.
                    La semaine précédente du <strong>{{ $prevLabel }}</strong> en contient
                    <strong>{{ $previousWeekPlanningCount }}</strong>.
                </p>
                <form action="{{ route('esbtp.emploi-temps.duplicate-week') }}"
                      method="POST"
                      class="et-duplicate-empty__form"
                      onsubmit="return confirm('Dupliquer {{ $previousWeekPlanningCount }} emploi(s) du temps vers la semaine cible ?\n\nLes séances avec conflits enseignant seront ignorées. Les classes déjà planifiées ne seront pas écrasées.')">
                    @csrf
                    <input type="hidden" name="source_semaine" value="{{ $previousWeekValue }}">
                    <input type="hidden" name="target_semaine" value="{{ $requestedWeek }}">
                    <button type="submit" class="et-duplicate-empty__btn">
                        <i class="fas fa-wand-magic-sparkles"></i>
                        Dupliquer pour cette semaine
                    </button>
                    <a href="{{ route('esbtp.emploi-temps.create') }}" class="et-duplicate-empty__link">
                        Ou créer manuellement →
                    </a>
                </form>
            </div>
        </div>
    @else
        <div class="empty-state-card">
            <div class="text-center py-5">
                <i class="fas fa-calendar-times fa-4x text-muted mb-4"></i>
                <h5 class="text-muted mb-2">Aucun emploi du temps trouvé</h5>
                <p class="text-muted mb-4">Créez votre premier emploi du temps pour commencer.</p>
                <a href="{{ route('esbtp.emploi-temps.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Créer un emploi du temps
                </a>
            </div>
        </div>
    @endif
@endforelse

{{-- Empty-state client-side : visible quand chip + recherche masquent toutes les cards --}}
@if($emploisTemps->isNotEmpty())
    <div class="et-empty-filter"
         x-show="hasActiveFilter && visibleCardsCount === 0"
         x-cloak
         style="display: none;">
        <i class="fas fa-filter"></i>
        <div>Aucun emploi du temps ne correspond au filtre actuel.</div>
        <button type="button"
                class="et-card-btn et-card-btn--ghost mt-3"
                @click="statusFilter = 'all'; searchQuery = ''">
            <i class="fas fa-times"></i>Réinitialiser les filtres
        </button>
    </div>
@endif

