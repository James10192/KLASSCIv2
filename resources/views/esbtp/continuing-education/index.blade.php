@extends('layouts.app')

@section('title', 'Formation Continue')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Formation Continue</h3>
                    <div class="card-tools">
                        <a href="{{ route('esbtp.continuing-education.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nouveau Programme
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <ul class="nav nav-tabs" id="programTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="active-tab" data-toggle="tab" href="#active" role="tab">
                                Programmes Actifs ({{ $activePrograms->count() }})
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="inactive-tab" data-toggle="tab" href="#inactive" role="tab">
                                Programmes Inactifs ({{ $inactivePrograms->count() }})
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="archived-tab" data-toggle="tab" href="#archived" role="tab">
                                Programmes Archivés ({{ $archivedPrograms->count() }})
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content" id="programTabsContent">
                        <!-- Active Programs -->
                        <div class="tab-pane fade show active" id="active" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Nom</th>
                                            <th>Département</th>
                                            <th>Cycle</th>
                                            <th>Durée</th>
                                            <th>Prix</th>
                                            <th>Date de début</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($activePrograms as $program)
                                            <tr>
                                                <td>{{ $program->code }}</td>
                                                <td>{{ $program->name }}</td>
                                                <td>{{ $program->department->name }}</td>
                                                <td>{{ $program->cycle->name }}</td>
                                                <td>{{ $program->duration_text }}</td>
                                                <td>{{ number_format($program->price, 0, ',', ' ') }} FCFA</td>
                                                <td>{{ $program->start_date->format('d/m/Y') }}</td>
                                                <td>
                                                    <a href="{{ route('esbtp.continuing-education.show', $program) }}" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('esbtp.continuing-education.edit', $program) }}" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('esbtp.continuing-education.destroy', $program) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir archiver ce programme ?')">
                                                            <i class="fas fa-archive"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center">Aucun programme actif trouvé.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Inactive Programs -->
                        <div class="tab-pane fade" id="inactive" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Nom</th>
                                            <th>Département</th>
                                            <th>Cycle</th>
                                            <th>Durée</th>
                                            <th>Prix</th>
                                            <th>Date de début</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($inactivePrograms as $program)
                                            <tr>
                                                <td>{{ $program->code }}</td>
                                                <td>{{ $program->name }}</td>
                                                <td>{{ $program->department->name }}</td>
                                                <td>{{ $program->cycle->name }}</td>
                                                <td>{{ $program->duration_text }}</td>
                                                <td>{{ number_format($program->price, 0, ',', ' ') }} FCFA</td>
                                                <td>{{ $program->start_date->format('d/m/Y') }}</td>
                                                <td>
                                                    <a href="{{ route('esbtp.continuing-education.show', $program) }}" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('esbtp.continuing-education.edit', $program) }}" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('esbtp.continuing-education.destroy', $program) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir archiver ce programme ?')">
                                                            <i class="fas fa-archive"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center">Aucun programme inactif trouvé.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Archived Programs -->
                        <div class="tab-pane fade" id="archived" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Nom</th>
                                            <th>Département</th>
                                            <th>Cycle</th>
                                            <th>Durée</th>
                                            <th>Prix</th>
                                            <th>Date de début</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($archivedPrograms as $program)
                                            <tr>
                                                <td>{{ $program->code }}</td>
                                                <td>{{ $program->name }}</td>
                                                <td>{{ $program->department->name }}</td>
                                                <td>{{ $program->cycle->name }}</td>
                                                <td>{{ $program->duration_text }}</td>
                                                <td>{{ number_format($program->price, 0, ',', ' ') }} FCFA</td>
                                                <td>{{ $program->start_date->format('d/m/Y') }}</td>
                                                <td>
                                                    <form action="{{ route('esbtp.continuing-education.restore', $program->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Êtes-vous sûr de vouloir restaurer ce programme ?')">
                                                            <i class="fas fa-trash-restore"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center">Aucun programme archivé trouvé.</td>
                                            </tr>
                                        @endforelse
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
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialize DataTables
        $('.table').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/French.json"
            }
        });
    });
</script>
@endpush
