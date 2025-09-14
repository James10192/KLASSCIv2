@extends('layouts.app')

@section('title', 'Détails du niveau : ' . $niveauxEtude->name)

@section('content')
<div class="main-content">
    <!-- Header -->
    <div class="dashboard-header mb-xl" style="background-color: var(--primary); color: white; border-radius: var(--radius-medium);">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div style="display: flex; align-items: center; gap: var(--space-lg);">
                    <div style="width: 80px; height: 80px; border-radius: var(--radius-circle); background-color: var(--secondary); color: white; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 700; box-shadow: var(--shadow-elevated);">
                        {{ $niveauxEtude->niveau ?? '1' }}
                    </div>
                    <div>
                        <h1 style="color: white; margin: 0; font-size: var(--title-main); font-weight: 700;">{{ $niveauxEtude->name }}</h1>
                        <p style="color: rgba(255,255,255,0.8); margin: var(--space-xs) 0 0 0;">{{ $niveauxEtude->libelle ?? $niveauxEtude->code }}</p>
                        @if($niveauxEtude->description)
                            <p style="color: rgba(255,255,255,0.7); margin: var(--space-sm) 0 0 0;">{{ $niveauxEtude->description }}</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-lg-4 text-end">
                <div class="header-actions">
                    @if($niveauxEtude->is_active)
                        <span class="badge success" style="font-size: var(--text-normal); padding: var(--space-sm) var(--space-md);">
                            <i class="fas fa-check-circle"></i> Niveau Actif
                        </span>
                    @else
                        <span class="badge danger" style="font-size: var(--text-normal); padding: var(--space-sm) var(--space-md);">
                            <i class="fas fa-times-circle"></i> Niveau Inactif
                        </span>
                    @endif
                    <a href="{{ route('esbtp.niveaux-etudes.edit', $niveauxEtude) }}" class="btn-acasi secondary" style="margin-left: var(--space-md);">
                        <i class="fas fa-edit"></i> Modifier
                    </a>
                </div>
            </div>
        </div>
    </div>
    <!-- Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-lg" style="background-color: rgba(16, 185, 129, 0.1); border: 1px solid var(--success); border-radius: var(--radius-medium); padding: var(--space-md);">
            <div style="color: var(--success); font-weight: 600;">
                <i class="fas fa-check-circle"></i> {{ session('success') }}
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- KPI Cards -->
    <div class="kpi-grid mb-xl">
        <div class="kpi-card card-moderne" style="background-color: var(--primary); color: white; text-align: center;">
            <i class="fas fa-stream fa-2x mb-md"></i>
            <div class="kpi-title" style="color: white;">Filières</div>
            <div class="kpi-value" style="color: white;">{{ $niveauxEtude->filieres ? $niveauxEtude->filieres->count() : 0 }}</div>
            <div style="color: rgba(255,255,255,0.8); font-size: var(--text-small);">Associées</div>
        </div>

        <div class="kpi-card card-moderne" style="background-color: var(--success); color: white; text-align: center;">
            <i class="fas fa-book fa-2x mb-md"></i>
            <div class="kpi-title" style="color: white;">Matières</div>
            <div class="kpi-value" style="color: white;">{{ $niveauxEtude->matieres ? $niveauxEtude->matieres->count() : 0 }}</div>
            <div style="color: rgba(255,255,255,0.8); font-size: var(--text-small);">Enseignées</div>
        </div>

        <div class="kpi-card card-moderne" style="background-color: var(--warning); color: white; text-align: center;">
            <i class="fas fa-users fa-2x mb-md"></i>
            <div class="kpi-title" style="color: white;">Classes</div>
            <div class="kpi-value" style="color: white;">{{ $niveauxEtude->classes ? $niveauxEtude->classes->count() : 0 }}</div>
            <div style="color: rgba(255,255,255,0.8); font-size: var(--text-small);">Ouvertes</div>
        </div>

        <div class="kpi-card card-moderne" style="background-color: var(--accent-blue); color: white; text-align: center;">
            <i class="fas fa-graduation-cap fa-2x mb-md"></i>
            <div class="kpi-title" style="color: white;">Année</div>
            <div class="kpi-value" style="color: white;">{{ $niveauxEtude->niveau ?? 1 }}{{ ($niveauxEtude->niveau ?? 1) == 1 ? 'ère' : 'ème' }}</div>
            <div style="color: rgba(255,255,255,0.8); font-size: var(--text-small);">d'études</div>
        </div>
    </div>

    <!-- Detailed Information -->
    <div class="row">
        <!-- General Information -->
        <div class="col-lg-6 mb-lg">
            <div class="card-moderne p-lg">
                <div class="section-title mb-lg" style="color: var(--primary); border-bottom: 2px solid var(--primary); padding-bottom: var(--space-sm);">
                    <i class="fas fa-info-circle"></i>
                    Informations Générales
                </div>

                <div class="row">
                    <div class="col-sm-6 mb-md">
                        <div style="color: var(--text-secondary); font-weight: 600; margin-bottom: var(--space-xs);">Nom complet</div>
                        <div style="color: var(--text-primary); font-size: var(--amount-medium); font-weight: 600;">{{ $niveauxEtude->name }}</div>
                    </div>
                    <div class="col-sm-6 mb-md">
                        <div style="color: var(--text-secondary); font-weight: 600; margin-bottom: var(--space-xs);">Code</div>
                        <div style="color: var(--text-primary); font-size: var(--amount-medium); font-weight: 600;">{{ $niveauxEtude->code }}</div>
                    </div>
                    <div class="col-sm-6 mb-md">
                        <div style="color: var(--text-secondary); font-weight: 600; margin-bottom: var(--space-xs);">Niveau</div>
                        <div style="color: var(--text-primary); font-size: var(--amount-medium); font-weight: 600;">{{ $niveauxEtude->niveau ?? 1 }}{{ ($niveauxEtude->niveau ?? 1) == 1 ? 'ère' : 'ème' }} année</div>
                    </div>
                    <div class="col-sm-6 mb-md">
                        <div style="color: var(--text-secondary); font-weight: 600; margin-bottom: var(--space-xs);">Type</div>
                        <div style="color: var(--text-primary); font-size: var(--amount-medium); font-weight: 600;">{{ $niveauxEtude->type ?? 'BTS' }}</div>
                    </div>
                </div>

                @if($niveauxEtude->libelle)
                    <div class="mt-md">
                        <div style="color: var(--text-secondary); font-weight: 600; margin-bottom: var(--space-xs);">Libellé</div>
                        <div style="color: var(--text-primary); font-size: var(--amount-medium); font-weight: 600;">{{ $niveauxEtude->libelle }}</div>
                    </div>
                @endif

                @if($niveauxEtude->description)
                    <div class="mt-md">
                        <div style="color: var(--text-secondary); font-weight: 600; margin-bottom: var(--space-xs);">Description</div>
                        <p style="color: var(--text-primary); margin: 0;">{{ $niveauxEtude->description }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Statistics -->
        <div class="col-lg-6 mb-lg">
            <div class="card-moderne p-lg">
                <div class="section-title mb-lg" style="color: var(--success); border-bottom: 2px solid var(--success); padding-bottom: var(--space-sm);">
                    <i class="fas fa-chart-pie"></i>
                    Statistiques Détaillées
                </div>

                <div class="row text-center">
                    <div class="col-4 mb-md">
                        <div style="width: 80px; height: 80px; border-radius: var(--radius-circle); background-color: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: var(--amount-medium); margin: 0 auto var(--space-sm) auto; box-shadow: var(--shadow-card);">
                            {{ $niveauxEtude->filieres ? $niveauxEtude->filieres->count() : 0 }}
                        </div>
                        <small style="color: var(--text-secondary); font-weight: 500;">Filières</small>
                    </div>

                    <div class="col-4 mb-md">
                        <div style="width: 80px; height: 80px; border-radius: var(--radius-circle); background-color: var(--success); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: var(--amount-medium); margin: 0 auto var(--space-sm) auto; box-shadow: var(--shadow-card);">
                            {{ $niveauxEtude->matieres ? $niveauxEtude->matieres->count() : 0 }}
                        </div>
                        <small style="color: var(--text-secondary); font-weight: 500;">Matières</small>
                    </div>

                    <div class="col-4 mb-md">
                        <div style="width: 80px; height: 80px; border-radius: var(--radius-circle); background-color: var(--warning); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: var(--amount-medium); margin: 0 auto var(--space-sm) auto; box-shadow: var(--shadow-card);">
                            {{ $niveauxEtude->classes ? $niveauxEtude->classes->count() : 0 }}
                        </div>
                        <small style="color: var(--text-secondary); font-weight: 500;">Classes</small>
                    </div>
                </div>

                <div class="text-center mt-lg">
                    <div class="row">
                        <div class="col-6">
                            <div style="color: var(--primary);">
                                <i class="fas fa-calendar-alt fa-2x mb-sm"></i>
                                <div style="font-weight: 600; margin-bottom: var(--space-xs);">Créé le</div>
                                <small style="color: var(--text-secondary);">{{ $niveauxEtude->created_at->format('d/m/Y') }}</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div style="color: var(--success);">
                                <i class="fas fa-edit fa-2x mb-sm"></i>
                                <div style="font-weight: 600; margin-bottom: var(--space-xs);">Modifié le</div>
                                <small style="color: var(--text-secondary);">{{ $niveauxEtude->updated_at->format('d/m/Y') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Data -->
    <div class="row">

        <!-- Classes ouvertes -->
        @if(($niveauxEtude->classes ? $niveauxEtude->classes->count() : 0) > 0)
            <div class="col-lg-12 mb-lg">
                <div class="card-moderne">
                    <div class="p-md" style="background-color: var(--warning); color: white; border-radius: var(--radius-medium) var(--radius-medium) 0 0;">
                        <div class="section-title" style="color: white; margin: 0; border: none; padding: 0;">
                            <i class="fas fa-users"></i>
                            Classes Ouvertes
                        </div>
                    </div>

                    <div class="p-lg">
                        @foreach($niveauxEtude->classes->take(5) as $classe)
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--space-md); margin-bottom: var(--space-sm); background: var(--background); border-radius: var(--radius-small); border-left: 4px solid var(--warning);">
                                <div>
                                    <div style="font-weight: 600; color: var(--text-primary);">{{ $classe->name }}</div>
                                    <small style="color: var(--text-secondary);">{{ $classe->code ?? 'Code non défini' }}</small>
                                </div>
                                <span class="badge info">{{ $classe->places_totales ?? 0 }} places</span>
                            </div>
                        @endforeach

                        @if($niveauxEtude->classes->count() > 5)
                            <div class="text-center mt-md">
                                <small style="color: var(--text-secondary);">et {{ $niveauxEtude->classes->count() - 5 }} autres...</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Empty State -->
    @if(($niveauxEtude->filieres ? $niveauxEtude->filieres->count() : 0) == 0 && ($niveauxEtude->matieres ? $niveauxEtude->matieres->count() : 0) == 0 && ($niveauxEtude->classes ? $niveauxEtude->classes->count() : 0) == 0)
        <div class="text-center p-xl">
            <i class="fas fa-inbox fa-3x mb-lg" style="color: var(--text-muted);"></i>
            <h5 style="color: var(--text-muted);">Aucune donnée associée</h5>
            <p style="color: var(--text-secondary);">Ce niveau d'étude n'a pas encore de filières, matières ou classes associées.</p>
        </div>
    @endif

    <!-- Action Buttons -->
    <div class="text-center mt-xl">
        <a href="{{ route('esbtp.niveaux-etudes.edit', $niveauxEtude) }}" class="btn-acasi primary" style="margin-right: var(--space-md);">
            <i class="fas fa-edit"></i> Modifier
        </a>
        <button type="button" class="btn-acasi" style="background-color: var(--danger); color: white; margin-right: var(--space-md);" data-bs-toggle="modal" data-bs-target="#deleteModal">
            <i class="fas fa-trash"></i> Supprimer
        </button>
        <a href="{{ route('esbtp.niveaux-etudes.index') }}" class="btn-acasi secondary">
            <i class="fas fa-arrow-left"></i> Retour à la liste
        </a>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content card-moderne">
            <div class="modal-header" style="background-color: var(--danger); color: white; border-radius: var(--radius-medium) var(--radius-medium) 0 0;">
                <h5 class="modal-title" style="color: white;">
                    <i class="fas fa-exclamation-triangle"></i>
                    Confirmation de suppression
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-lg">
                <i class="fas fa-trash fa-3x mb-lg" style="color: var(--danger);"></i>
                <h6>Êtes-vous sûr de vouloir supprimer ce niveau d'étude ?</h6>
                <p style="color: var(--text-secondary); margin: var(--space-md) 0;">{{ $niveauxEtude->name }}</p>

                @if(($niveauxEtude->filieres ? $niveauxEtude->filieres->count() : 0) > 0 || ($niveauxEtude->matieres ? $niveauxEtude->matieres->count() : 0) > 0 || ($niveauxEtude->classes ? $niveauxEtude->classes->count() : 0) > 0)
                    <div class="alert alert-warning" style="background-color: rgba(245, 158, 11, 0.1); border: 1px solid var(--warning); border-radius: var(--radius-medium); padding: var(--space-md);">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Attention :</strong> Ce niveau d'étude possède des données associées qui seront également affectées.
                    </div>
                @endif
            </div>
            <div class="modal-footer" style="padding: var(--space-lg); border-top: 1px solid #f3f4f6;">
                <button type="button" class="btn-acasi secondary" data-bs-dismiss="modal" style="margin-right: var(--space-md);">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <form action="{{ route('esbtp.niveaux-etudes.destroy', $niveauxEtude) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-acasi" style="background-color: var(--danger); color: white;">
                        <i class="fas fa-trash"></i> Confirmer
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Animation d'entrée pour les cartes KPI
    $('.kpi-card').each(function(index) {
        $(this).css('opacity', '0').css('transform', 'translateY(20px)');
        setTimeout(() => {
            $(this).animate({
                opacity: 1
            }, 500).css('transform', 'translateY(0)');
        }, index * 100);
    });

    // Animation d'entrée pour les cartes modernes
    $('.card-moderne').each(function(index) {
        $(this).css('opacity', '0').css('transform', 'translateY(15px)');
        setTimeout(() => {
            $(this).animate({
                opacity: 1
            }, 400).css('transform', 'translateY(0)');
        }, (index + 4) * 100);
    });

    // Effet hover sur les boutons avec animation douce
    $('.btn-acasi').hover(
        function() {
            $(this).css('transform', 'translateY(-2px)');
        },
        function() {
            $(this).css('transform', 'translateY(0)');
        }
    );
});
</script>
@endsection
