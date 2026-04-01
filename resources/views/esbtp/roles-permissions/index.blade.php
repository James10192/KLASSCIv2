@extends('layouts.app')

@php
    use Illuminate\Support\Str;
    $roleLabels = [
        'superAdmin' => 'Super Admin',
        'secretaire' => 'Secrétaire',
        'comptable' => 'Comptable',
        'coordinateur' => 'Coordinateur',
        'enseignant' => 'Enseignant',
        'etudiant' => 'Étudiant',
    ];
    $roleDescriptions = [
        'superAdmin' => 'Accès complet au système',
        'secretaire' => 'Gestion administrative quotidienne',
        'comptable' => 'Gestion financière et comptabilité',
        'coordinateur' => 'Suivi pédagogique et encadrement',
        'enseignant' => 'Cours, présence, évaluations',
        'etudiant' => 'Accès aux services étudiant',
    ];
    $roleIcons = [
        'superAdmin' => 'fa-crown',
        'secretaire' => 'fa-clipboard',
        'comptable' => 'fa-calculator',
        'coordinateur' => 'fa-user-tie',
        'enseignant' => 'fa-chalkboard-teacher',
        'etudiant' => 'fa-user-graduate',
    ];
    $groupDescriptions = [
        'Administration' => 'Pilotage et gestion globale',
        'Pédagogie' => 'Suivi pédagogique et cours',
        'Étudiants' => 'Accès étudiant',
    ];
@endphp

@section('title', 'Gestion Rôles & Permissions')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="main-content">
    <div class="dashboard-header">
        <div class="header-left">
            <h1><i class="fas fa-user-shield me-2"></i>Rôles & Permissions</h1>
            <p class="header-subtitle">Configurez les accès de chaque rôle en langage clair</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('esbtp.roles-permissions.update') }}" method="POST" id="rpForm">
        @csrf
        <input type="hidden" id="rpRoleInput" name="role" value="{{ $selectedRoleName }}">

        {{-- ── Sélection du rôle ── --}}
        <div class="rp-roles-bar">
            @foreach($roles as $role)
                <button type="button"
                        class="rp-role-chip {{ $selectedRoleName === $role->name ? 'active' : '' }}"
                        data-role="{{ $role->name }}"
                        data-permissions='@json($rolePermissions[$role->name] ?? [])'>
                    <i class="fas {{ $roleIcons[$role->name] ?? 'fa-user' }}"></i>
                    <span>{{ $roleLabels[$role->name] ?? $role->name }}</span>
                </button>
            @endforeach
        </div>

        {{-- ── Barre d'actions ── --}}
        <div class="rp-actions-bar">
            <div class="rp-actions-left">
                <span class="rp-counter"><strong id="rpCheckedCount">0</strong> / {{ $permissions->count() }} permissions activées</span>
            </div>
            <div class="rp-actions-right">
                <button type="button" class="btn-acasi secondary btn-sm" id="rpSelectAll">
                    <i class="fas fa-check-double me-1"></i>Tout activer
                </button>
                <button type="button" class="btn-acasi secondary btn-sm" id="rpClearAll">
                    <i class="fas fa-eraser me-1"></i>Tout désactiver
                </button>
                <button type="submit" class="btn-acasi primary btn-sm">
                    <i class="fas fa-save me-1"></i>Enregistrer
                </button>
            </div>
        </div>

        {{-- ── Groupes de permissions ── --}}
        <div class="rp-groups">
            @foreach($sortedGroups as $groupName => $groupItems)
                @php
                    $groupIcon = 'fa-layer-group';
                    $firstPerm = $groupItems->first();
                    if ($firstPerm && isset($catalog[$firstPerm->name])) {
                        $groupIcon = $catalog[$firstPerm->name][2];
                    }
                    $groupSlug = Str::slug($groupName);
                    $selectedPermissions = $rolePermissions[$selectedRoleName] ?? collect();
                    $checkedInGroup = $groupItems->filter(fn($p) => $selectedPermissions->contains($p->name))->count();
                @endphp
                <div class="rp-group" data-group="{{ $groupSlug }}">
                    <div class="rp-group-header" data-bs-toggle="collapse" data-bs-target="#rp-group-{{ $groupSlug }}">
                        <div class="rp-group-left">
                            <div class="rp-group-icon"><i class="fas {{ $groupIcon }}"></i></div>
                            <div>
                                <div class="rp-group-name">{{ $groupName }}</div>
                                <div class="rp-group-meta">{{ $groupItems->count() }} permissions</div>
                            </div>
                        </div>
                        <div class="rp-group-right">
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
                                        $label = $entry ? $entry[0] : $permission->name;
                                    @endphp
                                    <label class="rp-perm-card">
                                        <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                               data-group="{{ $groupSlug }}"
                                               {{ $selectedPermissions->contains($permission->name) ? 'checked' : '' }}>
                                        <div class="rp-perm-content">
                                            <span class="rp-perm-label">{{ $label }}</span>
                                            <span class="rp-perm-key">{{ $permission->name }}</span>
                                        </div>
                                        <div class="rp-perm-toggle"></div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ── Bouton enregistrer en bas ── --}}
        <div class="rp-bottom-bar">
            <button type="submit" class="btn-acasi primary">
                <i class="fas fa-save me-1"></i>Enregistrer les modifications
            </button>
        </div>
    </form>
