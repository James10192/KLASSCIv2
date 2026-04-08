@extends('layouts.app')

@section('title', 'Modifier l\'enseignant — ' . $teacher->user->name . ' — KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* ===================================================================
   TEACHER EDIT — Premium Hero + Form — KLASSCI Design System
   Namespace: te- (teacher-edit)
=================================================================== */
:root {
    --te-blue:      #0453cb;
    --te-blue-2:    #5e91de;
    --te-surface:   #f4f7fb;
    --te-card:      #ffffff;
    --te-border:    #e2e8f0;
    --te-text:      #1e293b;
    --te-muted:     #64748b;
    --te-radius:    12px;
    --te-radius-lg: 20px;
    --te-shadow:    0 1px 3px rgba(0,0,0,.08), 0 4px 16px rgba(0,0,0,.06);
}

.te-page { background: var(--te-surface); min-height: 100vh; }

/* -- HERO ---------------------------------------------------------- */
.te-hero {
    position: relative;
    background: linear-gradient(135deg, var(--te-blue) 0%, var(--te-blue-2) 100%);
    padding: 0;
}
.te-hero::before {
    content: '';
    position: absolute; inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg width='24' height='24' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='12' cy='12' r='1.5' fill='rgba(255,255,255,0.1)'/%3E%3C/svg%3E");
    pointer-events: none; overflow: hidden;
}
.te-hero::after {
    content: '';
    position: absolute; bottom: 0; left: 0; right: 0; height: 48px;
    background: linear-gradient(to top, var(--te-surface) 0%, transparent 100%);
}
.te-hero-inner {
    position: relative; z-index: 2;
    max-width: 1280px; margin: 0 auto;
    padding: 32px 32px 28px;
    display: flex; align-items: center; gap: 24px; flex-wrap: wrap;
}
.te-hero-avatar {
    width: 72px; height: 72px; border-radius: 50%;
    border: 3px solid rgba(255,255,255,.6);
    background: rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.6rem; font-weight: 700; color: rgba(255,255,255,.9);
    box-shadow: 0 4px 20px rgba(0,0,0,.22);
    backdrop-filter: blur(4px); flex-shrink: 0;
}
.te-hero-text { flex: 1; min-width: 200px; color: #fff; }
.te-hero-name { font-size: 1.4rem; font-weight: 800; margin: 0 0 4px; letter-spacing: -.02em; }
.te-hero-sub { font-size: .85rem; opacity: .8; margin: 0; }
.te-hero-btns { display: flex; gap: 8px; margin-left: auto; flex-wrap: wrap; }
.te-hero-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 9px 18px; border-radius: 8px; font-size: .82rem; font-weight: 600;
    text-decoration: none; cursor: pointer; border: none; transition: all .18s;
    white-space: nowrap;
}
.te-hero-btn.ghost { background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.35); }
.te-hero-btn.ghost:hover { background: rgba(255,255,255,.25); }

/* -- Form container ------------------------------------------------ */
.te-form-wrap {
    max-width: 1280px; margin: 0 auto;
    padding: 28px 24px 60px;
}
.te-form-card {
    background: var(--te-card); border: 1px solid var(--te-border);
    border-radius: var(--te-radius-lg); padding: 32px;
    box-shadow: var(--te-shadow);
}
.te-section-title {
    display: flex; align-items: center; gap: 10px;
    font-size: 1rem; font-weight: 700; color: var(--te-text);
    margin: 0 0 20px; padding-bottom: 12px;
    border-bottom: 1px solid var(--te-border);
}
.te-section-icon {
    width: 32px; height: 32px; border-radius: 8px;
    background: linear-gradient(135deg, var(--te-blue) 0%, var(--te-blue-2) 100%);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .8rem; flex-shrink: 0;
}
.te-form-actions {
    display: flex; justify-content: flex-end; gap: 12px;
    padding-top: 20px; border-top: 1px solid var(--te-border);
    margin-top: 24px;
}
.te-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 10px 24px; border-radius: 8px; font-size: .85rem; font-weight: 600;
    text-decoration: none; cursor: pointer; border: none; transition: all .18s;
}
.te-btn.secondary { background: var(--te-surface); color: var(--te-text); border: 1px solid var(--te-border); }
.te-btn.secondary:hover { border-color: var(--te-blue); color: var(--te-blue); }
.te-btn.primary {
    background: linear-gradient(135deg, var(--te-blue) 0%, var(--te-blue-2) 100%);
    color: #fff; box-shadow: 0 4px 12px rgba(4,83,203,.3);
}
.te-btn.primary:hover { box-shadow: 0 6px 20px rgba(4,83,203,.4); transform: translateY(-1px); }

