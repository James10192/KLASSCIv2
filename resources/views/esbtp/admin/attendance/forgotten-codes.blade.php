@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Gestion des Codes Oubliés</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Generate Manual Code Section -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">Générer un Code Manuel</h5>
                                </div>
                                <div class="card-body">
                                    <form id="generateCodeForm">
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
                                            <label for="reason" class="form-label">Raison</label>
                                            <textarea class="form-control" id="reason" name="reason" rows="2" required></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-key"></i> Générer un Code
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Mark Manual Attendance Section -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">Marquer une Présence Manuellement</h5>
                                </div>
                                <div class="card-body">
                                    <form id="markAttendanceForm">
                                        <div class="mb-3">
                                            <label for="manual_teacher_id" class="form-label">Enseignant</label>
                                            <select class="form-select" id="manual_teacher_id" name="teacher_id" required>
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
                                            <label for="manual_reason" class="form-label">Raison</label>
                                            <textarea class="form-control" id="manual_reason" name="reason" rows="2" required></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-check"></i> Marquer la Présence
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Codes Section -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">Codes Récents</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Code</th>
                                                    <th>Généré le</th>
                                                    <th>Utilisé par</th>
                                                    <th>Statut</th>
                                                    <th>Raison</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($recentCodes as $code)
                                                    <tr>
                                                        <td>{{ $code->code }}</td>
                                                        <td>{{ $code->created_at->format('H:i:s') }}</td>
                                                        <td>{{ $code->used_by ? $code->used_by->user->name : 'Non utilisé' }}</td>
                                                        <td>
                                                            @if($code->is_used)
                                                                <span class="badge bg-success">Utilisé</span>
                                                            @elseif($code->expires_at < now())
                                                                <span class="badge bg-danger">Expiré</span>
                                                            @else
                                                                <span class="badge bg-warning">En attente</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $code->manual_reason ?? 'N/A' }}</td>
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

<!-- Code Generated Modal -->
<div class="modal fade" id="codeGeneratedModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Code Généré</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <h3 class="code-display mb-3"></h3>
                    <p class="text-muted">Communiquez ce code à l'enseignant</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const generateCodeForm = document.getElementById('generateCodeForm');
    const markAttendanceForm = document.getElementById('markAttendanceForm');
    const codeGeneratedModal = new bootstrap.Modal(document.getElementById('codeGeneratedModal'));

    generateCodeForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        try {
            const response = await fetch('{{ route("esbtp.admin.attendance.generate-manual-code") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    teacher_id: this.teacher_id.value,
                    reason: this.reason.value
                })
            });

            const data = await response.json();

            if (data.success) {
                document.querySelector('.code-display').textContent = data.code;
                codeGeneratedModal.show();
                this.reset();
            } else {
                alert('Erreur: ' + data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Une erreur est survenue');
        }
    });

    markAttendanceForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        try {
            const response = await fetch('{{ route("esbtp.admin.attendance.mark-manual") }}', {
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
});
</script>
@endpush
