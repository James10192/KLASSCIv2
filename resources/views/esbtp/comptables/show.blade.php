@extends('layouts.app')

@section('title', 'Fiche Comptable — ' . $user->name)

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .comptable-avatar {
        width: 80px; height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        display: flex; align-items: center; justify-content: center;
        font-size: 2rem; font-weight: 700; color: white;
        flex-shrink: 0;
    }
    .info-row { display: flex; align-items: center; gap: 10px; padding: 10px 0; border-bottom: 1px solid var(--border-light); }
    .info-row:last-child { border-bottom: none; }
    .info-label { font-weight: 600; color: var(--text-secondary); min-width: 130px; font-size: .875rem; }
    .info-value { color: var(--text-primary); flex: 1; }
    .edit-input { border: none; border-bottom: 2px solid var(--primary); background: transparent; width: 100%; font-size: inherit; color: var(--text-primary); padding: 2px 4px; outline: none; display: none; }
    .save-btn { display: none; }
    #editToggle.editing ~ #cancelBtn { display: inline-block !important; }
    .perm-badge {
        display: inline-flex; align-items: center; gap: 5px;
        background: rgba(16,185,129,.1); color: #059669;
        border-radius: 20px; padding: 3px 10px; font-size: .78rem; margin: 3px;
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">

        <!-- Header -->
        <div class="dashboard-header">
            <div>
                <h1 class="page-title"><i class="fas fa-calculator me-2 text-success"></i>Fiche Comptable</h1>
            </div>
            <div class="header-actions d-flex gap-2">
                <a href="{{ route('esbtp.comptabilite.dashboard') }}" class="btn-acasi success">
                    <i class="fas fa-chart-line me-1"></i>Dashboard Compta
                </a>
                <a href="{{ route('esbtp.personnel.unified.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left me-1"></i>Retour
                </a>
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <div id="saveNotification" style="display:none;" class="alert alert-success rounded-3 mb-3">
            <i class="fas fa-check-circle me-2"></i><span id="saveNotificationText">Sauvegardé !</span>
        </div>

        <div class="row g-4">

            <!-- Profil -->
            <div class="col-12 col-lg-4">
                <div class="main-card p-4 text-center">
                    <div class="comptable-avatar mx-auto mb-3">
                        {{ strtoupper(substr($user->name, 0, 2)) }}
                    </div>
                    <h4 class="fw-bold mb-1" id="displayName">{{ $user->name }}</h4>
                    <span class="badge bg-success rounded-pill px-3">Comptable</span>

                    <div class="mt-3">
                        <span class="badge {{ $user->is_active ? 'bg-success' : 'bg-secondary' }} px-3 py-2">
                            <i class="fas fa-circle me-1" style="font-size:.5rem;"></i>
                            {{ $user->is_active ? 'Actif' : 'Inactif' }}
                        </span>
                    </div>

                    <div class="mt-4 d-flex gap-2 justify-content-center flex-wrap">
                        <button id="editToggle" class="btn btn-success px-3" onclick="startEdit()">
                            <i class="fas fa-edit me-1"></i>Modifier
                        </button>
                        <button id="saveBtn" class="btn btn-primary px-3 save-btn" onclick="saveChanges()">
                            <i class="fas fa-save me-1"></i>Sauvegarder
                        </button>
                        <button id="cancelBtn" class="btn btn-secondary px-3" style="display:none;" onclick="cancelEdit()">
                            <i class="fas fa-times me-1"></i>Annuler
                        </button>
                    </div>

                    <div class="mt-3">
                        <form method="POST" action="{{ route('esbtp.comptables.toggle-status', $user) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm {{ $user->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                    onclick="return confirm('Changer le statut de ce comptable ?')">
                                <i class="fas fa-{{ $user->is_active ? 'ban' : 'check' }} me-1"></i>
                                {{ $user->is_active ? 'Désactiver' : 'Activer' }}
                            </button>
                        </form>
                    </div>

                    <hr class="my-3">
                    <small class="text-muted">Créé le {{ $user->created_at->format('d/m/Y') }}</small>
                </div>
            </div>

            <!-- Informations -->
            <div class="col-12 col-lg-8">
                <div class="main-card p-4">
                    <h5 class="fw-bold mb-4 text-success"><i class="fas fa-user-edit me-2"></i>Informations du compte</h5>

                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-user me-2 text-success"></i>Nom complet</span>
                        <span class="info-value" data-field="name">{{ $user->name }}</span>
                        <input class="edit-input" type="text" data-field="name" value="{{ $user->name }}" placeholder="Nom complet">
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-envelope me-2 text-success"></i>Email</span>
                        <span class="info-value" data-field="email">{{ $user->email }}</span>
                        <input class="edit-input" type="email" data-field="email" value="{{ $user->email }}" placeholder="Email">
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-phone me-2 text-success"></i>Téléphone</span>
                        <span class="info-value" data-field="telephone">{{ $user->telephone ?: '—' }}</span>
                        <input class="edit-input" type="text" data-field="telephone" value="{{ $user->telephone }}" placeholder="Téléphone">
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-building me-2 text-success"></i>Département</span>
                        <span class="info-value" data-field="department">{{ $user->department ?: '—' }}</span>
                        <select class="edit-input form-select" data-field="department" style="display:none;">
                            <option value="">-- Sélectionner --</option>
                            <option value="Comptabilité" {{ $user->department === 'Comptabilité' ? 'selected' : '' }}>Comptabilité</option>
                            <option value="Finance" {{ $user->department === 'Finance' ? 'selected' : '' }}>Finance</option>
                            <option value="Audit" {{ $user->department === 'Audit' ? 'selected' : '' }}>Audit</option>
                        </select>
                    </div>
                </div>

                <!-- Permissions -->
                <div class="main-card p-4 mt-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="fw-bold mb-0 text-success"><i class="fas fa-shield-alt me-2"></i>Permissions actives</h5>
                        <a href="{{ route('esbtp.roles-permissions.index', ['role' => 'comptable']) }}" class="btn-acasi primary btn-sm">
                            <i class="fas fa-cog me-1"></i>Gérer les permissions
                        </a>
                    </div>
                    <div>
                        @php
                            $role = \Spatie\Permission\Models\Role::where('name', 'comptable')->first();
                            $perms = $role ? $role->permissions : collect();
                        @endphp
                        @forelse($perms as $perm)
                            <span class="perm-badge"><i class="fas fa-check"></i>{{ $perm->name }}</span>
                        @empty
                            <p class="text-muted">Aucune permission attribuée au rôle comptable.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Accès rapides -->
                <div class="main-card p-4 mt-4">
                    <h5 class="fw-bold mb-3 text-success"><i class="fas fa-rocket me-2"></i>Accès rapides pour ce comptable</h5>
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <a href="{{ route('esbtp.comptabilite.dashboard') }}" class="card border-0 shadow-sm p-3 text-center text-decoration-none hover-lift d-block">
                                <i class="fas fa-chart-line fa-2x text-success mb-2"></i>
                                <small class="fw-semibold text-dark">Dashboard</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="{{ route('esbtp.paiements.index') }}" class="card border-0 shadow-sm p-3 text-center text-decoration-none hover-lift d-block">
                                <i class="fas fa-money-bill-wave fa-2x text-primary mb-2"></i>
                                <small class="fw-semibold text-dark">Paiements</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="{{ route('esbtp.frais.index') }}" class="card border-0 shadow-sm p-3 text-center text-decoration-none hover-lift d-block">
                                <i class="fas fa-tags fa-2x text-warning mb-2"></i>
                                <small class="fw-semibold text-dark">Frais</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="{{ route('esbtp.roles-permissions.index', ['role' => 'comptable']) }}" class="card border-0 shadow-sm p-3 text-center text-decoration-none hover-lift d-block">
                                <i class="fas fa-sliders-h fa-2x text-danger mb-2"></i>
                                <small class="fw-semibold text-dark">Permissions</small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const updateUrl = "{{ route('esbtp.comptables.update', $user) }}";
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

function startEdit() {
    document.querySelectorAll('.info-value').forEach(el => { el.style.display = 'none'; });
    document.querySelectorAll('.edit-input').forEach(el => { el.style.display = ''; });
    document.getElementById('saveBtn').style.display = 'inline-block';
    document.getElementById('cancelBtn').style.display = 'inline-block';
    document.getElementById('editToggle').style.display = 'none';
}

function cancelEdit() {
    document.querySelectorAll('.info-value').forEach(el => { el.style.display = ''; });
    document.querySelectorAll('.edit-input').forEach(el => { el.style.display = 'none'; });
    document.getElementById('saveBtn').style.display = 'none';
    document.getElementById('cancelBtn').style.display = 'none';
    document.getElementById('editToggle').style.display = 'inline-block';
}

function saveChanges() {
    const data = { _method: 'PUT' };
    document.querySelectorAll('.edit-input').forEach(el => {
        const field = el.dataset.field;
        data[field] = el.value;
    });

    fetch(updateUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
        },
        body: JSON.stringify(data),
    })
    .then(r => r.json())
    .then(resp => {
        if (resp.success) {
            // Update displayed values
            document.querySelectorAll('.info-value').forEach(el => {
                const field = el.dataset.field;
                if (field) {
                    const input = document.querySelector(`.edit-input[data-field="${field}"]`);
                    el.textContent = input.value || '—';
                }
            });
            if (data.name) {
                document.getElementById('displayName').textContent = data.name;
            }
            cancelEdit();
            showNotification('Informations mises à jour avec succès.');
        } else {
            alert('Erreur lors de la sauvegarde.');
        }
    })
    .catch(() => alert('Erreur réseau lors de la sauvegarde.'));
}

function showNotification(msg) {
    const notif = document.getElementById('saveNotification');
    document.getElementById('saveNotificationText').textContent = msg;
    notif.style.display = 'block';
    setTimeout(() => { notif.style.display = 'none'; }, 3000);
}
</script>
@endpush
