@extends('layouts.app')

@section('title', 'Marquer les présences')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form action="{{ route('attendance.filter') }}" method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Classe</label>
                            <select name="class_id" class="form-select" required>
                                <option value="">Sélectionner une classe</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                        {{ $class->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date</label>
                            <input type="date" name="date" class="form-control" value="{{ request('date', date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Matière</label>
                            <select name="course_id" class="form-select" required>
                                <option value="">Sélectionner une matière</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                                        {{ $course->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i> Filtrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if(isset($students) && $students->count() > 0)
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-0">Liste des étudiants</h5>
                    <small class="text-muted">{{ $students->count() }} étudiants</small>
                </div>
                <div class="d-flex gap-2">
                    <button id="mark-all-present" class="btn btn-success">
                        <i class="fas fa-check-circle me-1"></i> Tous présents
                    </button>
                    <button id="mark-all-absent" class="btn btn-danger">
                        <i class="fas fa-times-circle me-1"></i> Tous absents
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">#</th>
                            <th>Étudiant</th>
                            <th>Numéro</th>
                            <th>Statut</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $index => $student)
                            @php
                                $attendance = $attendances->get($student->id);
                                $status = $attendance ? $attendance->status : null;
                            @endphp
                            <tr data-student-id="{{ $student->id }}" class="{{ $status ? 'marked' : '' }}">
                                <td class="ps-4">{{ $index + 1 }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle bg-primary-light text-primary me-2">
                                            {{ strtoupper(substr($student->user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <h6 class="mb-0">{{ $student->user->name }}</h6>
                                            <small class="text-muted">{{ $student->class->name ?? 'N/A' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $student->student_id }}</td>
                                <td class="status-cell">
                                    @if($status)
                                        @if($status === 'present')
                                            <span class="badge bg-success-light text-success">Présent</span>
                                        @elseif($status === 'absent')
                                            <span class="badge bg-danger-light text-danger">Absent</span>
                                        @elseif($status === 'late')
                                            <span class="badge bg-warning-light text-warning">En retard</span>
                                        @elseif($status === 'excused')
                                            <span class="badge bg-info-light text-info">Excusé</span>
                                        @endif
                                    @else
                                        <span class="badge bg-secondary-light text-secondary">Non marqué</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group" role="group">
                                        <button type="button"
                                            class="btn btn-sm {{ $status === 'present' ? 'btn-success' : 'btn-outline-success' }} attendance-btn"
                                            data-student-id="{{ $student->id }}"
                                            data-date="{{ $date }}"
                                            data-status="present"
                                            data-original-status="{{ $status }}">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button type="button"
                                            class="btn btn-sm {{ $status === 'late' ? 'btn-warning' : 'btn-outline-warning' }} attendance-btn"
                                            data-student-id="{{ $student->id }}"
                                            data-date="{{ $date }}"
                                            data-status="late"
                                            data-original-status="{{ $status }}">
                                            <i class="fas fa-clock"></i>
                                        </button>
                                        <button type="button"
                                            class="btn btn-sm {{ $status === 'absent' ? 'btn-danger' : 'btn-outline-danger' }} attendance-btn"
                                            data-student-id="{{ $student->id }}"
                                            data-date="{{ $date }}"
                                            data-status="absent"
                                            data-original-status="{{ $status }}">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <button type="button"
                                            class="btn btn-sm {{ $status === 'excused' ? 'btn-info' : 'btn-outline-info' }} attendance-btn"
                                            data-student-id="{{ $student->id }}"
                                            data-date="{{ $date }}"
                                            data-status="excused"
                                            data-original-status="{{ $status }}">
                                            <i class="fas fa-file-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @else
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Veuillez sélectionner une classe et une date pour afficher la liste des étudiants.
        </div>
    @endif
</div>
@endsection

@push('styles')
<style>
    .avatar-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
    }
    .bg-primary-light {
        background-color: rgba(0, 123, 255, 0.1);
    }
    .bg-success-light {
        background-color: rgba(40, 167, 69, 0.1);
    }
    .bg-danger-light {
        background-color: rgba(220, 53, 69, 0.1);
    }
    .bg-warning-light {
        background-color: rgba(255, 193, 7, 0.1);
    }
    .bg-info-light {
        background-color: rgba(23, 162, 184, 0.1);
    }
    .bg-secondary-light {
        background-color: rgba(108, 117, 125, 0.1);
    }
    .badge {
        font-weight: 500;
        padding: 0.5em 0.75em;
    }
    .btn-group .btn {
        padding: 0.375rem 0.75rem;
    }
    .marked {
        background-color: rgba(0, 0, 0, 0.01);
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const markAllPresent = document.getElementById('mark-all-present');
    const markAllAbsent = document.getElementById('mark-all-absent');
    const attendanceButtons = document.querySelectorAll('.attendance-btn');

    // Fonction pour mettre à jour le statut
    function updateAttendance(studentId, date, status) {
        fetch('/attendance/mark', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ student_id: studentId, date, status })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const row = document.querySelector(`tr[data-student-id="${studentId}"]`);
                const statusCell = row.querySelector('.status-cell');
                const buttons = row.querySelectorAll('.attendance-btn');

                // Mise à jour du badge de statut
                let badgeClass = '';
                let badgeText = '';

                switch(status) {
                    case 'present':
                        badgeClass = 'bg-success-light text-success';
                        badgeText = 'Présent';
                        break;
                    case 'absent':
                        badgeClass = 'bg-danger-light text-danger';
                        badgeText = 'Absent';
                        break;
                    case 'late':
                        badgeClass = 'bg-warning-light text-warning';
                        badgeText = 'En retard';
                        break;
                    case 'excused':
                        badgeClass = 'bg-info-light text-info';
                        badgeText = 'Excusé';
                        break;
                }

                statusCell.innerHTML = `<span class="badge ${badgeClass}">${badgeText}</span>`;

                // Mise à jour des boutons
                buttons.forEach(button => {
                    const buttonStatus = button.dataset.status;
                    if (buttonStatus === status) {
                        button.classList.remove('btn-outline-' + getButtonColor(buttonStatus));
                        button.classList.add('btn-' + getButtonColor(buttonStatus));
                    } else {
                        button.classList.remove('btn-' + getButtonColor(buttonStatus));
                        button.classList.add('btn-outline-' + getButtonColor(buttonStatus));
                    }
                });

                row.classList.add('marked');
            }
        })
        .catch(error => debugError('Error:', error));
    }

    // Fonction pour obtenir la couleur du bouton selon le statut
    function getButtonColor(status) {
        switch(status) {
            case 'present': return 'success';
            case 'absent': return 'danger';
            case 'late': return 'warning';
            case 'excused': return 'info';
            default: return 'secondary';
        }
    }

    // Gestionnaire d'événements pour les boutons individuels
    attendanceButtons.forEach(button => {
        button.addEventListener('click', function() {
            const studentId = this.dataset.studentId;
            const date = this.dataset.date;
            const status = this.dataset.status;
            updateAttendance(studentId, date, status);
        });
    });

    // Gestionnaire pour "Tous présents"
    markAllPresent.addEventListener('click', function() {
        if (confirm('Êtes-vous sûr de vouloir marquer tous les étudiants comme présents ?')) {
            document.querySelectorAll('tr[data-student-id]').forEach(row => {
                const studentId = row.dataset.studentId;
                const date = row.querySelector('.attendance-btn').dataset.date;
                updateAttendance(studentId, date, 'present');
            });
        }
    });

    // Gestionnaire pour "Tous absents"
    markAllAbsent.addEventListener('click', function() {
        if (confirm('Êtes-vous sûr de vouloir marquer tous les étudiants comme absents ?')) {
            document.querySelectorAll('tr[data-student-id]').forEach(row => {
                const studentId = row.dataset.studentId;
                const date = row.querySelector('.attendance-btn').dataset.date;
                updateAttendance(studentId, date, 'absent');
            });
        }
    });
});
</script>
@endpush
