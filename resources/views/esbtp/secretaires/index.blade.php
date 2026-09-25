@extends('layouts.app')

@section('title', 'Gestion des Secrétaires')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mt-4">Gestion des Secrétaires</h1>
        <a href="{{ route('esbtp.secretaires.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Ajouter un secrétaire
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Liste des secrétaires
        </div>
        <div class="card-body">
            @if($secretaires->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="secretairesTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Email</th>
                                <th>Nom d'utilisateur</th>
                                <th>Téléphone</th>
                                <th>Statut</th>
                                <th>Date de création</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="sec-tbody">
                            @foreach($secretaires as $secretaire)
                                @include('esbtp.secretaires._ligne')
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <x-liste-infinie :paginateur="$secretaires" cible="#sec-tbody" libelle="secrétaires" />
            @else
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>Aucun secrétaire n'a été trouvé.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        const table = $('#secretairesTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/French.json'
            },
            "paging": false,
            "info": false
        });

        // Les lignes chargees au defilement arrivent dans le DOM, pas dans
        // DataTables : sans cet enregistrement, un tri ou une recherche les
        // ferait disparaitre.
        document.addEventListener('liste-infinie:ajout', function (event) {
            const lignes = (event.detail.lignes || []).filter(l => l.closest('#secretairesTable'));
            if (lignes.length) table.rows.add(lignes).draw(false);
        });
    });
</script>
@endsection