@media (max-width: 768px) {
    .te-hero-inner { padding: 24px 16px; flex-direction: column; text-align: center; }
    .te-hero-btns { margin-left: 0; justify-content: center; }
    .te-form-wrap { padding: 20px 16px 40px; }
    .te-form-card { padding: 20px; }
}
</style>
@endsection

@section('content')
<div class="te-page">

    {{-- ============================================================
         HERO HEADER
    ============================================================= --}}
    <div class="te-hero">
        <div class="te-hero-inner">
            <div class="te-hero-avatar">
                {{ strtoupper(substr($teacher->user->name, 0, 2)) }}
            </div>
            <div class="te-hero-text">
                <h1 class="te-hero-name">Modifier : {{ $teacher->user->name }}</h1>
                <p class="te-hero-sub">{{ $teacher->grade ?? 'Enseignant' }} &middot; {{ $teacher->department->name ?? 'Sans departement' }}</p>
            </div>
            <div class="te-hero-btns">
                <a href="{{ route('esbtp.teachers.show', $teacher->id) }}" class="te-hero-btn ghost">
                    <i class="fas fa-eye"></i> Voir le profil
                </a>
                <a href="{{ route('esbtp.teachers.index') }}" class="te-hero-btn ghost">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>
        </div>
    </div>

    {{-- ============================================================
         FORM
    ============================================================= --}}
    <div class="te-form-wrap">
        <div class="te-form-card">

            @if ($errors->any())
            <div class="alert alert-danger" style="border-radius: 10px; margin-bottom: 24px;">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form action="{{ route('esbtp.teachers.update', $teacher->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                {{-- Section: Identification --}}
                <div class="te-section-title">
                    <div class="te-section-icon"><i class="fas fa-id-card"></i></div>
                    Informations d'identification
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="name" class="form-label">Nom complet <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $teacher->user->name) }}" required>
                            @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $teacher->user->email) }}" required>
                            @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="username" class="form-label">Nom d'utilisateur <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username', $teacher->user->username) }}" required>
                            @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="phone" class="form-label">Telephone</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $teacher->user->phone) }}">
                            @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="password" class="form-label">Nouveau mot de passe</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password">
                            <small class="text-muted">Laissez vide pour conserver le mot de passe actuel</small>
                            @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="password_confirmation" class="form-label">Confirmer le nouveau mot de passe</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                        </div>
                    </div>
                </div>

                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ $teacher->user->is_active ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Compte actif</label>
                </div>

                {{-- Section: Professionnel --}}
                <div class="te-section-title" style="margin-top: 8px;">
                    <div class="te-section-icon"><i class="fas fa-briefcase"></i></div>
                    Informations professionnelles
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="employee_id" class="form-label">Numero d'employe</label>
                            <input type="text" class="form-control @error('employee_id') is-invalid @enderror" id="employee_id" name="employee_id" value="{{ old('employee_id', $teacher->employee_id) }}">
                            @error('employee_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="department_id" class="form-label">Departement <span class="text-danger">*</span></label>
                            <select class="form-select @error('department_id') is-invalid @enderror" id="department_id" name="department_id" required>
                                <option value="">Selectionner un departement</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}" {{ old('department_id', $teacher->department_id) == $department->id ? 'selected' : '' }}>
                                        {{ $department->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="laboratory_id" class="form-label">Laboratoire</label>
                            <select class="form-select @error('laboratory_id') is-invalid @enderror" id="laboratory_id" name="laboratory_id">
                                <option value="">Selectionner un laboratoire</option>
                                @foreach($laboratories as $laboratory)
                                    <option value="{{ $laboratory->id }}" {{ old('laboratory_id', $teacher->laboratory_id) == $laboratory->id ? 'selected' : '' }}>
                                        {{ $laboratory->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('laboratory_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="matricule" class="form-label">Matricule <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('matricule') is-invalid @enderror" id="matricule" name="matricule" value="{{ old('matricule', $teacher->matricule) }}" required>
                            @error('matricule')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="grade" class="form-label">Grade</label>
                            <select class="form-select @error('grade') is-invalid @enderror" id="grade" name="grade">
                                <option value="">Selectionner un grade</option>
                                <option value="Professeur" {{ old('grade', $teacher->grade) == 'Professeur' ? 'selected' : '' }}>Professeur</option>
                                <option value="Maitre de conferences" {{ old('grade', $teacher->grade) == 'Maitre de conferences' ? 'selected' : '' }}>Maitre de conferences</option>
                                <option value="Assistant" {{ old('grade', $teacher->grade) == 'Assistant' ? 'selected' : '' }}>Assistant</option>
                                <option value="Vacataire" {{ old('grade', $teacher->grade) == 'Vacataire' ? 'selected' : '' }}>Vacataire</option>
                                <option value="Autre" {{ old('grade', $teacher->grade) == 'Autre' ? 'selected' : '' }}>Autre</option>
                            </select>
                            @error('grade')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="status" class="form-label">Statut <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="">Selectionner un statut</option>
                                @foreach($statuses as $value => $label)
                                    <option value="{{ $value }}" {{ old('status', $teacher->status) == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="teaching_hours_due" class="form-label">Heures dues</label>
                                    <input type="number" class="form-control @error('teaching_hours_due') is-invalid @enderror" id="teaching_hours_due" name="teaching_hours_due" value="{{ old('teaching_hours_due', (int)$teacher->teaching_hours_due) }}">
                                    @error('teaching_hours_due')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="teaching_hours_done" class="form-label">Heures effectuees</label>
                                    <input type="number" class="form-control @error('teaching_hours_done') is-invalid @enderror" id="teaching_hours_done" name="teaching_hours_done" value="{{ old('teaching_hours_done', $teacher->teaching_hours_done) }}">
                                    @error('teaching_hours_done')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Section: Enseignement --}}
                <div class="te-section-title" style="margin-top: 8px;">
                    <div class="te-section-icon"><i class="fas fa-chalkboard"></i></div>
                    Enseignement et recherche
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="office_location" class="form-label">Emplacement du bureau</label>
                            <input type="text" class="form-control @error('office_location') is-invalid @enderror" id="office_location" name="office_location" value="{{ old('office_location', $teacher->office_location) }}">
                            @error('office_location')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="specialties" class="form-label">Specialites (separees par des virgules)</label>
                            <input type="text" class="form-control @error('specialties') is-invalid @enderror" id="specialties" name="specialties" value="{{ old('specialties', is_array($teacher->specialties) ? implode(', ', $teacher->specialties) : $teacher->specialties) }}">
                            @error('specialties')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-12">
                        <div class="form-group mb-3">
                            <label for="bio" class="form-label">Biographie</label>
                            <textarea class="form-control @error('bio') is-invalid @enderror" id="bio" name="bio" rows="3">{{ old('bio', $teacher->bio) }}</textarea>
                            @error('bio')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-12">
                        <div class="form-group mb-3">
                            <label for="research_interests" class="form-label">Interets de recherche (separes par des virgules)</label>
                            <input type="text" class="form-control @error('research_interests') is-invalid @enderror" id="research_interests" name="research_interests" value="{{ old('research_interests', is_array($teacher->research_interests) ? implode(', ', $teacher->research_interests) : $teacher->research_interests) }}">
                            @error('research_interests')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="website" class="form-label">Site web</label>
                            <input type="url" class="form-control @error('website') is-invalid @enderror" id="website" name="website" value="{{ old('website', $teacher->website) }}">
                            @error('website')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="te-form-actions">
                    <a href="{{ route('esbtp.teachers.show', $teacher->id) }}" class="te-btn secondary">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                    <button type="submit" class="te-btn primary">
                        <i class="fas fa-save"></i> Mettre a jour
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    'use strict';

    // Handle department selection affecting laboratories
    var departmentSelect = document.getElementById('department_id');
    var laboratorySelect = document.getElementById('laboratory_id');

    if (departmentSelect && laboratorySelect) {
        departmentSelect.addEventListener('change', function() {
            var departmentId = this.value;
            laboratorySelect.innerHTML = '<option value="">Selectionner un laboratoire</option>';

            if (departmentId) {
                fetch('/api/departments/' + departmentId + '/laboratories')
                    .then(function(response) { return response.json(); })
                    .then(function(data) {
                        data.forEach(function(lab) {
                            var option = document.createElement('option');
                            option.value = lab.id;
                            option.textContent = lab.name;
                            laboratorySelect.appendChild(option);
                        });
                    })
                    .catch(function(error) { console.error('Error fetching laboratories:', error); });
            }
        });
    }
})();
</script>
@endpush
