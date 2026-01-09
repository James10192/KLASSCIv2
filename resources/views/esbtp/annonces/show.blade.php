@extends('layouts.app')

@section('title', 'Annonce : ' . $annonce->titre . ' - KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .annonce-page {
        background-color: var(--background);
        min-height: 100vh;
        padding: var(--space-lg);
    }

    .annonce-header {
        background-color: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-bottom: var(--space-lg);
        box-shadow: var(--shadow-card);
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: var(--space-md);
    }

    .header-content {
        flex: 1;
        min-width: 0;
    }

    .annonce-title {
        font-size: var(--title-main);
        font-weight: 700;
        color: var(--primary);
        margin: 0 0 var(--space-sm) 0;
        line-height: 1.2;
    }

    .annonce-meta {
        display: flex;
        align-items: center;
        gap: var(--space-md);
        flex-wrap: wrap;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: var(--space-xs);
        font-size: var(--text-small);
        color: var(--text-secondary);
    }

    .meta-icon {
        color: var(--accent-blue);
        width: 16px;
    }

    .header-actions {
        display: flex;
        gap: var(--space-sm);
        flex-shrink: 0;
    }

    .priority-badge {
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-small);
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
        gap: var(--space-xs);
    }

    .priority-badge.normal {
        background-color: rgba(107, 114, 128, 0.1);
        color: var(--neutral);
    }

    .priority-badge.important {
        background-color: rgba(245, 158, 11, 0.1);
        color: var(--warning);
    }

    .priority-badge.urgent {
        background-color: rgba(239, 68, 68, 0.1);
        color: var(--danger);
    }

    .status-badge {
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-small);
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-badge.published {
        background-color: rgba(16, 185, 129, 0.1);
        color: var(--success);
    }

    .status-badge.draft {
        background-color: rgba(107, 114, 128, 0.1);
        color: var(--neutral);
    }

    .status-badge.expired {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }

    .type-badge {
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-small);
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .type-badge.global {
        background-color: rgba(30, 58, 138, 0.1);
        color: var(--primary);
    }

    .type-badge.class {
        background-color: rgba(6, 182, 212, 0.1);
        color: var(--accent-blue);
    }

    .type-badge.student {
        background-color: rgba(249, 115, 22, 0.1);
        color: var(--accent-orange);
    }

    .content-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: var(--space-lg);
        margin-bottom: var(--space-lg);
    }

    .content-card {
        background-color: var(--surface);
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-card);
        overflow: hidden;
    }

    .card-header-moderne {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        padding: var(--space-lg);
        font-size: var(--title-section);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }

    .card-body-moderne {
        padding: var(--space-lg);
    }

    .annonce-content {
        color: var(--text-primary);
        line-height: 1.6;
        margin-bottom: var(--space-lg);
        font-size: var(--text-normal);
    }

    .attachment-link {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.05));
        border: 1px solid rgba(99, 102, 241, 0.1);
        border-radius: var(--radius-small);
        padding: var(--space-sm) var(--space-md);
        display: inline-flex;
        align-items: center;
        gap: var(--space-sm);
        color: var(--primary);
        text-decoration: none;
        font-size: var(--text-small);
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .attachment-link:hover {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
        transform: translateY(-1px);
        box-shadow: var(--shadow-elevated);
        color: var(--secondary);
    }

    .info-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .info-table tr {
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .info-table tr:last-child {
        border-bottom: none;
    }

    .info-table th,
    .info-table td {
        padding: var(--space-sm) 0;
        vertical-align: top;
        font-size: var(--text-small);
    }

    .info-table th {
        font-weight: 600;
        color: var(--text-secondary);
        width: 40%;
    }

    .info-table td {
        color: var(--text-primary);
    }

    .recipients-section {
        grid-column: 1 / -1;
        background-color: var(--surface);
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-card);
        overflow: hidden;
    }

    .recipients-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: var(--text-small);
    }

    .recipients-table th {
        background-color: var(--background);
        padding: var(--space-md);
        font-weight: 600;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 11px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .recipients-table td {
        padding: var(--space-md);
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        color: var(--text-primary);
    }

    .recipients-table tr:hover {
        background-color: rgba(99, 102, 241, 0.02);
    }

    .read-status-badge {
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-small);
        font-size: 10px;
        font-weight: 600;
    }

    .read-status-badge.read {
        background-color: rgba(16, 185, 129, 0.1);
        color: var(--success);
    }

    .read-status-badge.unread {
        background-color: rgba(239, 68, 68, 0.1);
        color: var(--danger);
    }

    .class-badges {
        display: flex;
        flex-wrap: wrap;
        gap: var(--space-xs);
    }

    .class-badge {
        background-color: rgba(6, 182, 212, 0.1);
        color: var(--accent-blue);
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-small);
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .danger-zone {
        background-color: var(--surface);
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-card);
        border-left: 4px solid var(--danger);
        padding: var(--space-lg);
        margin-top: var(--space-lg);
    }

    .danger-zone-title {
        color: var(--danger);
        font-weight: 600;
        margin-bottom: var(--space-sm);
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }

    .danger-zone-text {
        color: var(--text-secondary);
        font-size: var(--text-small);
        margin-bottom: var(--space-md);
    }

    .modal-moderne .modal-content {
        border-radius: var(--radius-medium);
        border: none;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
    }

    .modal-moderne .modal-header {
        background: linear-gradient(135deg, var(--danger), #dc2626);
        color: white;
        border-bottom: none;
        padding: var(--space-lg);
        border-radius: var(--radius-medium) var(--radius-medium) 0 0;
    }

    .modal-moderne .modal-body {
        padding: var(--space-lg);
    }

    .modal-moderne .modal-footer {
        border-top: 1px solid rgba(0, 0, 0, 0.05);
        padding: var(--space-lg);
        background-color: var(--background);
        border-radius: 0 0 var(--radius-medium) var(--radius-medium);
    }

    .alert-moderne {
        border-radius: var(--radius-small);
        border: none;
        padding: var(--space-md) var(--space-lg);
        margin-bottom: var(--space-lg);
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }

    .alert-moderne.success {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05));
        color: var(--success);
        border-left: 4px solid var(--success);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .annonce-page {
            padding: var(--space-md);
        }

        .content-grid {
            grid-template-columns: 1fr;
        }

        .annonce-header {
            flex-direction: column;
            align-items: stretch;
        }

        .header-actions {
            justify-content: center;
            margin-top: var(--space-md);
        }

        .annonce-meta {
            justify-content: center;
            text-align: center;
        }

        .recipients-table {
            font-size: 11px;
        }

        .recipients-table th,
        .recipients-table td {
            padding: var(--space-sm);
        }
    }

    /* Styles pour boutons disabled */
    .btn-acasi.disabled {
        background-color: #f9fafb !important;
        color: #9ca3af !important;
        cursor: not-allowed !important;
        opacity: 0.5;
        border-color: #e5e7eb !important;
    }

    .btn-acasi.disabled:hover {
        background-color: #f9fafb !important;
        color: #9ca3af !important;
        transform: none !important;
        box-shadow: none !important;
    }
