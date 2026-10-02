@extends('layouts.app')

@php
    use Illuminate\Support\Str;
    // $roleLabels, $roleDescriptions, $roleIcons et $managementMatrix viennent du registre.
    $selectedPermissions = $rolePermissions[$selectedRoleName] ?? collect();
    // Un rôle porte aussi les anciens noms ; on ne compte que ce qui a une case.
    $selectedVisibleCount = $permissions->pluck('name')->intersect($selectedPermissions)->count();
    $roleInfos = $roles->mapWithKeys(fn ($r) => [$r->name => [
        'label' => $roleLabels[$r->name] ?? $r->name,
        'description' => $roleDescriptions[$r->name] ?? '',
        'icon' => $roleIcons[$r->name] ?? 'fa-user',
        'gere' => collect($managementMatrix[$r->name] ?? [])->map(fn ($n) => $roleLabels[$n] ?? $n)->values()->all(),
    ]])->all();
    $selectedInfo = $roleInfos[$selectedRoleName] ?? ['label' => $selectedRoleName, 'description' => '', 'icon' => 'fa-user', 'gere' => []];
@endphp

@section('title', 'Rôles et permissions')

@push('styles')
<style>
.rp-page { --rp-p:#0453cb; --rp-pd:#033a8e; --rp-txt:#1e293b; --rp-muted:#64748b; --rp-line:#e2e8f0; }

/* ── Hero ── */
.rp-hero { background:linear-gradient(135deg,#0a3d8f 0%,#0453cb 40%,#3b7ddb 100%); border-radius:18px; padding:2rem 2.5rem 1.5rem; color:#fff; margin-bottom:1.25rem; }
.rp-hero-top { display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:1rem; }
.rp-hero-left { display:flex; align-items:center; gap:1rem; min-width:0; }
.rp-hero-icon { width:52px; height:52px; border-radius:14px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.15); display:flex; align-items:center; justify-content:center; font-size:1.35rem; flex-shrink:0; }
.rp-hero h1 { font-size:1.45rem; font-weight:700; color:#fff; margin:0; }
.rp-hero p { color:rgba(255,255,255,.75); font-size:.88rem; margin:.15rem 0 0; }
.rp-hero-actions { display:flex; gap:.5rem; flex-wrap:wrap; }
.rp-btn-glass { display:inline-flex; align-items:center; gap:.45rem; background:rgba(255,255,255,.15); color:#fff; border:1px solid rgba(255,255,255,.22); border-radius:10px; padding:.5rem 1rem; font-size:.82rem; font-weight:600; text-decoration:none; cursor:pointer; transition:background .2s ease; white-space:nowrap; }
.rp-btn-glass:hover { background:rgba(255,255,255,.25); color:#fff; }
.rp-btn-glass:disabled { opacity:.6; cursor:wait; }
.rp-kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:.75rem; margin-top:1.5rem; }
.rp-kpi { background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.15); border-radius:12px; padding:.9rem 1rem; min-width:0; }
.rp-kpi-value { font-size:1.35rem; font-weight:700; color:#fff; white-space:nowrap; }
.rp-kpi-value small { font-size:.8rem; font-weight:600; color:rgba(255,255,255,.7); }
.rp-kpi-label { font-size:.72rem; color:rgba(255,255,255,.7); margin-top:.15rem; text-transform:uppercase; letter-spacing:.4px; }

/* ── Cartes ── */
.rp-card { background:#fff; border:1px solid var(--rp-line); border-radius:14px; box-shadow:0 1px 3px rgba(15,23,42,.04),0 1px 2px rgba(15,23,42,.06); padding:1.25rem 1.5rem; margin-bottom:1.25rem; }
.rp-card-head { display:flex; align-items:center; gap:.75rem; margin-bottom:1rem; }
.rp-card-icon { width:40px; height:40px; border-radius:10px; background:linear-gradient(135deg,#0453cb,#3b7ddb); color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.rp-card-title { font-weight:700; color:var(--rp-txt); font-size:1rem; margin:0; }
.rp-card-sub { color:var(--rp-muted); font-size:.82rem; margin:0; }

/* ── Choix du rôle ── */
.rp-role-groups { display:flex; flex-direction:column; gap:.85rem; }
.rp-role-group-name { font-size:.68rem; font-weight:700; color:var(--rp-muted); text-transform:uppercase; letter-spacing:.6px; margin-bottom:.4rem; }
.rp-roles-bar { display:flex; gap:.5rem; flex-wrap:wrap; }
.rp-role-chip { display:inline-flex; align-items:center; gap:.5rem; padding:.55rem 1rem; border-radius:999px; border:1.5px solid var(--rp-line); background:#fff; font-weight:600; font-size:.85rem; color:#475569; cursor:pointer; transition:all .2s ease; }
.rp-role-chip:hover { border-color:#94a3b8; background:#f8fafc; }
.rp-role-chip.active { border-color:var(--rp-p); background:rgba(4,83,203,.07); color:var(--rp-p); box-shadow:0 4px 12px rgba(4,83,203,.12); }
.rp-role-chip i { font-size:.8rem; }
.rp-role-detail { display:flex; gap:1rem; align-items:flex-start; margin-top:1.1rem; padding:1rem; border-radius:12px; background:#f8fafc; border:1px solid var(--rp-line); }
.rp-role-detail-icon { width:44px; height:44px; border-radius:12px; background:rgba(4,83,203,.1); color:var(--rp-p); display:flex; align-items:center; justify-content:center; font-size:1.05rem; flex-shrink:0; }
.rp-role-detail-name { font-weight:700; color:var(--rp-txt); }
.rp-role-detail-desc { color:var(--rp-muted); font-size:.85rem; margin-top:.1rem; }
.rp-role-detail-gere { font-size:.8rem; color:#475569; margin-top:.45rem; }
.rp-role-detail-gere strong { color:var(--rp-txt); }
.rp-note { margin-top:.6rem; font-size:.8rem; color:#1e40af; background:rgba(4,83,203,.06); border:1px solid rgba(4,83,203,.15); border-radius:8px; padding:.45rem .7rem; }

/* ── Barre d'actions (collante) ── */
.rp-actions-bar { position:sticky; top:70px; z-index:20; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.75rem; padding:.75rem 1rem; background:#fff; border:1px solid var(--rp-line); border-radius:14px; box-shadow:0 4px 16px rgba(4,83,203,.06),0 1px 3px rgba(15,23,42,.04); margin-bottom:1rem; }
.rp-actions-left { display:flex; align-items:center; gap:.75rem; flex:1 1 280px; min-width:0; }
.rp-search { position:relative; flex:1; min-width:0; }
.rp-search i { position:absolute; left:.8rem; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:.85rem; }
.rp-search input { width:100%; border:1px solid var(--rp-line); border-radius:10px; padding:.55rem .8rem .55rem 2.2rem; font-size:.88rem; color:var(--rp-txt); background:#f8fafc; }
.rp-search input:focus { outline:none; border-color:var(--rp-p); background:#fff; box-shadow:0 0 0 3px rgba(4,83,203,.12); }
.rp-dirty { display:none; align-items:center; gap:.35rem; font-size:.78rem; font-weight:600; color:#b45309; white-space:nowrap; }
.rp-dirty.is-on { display:inline-flex; }
.rp-actions-right { display:flex; align-items:center; gap:.4rem; flex-wrap:wrap; }
.rp-btn { display:inline-flex; align-items:center; gap:.4rem; padding:.5rem .85rem; border-radius:10px; font-size:.8rem; font-weight:600; border:1px solid var(--rp-line); background:#fff; color:#334155; cursor:pointer; transition:all .2s ease; white-space:nowrap; text-decoration:none; }
.rp-btn:hover { border-color:var(--rp-p); color:var(--rp-p); }
.rp-btn--primary { background:var(--rp-p); border-color:var(--rp-p); color:#fff; }
.rp-btn--primary:hover { background:var(--rp-pd); border-color:var(--rp-pd); color:#fff; }
.rp-btn:disabled { opacity:.6; cursor:wait; }

/* ── Contrôle du registre ── */
.rp-audit { display:none; }
.rp-audit.is-on { display:block; }
.rp-audit-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:.6rem; }
.rp-audit-cell { border:1px solid var(--rp-line); border-radius:10px; padding:.7rem .85rem; }
.rp-audit-cell b { display:block; font-size:1.2rem; color:var(--rp-txt); }
.rp-audit-cell span { font-size:.75rem; color:var(--rp-muted); }
.rp-audit-cell--ko b { color:#dc2626; }
.rp-audit-list { margin:.75rem 0 0; padding-left:1.1rem; font-size:.8rem; color:#475569; }
.rp-audit-list code { font-size:.75rem; }

/* ── Groupes ── */
.rp-groups { display:flex; flex-direction:column; gap:.6rem; }
.rp-group { border:1px solid var(--rp-line); border-radius:14px; background:#fff; box-shadow:0 1px 3px rgba(15,23,42,.04); }
.rp-group.is-hidden { display:none; }
.rp-group-header { display:flex; align-items:center; justify-content:space-between; gap:.75rem; padding:.85rem 1.1rem; cursor:pointer; border-radius:14px; transition:background .15s ease; }
.rp-group-header:hover { background:#f8fafc; }
.rp-group-left { display:flex; align-items:center; gap:.75rem; min-width:0; }
.rp-group-icon { width:38px; height:38px; border-radius:10px; background:rgba(4,83,203,.1); color:var(--rp-p); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.rp-group-name { font-weight:700; font-size:.92rem; color:var(--rp-txt); }
.rp-group-meta { font-size:.76rem; color:#94a3b8; }
.rp-group-right { display:flex; align-items:center; gap:.6rem; flex-shrink:0; }
.rp-group-bar { width:70px; height:6px; border-radius:999px; background:#e2e8f0; overflow:hidden; }
.rp-group-bar span { display:block; height:100%; background:var(--rp-p); border-radius:999px; transition:width .2s ease; }
.rp-group-badge { font-size:.76rem; font-weight:700; color:#475569; background:#f1f5f9; padding:.2rem .6rem; border-radius:999px; white-space:nowrap; }
.rp-chevron { font-size:.72rem; color:#94a3b8; transition:transform .2s ease; }
.rp-group-header:not(.collapsed) .rp-chevron { transform:rotate(180deg); }
.rp-group-body { padding:0 1.1rem 1.1rem; }
.rp-group-actions { display:flex; gap:1rem; margin-bottom:.7rem; }
.rp-link-btn { background:none; border:none; color:var(--rp-p); font-size:.8rem; font-weight:600; cursor:pointer; padding:0; }
.rp-link-btn:hover { text-decoration:underline; }
.rp-perms-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(270px,1fr)); gap:.5rem; }
.rp-perm-card { display:flex; align-items:center; gap:.75rem; padding:.65rem .85rem; border-radius:10px; border:1px solid var(--rp-line); background:#fff; cursor:pointer; margin:0; transition:border-color .15s ease, background .15s ease; }
.rp-perm-card.is-hidden { display:none; }
.rp-perm-card:hover { border-color:#cbd5e1; background:#f8fafc; }
.rp-perm-card:has(input:checked) { border-color:rgba(4,83,203,.3); background:rgba(4,83,203,.04); }
.rp-perm-card input[type="checkbox"] { position:absolute; opacity:0; width:1px; height:1px; }
.rp-perm-card:has(input:focus-visible) { box-shadow:0 0 0 3px rgba(4,83,203,.2); }
.rp-perm-content { flex:1; min-width:0; }
.rp-perm-label { display:block; font-weight:600; font-size:.86rem; color:var(--rp-txt); line-height:1.3; }
.rp-perm-key { display:block; font-size:.7rem; color:#94a3b8; font-family:monospace; margin-top:1px; overflow-wrap:anywhere; }
.rp-perm-toggle { width:36px; height:20px; border-radius:999px; background:#cbd5e1; position:relative; flex-shrink:0; transition:background .2s ease; }
.rp-perm-toggle::after { content:''; position:absolute; top:2px; left:2px; width:16px; height:16px; border-radius:50%; background:#fff; box-shadow:0 1px 3px rgba(0,0,0,.15); transition:left .2s ease; }
.rp-perm-card:has(input:checked) .rp-perm-toggle { background:var(--rp-p); }
.rp-perm-card:has(input:checked) .rp-perm-toggle::after { left:18px; }
.rp-badge { display:inline-block; margin-left:.35rem; padding:1px 6px; border-radius:4px; font-size:.62rem; font-weight:700; vertical-align:middle; }
.rp-badge-legacy { background:rgba(245,158,11,.12); color:#b45309; border:1px solid rgba(245,158,11,.3); }
.rp-badge-deprecated { background:rgba(220,38,38,.1); color:#b91c1c; border:1px solid rgba(220,38,38,.3); }
.rp-perm-legacy { opacity:.8; }
.rp-perm-deprecated .rp-perm-label { text-decoration:line-through; }
.rp-empty { display:none; text-align:center; color:var(--rp-muted); padding:2rem 1rem; font-size:.9rem; }
.rp-empty.is-on { display:block; }
.rp-bottom-bar { display:flex; justify-content:flex-end; padding:1.25rem 0; }

@@media (max-width: 992px) {
    .rp-hero { padding:1.5rem 1.5rem 1.25rem; }
}
@@media (max-width: 768px) {
    .rp-hero { padding:1.25rem 1rem; border-radius:14px; }
    .rp-hero h1 { font-size:1.2rem; }
    .rp-hero-icon { width:44px; height:44px; font-size:1.1rem; }
    .rp-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); gap:.5rem; }
    .rp-kpi-value { font-size:1.1rem; }
    .rp-card { padding:1rem; }
    .rp-roles-bar { flex-wrap:nowrap; overflow-x:auto; padding-bottom:.25rem; scrollbar-width:thin; }
    .rp-role-chip { flex-shrink:0; padding:.5rem .85rem; font-size:.8rem; }
    .rp-actions-bar { top:60px; padding:.65rem; }
    .rp-actions-right { width:100%; }
    .rp-actions-right .rp-btn { flex:1 1 calc(50% - .4rem); justify-content:center; }
    .rp-perms-grid { grid-template-columns:1fr; }
    .rp-group-bar { display:none; }
}
</style>
@endpush

@section('content')
<div class="main-content rp-page" id="rpPage"
     data-update-url="{{ route('esbtp.roles-permissions.update') }}"
     data-audit-url="{{ route('esbtp.roles-permissions.audit') }}"
     data-index-url="{{ route('esbtp.roles-permissions.index') }}"
     data-show-legacy="{{ $showLegacy ? 1 : 0 }}">

    <div class="rp-hero">
        <div class="rp-hero-top">
            <div class="rp-hero-left">
                <div class="rp-hero-icon"><i class="fas fa-user-shield"></i></div>
                <div>
                    <h1>Rôles et permissions</h1>
                    <p>Ce que chaque rôle peut voir et faire dans l'établissement</p>
                </div>
            </div>
            <div class="rp-hero-actions">
                <button type="button" class="rp-btn-glass" id="rpAuditBtn" title="Vérifie que chaque permission utilisée par l'application existe">
                    <i class="fas fa-stethoscope"></i><span>Contrôler le registre</span>
                </button>
                <a href="{{ route('esbtp.roles-permissions.index', ['role' => $selectedRoleName, 'show_legacy' => $showLegacy ? 0 : 1]) }}"
                   class="rp-btn-glass" id="rpLegacyLink" title="Affiche aussi les anciens noms de permissions">
                    <i class="fas {{ $showLegacy ? 'fa-eye-slash' : 'fa-history' }}"></i><span>{{ $showLegacy ? 'Masquer les anciens noms' : 'Voir les anciens noms' }}</span>
                </a>
            </div>
        </div>
        <div class="rp-kpis">
            <div class="rp-kpi">
                <div class="rp-kpi-value">{{ $roles->count() }}</div>
                <div class="rp-kpi-label">Rôles configurables</div>
            </div>
            <div class="rp-kpi">
                <div class="rp-kpi-value"><span id="rpCheckedCount">{{ $selectedVisibleCount }}</span> <small>/ {{ $permissions->count() }}</small></div>
                <div class="rp-kpi-label">Accordées à <span id="rpKpiRole">{{ $selectedInfo['label'] }}</span></div>
            </div>
            <div class="rp-kpi">
                <div class="rp-kpi-value">{{ $sortedGroups->count() }}</div>
                <div class="rp-kpi-label">Domaines</div>
            </div>
            <div class="rp-kpi">
                <div class="rp-kpi-value">{{ $permissions->count() }}</div>
                <div class="rp-kpi-label">{{ $showLegacy ? 'Permissions, anciens noms compris' : 'Permissions au catalogue' }}</div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    {{-- Contrôle du registre (rempli à la demande) --}}
    <div class="rp-card rp-audit" id="rpAudit" aria-live="polite">
        <div class="rp-card-head">
            <div class="rp-card-icon"><i class="fas fa-stethoscope"></i></div>
            <div>
                <p class="rp-card-title">Contrôle du registre des permissions</p>
                <p class="rp-card-sub" id="rpAuditSub">Analyse en cours…</p>
            </div>
        </div>
        <div id="rpAuditBody"></div>
    </div>

    <form action="{{ route('esbtp.roles-permissions.update') }}" method="POST" id="rpForm">
        @csrf
        <input type="hidden" id="rpRoleInput" name="role" value="{{ $selectedRoleName }}">
        <input type="hidden" name="show_legacy" value="{{ $showLegacy ? 1 : 0 }}">

        <div class="rp-card">
            <div class="rp-card-head">
                <div class="rp-card-icon"><i class="fas fa-users-cog"></i></div>
                <div>
                    <p class="rp-card-title">Rôle à configurer</p>
                    <p class="rp-card-sub">Choisissez un rôle, cochez ce qu'il peut faire, puis enregistrez.</p>
                </div>
            </div>
            <div class="rp-role-groups">
                @foreach($groupedRoles as $groupName => $groupRoles)
                    <div>
                        <div class="rp-role-group-name">{{ $groupName }}</div>
                        <div class="rp-roles-bar">
                            @foreach($groupRoles as $role)
                                <button type="button"
                                        class="rp-role-chip {{ $selectedRoleName === $role->name ? 'active' : '' }}"
                                        data-role="{{ $role->name }}"
                                        data-info='@json($roleInfos[$role->name])'
                                        data-permissions='@json($rolePermissions[$role->name] ?? [])'>
                                    <i class="fas {{ $roleIcons[$role->name] ?? 'fa-user' }}"></i>
                                    <span>{{ $roleLabels[$role->name] ?? $role->name }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="rp-role-detail">
                <div class="rp-role-detail-icon"><i class="fas {{ $selectedInfo['icon'] }}" id="rpDetailIcon"></i></div>
                <div style="min-width:0">
                    <div class="rp-role-detail-name" id="rpDetailName">{{ $selectedInfo['label'] }}</div>
                    <div class="rp-role-detail-desc" id="rpDetailDesc">{{ $selectedInfo['description'] }}</div>
                    <div class="rp-role-detail-gere" id="rpDetailGere" @if(empty($selectedInfo['gere'])) hidden @endif>
                        Peut créer et gérer les comptes : <strong>{{ implode(', ', $selectedInfo['gere']) }}</strong>
                    </div>
                    <div class="rp-note" id="rpSuperAdminNote" @if($selectedRoleName !== 'superAdmin') hidden @endif>
                        <i class="fas fa-info-circle me-1"></i>Le super administrateur a tous les droits, quoi qu'on coche ici.
                    </div>
                </div>
            </div>
        </div>

        <div class="rp-actions-bar">
            <div class="rp-actions-left">
                <div class="rp-search">
                    <i class="fas fa-search"></i>
                    <input type="search" id="rpSearch" placeholder="Chercher une permission (ex. paiement, bulletin)…" autocomplete="off" aria-label="Chercher une permission">
                </div>
                <span class="rp-dirty" id="rpDirty"><i class="fas fa-circle" style="font-size:.45rem"></i>Non enregistré</span>
            </div>
            <div class="rp-actions-right">
                <button type="button" class="rp-btn" id="rpRestoreDefaults"
                        data-restore-url="{{ route('esbtp.roles-permissions.restore-defaults') }}"
                        data-role="{{ $selectedRoleName }}"
                        title="Remettre les permissions prévues par défaut pour ce rôle">
                    <i class="fas fa-undo"></i>Défauts
                </button>
                <button type="button" class="rp-btn" id="rpSelectAll"><i class="fas fa-check-double"></i>Tout activer</button>
                <button type="button" class="rp-btn" id="rpClearAll"><i class="fas fa-eraser"></i>Tout désactiver</button>
                <button type="submit" class="rp-btn rp-btn--primary" id="rpSave"><i class="fas fa-save"></i><span>Enregistrer</span></button>
            </div>
        </div>

        <div class="rp-groups">
            @foreach($sortedGroups as $groupName => $groupItems)
                @php
                    $groupIcon = 'fa-layer-group';
                    $firstPerm = $groupItems->first();
                    if ($firstPerm && isset($catalog[$firstPerm->name])) {
                        $groupIcon = $catalog[$firstPerm->name]['icon'] ?? $groupIcon;
                    }
                    $groupSlug = Str::slug($groupName);
                    $checkedInGroup = $groupItems->filter(fn ($p) => $selectedPermissions->contains($p->name))->count();
                @endphp
                <div class="rp-group" data-group="{{ $groupSlug }}">
                    <div class="rp-group-header collapsed" data-bs-toggle="collapse" data-bs-target="#rp-group-{{ $groupSlug }}" aria-expanded="false" role="button">
                        <div class="rp-group-left">
                            <div class="rp-group-icon"><i class="fas {{ $groupIcon }}"></i></div>
                            <div style="min-width:0">
                                <div class="rp-group-name">{{ $groupName }}</div>
                                <div class="rp-group-meta">{{ $groupItems->count() }} permissions</div>
                            </div>
                        </div>
                        <div class="rp-group-right">
                            <div class="rp-group-bar"><span data-group-bar="{{ $groupSlug }}" style="width: {{ $groupItems->count() ? round($checkedInGroup * 100 / $groupItems->count()) : 0 }}%"></span></div>
                            <span class="rp-group-badge" data-group-slug="{{ $groupSlug }}">{{ $checkedInGroup }} / {{ $groupItems->count() }}</span>
                            <i class="fas fa-chevron-down rp-chevron"></i>
                        </div>
                    </div>
                    <div class="collapse" id="rp-group-{{ $groupSlug }}">
                        <div class="rp-group-body">
                            <div class="rp-group-actions">
                                <button type="button" class="rp-link-btn rp-group-check-all" data-group="{{ $groupSlug }}">Tout activer</button>
                                <button type="button" class="rp-link-btn rp-group-uncheck-all" data-group="{{ $groupSlug }}">Tout désactiver</button>
                            </div>
                            <div class="rp-perms-grid">
                                @foreach($groupItems as $permission)
                                    @php
                                        $entry = $catalog[$permission->name] ?? null;
                                        $label = $entry['label'] ?? $permission->name;
                                        $isAlias = $entry['is_alias'] ?? false;
                                        $depReason = $entry['deprecated_reason'] ?? null;
                                    @endphp
                                    <label class="rp-perm-card {{ $isAlias ? 'rp-perm-legacy' : '' }} {{ $depReason ? 'rp-perm-deprecated' : '' }}"
                                           data-search="{{ Str::lower(Str::ascii($label . ' ' . $permission->name . ' ' . $groupName)) }}">
                                        <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                               data-group="{{ $groupSlug }}"
                                               {{ $selectedPermissions->contains($permission->name) ? 'checked' : '' }}>
                                        <div class="rp-perm-content">
                                            <span class="rp-perm-label">
                                                {{ $label }}
                                                @if($isAlias)<span class="rp-badge rp-badge-legacy" title="Ancien nom de {{ $entry['canonical'] }}">Ancien nom</span>@endif
                                                @if($depReason)<span class="rp-badge rp-badge-deprecated" title="{{ $depReason }}">Obsolète</span>@endif
                                            </span>
                                            <span class="rp-perm-key">{{ $permission->name }}</span>
                                        </div>
                                        <div class="rp-perm-toggle" aria-hidden="true"></div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
            <div class="rp-empty" id="rpEmpty"><i class="fas fa-search me-1"></i>Aucune permission ne correspond à cette recherche.</div>
        </div>

        <div class="rp-bottom-bar">
            <button type="submit" class="rp-btn rp-btn--primary"><i class="fas fa-save"></i>Enregistrer les modifications</button>
        </div>
    </form>
</div>

@include('partials._klassci_toast')
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const page = document.getElementById('rpPage');
    const form = document.getElementById('rpForm');
    if (!page || !form) return;

    const roleInput = document.getElementById('rpRoleInput');
    const chips = Array.from(document.querySelectorAll('.rp-role-chip'));
    const boxes = Array.from(document.querySelectorAll('input[name="permissions[]"]'));
    const counter = document.getElementById('rpCheckedCount');
    const dirtyFlag = document.getElementById('rpDirty');
    const restoreBtn = document.getElementById('rpRestoreDefaults');
    const legacyLink = document.getElementById('rpLegacyLink');
    const searchInput = document.getElementById('rpSearch');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || form.querySelector('input[name="_token"]')?.value;
    const toast = (type, message) => (window.klassciToast ? window.klassciToast(type, message) : alert(message));

    const activeChip = () => chips.find(c => c.dataset.role === roleInput.value) || chips[0];
    // Le rôle porte aussi des anciens noms sans case quand ils sont masqués :
    // on ne compare que ce qui est affiché.
    const visibles = new Set(boxes.map(b => b.value));
    const savedSet = () => new Set(JSON.parse(activeChip()?.dataset.permissions || '[]').filter(p => visibles.has(p)));
    const checkedValues = () => boxes.filter(b => b.checked).map(b => b.value);

    function isDirty() {
        const saved = savedSet();
        const now = checkedValues();
        return now.length !== saved.size || now.some(v => !saved.has(v));
    }

    function refresh() {
        if (counter) counter.textContent = checkedValues().length;
        document.querySelectorAll('.rp-group').forEach(group => {
            const slug = group.dataset.group;
            const all = group.querySelectorAll('input[name="permissions[]"]');
            const on = group.querySelectorAll('input[name="permissions[]"]:checked').length;
            const badge = group.querySelector(`.rp-group-badge[data-group-slug="${slug}"]`);
            const bar = group.querySelector(`[data-group-bar="${slug}"]`);
            if (badge) badge.textContent = `${on} / ${all.length}`;
            if (bar) bar.style.width = (all.length ? Math.round(on * 100 / all.length) : 0) + '%';
        });
        dirtyFlag?.classList.toggle('is-on', isDirty());
    }

    function applySet(list) {
        const set = new Set(list);
        boxes.forEach(b => { b.checked = set.has(b.value); });
        refresh();
    }

    function showRole(chip) {
        const info = JSON.parse(chip.dataset.info || '{}');
        chips.forEach(c => c.classList.toggle('active', c === chip));
        roleInput.value = chip.dataset.role;
        if (restoreBtn) restoreBtn.dataset.role = chip.dataset.role;
        if (legacyLink) {
            const url = new URL(page.dataset.indexUrl, window.location.origin);
            url.searchParams.set('role', chip.dataset.role);
            url.searchParams.set('show_legacy', page.dataset.showLegacy === '1' ? '0' : '1');
            legacyLink.href = url.toString();
        }
        document.getElementById('rpKpiRole').textContent = info.label || chip.dataset.role;
        document.getElementById('rpDetailName').textContent = info.label || chip.dataset.role;
        document.getElementById('rpDetailDesc').textContent = info.description || '';
        document.getElementById('rpDetailIcon').className = 'fas ' + (info.icon || 'fa-user');
        const gere = document.getElementById('rpDetailGere');
        gere.hidden = !(info.gere && info.gere.length);
        if (!gere.hidden) gere.querySelector('strong').textContent = info.gere.join(', ');
        document.getElementById('rpSuperAdminNote').hidden = chip.dataset.role !== 'superAdmin';
        const url = new URL(window.location.href);
        url.searchParams.set('role', chip.dataset.role);
        history.replaceState(null, '', url.toString());
        applySet(JSON.parse(chip.dataset.permissions || '[]'));
    }

    chips.forEach(chip => chip.addEventListener('click', () => {
        if (chip.classList.contains('active')) return;
        if (isDirty() && !confirm('Les modifications de ce rôle ne sont pas enregistrées. Les abandonner ?')) return;
        showRole(chip);
    }));

    document.getElementById('rpSelectAll')?.addEventListener('click', () => {
        boxes.filter(b => !b.closest('.rp-perm-card').classList.contains('is-hidden')).forEach(b => { b.checked = true; });
        refresh();
    });
    document.getElementById('rpClearAll')?.addEventListener('click', () => {
        boxes.filter(b => !b.closest('.rp-perm-card').classList.contains('is-hidden')).forEach(b => { b.checked = false; });
        refresh();
    });
    document.querySelectorAll('.rp-group-check-all, .rp-group-uncheck-all').forEach(btn => btn.addEventListener('click', () => {
        const on = btn.classList.contains('rp-group-check-all');
        document.querySelectorAll(`input[data-group="${btn.dataset.group}"]`).forEach(b => {
            if (!b.closest('.rp-perm-card').classList.contains('is-hidden')) b.checked = on;
        });
        refresh();
    }));
    boxes.forEach(b => b.addEventListener('change', refresh));

    // Recherche : ouvre les domaines qui ont un résultat, masque les autres.
    const norm = s => s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim();
    let searchTimer = null;
    searchInput?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            const q = norm(searchInput.value);
            let found = 0;
            document.querySelectorAll('.rp-group').forEach(group => {
                let hits = 0;
                group.querySelectorAll('.rp-perm-card').forEach(card => {
                    const ok = !q || card.dataset.search.includes(q);
                    card.classList.toggle('is-hidden', !ok);
                    if (ok) hits++;
                });
                group.classList.toggle('is-hidden', hits === 0);
                found += hits;
                const collapseEl = group.querySelector('.collapse');
                if (collapseEl && window.bootstrap?.Collapse) {
                    const c = bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false });
                    if (q && hits) c.show(); else if (!q) c.hide();
                }
            });
            document.getElementById('rpEmpty').classList.toggle('is-on', found === 0);
        }, 150);
    });

    async function send(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body,
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || data.success === false) {
            const firstError = data.errors ? Object.values(data.errors).flat()[0] : null;
            throw new Error(firstError || data.message || `Erreur ${res.status}`);
        }
        return data;
    }

    function rememberSaved(data) {
        const chip = chips.find(c => c.dataset.role === data.role);
        if (chip && Array.isArray(data.permissions)) chip.dataset.permissions = JSON.stringify(data.permissions);
        if (data.role === roleInput.value && Array.isArray(data.permissions)) applySet(data.permissions);
    }

    form.addEventListener('submit', async (ev) => {
        ev.preventDefault();
        const buttons = form.querySelectorAll('button[type="submit"]');
        buttons.forEach(b => b.disabled = true);
        try {
            const data = await send(form.action, new FormData(form));
            rememberSaved(data);
            toast('success', data.message);
        } catch (err) {
            toast('error', err.message);
        } finally {
            buttons.forEach(b => b.disabled = false);
        }
    });

    restoreBtn?.addEventListener('click', async function () {
        const chip = activeChip();
        const label = JSON.parse(chip?.dataset.info || '{}').label || this.dataset.role;
        if (!confirm(`Remettre les permissions par défaut du rôle « ${label} » ?\nCe qui a été ajouté ou retiré pour ce rôle sera perdu.`)) return;
        const body = new FormData();
        body.append('role', this.dataset.role);
        this.disabled = true;
        try {
            const data = await send(this.dataset.restoreUrl, body);
            rememberSaved(data);
            toast('success', data.message);
        } catch (err) {
            toast('error', err.message);
        } finally {
            this.disabled = false;
        }
    });

    // Contrôle du registre, à la demande (l'analyse lit tout le code de l'application).
    const auditBtn = document.getElementById('rpAuditBtn');
    auditBtn?.addEventListener('click', async () => {
        const box = document.getElementById('rpAudit');
        const sub = document.getElementById('rpAuditSub');
        const body = document.getElementById('rpAuditBody');
        box.classList.add('is-on');
        sub.textContent = 'Analyse en cours…';
        body.innerHTML = '';
        auditBtn.disabled = true;
        try {
            const res = await fetch(page.dataset.auditUrl, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (!res.ok || data.error) throw new Error(data.error || `Erreur ${res.status}`);
            const n = v => Array.isArray(v) ? v.length : (v && typeof v === 'object' ? Object.keys(v).length : 0);
            const cells = [
                ['Cassées', n(data.broken), 'utilisées par l\'application, inconnues partout', true],
                ['Hors registre', n(data.off_registry), 'en base, absentes du registre', false],
                ['Anciens noms utilisés', n(data.aliases_used), 'à renommer dans le code', false],
                ['Orphelines', n(data.orphaned), 'en base, utilisées nulle part', false],
            ];
            const esc = s => String(s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
            const s = data.summary || {};
            sub.textContent = `${s.used_in_code ?? '—'} utilisées par l'application · ${s.in_registry ?? '—'} au registre · ${s.in_db ?? '—'} en base`;
            let html = '<div class="rp-audit-grid">' + cells.map(([t, v, h, critique]) =>
                `<div class="rp-audit-cell ${critique && v ? 'rp-audit-cell--ko' : ''}"><b>${v}</b><span>${esc(t)}<br>${esc(h)}</span></div>`).join('') + '</div>';
            const broken = Array.isArray(data.broken) ? data.broken : Object.keys(data.broken || {});
            if (broken.length) {
                html += '<ul class="rp-audit-list">' + broken.slice(0, 12).map(b => `<li><code>${esc(typeof b === 'string' ? b : (b.name || JSON.stringify(b)))}</code></li>`).join('') + '</ul>';
            }
            body.innerHTML = html;
        } catch (err) {
            sub.textContent = 'Le contrôle n\'a pas pu aboutir : ' + err.message;
        } finally {
            auditBtn.disabled = false;
        }
    });

    window.addEventListener('beforeunload', (e) => { if (isDirty()) { e.preventDefault(); e.returnValue = ''; } });

    refresh();
});
</script>
@endpush