</div>

<style>
/* ── Rôles bar ── */
.rp-roles-bar {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 1.25rem;
}

.rp-role-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 999px;
    border: 2px solid #e2e8f0;
    background: #fff;
    font-weight: 600;
    font-size: 0.9rem;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s ease;
}

.rp-role-chip:hover {
    border-color: #94a3b8;
    background: #f8fafc;
}

.rp-role-chip.active {
    border-color: var(--primary, #0453cb);
    background: linear-gradient(135deg, rgba(4,83,203,0.08), rgba(94,145,222,0.05));
    color: var(--primary, #0453cb);
    box-shadow: 0 4px 12px rgba(4,83,203,0.15);
}

.rp-role-chip i {
    font-size: 0.85rem;
}

/* ── Actions bar ── */
.rp-actions-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    padding: 12px 16px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    margin-bottom: 1.25rem;
}

.rp-actions-left { display: flex; align-items: center; gap: 12px; }
.rp-actions-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

.rp-counter {
    font-size: 0.9rem;
    color: #64748b;
}

.rp-counter strong {
    color: var(--primary, #0453cb);
    font-size: 1.05rem;
}

/* ── Groupes ── */
.rp-groups {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.rp-group {
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    background: #fff;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(15,23,42,0.04);
}

.rp-group-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px;
    cursor: pointer;
    transition: background 0.15s ease;
}

.rp-group-header:hover {
    background: #f8fafc;
}

.rp-group-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.rp-group-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(4,83,203,0.1);
    color: var(--primary, #0453cb);
    font-size: 1rem;
    flex-shrink: 0;
}

.rp-group-name {
    font-weight: 700;
    font-size: 0.95rem;
    color: #1e293b;
}

.rp-group-meta {
    font-size: 0.8rem;
    color: #94a3b8;
}

.rp-group-right {
    display: flex;
    align-items: center;
    gap: 10px;
}

.rp-group-badge {
    font-size: 0.8rem;
    font-weight: 600;
    color: #64748b;
    background: #f1f5f9;
    padding: 4px 10px;
    border-radius: 999px;
}

.rp-chevron {
    font-size: 0.75rem;
    color: #94a3b8;
    transition: transform 0.2s ease;
}

.rp-group-header[aria-expanded="true"] .rp-chevron,
[data-bs-toggle="collapse"]:not(.collapsed) .rp-chevron {
    transform: rotate(180deg);
}

/* ── Group body ── */
.rp-group-body {
    padding: 0 18px 18px;
}

.rp-group-actions {
    display: flex;
    gap: 16px;
    margin-bottom: 12px;
    padding-top: 4px;
}

.rp-link-btn {
    background: none;
    border: none;
    color: var(--primary, #0453cb);
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    padding: 0;
    text-decoration: underline;
    text-underline-offset: 2px;
}

.rp-link-btn:hover {
    color: #1e40af;
}

/* ── Permission grid ── */
.rp-perms-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 8px;
}

.rp-perm-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    background: #fff;
    cursor: pointer;
    transition: border-color 0.15s ease, background 0.15s ease;
}

.rp-perm-card:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
}

