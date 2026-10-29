@extends('layouts.app')

@section('title', 'Gestion des Certificats')

@section('content')
<div class="container-fluid">
    <!-- Hero Section -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 15px;">
                <div class="card-body p-0">
                    <div class="row g-0">
                        <div class="col-md-8 p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h2 class="fw-bold mb-0">Gestion des certificats</h2>
                                <div>
                                    <a href="{{ route('certificate-types.index') }}" class="btn btn-primary px-4 me-2">
                                        <i class="fas fa-list-alt me-2"></i> Types de certificats
                                    </a>
                                    <a href="{{ route('certificates.create') }}" class="btn btn-success px-4">
                                        <i class="fas fa-plus-circle me-2"></i> Générer un certificat
                                    </a>
                                </div>
                            </div>
                            <p class="text-muted mb-4">Gérez les certificats délivrés aux étudiants, consultez les statistiques et générez de nouveaux documents.</p>
                            
                            <div class="d-flex gap-3 mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="icon-box bg-primary-light rounded-circle p-2 me-2">
                                        <i class="fas fa-certificate text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $totalCertificates ?? 0 }}</h6>
                                        <small class="text-muted">Certificats</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="icon-box bg-success-light rounded-circle p-2 me-2">
                                        <i class="fas fa-check-circle text-success"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $activeCertificates ?? 0 }}</h6>
                                        <small class="text-muted">Actifs</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="icon-box bg-danger-light rounded-circle p-2 me-2">
                                        <i class="fas fa-ban text-danger"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $revokedCertificates ?? 0 }}</h6>
                                        <small class="text-muted">Révoqués</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 d-none d-md-block" style="background: linear-gradient(135deg, var(--esbtp-green-light), var(--esbtp-green)); min-height: 220px;">
                            <div class="h-100 d-flex align-items-center justify-content-center">
                                <img src="https://img.freepik.com/free-vector/certification-concept-illustration_114360-5571.jpg" alt="Certificates" class="img-fluid" style="max-height: 200px; opacity: 0.9;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Search and Filter Card -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                <div class="card-body p-4">
                    <form action="{{ route('certificates.index') }}" method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label small text-muted">Recherche</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-0 bg-light" id="search" name="search" placeholder="Nom ou numéro de certificat" value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="certificate_type_id" class="form-label small text-muted">Type de certificat</label>
                            <select class="form-select border-0 bg-light" id="certificate_type_id" name="certificate_type_id">
                                <option value="">Tous les types</option>
                                @foreach($certificateTypes ?? [] as $type)
                                    <option value="{{ $type->id }}" {{ request('certificate_type_id') == $type->id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label small text-muted">Statut</label>
                            <select class="form-select border-0 bg-light" id="status" name="status">
                                <option value="">Tous les statuts</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actif</option>
                                <option value="revoked" {{ request('status') == 'revoked' ? 'selected' : '' }}>Révoqué</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="d-grid w-100">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-2"></i> Filtrer
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques des certificats -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 overflow-hidden stat-card">
                <div class="card-body position-relative">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h6 class="text-uppercase fw-semibold text-muted mb-1">Total</h6>
                            <h2 class="mb-0 display-5 fw-bold">{{ $totalCertificates ?? 0 }}</h2>
                        </div>
                        <div class="stat-icon bg-primary-light rounded-circle p-3">
                            <i class="fas fa-certificate text-primary fa-2x"></i>
                        </div>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: 100%"></div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">Certificats délivrés</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 overflow-hidden stat-card">
                <div class="card-body position-relative">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h6 class="text-uppercase fw-semibold text-muted mb-1">Actifs</h6>
                            <h2 class="mb-0 display-5 fw-bold">{{ $activeCertificates ?? 0 }}</h2>
                        </div>
                        <div class="stat-icon bg-success-light rounded-circle p-3">
                            <i class="fas fa-check-circle text-success fa-2x"></i>
                        </div>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ ($totalCertificates ?? 0) > 0 ? (($activeCertificates ?? 0) / ($totalCertificates ?? 1)) * 100 : 0 }}%"></div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">Taux d'activité: {{ ($totalCertificates ?? 0) > 0 ? round((($activeCertificates ?? 0) / ($totalCertificates ?? 1)) * 100) : 0 }}%</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 overflow-hidden stat-card">
                <div class="card-body position-relative">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h6 class="text-uppercase fw-semibold text-muted mb-1">Révoqués</h6>
                            <h2 class="mb-0 display-5 fw-bold">{{ $revokedCertificates ?? 0 }}</h2>
                        </div>
                        <div class="stat-icon bg-danger-light rounded-circle p-3">
                            <i class="fas fa-ban text-danger fa-2x"></i>
                        </div>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-danger" role="progressbar" style="width: {{ ($totalCertificates ?? 0) > 0 ? (($revokedCertificates ?? 0) / ($totalCertificates ?? 1)) * 100 : 0 }}%"></div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">Taux de révocation: {{ ($totalCertificates ?? 0) > 0 ? round((($revokedCertificates ?? 0) / ($totalCertificates ?? 1)) * 100) : 0 }}%</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 overflow-hidden stat-card">
                <div class="card-body position-relative">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h6 class="text-uppercase fw-semibold text-muted mb-1">Ce mois</h6>
                            <h2 class="mb-0 display-5 fw-bold">{{ $thisMonthCertificates ?? 0 }}</h2>
                        </div>
                        <div class="stat-icon bg-info-light rounded-circle p-3">
                            <i class="fas fa-calendar-alt text-info fa-2x"></i>
                        </div>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-info" role="progressbar" style="width: {{ ($totalCertificates ?? 0) > 0 ? (($thisMonthCertificates ?? 0) / ($totalCertificates ?? 1)) * 100 : 0 }}%"></div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">Délivrés ce mois-ci</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des certificats -->
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                <div class="card-header bg-white py-3 d-flex align-items-center">
                    <i class="fas fa-list text-primary me-2"></i>
                    <h5 class="card-title mb-0 fw-bold">Liste des certificats</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th scope="col" class="ps-4">#</th>
                                    <th scope="col">Numéro</th>
                                    <th scope="col">Étudiant</th>
                                    <th scope="col">Type</th>
                                    <th scope="col">Date d'émission</th>
                                    <th scope="col">Statut</th>
                                    <th scope="col" class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($certificates ?? [] as $certificate)
                                <tr>
                                    <td class="ps-4">{{ $loop->iteration }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            <i class="fas fa-hashtag me-1"></i> {{ $certificate->certificate_number }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle bg-primary-light text-primary me-2">
                                                {{ strtoupper(substr($certificate->student->name ?? 'E', 0, 1)) }}
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-semibold">{{ $certificate->student->name ?? 'N/A' }}</h6>
                                                <small class="text-muted">ID: {{ $certificate->student->student_id ?? 'N/A' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge 
                                            @if($certificate->certificate_type_id == 1) bg-primary-light text-primary
                                            @elseif($certificate->certificate_type_id == 2) bg-success-light text-success
                                            @elseif($certificate->certificate_type_id == 3) bg-info-light text-info
                                            @elseif($certificate->certificate_type_id == 4) bg-warning-light text-warning
                                            @else bg-secondary-light text-secondary
                                            @endif">
                                            {{ $certificate->certificateType->name ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-calendar-day text-muted me-2"></i>
                                            {{ \Carbon\Carbon::parse($certificate->issue_date)->format('d/m/Y') }}
                                        </div>
                                    </td>
                                    <td>
                                        @if($certificate->status == 'active')
                                            <span class="badge bg-success-light text-success">Actif</span>
                                        @elseif($certificate->status == 'revoked')
                                            <span class="badge bg-danger-light text-danger">Révoqué</span>
                                        @else
                                            <span class="badge bg-secondary-light text-secondary">{{ $certificate->status }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('certificates.show', $certificate->id) }}">
                                                        <i class="fas fa-eye text-primary me-2"></i> Voir
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('certificates.download', $certificate->id) }}">
                                                        <i class="fas fa-download text-info me-2"></i> Télécharger
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('certificates.verify', $certificate->certificate_number) }}">
                                                        <i class="fas fa-check-double text-success me-2"></i> Vérifier
                                                    </a>
                                                </li>
                                                @if($certificate->status == 'active')
                                                <li>
                                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#revokeModal{{ $certificate->id }}">
                                                        <i class="fas fa-ban text-danger me-2"></i> Révoquer
                                                    </a>
                                                </li>
                                                @endif
                                            </ul>
                                        </div>
                                        
                                        <!-- Modal de révocation -->
                                        @if($certificate->status == 'active')
                                        <div class="modal fade" id="revokeModal{{ $certificate->id }}" tabindex="-1" aria-labelledby="revokeModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="revokeModalLabel">Confirmer la révocation</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Êtes-vous sûr de vouloir révoquer ce certificat ?</p>
                                                        <div class="alert alert-warning">
                                                            <i class="fas fa-exclamation-triangle me-2"></i> Cette action est irréversible.
                                                        </div>
                                                        <form action="{{ route('certificates.revoke', $certificate->id) }}" method="POST" id="revokeForm{{ $certificate->id }}">
                                                            @csrf
                                                            <div class="mb-3">
                                                                <label for="revocation_reason" class="form-label">Raison de la révocation</label>
                                                                <textarea class="form-control" id="revocation_reason" name="revocation_reason" rows="3" required></textarea>
                                                            </div>
                                                        </form>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                        <button type="submit" form="revokeForm{{ $certificate->id }}" class="btn btn-danger">Révoquer</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="mb-3">
                                                <i class="fas fa-certificate fa-3x text-muted"></i>
                                            </div>
                                            <h5 class="text-muted">Aucun certificat trouvé</h5>
                                            <p class="text-muted small mb-0">Ajustez vos filtres ou générez un nouveau certificat</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted small mb-0">Affichage de {{ $certificates->firstItem() ?? 0 }} à {{ $certificates->lastItem() ?? 0 }} sur {{ $certificates->total() ?? 0 }} certificats</p>
                        </div>
                        <div>
                            {{ $certificates->withQueryString()->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Styles pour les badges et icônes */
    .bg-primary-light {
        background-color: rgba(13, 110, 253, 0.1);
    }
    
    .bg-success-light {
        background-color: rgba(25, 135, 84, 0.1);
    }
    
    .bg-warning-light {
        background-color: rgba(255, 193, 7, 0.1);
    }
    
    .bg-danger-light {
        background-color: rgba(220, 53, 69, 0.1);
    }
    
    .bg-info-light {
        background-color: rgba(13, 202, 240, 0.1);
    }
    
    .bg-secondary-light {
        background-color: rgba(108, 117, 125, 0.1);
    }
    
    .icon-box {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Style pour les cartes statistiques */
    .stat-card {
        transition: all 0.3s ease;
        border-radius: 15px;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
    }
    
    .stat-icon {
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }
    
    /* Style pour les avatars */
    .avatar-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }
    
    /* Style pour la pagination */
    .pagination {
        margin-bottom: 0;
    }
    
    .page-item.active .page-link {
        background-color: var(--esbtp-green);
        border-color: var(--esbtp-green);
    }
    
    .page-link {
        color: var(--esbtp-green);
    }
    
    /* Animation pour les lignes du tableau */
    tbody tr {
        transition: all 0.2s ease;
    }
    
    tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.02);
    }
</style>
@endsection 