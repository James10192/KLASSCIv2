@php
    // Même palette monochrome bleu KLASSCI que les cards v1 pour cohérence visuelle
    $etStripePalette = ['#0a3d8f', '#0453cb', '#1e5fc4', '#2d6dc8', '#3b7ddb', '#4a8bd9', '#5e91de', '#7aa8e4'];
@endphp

@forelse($emploisTemps as $emploiTemps)
    @php
        $today = \Carbon\Carbon::today();
        $startDate = $emploiTemps->date_debut ? \Carbon\Carbon::parse($emploiTemps->date_debut) : null;
        $endDate = $emploiTemps->date_fin ? \Carbon\Carbon::parse($emploiTemps->date_fin) : null;
        $isExpired = $endDate && $endDate->lt($today);
        $isUpcoming = $startDate && $startDate->gt($today);
        $isCurrentPeriod = $startDate && $endDate && $today->between($startDate, $endDate);
        $isExpiringSoon = $endDate && $endDate->gte($today) && $endDate->diffInDays($today) <= 3;

        $cardStatus = $isExpired ? 'expired' : ($isCurrentPeriod ? 'active' : 'upcoming');

        $filiereName = $emploiTemps->classe->filiere->name ?? '—';
        $stripeIdx = hexdec(substr(md5($filiereName), 0, 4)) % count($etStripePalette);
        $stripeColor = $etStripePalette[$stripeIdx];

        $seancesCount = $emploiTemps->seances_count ?? ($emploiTemps->seances?->count() ?? 0);
        $totalMinutes = ($emploiTemps->seances ?? collect())->sum(function ($s) {
            if (!$s->heure_debut || !$s->heure_fin) return 0;
            return \Carbon\Carbon::parse($s->heure_debut)->diffInMinutes(\Carbon\Carbon::parse($s->heure_fin));
        });
        $totalHours = intdiv((int) $totalMinutes, 60);

        $searchHaystack = strtolower(trim(
            ($emploiTemps->classe->name ?? '') . ' ' .
            ($emploiTemps->classe->filiere->name ?? '') . ' ' .
            ($emploiTemps->classe->niveau->name ?? '')
        ));

        $canEdit = auth()->user()->hasAnyPermission(['admin.access', 'identity.school_manager']) || auth()->user()->can('timetables.edit');
        $canDelete = auth()->user()->can('admin.access') && auth()->user()->can('timetables.delete');
    @endphp

    <div class="et-row"
         data-status="{{ $cardStatus }}"
         data-search="{{ $searchHaystack }}"
         x-show="(statusFilter === 'all' || statusFilter === '{{ $cardStatus }}')
                 && (searchQuery.trim() === '' || $el.dataset.search.includes(searchQuery.toLowerCase()))"
         x-transition.opacity>

        <span class="et-row__stripe" style="background: {{ $stripeColor }};" aria-hidden="true"></span>

        <div class="et-row__content">
            <div class="et-row__titles">
                <div class="et-row__title">{{ $emploiTemps->classe->name ?? 'Classe inconnue' }}</div>
                <div class="et-row__subtitle">
                    <span>{{ $filiereName }}</span>
                    <span class="et-card__sep">·</span>
                    <span>{{ $emploiTemps->classe->niveau->name ?? 'Niveau inconnu' }}</span>
                </div>
            </div>

            <div class="et-row__meta">
                @if($startDate && $endDate)
                    <span class="et-row__meta-line">
                        <i class="fas fa-calendar-day"></i>
                        {{ $startDate->isoFormat('D MMM') }} → {{ $endDate->isoFormat('D MMM') }}
                    </span>
                @endif
                <span class="et-row__meta-line">
                    <i class="fas fa-layer-group"></i>
                    {{ $seancesCount }} séance{{ $seancesCount > 1 ? 's' : '' }}
                    @if($totalMinutes > 0)
                        · {{ $totalHours }}h
                    @endif
                </span>
            </div>

            <div>
                @if($isExpired)
                    <span class="et-row__badge et-row__badge--danger">
                        <i class="fas fa-circle-exclamation"></i>Expiré
                    </span>
                @elseif($isCurrentPeriod)
                    <span class="et-row__badge et-row__badge--success">
                        <i class="fas fa-circle-check"></i>Actif
                    </span>
                @elseif($isUpcoming)
                    <span class="et-row__badge et-row__badge--info">
                        <i class="fas fa-calendar"></i>À venir
                    </span>
                @endif
                @if($isExpiringSoon && !$isExpired)
                    <span class="et-row__badge et-row__badge--warning">
                        <i class="fas fa-clock"></i>Bientôt
                    </span>
                @endif
            </div>

            <div class="et-row__actions">
                <a href="{{ route('esbtp.emploi-temps.preview-pdf', $emploiTemps->id) }}"
                   class="et-row__btn"
                   target="_blank"
                   title="Aperçu PDF" aria-label="Aperçu PDF">
                    <i class="fas fa-eye"></i>
                </a>
                <a href="{{ route('esbtp.emploi-temps.export-pdf', $emploiTemps->id) }}"
                   class="et-row__btn et-row__btn--pdf"
                   title="Exporter en PDF" aria-label="PDF">
                    <i class="fas fa-file-pdf"></i>
                </a>
                <a href="{{ route('esbtp.emploi-temps.show', $emploiTemps->id) }}"
                   class="et-row__btn et-row__btn--primary"
                   title="Ouvrir">
                    Ouvrir<i class="fas fa-arrow-right"></i>
                </a>
                @if($canEdit || $canDelete)
                    <div class="et-card__menu" x-data="{ open: false }" @click.outside="open = false">
                        <button type="button"
                                class="et-row__btn"
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
    <div class="empty-state-card">
        <div class="text-center py-4">
            <i class="fas fa-bars fa-2x text-muted mb-3"></i>
            <h6 class="text-muted mb-0">Aucun emploi du temps</h6>
        </div>
    </div>
@endforelse