.rp-perm-card:has(input:checked) {
    border-color: rgba(4,83,203,0.3);
    background: rgba(4,83,203,0.04);
}

.rp-perm-card input[type="checkbox"] {
    display: none;
}

.rp-perm-content {
    flex: 1;
    min-width: 0;
}

.rp-perm-label {
    display: block;
    font-weight: 600;
    font-size: 0.88rem;
    color: #1e293b;
    line-height: 1.3;
}

.rp-perm-key {
    display: block;
    font-size: 0.72rem;
    color: #94a3b8;
    font-family: monospace;
    margin-top: 1px;
}

/* ── Toggle visual ── */
.rp-perm-toggle {
    width: 36px;
    height: 20px;
    border-radius: 999px;
    background: #cbd5e1;
    position: relative;
    flex-shrink: 0;
    transition: background 0.2s ease;
}

.rp-perm-toggle::after {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.15);
    transition: transform 0.2s ease;
}

.rp-perm-card:has(input:checked) .rp-perm-toggle {
    background: var(--primary, #0453cb);
}

.rp-perm-card:has(input:checked) .rp-perm-toggle::after {
    transform: translateX(16px);
}

/* ── Bottom bar ── */
.rp-bottom-bar {
    display: flex;
    justify-content: flex-end;
    padding: 1.25rem 0;
}

/* ── Responsive ── */
@media (max-width: 768px) {
    .rp-roles-bar { gap: 6px; }
    .rp-role-chip { padding: 8px 12px; font-size: 0.82rem; }
    .rp-perms-grid { grid-template-columns: 1fr; }
    .rp-actions-bar { flex-direction: column; align-items: stretch; }
}
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('rpForm');
    const roleInput = document.getElementById('rpRoleInput');
    const chips = document.querySelectorAll('.rp-role-chip');
    const checkedCounter = document.getElementById('rpCheckedCount');

    function updateCounter() {
        const count = document.querySelectorAll('input[name="permissions[]"]:checked').length;
        if (checkedCounter) checkedCounter.textContent = count;

        // Update group badges
        document.querySelectorAll('.rp-group').forEach(group => {
            const slug = group.dataset.group;
            const total = group.querySelectorAll('input[name="permissions[]"]').length;
            const checked = group.querySelectorAll('input[name="permissions[]"]:checked').length;
            const badge = document.querySelector(`.rp-group-badge[data-group-slug="${slug}"]`);
            if (badge) badge.textContent = `${checked} / ${total}`;
        });
    }

    function syncFromRole(chip) {
        const perms = JSON.parse(chip.dataset.permissions || '[]');
        document.querySelectorAll('input[name="permissions[]"]').forEach(cb => {
            cb.checked = perms.includes(cb.value);
        });
        updateCounter();
    }

    // Role chip click
    chips.forEach(chip => {
        chip.addEventListener('click', () => {
            chips.forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            roleInput.value = chip.dataset.role;
            syncFromRole(chip);
        });
    });

    // Select all / Clear all
    document.getElementById('rpSelectAll')?.addEventListener('click', () => {
        document.querySelectorAll('input[name="permissions[]"]').forEach(cb => cb.checked = true);
        updateCounter();
    });
    document.getElementById('rpClearAll')?.addEventListener('click', () => {
        document.querySelectorAll('input[name="permissions[]"]').forEach(cb => cb.checked = false);
        updateCounter();
    });

    // Group-level actions
    document.querySelectorAll('.rp-group-check-all').forEach(btn => {
        btn.addEventListener('click', () => {
            const group = btn.dataset.group;
            document.querySelectorAll(`input[data-group="${group}"]`).forEach(cb => cb.checked = true);
            updateCounter();
        });
    });
    document.querySelectorAll('.rp-group-uncheck-all').forEach(btn => {
        btn.addEventListener('click', () => {
            const group = btn.dataset.group;
            document.querySelectorAll(`input[data-group="${group}"]`).forEach(cb => cb.checked = false);
            updateCounter();
        });
    });

    // Checkbox change
    document.querySelectorAll('input[name="permissions[]"]').forEach(cb => {
        cb.addEventListener('change', updateCounter);
    });

    // Init counter
    updateCounter();
});
</script>
@endpush
@endsection
