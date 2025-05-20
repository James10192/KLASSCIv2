@extends('layouts.app')

@section('title', 'Historique des présences')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Historique des présences</h4>
        </div>
        <div class="card-body">
            <!-- Filtres -->
            <form action="{{ route('esbtp.teacher.attendance.history') }}" method="GET" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">Date de début</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">Date de fin</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="{{ $endDate }}">
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Statut</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Tous</option>
                            <option value="validated" {{ $status === 'validated' ? 'selected' : '' }}>Validé</option>
                            <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>En attente</option>
                            <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Rejeté</option>
                        </select>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                    </div>
                </div>
            </form>

            <!-- Tableau des présences -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Heure</th>
                            <th>Code</th>
                            <th>Statut</th>
                            <th>IP</th>
                            <th>Appareil</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendances as $attendance)
                            <tr>
                                <td>{{ $attendance->marked_at->format('d/m/Y') }}</td>
                                <td>{{ $attendance->marked_at->format('H:i') }}</td>
                                <td>{{ $attendance->code->code }}</td>
                                <td>
                                    <span class="badge bg-{{ $attendance->validation_status === 'validated' ? 'success' : ($attendance->validation_status === 'rejected' ? 'danger' : 'warning') }}">
                                        {{ $attendance->validation_status === 'validated' ? 'Validé' : ($attendance->validation_status === 'rejected' ? 'Rejeté' : 'En attente') }}
                                    </span>
                                </td>
                                <td>{{ $attendance->ip_address }}</td>
                                <td>
                                    <small class="text-muted">
                                        {{ $attendance->device_info['browser'] ?? 'N/A' }} /
                                        {{ $attendance->device_info['platform'] ?? 'N/A' }}
                                    </small>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">Aucun enregistrement trouvé</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-4">
                {{ $attendances->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialiser les datepickers
        $('input[type="date"]').on('change', function() {
            // Vérifier que la date de fin n'est pas antérieure à la date de début
            var startDate = $('input[name="start_date"]').val();
            var endDate = $('input[name="end_date"]').val();

            if (startDate && endDate && endDate < startDate) {
                alert('La date de fin ne peut pas être antérieure à la date de début.');
                $(this).val('');
            }
        });
    });
</script>
@endpush
