{{-- Composant réutilisable pour les cartes de statistiques --}}
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body text-center">
                <i class="fas fa-list-ul fa-2x text-primary mb-2"></i>
                <h3 class="text-primary">{{ $stats['total_categories'] ?? 0 }}</h3>
                <p class="text-muted mb-0">Total Catégories</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body text-center">
                <i class="fas fa-graduation-cap fa-2x text-success mb-2"></i>
                <h3 class="text-success">{{ $stats['academic_categories'] ?? 0 }}</h3>
                <p class="text-muted mb-0">Académiques</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-warning">
            <div class="card-body text-center">
                <i class="fas fa-cogs fa-2x text-warning mb-2"></i>
                <h3 class="text-warning">{{ $stats['service_categories'] ?? 0 }}</h3>
                <p class="text-muted mb-0">Services</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-info">
            <div class="card-body text-center">
                <i class="fas fa-file-alt fa-2x text-info mb-2"></i>
                <h3 class="text-info">{{ $stats['administrative_categories'] ?? 0 }}</h3>
                <p class="text-muted mb-0">Administratifs</p>
            </div>
        </div>
    </div>
</div>