</style>
@endpush

@section('content')
<div class="annonce-page">
    <!-- Alert de succès -->
    @if(session('success'))
    <div class="alert-moderne success">
        <i class="fas fa-check-circle"></i>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    <!-- Header de l'annonce -->
    <div class="annonce-header">
        <div class="header-content">
            <h1 class="annonce-title">{{ $annonce->titre }}</h1>
            
            <div class="annonce-meta">
                <div class="meta-item">
                    <i class="fas fa-calendar-alt meta-icon"></i>
                    <span>Publié {{ $annonce->date_publication ? $annonce->date_publication->format('d/m/Y à H:i') : 'Non publié' }}</span>
                </div>
                
                @if($annonce->date_expiration)
                <div class="meta-item">
                    <i class="fas fa-hourglass-end meta-icon"></i>
                    <span>Expire {{ $annonce->date_expiration->format('d/m/Y à H:i') }}</span>
                </div>
                @endif
                
                <div class="meta-item">
                    <i class="fas fa-user meta-icon"></i>
                    <span>{{ $annonce->createdBy ? $annonce->createdBy->name : 'Système' }}</span>
                </div>

                <!-- Badges de statut -->
                @if($annonce->isExpired())
                    <span class="status-badge expired">
                        <i class="fas fa-clock"></i>
                        Expirée
                    </span>
                @else
                    <span class="status-badge {{ $annonce->is_published ? 'published' : 'draft' }}">
                        <i class="fas fa-{{ $annonce->is_published ? 'check' : 'edit' }}"></i>
                        {{ $annonce->is_published ? 'Publiée' : 'Brouillon' }}
                    </span>
                @endif

                <span class="priority-badge {{ $annonce->priorite == 2 ? 'urgent' : ($annonce->priorite == 1 ? 'important' : 'normal') }}">
                    <i class="fas fa-{{ $annonce->priorite == 2 ? 'exclamation-triangle' : ($annonce->priorite == 1 ? 'exclamation-circle' : 'info-circle') }}"></i>
                    {{ $annonce->priorite == 2 ? 'Urgent' : ($annonce->priorite == 1 ? 'Important' : 'Normal') }}
                </span>

                <span class="type-badge {{ $annonce->type == 'general' ? 'global' : ($annonce->type == 'classe' ? 'class' : 'student') }}">
                    <i class="fas fa-{{ $annonce->type == 'general' ? 'globe' : ($annonce->type == 'classe' ? 'users' : 'user') }}"></i>
                    {{ $annonce->type == 'general' ? 'Tous les étudiants' : ($annonce->type == 'classe' ? 'Classes spécifiques' : 'Étudiants spécifiques') }}
                </span>
            </div>
        </div>
        
        <div class="header-actions">
            @php
                $canEdit = true;
                $minutesElapsed = 0;
                if ($annonce->is_published) {
                    $publishedAt = $annonce->date_publication && $annonce->date_publication > $annonce->created_at
                        ? $annonce->date_publication
                        : $annonce->created_at;
                    $minutesElapsed = $publishedAt->diffInMinutes(now());
                    $canEdit = $minutesElapsed <= 15;
                }
            @endphp

            @if($canEdit)
                <a href="{{ route('esbtp.annonces.edit', $annonce) }}" class="btn-acasi secondary">
                    <i class="fas fa-edit"></i>
                    Modifier l'annonce
                </a>
            @else
                <button class="btn-acasi secondary disabled" disabled>
                    <i class="fas fa-edit"></i>
                    Modification impossible
                </button>
            @endif
            <a href="{{ route('esbtp.annonces.index') }}" class="btn-acasi primary">
                <i class="fas fa-arrow-left"></i>
                Retour à la liste
            </a>
        </div>
    </div>

    @if(!$canEdit && $annonce->is_published)
        <div class="alert-modern warning mb-3">
            <i class="fas fa-clock"></i>
            <div>
                <h4>Modification impossible</h4>
                <p>Cette annonce ne peut plus être modifiée (publiée il y a {{ $minutesElapsed }} minutes).
                   Vous pouvez la supprimer et en créer une nouvelle si nécessaire.</p>
            </div>
        </div>
    @endif

    <!-- Contenu principal -->
    <div class="content-grid">
        <!-- Contenu de l'annonce -->
        <div class="content-card">
            <div class="card-header-moderne">
                <i class="fas fa-file-alt"></i>
                Contenu de l'annonce
            </div>
            <div class="card-body-moderne">
                <div class="annonce-content">
                    {!! nl2br(e($annonce->contenu)) !!}
                </div>
                
                @if($annonce->piece_jointe)
                <div class="mt-4">
                    <h6 class="mb-3" style="color: var(--text-secondary); font-size: var(--text-small); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-paperclip me-2"></i>Pièce jointe
                    </h6>
                    <a href="{{ asset('storage/' . $annonce->piece_jointe) }}" target="_blank" class="attachment-link">
                        <i class="fas fa-download"></i>
                        <span>Télécharger la pièce jointe</span>
                    </a>
                </div>
                @endif
            </div>
        </div>

        <!-- Informations détaillées -->
        <div class="content-card">
            <div class="card-header-moderne">
                <i class="fas fa-info-circle"></i>
                Informations détaillées
            </div>
            <div class="card-body-moderne">
                <table class="info-table">
                    <tbody>
                        <tr>
                            <th>Type de diffusion</th>
                            <td>
                                @if($annonce->type == 'general')
                                    Tous les étudiants
                                @elseif($annonce->type == 'classe')
                                    Classes spécifiques
                                @else
                                    Étudiants spécifiques
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Niveau de priorité</th>
                            <td>
                                @if($annonce->priorite == 0)
                                    Normale
                                @elseif($annonce->priorite == 1)
                                    Importante
                                @else
                                    Urgente
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Statut de publication</th>
                            <td>
                                {{ $annonce->is_published ? 'Publiée' : 'Brouillon' }}
                            </td>
                        </tr>
                        <tr>
                            <th>Date de création</th>
                            <td>{{ $annonce->created_at->format('d/m/Y à H:i') }}</td>
                        </tr>
                        <tr>
                            <th>Dernière modification</th>
                            <td>{{ $annonce->updated_at->format('d/m/Y à H:i') }}</td>
                        </tr>
                        @if($annonce->date_publication)
                        <tr>
                            <th>Date de publication</th>
                            <td>{{ $annonce->date_publication->format('d/m/Y à H:i') }}</td>
                        </tr>
                        @endif
                        @if($annonce->date_expiration)
                        <tr>
                            <th>Date d'expiration</th>
                            <td>{{ $annonce->date_expiration->format('d/m/Y à H:i') }}</td>
                        </tr>
                        @endif
                        <tr>
                            <th>Créée par</th>
                            <td>{{ $annonce->createdBy ? $annonce->createdBy->name : 'Système' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Destinataires -->
    @if($annonce->type != 'general')
    <div class="recipients-section">
        <div class="card-header-moderne">
            <i class="fas fa-users"></i>
            Destinataires de l'annonce
        </div>
        <div class="card-body-moderne">
            @if($annonce->type == 'classe')
                <h6 style="color: var(--text-secondary); font-size: var(--text-small); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: var(--space-md);">
                    Classes concernées
                </h6>
                <div class="class-badges">
                    @forelse($annonce->classes as $classe)
                        <span class="class-badge">{{ $classe->name }}</span>
                    @empty
                        <p style="color: var(--text-muted); font-style: italic;">Aucune classe sélectionnée.</p>
                    @endforelse
                </div>
            @elseif($annonce->type == 'etudiant')
                <h6 style="color: var(--text-secondary); font-size: var(--text-small); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: var(--space-md);">
                    Étudiants concernés
                </h6>
                <div style="overflow-x: auto;">
                    <table class="recipients-table">
                        <thead>
                            <tr>
                                <th>Matricule</th>
                                <th>Nom complet</th>
                                <th>Classe</th>
                                <th>Statut de lecture</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($annonce->etudiants as $etudiant)
                                <tr>
                                    <td style="font-family: monospace; font-weight: 600;">{{ $etudiant->matricule }}</td>
                                    <td style="font-weight: 500;">{{ $etudiant->nom }} {{ $etudiant->prenoms }}</td>
                                    <td>{{ $etudiant->classe ? $etudiant->classe->name : 'Non assigné' }}</td>
                                    <td>
                                        @if($etudiant->pivot->is_read)
                                            <span class="read-status-badge read">
                                                <i class="fas fa-check-circle me-1"></i>
                                                Lu le {{ \Carbon\Carbon::parse($etudiant->pivot->read_at)->format('d/m/Y H:i') }}
                                            </span>
                                        @else
                                            <span class="read-status-badge unread">
                                                <i class="fas fa-times-circle me-1"></i>
                                                Non lu
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" style="text-align: center; color: var(--text-muted); font-style: italic; padding: var(--space-lg);">
                                        Aucun étudiant sélectionné.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Zone de danger -->
    <div class="danger-zone">
        <div class="danger-zone-title">
            <i class="fas fa-exclamation-triangle"></i>
            Zone de danger
        </div>
        <p class="danger-zone-text">
            La suppression de cette annonce est irréversible et supprimera également tous les liens avec les destinataires et les données de lecture associées.
        </p>
        <form action="{{ route('esbtp.annonces.destroy', $annonce) }}" method="POST" id="delete-form">
            @csrf
            @method('DELETE')
            <button type="button" class="btn-acasi secondary" style="background-color: var(--danger); color: white; border-color: var(--danger);" data-bs-toggle="modal" data-bs-target="#confirmDelete">
                <i class="fas fa-trash"></i>
                Supprimer définitivement
            </button>
        </form>
    </div>
</div>

<!-- Modal de confirmation moderne -->
<div class="modal fade modal-moderne" id="confirmDelete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirmation de suppression
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div style="text-align: center; margin-bottom: var(--space-lg);">
                    <div style="width: 80px; height: 80px; border-radius: 50%; background-color: rgba(239, 68, 68, 0.1); display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-md); color: var(--danger); font-size: 32px;">
                        <i class="fas fa-trash"></i>
                    </div>
                    <h6 style="color: var(--text-primary); margin-bottom: var(--space-sm);">Êtes-vous absolument sûr ?</h6>
                </div>
                
                <div style="background-color: var(--background); padding: var(--space-md); border-radius: var(--radius-small); margin-bottom: var(--space-md);">
                    <p style="margin: 0; font-size: var(--text-small);"><strong>Titre de l'annonce:</strong></p>
                    <p style="margin: var(--space-xs) 0 0 0; font-weight: 600; color: var(--primary);">{{ $annonce->titre }}</p>
                </div>
                
                <p style="color: var(--danger); font-size: var(--text-small); text-align: center; margin: 0;">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    <strong>Cette action ne peut pas être annulée.</strong>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-acasi secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i>
                    Annuler
                </button>
                <button type="button" class="btn-acasi primary" style="background-color: var(--danger); border-color: var(--danger);" onclick="document.getElementById('delete-form').submit();">
                    <i class="fas fa-trash"></i>
                    Supprimer définitivement
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animation d'entrée pour les cards
    const cards = document.querySelectorAll('.content-card, .recipients-section, .danger-zone');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});
</script>
@endpush