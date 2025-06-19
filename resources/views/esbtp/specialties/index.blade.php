@extends('layouts.app')

@section('title', 'Gestion des Spécialités')

@section('content')
<div class="container-fluid px-0">
    <div class="premium-header-glass mb-4 animate__animated animate__fadeInDown d-flex align-items-center justify-content-between" style="background: linear-gradient(90deg, #0453cb 0%, #1b64d4 100%); border-radius: 22px; box-shadow: 0 8px 32px 0 rgba(4,83,203,0.18); padding: 2.2rem 2.5rem 2rem 2.5rem; color: #fff;">
        <div class="d-flex align-items-center gap-3">
            <div class="premium-header-icon bg-white bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 64px; height: 64px; box-shadow: 0 4px 24px 0 rgba(4,83,203,0.12);">
                <i class="fas fa-layer-group fa-2x text-white"></i>
            </div>
            <div>
                <h1 class="mb-1 fw-bold" style="font-size:2.2rem; letter-spacing:-1px;">Gestion des Spécialités</h1>
                <div class="fs-5 text-white-50">Liste, création et gestion des spécialités académiques</div>
            </div>
        </div>
        <a href="{{ route('esbtp.specialties.create') }}" class="btn btn-lg btn-glass-premium shadow-lg animate__animated animate__fadeIn animate__delay-1s">
            <i class="fas fa-plus me-2"></i> Nouvelle Spécialité
        </a>
    </div>

    <div class="row justify-content-center animate__animated animate__fadeInUp animate__faster">
        <div class="col-12">
            <!-- Alertes de session -->
            @if(session('success'))
                <div class="alert alert-glass alert-success d-flex align-items-center mb-4 animate__animated animate__fadeInDown">
                    <i class="fas fa-check-circle fa-2x me-3"></i>
                    <div>{{ session('success') }}</div>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-glass alert-danger d-flex align-items-center mb-4 animate__animated animate__fadeInDown">
                    <i class="fas fa-times-circle fa-2x me-3"></i>
                    <div>{{ session('error') }}</div>
                </div>
            @endif

            <div class="glass-card p-4 mb-4" style="border-radius: 18px; box-shadow: 0 8px 32px 0 rgba(4,83,203,0.10); background: rgba(255,255,255,0.18); backdrop-filter: blur(8px);">
                <ul class="nav nav-tabs premium-tabs mb-3" id="specialtyTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="active-tab" data-toggle="tab" href="#active" role="tab" aria-controls="active" aria-selected="true">
                            Actives <span class="badge bg-success ms-1">{{ $activeSpecialties->count() }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="inactive-tab" data-toggle="tab" href="#inactive" role="tab" aria-controls="inactive" aria-selected="false">
                            Inactives <span class="badge bg-warning text-dark ms-1">{{ $inactiveSpecialties->count() }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="archived-tab" data-toggle="tab" href="#archived" role="tab" aria-controls="archived" aria-selected="false">
                            Archivées <span class="badge bg-danger ms-1">{{ $archivedSpecialties->count() }}</span>
                        </a>
                    </li>
                </ul>
                <div class="tab-content mt-3" id="specialtyTabsContent">
                    <!-- Spécialités actives -->
                    <div class="tab-pane fade show active" id="active" role="tabpanel" aria-labelledby="active-tab">
                        <div class="table-responsive">
                            <table class="table premium-table align-middle mb-0">
                                <thead class="sticky-top bg-white bg-opacity-75" style="backdrop-filter: blur(2px);">
                                    <tr>
                                        <th class="text-primary">ID</th>
                                        <th>Nom</th>
                                        <th>Code</th>
                                        <th>Cycle</th>
                                        <th>Département</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($activeSpecialties as $specialty)
                                        <tr class="glass-row">
                                            <td class="fw-bold text-primary">{{ $specialty->id }}</td>
                                            <td>{{ $specialty->name }}</td>
                                            <td><span class="badge bg-blue-gradient text-white">{{ $specialty->code }}</span></td>
                                            <td><span class="badge bg-info text-white">{{ $specialty->cycle->name ?? 'Non défini' }}</span></td>
                                            <td><span class="badge bg-secondary text-white">{{ $specialty->department->name ?? 'Non défini' }}</span></td>
                                            <td class="text-end">
                                                <div class="btn-group">
                                                    <a href="{{ route('esbtp.specialties.show', $specialty->id) }}" class="btn btn-glass-info btn-sm me-1"><i class="fas fa-eye"></i></a>
                                                    <a href="{{ route('esbtp.specialties.edit', $specialty->id) }}" class="btn btn-glass-warning btn-sm me-1"><i class="fas fa-edit"></i></a>
                                                    <form action="{{ route('esbtp.specialties.destroy', $specialty->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir archiver cette spécialité?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-glass-danger btn-sm"><i class="fas fa-trash"></i></button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- Spécialités inactives -->
                    <div class="tab-pane fade" id="inactive" role="tabpanel" aria-labelledby="inactive-tab">
                        <div class="table-responsive">
                            <table class="table premium-table align-middle mb-0">
                                <thead class="sticky-top bg-white bg-opacity-75" style="backdrop-filter: blur(2px);">
                                    <tr>
                                        <th class="text-primary">ID</th>
                                        <th>Nom</th>
                                        <th>Code</th>
                                        <th>Cycle</th>
                                        <th>Département</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($inactiveSpecialties as $specialty)
                                        <tr class="glass-row">
                                            <td class="fw-bold text-primary">{{ $specialty->id }}</td>
                                            <td>{{ $specialty->name }}</td>
                                            <td><span class="badge bg-blue-gradient text-white">{{ $specialty->code }}</span></td>
                                            <td><span class="badge bg-info text-white">{{ $specialty->cycle->name ?? 'Non défini' }}</span></td>
                                            <td><span class="badge bg-secondary text-white">{{ $specialty->department->name ?? 'Non défini' }}</span></td>
                                            <td class="text-end">
                                                <div class="btn-group">
                                                    <a href="{{ route('esbtp.specialties.show', $specialty->id) }}" class="btn btn-glass-info btn-sm me-1"><i class="fas fa-eye"></i></a>
                                                    <a href="{{ route('esbtp.specialties.edit', $specialty->id) }}" class="btn btn-glass-warning btn-sm me-1"><i class="fas fa-edit"></i></a>
                                                    <form action="{{ route('esbtp.specialties.destroy', $specialty->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir archiver cette spécialité?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-glass-danger btn-sm"><i class="fas fa-trash"></i></button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- Spécialités archivées -->
                    <div class="tab-pane fade" id="archived" role="tabpanel" aria-labelledby="archived-tab">
                        <div class="table-responsive">
                            <table class="table premium-table align-middle mb-0">
                                <thead class="sticky-top bg-white bg-opacity-75" style="backdrop-filter: blur(2px);">
                                    <tr>
                                        <th class="text-primary">ID</th>
                                        <th>Nom</th>
                                        <th>Code</th>
                                        <th>Cycle</th>
                                        <th>Département</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($archivedSpecialties as $specialty)
                                        <tr class="glass-row">
                                            <td class="fw-bold text-primary">{{ $specialty->id }}</td>
                                            <td>{{ $specialty->name }}</td>
                                            <td><span class="badge bg-blue-gradient text-white">{{ $specialty->code }}</span></td>
                                            <td><span class="badge bg-info text-white">{{ $specialty->cycle->name ?? 'Non défini' }}</span></td>
                                            <td><span class="badge bg-secondary text-white">{{ $specialty->department->name ?? 'Non défini' }}</span></td>
                                            <td class="text-end">
                                                <div class="btn-group">
                                                    <a href="{{ route('esbtp.specialties.show', $specialty->id) }}" class="btn btn-glass-info btn-sm me-1"><i class="fas fa-eye"></i></a>
                                                    <form action="{{ route('esbtp.specialties.restore', $specialty->id) }}" method="POST" style="display: inline;">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit" class="btn btn-glass-success btn-sm"><i class="fas fa-trash-restore"></i></button>
                                                    </form>
                                                    <form action="{{ route('esbtp.specialties.force-delete', $specialty->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer définitivement cette spécialité? Cette action est irréversible.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-glass-danger btn-sm"><i class="fas fa-times-circle"></i></button>
                                                    </form>
                                                </div>
                                            </td>
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
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Initialiser DataTables pour chaque tableau
        $('.datatable').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/French.json"
            }
        });
        // Conserver l'onglet actif après rechargement de la page
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            localStorage.setItem('activeSpecialtyTab', $(e.target).attr('href'));
        });
        var activeTab = localStorage.getItem('activeSpecialtyTab');
        if (activeTab) {
            $('#specialtyTabs a[href="' + activeTab + '"]').tab('show');
        }
    });
</script>
@endsection 