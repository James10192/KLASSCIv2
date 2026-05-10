@extends('layouts.app')

@section('title', $niveauxEtude->name)

@push('styles')
<style>
.ne-hero { background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%); border-radius: 18px; padding: 2rem 2.5rem 1.75rem; color: #fff; margin-bottom: 1.25rem; box-shadow: 0 8px 30px rgba(4,83,203,.18); }
.ne-hero-top { display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:1rem; }
.ne-hero-left { display:flex; align-items:center; gap:1rem; }
.ne-hero-avatar { width:56px; height:56px; border-radius:14px; background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.22); display:flex; align-items:center; justify-content:center; font-size:1.1rem; font-weight:700; flex-shrink:0; color:#fff; backdrop-filter: blur(6px); }
.ne-hero h1 { font-size:1.45rem; font-weight:700; color:#fff; margin:0 0 .25rem; }
.ne-hero p { color:rgba(255,255,255,.78); font-size:.88rem; margin:0; }
.ne-hero-chips { display:flex; flex-wrap:wrap; gap:.45rem; margin-top:.55rem; }
.ne-hero-chip { display:inline-flex; align-items:center; gap:.35rem; padding:.22rem .65rem; border-radius:20px; font-size:.74rem; font-weight:600; background:rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.2); color:#fff; }
.ne-hero-chip.ko { background:rgba(254,202,202,.2); border-color:rgba(254,202,202,.4); color:#fee2e2; }
.ne-hero-chip.warn { background:rgba(254,215,170,.22); border-color:rgba(254,215,170,.45); color:#ffedd5; }
.ne-hero-actions { display:flex; gap:.55rem; flex-wrap:wrap; }
.ne-btn-white { background:#fff; color:#0453cb; border:none; border-radius:10px; padding:.55rem 1.05rem; font-size:.84rem; font-weight:600; display:inline-flex; align-items:center; gap:.4rem; text-decoration:none; transition:all .2s; box-shadow:0 2px 8px rgba(0,0,0,.12); }
.ne-btn-white:hover { background:#eff6ff; color:#0453cb; transform:translateY(-1px); }
.ne-btn-glass { background:rgba(255,255,255,.15); color:#fff; border:1px solid rgba(255,255,255,.22); border-radius:10px; padding:.55rem 1.05rem; font-size:.84rem; font-weight:600; display:inline-flex; align-items:center; gap:.4rem; text-decoration:none; transition:all .2s; }
.ne-btn-glass:hover { background:rgba(255,255,255,.22); color:#fff; }
.ne-kpis { display:flex; gap:.75rem; margin-top:1.5rem; flex-wrap:wrap; }
.ne-kpi { flex:1; min-width:130px; background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.15); border-radius:12px; padding:.9rem 1rem; display:flex; align-items:center; gap:.75rem; }
.ne-kpi-icon { width:36px; height:36px; border-radius:10px; background:rgba(255,255,255,.15); display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:.9rem; color:#fff; }
.ne-kpi-value { font-size:1.35rem; font-weight:700; color:#fff; line-height:1.1; }
.ne-kpi-label { font-size:.72rem; color:rgba(255,255,255,.65); margin-top:.15rem; }
.ne-grid { display:grid; grid-template-columns:1fr 1fr; gap:1.25rem; }
@@media (max-width: 992px) {
    .ne-grid { grid-template-columns:1fr; }
    .ne-hero { padding:1.5rem 1.25rem 1.25rem; }
    .ne-hero h1 { font-size:1.2rem; }
}
.ne-card { background:#fff; border:1px solid #e2e8f0; border-radius:14px; box-shadow:0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); overflow:hidden; }
.ne-card-head { padding:1rem 1.25rem .85rem; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; gap:.65rem; }
.ne-card-icon { width:36px; height:36px; border-radius:10px; background:linear-gradient(135deg, #0453cb, #3b7ddb); color:#fff; display:flex; align-items:center; justify-content:center; font-size:.88rem; flex-shrink:0; }
.ne-card-head h5 { margin:0; font-size:.95rem; font-weight:700; color:#1e293b; }
.ne-card-head p { margin:.05rem 0 0; font-size:.76rem; color:#64748b; }
.ne-info-row { display:flex; padding:.65rem 1.25rem; border-bottom:1px solid #f8fafc; align-items:center; gap:1rem; }
.ne-info-row:last-child { border-bottom:none; }
.ne-info-label { width:42%; font-size:.78rem; color:#64748b; font-weight:500; display:flex; align-items:center; gap:.4rem; }
.ne-info-label i { color:#94a3b8; font-size:.74rem; width:14px; }
.ne-info-value { flex:1; font-size:.86rem; color:#1e293b; font-weight:600; }
.ne-info-value.muted { color:#94a3b8; font-weight:400; font-style:italic; }
.ne-status-on { display:inline-flex; align-items:center; gap:.3rem; padding:.18rem .6rem; border-radius:20px; font-size:.72rem; font-weight:600; background:#d1fae5; color:#065f46; }
.ne-status-off { display:inline-flex; align-items:center; gap:.3rem; padding:.18rem .6rem; border-radius:20px; font-size:.72rem; font-weight:600; background:#fee2e2; color:#991b1b; }
.ne-classes-list { padding:.5rem 0; }
.ne-class-row { display:flex; align-items:center; gap:.75rem; padding:.65rem 1.25rem; border-bottom:1px solid #f8fafc; transition:background .15s; }
.ne-class-row:last-child { border-bottom:none; }
.ne-class-row:hover { background:#f8fafc; }
.ne-class-avatar { width:34px; height:34px; border-radius:9px; background:linear-gradient(135deg, #0453cb, #3b7ddb); color:#fff; display:flex; align-items:center; justify-content:center; font-size:.78rem; font-weight:700; flex-shrink:0; }
.ne-class-info { flex:1; min-width:0; }
.ne-class-name { font-size:.85rem; font-weight:600; color:#1e293b; }
.ne-class-meta { font-size:.74rem; color:#64748b; margin-top:.1rem; }
.ne-class-places { font-size:.74rem; color:#64748b; white-space:nowrap; }
.ne-class-places strong { color:#0453cb; font-size:.85rem; }
.ne-empty-soft { padding:2rem 1.25rem; text-align:center; color:#94a3b8; font-size:.85rem; }
.ne-empty-soft i { font-size:1.85rem; color:#cbd5e1; display:block; margin-bottom:.5rem; }
.ne-card-footer-link { padding:.7rem 1.25rem; background:#f8fafc; border-top:1px solid #f1f5f9; text-align:center; font-size:.8rem; color:#0453cb; font-weight:600; text-decoration:none; display:block; transition:background .15s; }
.ne-card-footer-link:hover { background:#eff6ff; color:#0453cb; }
.ne-desc-body { padding:1.1rem 1.25rem; font-size:.88rem; color:#475569; line-height:1.55; }
.ne-bottom-actions { display:flex; justify-content:space-between; gap:.6rem; padding:1rem 0 0; flex-wrap:wrap; }
.ne-bottom-right { display:flex; gap:.6rem; flex-wrap:wrap; }
</style>
@endpush

@section('content')
<div class="main-content">

@php
    $nType = $niveauxEtude->type;
    $lmdTypes = ['Licence', 'Master', 'Doctorat', 'Bachelor'];
    if (!$nType) {
        $chipCls = 'warn'; $chipIco = 'fa-exclamation-triangle'; $chipLbl = 'Type non défini';
    } elseif (in_array($nType, $lmdTypes)) {
        $chipCls = ''; $chipIco = 'fa-graduation-cap'; $chipLbl = $nType;
    } elseif ($nType === 'BTS') {
        $chipCls = ''; $chipIco = 'fa-briefcase'; $chipLbl = 'BTS';
    } else {
        $chipCls = ''; $chipIco = 'fa-tag'; $chipLbl = $nType;
    }
    $initials = mb_strtoupper(mb_substr($niveauxEtude->code ?: $niveauxEtude->name, 0, 2, 'UTF-8'), 'UTF-8');
    $classesCount  = $niveauxEtude->classes ? $niveauxEtude->classes->count() : 0;
    $filieresCount = $niveauxEtude->filieres ? $niveauxEtude->filieres->count() : 0;
    $matieresCount = $niveauxEtude->matieres ? $niveauxEtude->matieres->count() : 0;
    $classesAffichees = $niveauxEtude->classes ? $niveauxEtude->classes->take(6) : collect();
    $classesAutres    = max($classesCount - $classesAffichees->count(), 0);
@endphp

<div class="ne-hero">
    <div class="ne-hero-top">
        <div class="ne-hero-left">
            <div class="ne-hero-avatar">{{ $initials }}</div>
            <div>
                <h1>{{ $niveauxEtude->name }}</h1>
                <p>{{ $niveauxEtude->libelle ?: $niveauxEtude->code }}</p>
                <div class="ne-hero-chips">
                    <span class="ne-hero-chip {{ $chipCls }}"><i class="fas {{ $chipIco }}" style="font-size:.66rem;"></i> {{ $chipLbl }}</span>
                    @if($niveauxEtude->year)
                    <span class="ne-hero-chip"><i class="fas fa-graduation-cap" style="font-size:.66rem;"></i> Année {{ $niveauxEtude->year }}</span>
                    @endif
                    @if($niveauxEtude->is_active)
                    <span class="ne-hero-chip"><i class="fas fa-circle" style="font-size:.5rem;"></i> Actif</span>
                    @else
                    <span class="ne-hero-chip ko"><i class="fas fa-circle" style="font-size:.5rem;"></i> Inactif</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="ne-hero-actions">
            <a href="{{ route('esbtp.niveaux-etudes.edit', $niveauxEtude) }}" class="ne-btn-white">
                <i class="fas fa-edit"></i> Modifier
            </a>
            <a href="{{ route('esbtp.niveaux-etudes.index') }}" class="ne-btn-glass">
                <i class="fas fa-list"></i> Liste
            </a>
        </div>
    </div>
    <div class="ne-kpis">
        <div class="ne-kpi">
            <div class="ne-kpi-icon"><i class="fas fa-sitemap"></i></div>
            <div><div class="ne-kpi-value">{{ $filieresCount }}</div><div class="ne-kpi-label">Filière(s)</div></div>
        </div>
        <div class="ne-kpi">
            <div class="ne-kpi-icon"><i class="fas fa-chalkboard"></i></div>
            <div><div class="ne-kpi-value">{{ $classesCount }}</div><div class="ne-kpi-label">Classe(s)</div></div>
        </div>
        <div class="ne-kpi">
            <div class="ne-kpi-icon"><i class="fas fa-book"></i></div>
            <div><div class="ne-kpi-value">{{ $matieresCount }}</div><div class="ne-kpi-label">Matière(s)</div></div>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-dismissible fade show mb-3" style="background:rgba(16,185,129,.1); border:1px solid #10b981; border-radius:12px; padding:.85rem 1rem;" role="alert">
    <i class="fas fa-check-circle" style="color:#10b981;"></i>
    <span style="color:#065f46; font-weight:600; margin-left:.4rem;">{{ session('success') }}</span>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-dismissible fade show mb-3" style="background:rgba(220,38,38,.08); border:1px solid #dc2626; border-radius:12px; padding:.85rem 1rem;" role="alert">
    <i class="fas fa-exclamation-circle" style="color:#dc2626;"></i>
    <span style="color:#991b1b; font-weight:600; margin-left:.4rem;">{{ session('error') }}</span>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="ne-grid">
    {{-- Card 1 : Informations générales --}}
    <div class="ne-card">
        <div class="ne-card-head">
            <div class="ne-card-icon"><i class="fas fa-info-circle"></i></div>
            <div>
                <h5>Informations générales</h5>
                <p>Identité et classification du niveau</p>
            </div>
        </div>
        <div class="ne-info-row">
            <div class="ne-info-label"><i class="fas fa-tag"></i> Nom</div>
            <div class="ne-info-value">{{ $niveauxEtude->name }}</div>
        </div>
        <div class="ne-info-row">
            <div class="ne-info-label"><i class="fas fa-hashtag"></i> Code</div>
            <div class="ne-info-value">{{ $niveauxEtude->code }}</div>
        </div>
        <div class="ne-info-row">
            <div class="ne-info-label"><i class="fas fa-tag"></i> Type de formation</div>
            <div class="ne-info-value @if(!$niveauxEtude->type) muted @endif">{{ $niveauxEtude->type ?: 'Non défini' }}</div>
        </div>
        <div class="ne-info-row">
            <div class="ne-info-label"><i class="fas fa-graduation-cap"></i> Année dans le cycle</div>
            <div class="ne-info-value @if(!$niveauxEtude->year) muted @endif">{{ $niveauxEtude->year ? 'Année ' . $niveauxEtude->year : '—' }}</div>
        </div>
        <div class="ne-info-row">
            <div class="ne-info-label"><i class="fas fa-bookmark"></i> Libellé court</div>
            <div class="ne-info-value @if(!$niveauxEtude->libelle) muted @endif">{{ $niveauxEtude->libelle ?: '—' }}</div>
        </div>
        <div class="ne-info-row">
            <div class="ne-info-label"><i class="fas fa-toggle-on"></i> Statut</div>
            <div class="ne-info-value">
                @if($niveauxEtude->is_active)
                <span class="ne-status-on"><i class="fas fa-circle" style="font-size:.5rem;"></i> Actif</span>
                @else
                <span class="ne-status-off"><i class="fas fa-circle" style="font-size:.5rem;"></i> Inactif</span>
                @endif
            </div>
        </div>
        <div class="ne-info-row">
            <div class="ne-info-label"><i class="fas fa-calendar-plus"></i> Créé le</div>
            <div class="ne-info-value">{{ optional($niveauxEtude->created_at)->format('d/m/Y à H:i') ?: '—' }}</div>
        </div>
        <div class="ne-info-row">
            <div class="ne-info-label"><i class="fas fa-history"></i> Modifié le</div>
            <div class="ne-info-value">{{ optional($niveauxEtude->updated_at)->format('d/m/Y à H:i') ?: '—' }}</div>
        </div>
        @if($niveauxEtude->description)
        <div class="ne-desc-body" style="border-top:1px solid #f1f5f9;">
            <strong style="color:#1e293b; font-size:.82rem; display:block; margin-bottom:.35rem;"><i class="fas fa-align-left" style="color:#0453cb; margin-right:.35rem;"></i>Description</strong>
            {{ $niveauxEtude->description }}
        </div>
        @endif
    </div>

    {{-- Card 2 : Classes associées --}}
    <div class="ne-card">
        <div class="ne-card-head">
            <div class="ne-card-icon"><i class="fas fa-chalkboard"></i></div>
            <div>
                <h5>Classes associées</h5>
                <p>{{ $classesCount }} classe(s) à ce niveau</p>
            </div>
        </div>
        @if($classesAffichees->isEmpty())
        <div class="ne-empty-soft">
            <i class="fas fa-chalkboard"></i>
            Aucune classe associée à ce niveau pour le moment.
        </div>
        @else
        <div class="ne-classes-list">
            @foreach($classesAffichees as $classe)
            @php
                $cInitials = mb_strtoupper(mb_substr($classe->code ?: $classe->name, 0, 2, 'UTF-8'), 'UTF-8');
                $places = $classe->capacite ?? null;
                $occupes = $classe->relationLoaded('etudiants') ? $classe->etudiants->count() : 0;
            @endphp
            <div class="ne-class-row">
                <div class="ne-class-avatar">{{ $cInitials }}</div>
                <div class="ne-class-info">
                    <div class="ne-class-name">{{ $classe->name }}</div>
                    <div class="ne-class-meta">
                        {{ $classe->code }}
                        @if($classe->relationLoaded('filiere') && $classe->filiere)
                        · {{ $classe->filiere->name }}
                        @endif
                    </div>
                </div>
                @if($places)
                <div class="ne-class-places"><strong>{{ $occupes }}</strong> / {{ $places }} places</div>
                @elseif($occupes > 0)
                <div class="ne-class-places"><strong>{{ $occupes }}</strong> étudiant(s)</div>
                @endif
            </div>
            @endforeach
        </div>
        @if($classesAutres > 0)
        <a href="{{ route('esbtp.classes.index') }}?niveau_etude_id={{ $niveauxEtude->id }}" class="ne-card-footer-link">
            <i class="fas fa-arrow-right"></i> Voir les {{ $classesAutres }} autre(s) classe(s)
        </a>
        @endif
        @endif
    </div>
</div>

<div class="ne-bottom-actions">
    <a href="{{ route('esbtp.niveaux-etudes.index') }}" class="btn-acasi secondary">
        <i class="fas fa-arrow-left"></i> Retour à la liste
    </a>
    <div class="ne-bottom-right">
        <a href="{{ route('esbtp.niveaux-etudes.edit', $niveauxEtude) }}" class="btn-acasi primary">
            <i class="fas fa-edit"></i> Modifier
        </a>
        @if($classesCount === 0 && $filieresCount === 0 && $matieresCount === 0)
        <button type="button" class="btn-acasi danger" data-bs-toggle="modal" data-bs-target="#ne-delete-modal-show">
            <i class="fas fa-trash"></i> Supprimer
        </button>
        @endif
    </div>
</div>

@if($classesCount === 0 && $filieresCount === 0 && $matieresCount === 0)
{{-- Modal suppression --}}
<div class="modal fade" id="ne-delete-modal-show" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px; border:none; overflow:hidden;">
            <div class="modal-header" style="background:linear-gradient(135deg, #dc2626, #ef4444); color:#fff; border:none; padding:1.1rem 1.5rem;">
                <h5 class="modal-title" style="font-weight:700; display:flex; align-items:center; gap:.55rem;">
                    <i class="fas fa-exclamation-triangle"></i> Confirmer la suppression
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:1.25rem 1.5rem;">
                <p style="margin:0 0 .5rem; color:#1e293b;">Êtes-vous sûr de vouloir supprimer le niveau <strong>{{ $niveauxEtude->name }}</strong> ?</p>
                <p style="margin:0; color:#64748b; font-size:.85rem;">Cette action est irréversible.</p>
            </div>
            <div class="modal-footer" style="background:#f8fafc; border-top:1px solid #f1f5f9; padding:.85rem 1.5rem;">
                <button type="button" class="btn-acasi secondary" data-bs-dismiss="modal">Annuler</button>
                <form method="POST" action="{{ route('esbtp.niveaux-etudes.destroy', $niveauxEtude) }}" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-acasi danger">
                        <i class="fas fa-trash"></i> Supprimer définitivement
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

</div>
@endsection
