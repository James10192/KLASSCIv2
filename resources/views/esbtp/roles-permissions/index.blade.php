@extends('layouts.app')

@php
    use Illuminate\Support\Str;
    $roleLabels = [
        'superAdmin' => 'Super Admin',
        'secretaire' => 'Secrétaire',
        'coordinateur' => 'Coordinateur',
        'enseignant' => 'Enseignant',
        'etudiant' => 'Étudiant',
    ];
    $roleDescriptions = [
        'superAdmin' => 'Accès complet au système',
        'secretaire' => 'Gestion administrative quotidienne',
        'coordinateur' => 'Suivi pédagogique et encadrement',
        'enseignant' => 'Cours, présence, évaluations',
        'etudiant' => 'Accès aux services étudiant',
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
            <p class="header-subtitle">Administration des rôles existants et de leurs permissions</p>
        </div>
        <div class="header-actions">
            <span class="badge primary">ADMIN CONFIG</span>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card-moderne mb-lg">
        <div class="section-card-header">
            <h3 class="section-card-title">
                <i class="fas fa-sliders-h"></i>
                Configuration des permissions
            </h3>
        </div>
        <div class="section-card-body">
            <form action="{{ route('esbtp.roles-permissions.update') }}" method="POST" id="rolePermissionsForm">
                @csrf

                <div class="form-grid-2 mb-lg">
                    <div class="form-group-moderne">
                        <label class="form-label-moderne">
                            <i class="fas fa-users-cog me-1"></i>Rôle cible
                        </label>
                        <input type="hidden" id="roleSelect" name="role" value="{{ $selectedRoleName }}">
                        <div class="role-accordion" id="roleAccordion">
                            @foreach($groupedRoles as $groupLabel => $groupRoles)
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading-{{ Str::slug($groupLabel) }}">
                                        <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ Str::slug($groupLabel) }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="collapse-{{ Str::slug($groupLabel) }}">
                                            <div class="group-title">
                                                <span class="group-icon">
                                                    <i class="fas fa-layer-group"></i>
                                                </span>
                                                <div>
                                                    <div class="group-name">{{ $groupLabel }}</div>
                                                    <div class="group-desc">{{ $groupDescriptions[$groupLabel] ?? '' }}</div>
                                                </div>
                                            </div>
                                            <span class="group-count">{{ $groupRoles->count() }} rôles</span>
                                        </button>
                                    </h2>
                                    <div id="collapse-{{ Str::slug($groupLabel) }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" aria-labelledby="heading-{{ Str::slug($groupLabel) }}" data-bs-parent="#roleAccordion">
                                        <div class="accordion-body">
                                            <div class="role-grid">
                                                @foreach($groupRoles as $role)
                                                    @php
                                                        $rolePerms = $rolePermissions[$role->name] ?? collect();
                                                    @endphp
                                                    <button type="button" class="role-card {{ $selectedRoleName === $role->name ? 'active' : '' }}" data-role="{{ $role->name }}" data-permissions='@json($rolePerms)'>
                                                    <div class="role-name">{{ $roleLabels[$role->name] ?? Str::title(str_replace(['_', '-'], ' ', $role->name)) }}</div>
                                                    <div class="role-desc">{{ $roleDescriptions[$role->name] ?? '' }}</div>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="form-group-moderne">
                        <label class="form-label-moderne">
                            <i class="fas fa-filter me-1"></i>Actions rapides
                        </label>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn-acasi secondary" id="selectAllPerms">
                                <i class="fas fa-check-double me-1"></i>Tout cocher
                            </button>
                            <button type="button" class="btn-acasi secondary" id="clearAllPerms">
                                <i class="fas fa-eraser me-1"></i>Tout retirer
                            </button>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info mb-lg">
                    <i class="fas fa-info-circle me-2"></i>
                    Les rôles existent déjà. Vous pouvez ajouter ou retirer des permissions puis enregistrer.
                </div>

                @php
                    $selectedPermissions = $rolePermissions[$selectedRoleName] ?? collect();
                @endphp
                <div class="permissions-accordion" id="permissionsAccordion">
                    @foreach($groupedPermissions as $groupName => $groupItems)
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="perm-heading-{{ Str::slug($groupName) }}">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#perm-collapse-{{ Str::slug($groupName) }}" aria-expanded="false" aria-controls="perm-collapse-{{ Str::slug($groupName) }}">
                                    <div class="group-title">
                                        <span class="group-icon">
                                            <i class="fas fa-layer-group"></i>
                                        </span>
                                        <div>
                                            <div class="group-name">{{ Str::title(str_replace('_', ' ', $groupName)) }}</div>
                                            <div class="group-desc">{{ $groupItems->count() }} permissions</div>
                                        </div>
                                    </div>
                                </button>
                            </h2>
                            <div id="perm-collapse-{{ Str::slug($groupName) }}" class="accordion-collapse collapse" aria-labelledby="perm-heading-{{ Str::slug($groupName) }}" data-bs-parent="#permissionsAccordion">
                                <div class="accordion-body" data-group="{{ $groupName }}">
                                    <div class="group-actions">
                                        <button type="button" class="btn-acasi secondary group-select-all" data-group="{{ $groupName }}">
                                            Tout cocher
                                        </button>
                                        <button type="button" class="btn-acasi secondary group-clear-all" data-group="{{ $groupName }}">
                                            Tout retirer
                                        </button>
                                    </div>
                                    <div class="permissions-grid">
                                        @foreach($groupItems as $permission)
                                            <label class="permission-card">
                                                <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                                    data-group="{{ $groupName }}"
                                                    {{ $selectedPermissions->contains($permission->name) ? 'checked' : '' }}>
                                                <span class="permission-label">{{ $permission->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="d-flex justify-content-end gap-2 mt-lg">
                    <button type="submit" class="btn-acasi primary">
                        <i class="fas fa-save me-2"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .permissions-group-list {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .role-accordion {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .role-accordion .accordion-item {
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
    }

    .role-accordion .accordion-button {
        font-weight: 700;
        color: #0f172a;
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 70%);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 16px 18px;
    }

    .role-accordion .accordion-button:focus {
        box-shadow: 0 0 0 3px rgba(4, 83, 203, 0.15);
    }

    .role-accordion .accordion-button .group-count {
        font-size: 0.85rem;
        color: #0f172a;
        font-weight: 700;
        background: #e2e8f0;
        border-radius: 999px;
        padding: 4px 10px;
    }

    .role-accordion .accordion-body {
        padding: 16px 18px 18px;
    }

    .group-title {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .group-icon {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(4, 83, 203, 0.12);
        color: #0453cb;
        font-size: 1rem;
    }

    .group-name {
        font-size: 1rem;
        font-weight: 700;
    }

    .group-desc {
        font-size: 0.85rem;
        color: #64748b;
        font-weight: 500;
    }

    .role-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 12px;
    }

    .role-card {
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        padding: 12px 14px;
        border-radius: 14px;
        text-align: left;
        font-weight: 700;
        color: #0f172a;
        transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }

    .role-name {
        font-size: 0.98rem;
    }

    .role-desc {
        font-size: 0.82rem;
        color: #64748b;
        font-weight: 500;
        margin-top: 2px;
    }

    .role-card.active {
        border-color: #0453cb;
        background: linear-gradient(135deg, rgba(4, 83, 203, 0.12), rgba(94, 145, 222, 0.08));
        box-shadow: 0 10px 24px rgba(4, 83, 203, 0.15);
    }

    .role-card:hover {
        border-color: #94a3b8;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
    }

    .permissions-accordion {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .permissions-accordion .accordion-item {
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
    }

    .permissions-accordion .accordion-button {
        font-weight: 700;
        color: #0f172a;
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 70%);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 16px 18px;
    }

    .permissions-accordion .accordion-button:focus {
        box-shadow: 0 0 0 3px rgba(4, 83, 203, 0.15);
    }

    .permissions-accordion .accordion-body {
        padding: 16px 18px 18px;
        background: #f8fafc;
    }

    .group-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }

    .group-title {
        font-weight: 700;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .group-count {
        font-size: 0.85rem;
        color: #64748b;
        font-weight: 500;
    }

    .group-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }

    .permissions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 12px;
    }

    .permission-card {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 14px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background: #fff;
        font-size: 0.95rem;
        cursor: pointer;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .permission-card:hover {
        border-color: #94a3b8;
        box-shadow: 0 6px 20px rgba(15, 23, 42, 0.06);
    }

    .permission-card input {
        width: 18px;
        height: 18px;
        accent-color: var(--primary);
    }

    .permission-label {
        font-weight: 600;
        color: #0f172a;
        word-break: break-word;
    }

    @media (max-width: 768px) {
        .group-actions {
            width: 100%;
        }

        .permissions-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    const roleSelect = document.getElementById('roleSelect');
    const roleCards = document.querySelectorAll('.role-card');
    const selectAllBtn = document.getElementById('selectAllPerms');
    const clearAllBtn = document.getElementById('clearAllPerms');
    const groupSelectButtons = document.querySelectorAll('.group-select-all');
    const groupClearButtons = document.querySelectorAll('.group-clear-all');

    function syncPermissionsFromRole() {
        const selectedCard = document.querySelector('.role-card.active');
        const allowed = JSON.parse(selectedCard?.dataset.permissions || '[]');
        document.querySelectorAll('input[name="permissions[]"]').forEach((checkbox) => {
            checkbox.checked = allowed.includes(checkbox.value);
        });
    }

    roleCards.forEach((card) => {
        card.addEventListener('click', () => {
            roleCards.forEach((item) => item.classList.remove('active'));
            card.classList.add('active');
            if (roleSelect) {
                roleSelect.value = card.dataset.role;
            }
            syncPermissionsFromRole();
        });
    });
    selectAllBtn?.addEventListener('click', () => {
        document.querySelectorAll('input[name="permissions[]"]').forEach((checkbox) => {
            checkbox.checked = true;
        });
    });
    clearAllBtn?.addEventListener('click', () => {
        document.querySelectorAll('input[name="permissions[]"]').forEach((checkbox) => {
            checkbox.checked = false;
        });
    });

    groupSelectButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const group = button.dataset.group;
            document.querySelectorAll(`input[name="permissions[]"][data-group="${group}"]`).forEach((checkbox) => {
                checkbox.checked = true;
            });
        });
    });

    groupClearButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const group = button.dataset.group;
            document.querySelectorAll(`input[name="permissions[]"][data-group="${group}"]`).forEach((checkbox) => {
                checkbox.checked = false;
            });
        });
    });
</script>
@endsection
