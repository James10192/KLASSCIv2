@extends('layouts.app')

@section('title', 'Modification rapide des disponibilités')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* ═══════════════════════════════════════════════════════════
   BULK AVAILABILITY — Premium (namespace bav-*)
   Couleurs sémantiques préservées : vert (disponible) / bleu KLASSCI
   (préféré) / rouge (indisponible).
   ═══════════════════════════════════════════════════════════ */

/* -- Hero ----------------------------------------------------- */
.bav-hero {
    position: relative;
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 0 0 22px 22px;
    margin-bottom: 1.75rem;
    overflow: hidden;
}
.bav-hero::before {
    content: '';
    position: absolute; inset: 0;
    background: radial-gradient(circle at 85% 20%, rgba(255,255,255,.12) 0%, transparent 50%);
    pointer-events: none;
}
.bav-hero-inner {
    position: relative; z-index: 2;
    max-width: 1280px; margin: 0 auto;
    padding: 28px 32px 24px;
    display: flex; align-items: center; gap: 18px; flex-wrap: wrap;
}
.bav-hero-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: rgba(255,255,255,.15);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.2);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; color: #fff; flex-shrink: 0;
    box-shadow: 0 4px 16px rgba(0,0,0,.15);
}
.bav-hero-text { flex: 1; min-width: 220px; color: #fff; }
.bav-hero-title { font-size: 1.45rem; font-weight: 800; margin: 0 0 4px; letter-spacing: -.02em; color: #fff; }
.bav-hero-sub { font-size: .85rem; opacity: .8; margin: 0 0 10px; }
.bav-hero-pills { display: flex; gap: 6px; flex-wrap: wrap; }
.bav-hero-pill {
    display: inline-flex; align-items: center; gap: 5px;
    background: rgba(255,255,255,.15);
    backdrop-filter: blur(6px);
    border: 1px solid rgba(255,255,255,.25);
    color: #fff; font-size: .73rem; font-weight: 600;
    padding: 4px 10px; border-radius: 20px; white-space: nowrap;
}
.bav-hero-btns { display: flex; gap: 8px; margin-left: auto; flex-shrink: 0; }
.bav-hero-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; border-radius: 10px; font-size: .82rem; font-weight: 600;
    text-decoration: none; cursor: pointer;
    transition: all .18s ease; white-space: nowrap;
    background: rgba(255,255,255,.15); color: #fff;
    border: 1px solid rgba(255,255,255,.3);
}
.bav-hero-btn:hover { background: rgba(255,255,255,.25); color: #fff; text-decoration: none; }
@media (max-width: 768px) {
    .bav-hero-inner { padding: 20px 16px; flex-direction: column; align-items: flex-start; }
    .bav-hero-btns { margin-left: 0; width: 100%; }
    .bav-hero-btn { flex: 1; justify-content: center; }
}

/* -- Item card (un par enseignant) ---------------------------- */
.bav-item {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    margin-bottom: 1.25rem;
    overflow: hidden;
    transition: box-shadow .25s ease;
}
.bav-item:hover {
    box-shadow: 0 8px 30px rgba(4,83,203,.08), 0 2px 8px rgba(15,23,42,.04);
}
.bav-item.bav-edit-active {
    box-shadow: 0 0 0 2px rgba(4,83,203,.18), 0 8px 30px rgba(4,83,203,.12);
}

/* Header item (bandeau avec avatar + nom + meta + actions) */
.bav-item-header {
    display: flex; align-items: center; gap: 1rem;
    padding: 1.1rem 1.4rem;
    background: linear-gradient(135deg, #f8fafc 0%, #ffffff 60%);
    border-bottom: 1px solid #e2e8f0;
    flex-wrap: wrap;
}
.bav-avatar {
    width: 44px; height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: .92rem; font-weight: 800; letter-spacing: .02em;
    flex-shrink: 0;
    box-shadow: 0 3px 10px rgba(4,83,203,.25);
}
.bav-item-info { flex: 1; min-width: 200px; }
.bav-item-name { font-size: 1.02rem; font-weight: 700; color: #0f172a; margin: 0; letter-spacing: -.01em; }
.bav-item-meta {
    font-size: .78rem; color: #64748b; margin: .15rem 0 0;
    display: flex; gap: .5rem; flex-wrap: wrap; align-items: center;
}
.bav-item-meta-sep { color: #cbd5e1; }
.bav-item-actions { display: flex; align-items: center; gap: .5rem; flex-shrink: 0; }

.bav-status-pill {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 20px;
    font-size: .72rem; font-weight: 700;
    letter-spacing: .02em;
}
.bav-status-pill.is-active { background: rgba(16,185,129,.12); color: #047857; border: 1px solid rgba(16,185,129,.25); }
.bav-status-pill.is-inactive { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }
.bav-status-pill .dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

.bav-btn-link {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 6px 12px; border-radius: 8px;
    font-size: .78rem; font-weight: 600;
    color: #0453cb; background: #fff;
    border: 1px solid #cbd5e1;
    text-decoration: none;
    transition: all .15s ease;
}
.bav-btn-link:hover { background: #0453cb; color: #fff; border-color: #0453cb; text-decoration: none; }

.bav-toggle-chevron {
    display: inline-flex; align-items: center; justify-content: center;
    width: 32px; height: 32px;
    border-radius: 8px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #64748b;
    cursor: pointer;
    transition: all .2s ease;
}
.bav-toggle-chevron:hover { background: #0453cb; color: #fff; border-color: #0453cb; }
.bav-toggle-chevron i { transition: transform .25s ease; }
.bav-toggle-chevron[aria-expanded="true"] i { transform: rotate(180deg); }

/* -- Body item (stats + actions + grille + légende) ----------- */
.bav-item-body { padding: 1.25rem 1.4rem 1.5rem; }

/* Stats premium */
.bav-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: .75rem;
    margin-bottom: 1.1rem;
}
.bav-stat {
    display: flex; align-items: center; gap: .75rem;
    padding: .85rem 1rem;
    border-radius: 12px;
    border: 1px solid;
    transition: transform .15s ease;
}
.bav-stat:hover { transform: translateY(-1px); }
.bav-stat-icon {
    width: 38px; height: 38px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; flex-shrink: 0;
}
.bav-stat-content { flex: 1; min-width: 0; }
.bav-stat-value { font-size: 1.4rem; font-weight: 800; line-height: 1; letter-spacing: -.02em; }
.bav-stat-label { font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; opacity: .85; margin-top: .15rem; }

.bav-stat--preferred {
    background: linear-gradient(135deg, rgba(4,83,203,.04) 0%, rgba(59,125,219,.06) 100%);
    border-color: rgba(4,83,203,.18);
    color: #1d4ed8;
}
.bav-stat--preferred .bav-stat-icon { background: rgba(4,83,203,.12); color: #1d4ed8; }

.bav-stat--available {
    background: linear-gradient(135deg, rgba(16,185,129,.04) 0%, rgba(34,197,94,.06) 100%);
    border-color: rgba(16,185,129,.2);
    color: #047857;
}
.bav-stat--available .bav-stat-icon { background: rgba(16,185,129,.14); color: #059669; }

.bav-stat--unavailable {
    background: linear-gradient(135deg, rgba(220,38,38,.04) 0%, rgba(239,68,68,.06) 100%);
    border-color: rgba(220,38,38,.18);
    color: #991b1b;
}
.bav-stat--unavailable .bav-stat-icon { background: rgba(220,38,38,.12); color: #b91c1c; }

/* Actions premium */
.bav-actions {
    display: flex; gap: .5rem; flex-wrap: wrap;
    margin-bottom: 1.1rem;
}
.bav-btn {
    display: inline-flex; align-items: center; gap: .5rem;
    padding: .55rem 1.05rem;
    border-radius: 10px;
    font-size: .85rem; font-weight: 600;
    border: none; cursor: pointer;
    transition: all .18s ease;
    text-decoration: none; white-space: nowrap;
}
.bav-btn--edit {
    background: linear-gradient(135deg, #0453cb 0%, #3b7ddb 100%);
    color: #fff;
    box-shadow: 0 2px 6px rgba(4,83,203,.25);
}
.bav-btn--edit:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(4,83,203,.35); color: #fff; }

.bav-btn--save {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #fff;
    box-shadow: 0 2px 6px rgba(16,185,129,.3);
}
.bav-btn--save:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(16,185,129,.4); color: #fff; }

.bav-btn--cancel {
    background: #fff;
    color: #64748b;
    border: 1px solid #cbd5e1;
}
.bav-btn--cancel:hover { background: #fee2e2; color: #b91c1c; border-color: #fecaca; }

.bav-edit-banner {
    display: none;
    align-items: center; gap: .55rem;
    padding: .55rem .9rem;
    margin-bottom: 1rem;
    border-radius: 10px;
    background: linear-gradient(135deg, rgba(245,158,11,.08), rgba(217,119,6,.06));
    border: 1px solid rgba(245,158,11,.25);
    color: #92400e;
    font-size: .82rem; font-weight: 500;
}
.bav-item.bav-edit-active .bav-edit-banner { display: flex; }
.bav-edit-banner i { color: #d97706; }

/* -- Grille calendrier premium ------------------------------- */
.bav-grid {
    display: grid;
    grid-template-columns: 76px repeat(7, 1fr);
    gap: 0;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(15,23,42,.03);
}

.bav-grid-time-header,
.bav-grid-day-header {
    padding: .65rem .35rem;
    text-align: center;
    font-size: .7rem; font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    background: #f8fafc;
    border-bottom: 2px solid #e2e8f0;
    color: #475569;
}
.bav-grid-time-header { color: #94a3b8; border-right: 2px solid #e2e8f0; }
.bav-grid-day-header { border-right: 1px solid #f1f5f9; color: #1d4ed8; }
.bav-grid-day-header:last-of-type { border-right: none; }

.bav-grid-time {
    padding: 0 .4rem;
    font-size: .76rem; font-weight: 600; color: #64748b;
    text-align: center;
    background: #f8fafc;
    border-right: 2px solid #e2e8f0;
    border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; justify-content: center;
    min-height: 42px;
}

.bav-grid-cell {
    min-height: 42px;
    padding: 4px 2px;
    display: flex; align-items: center; justify-content: center;
    gap: 4px;
    font-size: .72rem; font-weight: 600;
    border-bottom: 1px solid #f1f5f9;
    border-right: 1px solid #f1f5f9;
    transition: all .15s ease;
    user-select: none;
    position: relative;
}
.bav-grid-cell:nth-child(8n) { border-right: none; }

/* Statuts (couleurs sémantiques préservées) */
.bav-grid-cell.preferred {
    background: linear-gradient(135deg, #dbeafe 0%, #eff6ff 100%);
    color: #1d4ed8;
}
.bav-grid-cell.preferred i { color: #2563eb; }
.bav-grid-cell.available {
    background: linear-gradient(135deg, #dcfce7 0%, #f0fdf4 100%);
    color: #15803d;
}
.bav-grid-cell.available i { color: #16a34a; }
.bav-grid-cell.unavailable {
    background: linear-gradient(135deg, #fee2e2 0%, #fef2f2 100%);
    color: #991b1b;
}
.bav-grid-cell.unavailable i { color: #dc2626; opacity: .55; }

/* Hover seulement en mode edit */
.bav-item.bav-edit-active .bav-grid-cell {
    cursor: pointer;
}
.bav-item.bav-edit-active .bav-grid-cell:hover {
    transform: scale(1.05);
    box-shadow: inset 0 0 0 2px #0453cb;
    z-index: 2;
}

/* Cellule modifiée */
.bav-grid-cell.modified {
    box-shadow: inset 0 0 0 2px #f59e0b;
}
.bav-grid-cell.modified::after {
    content: '';
    position: absolute;
    top: 4px; right: 4px;
    width: 7px; height: 7px;
    background: #f59e0b;
    border-radius: 50%;
    box-shadow: 0 0 0 2px #fff;
}

.bav-slot-label { font-size: .68rem; }

/* -- Légende premium ----------------------------------------- */
.bav-legend {
    display: flex; gap: .6rem; flex-wrap: wrap;
    margin-top: 1rem;
    padding: .8rem 1rem;
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}
.bav-legend-item {
    display: inline-flex; align-items: center; gap: .5rem;
    font-size: .78rem; font-weight: 600;
    padding: .25rem .5rem;
}
.bav-legend-swatch {
    width: 22px; height: 22px;
    border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    font-size: .68rem;
    flex-shrink: 0;
}
.bav-legend-swatch.preferred {
    background: linear-gradient(135deg, #dbeafe, #eff6ff);
    color: #2563eb;
    border: 1px solid #bfdbfe;
}
.bav-legend-swatch.available {
    background: linear-gradient(135deg, #dcfce7, #f0fdf4);
    color: #16a34a;
    border: 1px solid #bbf7d0;
}
.bav-legend-swatch.unavailable {
    background: linear-gradient(135deg, #fee2e2, #fef2f2);
    color: #dc2626;
    border: 1px solid #fecaca;
}
.bav-legend-item span { color: #475569; }

/* -- Empty state ---------------------------------------------- */
.bav-empty {
    background: #fff;
    border: 1.5px dashed #cbd5e1;
    border-radius: 16px;
    padding: 3rem 1.5rem;
    text-align: center;
}
.bav-empty-icon {
    width: 64px; height: 64px;
    margin: 0 auto 1rem;
    border-radius: 50%;
    background: #f8fafc;
    color: #94a3b8;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem;
}
.bav-empty h3 { font-size: 1.1rem; font-weight: 700; color: #0f172a; margin: 0 0 .35rem; }
.bav-empty p { font-size: .88rem; color: #64748b; margin: 0; }

/* -- Toast notif --------------------------------------------- */
.bav-toast {
    position: fixed;
    top: 20px; right: 20px;
    padding: .75rem 1.1rem;
    border-radius: 12px;
    color: #fff; font-weight: 600; font-size: .88rem;
    box-shadow: 0 10px 30px rgba(0,0,0,.18);
    z-index: 9999;
    transform: translateX(120%);
    transition: transform .3s ease;
    display: flex; align-items: center; gap: .55rem;
}
.bav-toast.show { transform: translateX(0); }
.bav-toast.success { background: linear-gradient(135deg, #10b981, #059669); }
.bav-toast.danger { background: linear-gradient(135deg, #dc2626, #b91c1c); }
.bav-toast.warning { background: linear-gradient(135deg, #f59e0b, #d97706); }
.bav-toast.info { background: linear-gradient(135deg, #0453cb, #3b7ddb); }

/* -- Responsive ---------------------------------------------- */
@media (max-width: 768px) {
    .bav-grid { grid-template-columns: 56px repeat(7, 1fr); }
    .bav-grid-day-header { font-size: .62rem; padding: .45rem .15rem; }
    .bav-grid-time { font-size: .68rem; }
    .bav-grid-cell { min-height: 34px; font-size: .58rem; }
    .bav-grid-cell .bav-slot-label { display: none; }
    .bav-stats { grid-template-columns: 1fr 1fr; }
    .bav-stat-value { font-size: 1.2rem; }
    .bav-item-body { padding: 1rem; }
}
@media (max-width: 480px) {
    .bav-grid { grid-template-columns: 44px repeat(7, 1fr); }
    .bav-grid-cell { min-height: 30px; }
    .bav-stats { grid-template-columns: 1fr; }
}
</style>
@endsection

@section('content')
<div class="bav-hero">
    <div class="bav-hero-inner">
        <div class="bav-hero-icon"><i class="fas fa-calendar-check"></i></div>
        <div class="bav-hero-text">
            <h1 class="bav-hero-title">Disponibilités — modification rapide</h1>
            <p class="bav-hero-sub"><i class="fas fa-info-circle" style="margin-right:4px;"></i> Modifiez les créneaux de plusieurs enseignants depuis une seule page.</p>
            <div class="bav-hero-pills">
                <span class="bav-hero-pill"><i class="fas fa-users"></i> {{ $enseignants->count() }} enseignant{{ $enseignants->count() > 1 ? 's' : '' }}</span>
                <span class="bav-hero-pill"><i class="fas fa-mouse-pointer"></i> Cliquez sur un créneau pour cycler les statuts</span>
            </div>
        </div>
        <div class="bav-hero-btns">
            <a href="{{ route('esbtp.enseignants.index') }}" class="bav-hero-btn">
                <i class="fas fa-arrow-left"></i> Retour à la liste
            </a>
        </div>
    </div>
</div>

<div class="dashboard-acasi">
    <div class="main-content">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @forelse($enseignantsData as $data)
            @php
                $enseignant = $data['enseignant'];
                $collapseId = 'bav-collapse-' . $enseignant->id;
                $name = $enseignant->user->name ?? ('Enseignant #' . $enseignant->id);
                $initials = collect(explode(' ', trim($name)))
                    ->filter()
                    ->take(2)
                    ->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))
                    ->implode('');
                $isActive = ($enseignant->status === 'active');
            @endphp
            <div class="bav-item" id="bav-item-{{ $enseignant->id }}" data-enseignant-id="{{ $enseignant->id }}">
                <div class="bav-item-header">
                    <div class="bav-avatar">{{ $initials ?: 'NN' }}</div>
                    <div class="bav-item-info">
                        <h3 class="bav-item-name">{{ $name }}</h3>
                        <div class="bav-item-meta">
                            <span><i class="fas fa-star" style="color:#f59e0b;font-size:.7rem"></i> {{ $enseignant->specialization ?? 'Spécialisation non définie' }}</span>
                            <span class="bav-item-meta-sep">·</span>
                            <span><i class="fas fa-id-badge" style="color:#94a3b8;font-size:.7rem"></i> {{ $enseignant->matricule ?? 'N/A' }}</span>
                        </div>
                    </div>
                    <div class="bav-item-actions">
                        <span class="bav-status-pill {{ $isActive ? 'is-active' : 'is-inactive' }}">
                            <span class="dot"></span> {{ $isActive ? 'Actif' : 'Inactif' }}
                        </span>
                        <a href="{{ route('esbtp.enseignants.show', ['enseignant' => $enseignant->id]) }}"
                           class="bav-btn-link" target="_blank" rel="noopener">
                            <i class="fas fa-external-link-alt"></i> Voir
                        </a>
                        <button class="bav-toggle-chevron"
                                type="button" data-bs-toggle="collapse"
                                data-bs-target="#{{ $collapseId }}"
                                aria-expanded="true" aria-controls="{{ $collapseId }}">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                </div>

                <div id="{{ $collapseId }}" class="collapse show">
                    <div class="bav-item-body">
                        @include('esbtp.enseignants.partials.availability-block', $data)
                    </div>
                </div>
            </div>
        @empty
            <div class="bav-empty">
                <div class="bav-empty-icon"><i class="fas fa-users-slash"></i></div>
                <h3>Aucun enseignant sélectionné</h3>
                <p>Retournez à la liste et sélectionnez les enseignants à modifier.</p>
            </div>
        @endforelse
    </div>
</div>

<script>
function showNotification(message, type = 'info') {
    const t = document.createElement('div');
    t.className = 'bav-toast ' + type;
    const iconMap = { success: 'fa-check-circle', danger: 'fa-exclamation-circle', warning: 'fa-exclamation-triangle', info: 'fa-info-circle' };
    t.innerHTML = '<i class="fas ' + (iconMap[type] || 'fa-info-circle') + '"></i><span>' + message + '</span>';
    document.body.appendChild(t);
    requestAnimationFrame(() => t.classList.add('show'));
    setTimeout(() => {
        t.classList.remove('show');
        setTimeout(() => t.remove(), 300);
    }, 3500);
}

async function refreshBlock(enseignantId) {
    try {
        const response = await fetch(`{{ url('/esbtp/enseignants') }}/${enseignantId}/availability-section`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!response.ok) throw new Error('Erreur lors du rafraîchissement');
        const payload = await response.json();
        const container = document.querySelector(`#bav-item-${enseignantId} .bav-item-body`);
        if (container && payload.html) container.innerHTML = payload.html;
    } catch (error) {
        console.error('Erreur refresh:', error);
    }
}
</script>
@endsection
