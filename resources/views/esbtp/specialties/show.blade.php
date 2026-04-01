@extends('layouts.app')

@section('title', 'Détails de la Spécialité')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-4">
            <!-- Carte d'informations de la spécialité -->
            <div class="card card-primary card-outline">
                <div class="card-body box-profile">
                    <h3 class="profile-username text-center">{{ $specialty->name }}</h3>
                    <p class="text-muted text-center">{{ $specialty->code }}</p>

                    <ul class="list-group list-group-unbordered mb-3">
                        <li class="list-group-item">
                            <b>Cycle</b> <a class="float-right">{{ $specialty->cycle->name ?? 'Non défini' }}</a>
                        </li>
                        <li class="list-group-item">
                            <b>Département</b> <a class="float-right">{{ $specialty->department->name ?? 'Non défini' }}</a>
                        </li>
                        <li class="list-group-item">
                            <b>Responsable</b> <a class="float-right">{{ $specialty->coordinator_name ?? 'Non défini' }}</a>
                        </li>
                        <li class="list-group-item">
                            <b>Années d'études</b> <a class="float-right">{{ $specialty->studyYears->count() }}</a>
                        </li>
                        <li class="list-group-item">
                            <b>Statut</b> 
                            <a class="float-right">
                                @if($specialty->trashed())
                                    <span class="badge badge-danger">Archivée</span>
                                @elseif($specialty->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-warning">Inactive</span>
                                @endif
                            </a>
                        </li>
                    </ul>

                    <div class="btn-group w-100">
                        @if(!$specialty->trashed())
                            <a href="{{ route('esbtp.specialties.edit', $specialty->id) }}" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Modifier
                            </a>
                            <form action="{{ route('esbtp.specialties.destroy', $specialty->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir archiver cette spécialité?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-trash"></i> Archiver
                                </button>
                            </form>
                        @else
                            <form action="{{ route('esbtp.specialties.restore', $specialty->id) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-trash-restore"></i> Restaurer
                                </button>
                            </form>
                            <form action="{{ route('esbtp.specialties.force-delete', $specialty->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer définitivement cette spécialité? Cette action est irréversible.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-times-circle"></i> Supprimer
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
                <!-- /.card-body -->
            </div>
            <!-- /.card -->
        </div>
        <!-- /.col -->
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header p-2">
                    <ul class="nav nav-pills">
                        <li class="nav-item"><a class="nav-link active" href="#description" data-toggle="tab">Description</a></li>
                        <li class="nav-item"><a class="nav-link" href="#career" data-toggle="tab">Débouchés</a></li>
                        <li class="nav-item"><a class="nav-link" href="#study-years" data-toggle="tab">Années d'études</a></li>
                        <li class="nav-item"><a class="nav-link" href="#students" data-toggle="tab">Étudiants</a></li>
                    </ul>
                </div><!-- /.card-header -->
                
                <div class="card-body">
                    <div class="tab-content">
                        <!-- Onglet Description -->
                        <div class="active tab-pane" id="description">
                            <div class="post">
                                <div>
                                    {!! nl2br(e($specialty->description ?? 'Aucune description disponible.')) !!}
                                </div>
                            </div>
                        </div>
                        
                        <!-- Onglet Débouchés -->
                        <div class="tab-pane" id="career">
                            <div class="post">
                                <div>
                                    {!! nl2br(e($specialty->career_opportunities ?? 'Aucune information sur les débouchés disponible.')) !!}
                                </div>
                            </div>
                        </div>
                        
                        <!-- Onglet Années d'études -->
                        <div class="tab-pane" id="study-years">
                            @if(session('success'))
                                <div class="alert alert-success alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                                    {{ session('success') }}
                                </div>
                            @endif
                            
                            @if(session('error'))
                                <div class="alert alert-danger alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                                    {{ session('error') }}
                                </div>
                            @endif
                            
                            <!-- Bouton pour ajouter une année d'études -->
                            <div class="mb-3">
                                <a href="{{ route('esbtp.study-years.create', ['specialty_id' => $specialty->id]) }}" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Ajouter une année d'études
                                </a>
                            </div>
                            
                            <!-- Liste des années d'études -->
                            @if($specialty->studyYears->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Niveau</th>
                                                <th>Nom</th>
                                                <th>Semestres</th>
                                                <th>Statut</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($specialty->studyYears as $studyYear)
                                                <tr>
                                                    <td>{{ $studyYear->level }}</td>
                                                    <td>{{ $studyYear->name }}</td>
                                                    <td>{{ $studyYear->semesters->count() }}</td>
                                                    <td>
                                                        @if($studyYear->is_active)
                                                            <span class="badge badge-success">Actif</span>
                                                        @else
                                                            <span class="badge badge-warning">Inactif</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="{{ route('esbtp.study-years.show', $studyYear->id) }}" class="btn btn-info btn-sm">
                                                                <i class="fas fa-eye"></i> Voir
                                                            </a>
                                                            <a href="{{ route('esbtp.study-years.edit', $studyYear->id) }}" class="btn btn-warning btn-sm">
                                                                <i class="fas fa-edit"></i> Modifier
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-info">
                                    Aucune année d'études associée à cette spécialité.
                                </div>
                            @endif
                        </div>
                        
                        <!-- Onglet Étudiants -->
                        <div class="tab-pane" id="students">
                            <div class="alert alert-info">
                                Fonctionnalité à venir : Gestion des étudiants inscrits dans cette spécialité.
                            </div>
                        </div>
                    </div>
                    <!-- /.tab-content -->
                </div><!-- /.card-body -->
            </div>
            <!-- /.card -->
        </div>
        <!-- /.col -->
    </div>
    <!-- /.row -->
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Initialiser DataTables
        $('.table').DataTable({
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
    });
</script>
@endsection 