@extends('layouts.app')

@section('title', 'Détails du Partenariat')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-4">
            <!-- Carte d'informations du partenariat -->
            <div class="card card-primary card-outline">
                <div class="card-body box-profile">
                    <div class="text-center">
                        @if($partnership->logo)
                            <img class="profile-user-img img-fluid img-circle" src="{{ asset('storage/' . $partnership->logo) }}" alt="{{ $partnership->name }}">
                        @else
                            <img class="profile-user-img img-fluid img-circle" src="{{ asset('img/default-partnership.png') }}" alt="Default">
                        @endif
                    </div>

                    <h3 class="profile-username text-center">{{ $partnership->name }}</h3>
                    <p class="text-muted text-center">{{ $partnership->type }}</p>

                    <ul class="list-group list-group-unbordered mb-3">
                        <li class="list-group-item">
                            <b>Personne de contact</b> <a class="float-right">{{ $partnership->contact_person ?? 'Non défini' }}</a>
                        </li>
                        <li class="list-group-item">
                            <b>Email</b> <a class="float-right" href="mailto:{{ $partnership->email }}">{{ $partnership->email ?? 'Non défini' }}</a>
                        </li>
                        <li class="list-group-item">
                            <b>Téléphone</b> <a class="float-right">{{ $partnership->phone ?? 'Non défini' }}</a>
                        </li>
                        <li class="list-group-item">
                            <b>Site web</b> 
                            <a class="float-right" href="{{ $partnership->website }}" target="_blank">
                                {{ $partnership->website ?? 'Non défini' }}
                            </a>
                        </li>
                        <li class="list-group-item">
                            <b>Adresse</b> <a class="float-right">{{ $partnership->address ?? 'Non définie' }}</a>
                        </li>
                        <li class="list-group-item">
                            <b>Statut</b> 
                            <a class="float-right">
                                @if($partnership->trashed())
                                    <span class="badge badge-danger">Archivé</span>
                                @elseif($partnership->is_active)
                                    <span class="badge badge-success">Actif</span>
                                @else
                                    <span class="badge badge-warning">Inactif</span>
                                @endif
                            </a>
                        </li>
                    </ul>

                    <div class="btn-group w-100">
                        @if(!$partnership->trashed())
                            <a href="{{ route('esbtp.partnerships.edit', $partnership->id) }}" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Modifier
                            </a>
                            <form action="{{ route('esbtp.partnerships.destroy', $partnership->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir archiver ce partenariat?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-trash"></i> Archiver
                                </button>
                            </form>
                        @else
                            <form action="{{ route('esbtp.partnerships.restore', $partnership->id) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-trash-restore"></i> Restaurer
                                </button>
                            </form>
                            <form action="{{ route('esbtp.partnerships.force-delete', $partnership->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer définitivement ce partenariat? Cette action est irréversible.');">
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
                        <li class="nav-item"><a class="nav-link" href="#departments" data-toggle="tab">Départements</a></li>
                        <li class="nav-item"><a class="nav-link" href="#activities" data-toggle="tab">Activités</a></li>
                        <li class="nav-item"><a class="nav-link" href="#documents" data-toggle="tab">Documents</a></li>
                    </ul>
                </div><!-- /.card-header -->
                
                <div class="card-body">
                    <div class="tab-content">
                        <!-- Onglet Description -->
                        <div class="active tab-pane" id="description">
                            <div class="post">
                                <div>
                                    {!! nl2br(e($partnership->description ?? 'Aucune description disponible.')) !!}
                                </div>
                            </div>
                        </div>
                        
                        <!-- Onglet Départements -->
                        <div class="tab-pane" id="departments">
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
                            
                            <!-- Formulaire d'ajout de département -->
                            <div class="card card-primary collapsed-card">
                                <div class="card-header">
                                    <h3 class="card-title">Ajouter un département</h3>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body" style="display: none;">
                                    <form action="{{ route('esbtp.partnerships.attach-department', $partnership->id) }}" method="POST">
                                        @csrf
                                        <div class="form-group">
                                            <label for="department_id">Département</label>
                                            <select class="form-control select2 @error('department_id') is-invalid @enderror" id="department_id" name="department_id" required>
                                                <option value="">Sélectionnez un département</option>
                                                @foreach($departments as $department)
                                                    @if(!$partnershipDepartments->contains($department->id))
                                                        <option value="{{ $department->id }}">{{ $department->name }} ({{ $department->code }})</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            @error('department_id')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="specific_details">Détails spécifiques</label>
                                            <textarea class="form-control @error('specific_details') is-invalid @enderror" id="specific_details" name="specific_details" rows="3" placeholder="Détails spécifiques à cette relation">{{ old('specific_details') }}</textarea>
                                            @error('specific_details')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="start_date">Date de début</label>
                                                    <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date" value="{{ old('start_date') }}">
                                                    @error('start_date')
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="end_date">Date de fin</label>
                                                    <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date" name="end_date" value="{{ old('end_date') }}">
                                                    @error('end_date')
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">Ajouter</button>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- Liste des départements -->
                            @if($partnershipDepartments->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Nom</th>
                                                <th>Code</th>
                                                <th>Détails spécifiques</th>
                                                <th>Période</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($partnershipDepartments as $department)
                                                <tr>
                                                    <td>{{ $department->name }}</td>
                                                    <td>{{ $department->code }}</td>
                                                    <td>{{ $department->pivot->specific_details ?? 'Non spécifié' }}</td>
                                                    <td>
                                                        @if($department->pivot->start_date && $department->pivot->end_date)
                                                            Du {{ $department->pivot->start_date->format('d/m/Y') }} au {{ $department->pivot->end_date->format('d/m/Y') }}
                                                        @elseif($department->pivot->start_date)
                                                            Depuis le {{ $department->pivot->start_date->format('d/m/Y') }}
                                                        @elseif($department->pivot->end_date)
                                                            Jusqu'au {{ $department->pivot->end_date->format('d/m/Y') }}
                                                        @else
                                                            Non spécifié
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="{{ route('esbtp.departments.show', $department->id) }}" class="btn btn-info btn-sm">
                                                                <i class="fas fa-eye"></i> Voir
                                                            </a>
                                                            <form action="{{ route('esbtp.partnerships.detach-department', [$partnership->id, $department->id]) }}" method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir retirer ce département?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger btn-sm">
                                                                    <i class="fas fa-unlink"></i> Retirer
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-info">
                                    Aucun département associé à ce partenariat.
                                </div>
                            @endif
                        </div>
                        
                        <!-- Onglet Activités -->
                        <div class="tab-pane" id="activities">
                            <div class="alert alert-info">
                                Fonctionnalité à venir : Gestion des activités liées au partenariat.
                            </div>
                        </div>
                        
                        <!-- Onglet Documents -->
                        <div class="tab-pane" id="documents">
                            <div class="alert alert-info">
                                Fonctionnalité à venir : Gestion des documents liés au partenariat (conventions, accords, etc.).
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
        // Initialiser Select2
        $('.select2').select2({
            theme: 'bootstrap4'
        });
        
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