@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Marquage Manuel des Présences</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Individual Marking Section -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">Marquage Individuel</h5>
                                </div>
                                <div class="card-body">
                                    <form id="individualMarkingForm">
                                        <div class="mb-3">
                                            <label for="teacher_id" class="form-label">Enseignant</label>
                                            <select class="form-select" id="teacher_id" name="teacher_id" required>
                                                <option value="">Sélectionner un enseignant</option>
                                                @foreach($teachers as $teacher)
                                                    <option value="{{ $teacher->id }}">{{ $teacher->user->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="date" class="form-label">Date</label>
                                            <input type="date" class="form-control" id="date" name="date" required
                                                value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}">
                                        </div>
                                        <div class="mb-3">
                                            <label for="status" class="form-label">Statut</label>
                                            <select class="form-select" id="status" name="status" required>
                                                <option value="present">Présent</option>
                                                <option value="late">En retard</option>
                                                <option value="absent">Absent</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="reason" class="form-label">Raison</label>
                                            <textarea class="form-control" id="reason" name="reason" rows="2" required></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-check"></i> Marquer la Présence
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Bulk Marking Section -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">Marquage en Masse</h5>
                                </div>
                                <div class="card-body">
                                    <form id="bulkMarkingForm">
                                        <div class="mb-3">
                                            <label class="form-label">Enseignants</label>
                                            <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                                @foreach($teachers as $teacher)
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox"
                                                            name="teacher_ids[]" value="{{ $teacher->id }}"
                                                            id="teacher_{{ $teacher->id }}">
                                                        <label class="form-check-label" for="teacher_{{ $teacher->id }}">
                                                            {{ $teacher->user->name }}
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="bulk_date" class="form-label">Date</label>
                                            <input type="date" class="form-control" id="bulk_date" name="date" required
                                                value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}">
                                        </div>
                                        <div class="mb-3">
                                            <label for="bulk_status" class="form-label">Statut</label>
                                            <select class="form-select" id="bulk_status" name="status" required>
                                                <option value="present">Présent</option>
                                                <option value="late">En retard</option>
                                                <option value="absent">Absent</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="bulk_reason" class="form-label">Raison</label>
                                            <textarea class="form-control" id="bulk_reason" name="reason" rows="2" required></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-users"></i> Marquer les Présences
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Manual Attendances Section -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">Présences Manuelles Récentes</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Enseignant</th>
                                                    <th>Statut</th>
                                                    <th>Raison</th>
                                                    <th>Marqué par</th>
                                                    <th>Marqué le</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($recentManualAttendances as $attendance)
                                                    <tr>
                                                        <td>{{ $attendance->date->format('d/m/Y') }}</td>
                                                        <td>{{ $attendance->teacher->user->name }}</td>
                                                        <td>
                                                            @if($attendance->status === 'present')
                                                                <span class="badge bg-success">Présent</span>
                                                            @elseif($attendance->status === 'late')
                                                                <span class="badge bg-warning">En retard</span>
                                                            @else
                                                                <span class="badge bg-danger">Absent</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $attendance->manual_reason }}</td>
                                                        <td>{{ $attendance->markedBy->user->name }}</td>
                                                        <td>{{ $attendance->created_at->format('d/m/Y H:i') }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
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
document.addEventListener('DOMContentLoaded', function() {
    const individualForm = document.getElementById('individualMarkingForm');
    const bulkForm = document.getElementById('bulkMarkingForm');

    individualForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        try {
            const response = await fetch('{{ route("esbtp.admin.attendance.manual.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    teacher_id: this.teacher_id.value,
                    date: this.date.value,
                    status: this.status.value,
                    reason: this.reason.value
                })
            });

            const data = await response.json();

            if (data.success) {
                alert('Présence marquée avec succès');
                this.reset();
                window.location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Une erreur est survenue');
        }
    });

    bulkForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const selectedTeachers = Array.from(this.querySelectorAll('input[name="teacher_ids[]"]:checked'))
            .map(checkbox => checkbox.value);

        if (selectedTeachers.length === 0) {
            alert('Veuillez sélectionner au moins un enseignant');
            return;
        }

        try {
            const response = await fetch('{{ route("esbtp.admin.attendance.manual.bulk") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    teacher_ids: selectedTeachers,
                    date: this.date.value,
                    status: this.status.value,
                    reason: this.reason.value
                })
            });

            const data = await response.json();

            if (data.success) {
                alert(data.message);
                this.reset();
                window.location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Une erreur est survenue');
        }
    });
});
</script>
@endpush
