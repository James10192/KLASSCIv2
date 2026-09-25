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
                            {{-- Restauration et suppression definitive ne sont pas implementees
                                 cote controleur : on affiche l'etat plutot que des boutons morts. --}}
                            <span class="text-muted">
                                <i class="fas fa-archive"></i> Partenariat archive.
                            </span>
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
                            <div class="alert alert-info">
                                Fonctionnalite a venir : rattachement des departements au partenariat.
                            </div>
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
            // Toutes les lignes sont deja chargees : pas de pages, on fait defiler.
            "paging": false,
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