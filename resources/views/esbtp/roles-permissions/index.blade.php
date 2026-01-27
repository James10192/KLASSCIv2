@section('title', 'Gestion Roles & Permissions')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="main-content">
    <div class="dashboard-header">
        <div class="header-left">
            <h1><i class="fas fa-user-shield me-2"></i>Roles & Permissions</h1>
            <p class="header-subtitle">Administration des roles existants et de leurs permissions</p>
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
                            <i class="fas fa-users-cog me-1"></i>Role cible
                        </label>
                        <select class="form-select-moderne" id="roleSelect" name="role">
                            @foreach($roles as $role)
                                @php
                                    $rolePerms = $rolePermissions[$role->name] ?? collect();
                                @endphp
                                <option value="{{ $role->name }}"
                                    data-permissions='@json($rolePerms)'
                                    {{ $selectedRoleName === $role->name ? 'selected' : '' }}>
                                    {{ ucfirst($role->name) }}
                                </option>
                            @endforeach
                        </select>
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
                    Les roles existent deja. Vous pouvez ajouter ou retirer des permissions puis enregistrer.
                </div>

                @php
                    $selectedPermissions = $rolePermissions[$selectedRoleName] ?? collect();
                @endphp
                <div class="permissions-group-list">
                    @foreach($groupedPermissions as $groupName => $groupItems)
                        <div class="permissions-group" data-group="{{ $groupName }}">
                            <div class="group-header">
                                <div class="group-title">
                                    <i class="fas fa-layer-group me-2"></i>
                                    {{ ucfirst($groupName) }}
                                    <span class="group-count">{{ $groupItems->count() }} permissions</span>
                                </div>
                                <div class="group-actions">
                                    <button type="button" class="btn-acasi secondary group-select-all" data-group="{{ $groupName }}">
                                        Tout cocher
                                    </button>
                                    <button type="button" class="btn-acasi secondary group-clear-all" data-group="{{ $groupName }}">
                                        Tout retirer
                                    </button>
                                </div>
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

    .permissions-group {
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 16px;
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
    const selectAllBtn = document.getElementById('selectAllPerms');
    const clearAllBtn = document.getElementById('clearAllPerms');
    const groupSelectButtons = document.querySelectorAll('.group-select-all');
    const groupClearButtons = document.querySelectorAll('.group-clear-all');

    function syncPermissionsFromRole() {
        const selectedOption = roleSelect.options[roleSelect.selectedIndex];
        const allowed = JSON.parse(selectedOption.dataset.permissions || '[]');
        document.querySelectorAll('input[name="permissions[]"]').forEach((checkbox) => {
            checkbox.checked = allowed.includes(checkbox.value);
        });
    }

    roleSelect?.addEventListener('change', syncPermissionsFromRole);
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
