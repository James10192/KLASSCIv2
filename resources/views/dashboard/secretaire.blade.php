@extends('layouts.app')

@section('title', 'Tableau de bord Secrétaire')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    body {
        background-color: var(--background);
    }

    .kpi-card {
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
    }

    .kpi-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(4, 83, 203, 0.08);
        color: var(--primary);
    }

    .notification-card {
        border-left: 4px solid;
        padding: var(--space-md);
        border-radius: var(--radius-small);
        background: rgba(245, 158, 11, 0.05);
        border-color: var(--warning);
    }

    @media (max-width: 768px) {
        .dashboard-header {
            flex-direction: column;
            text-align: center;
            gap: var(--space-md);
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content" style="padding: 1.5rem; max-width: 100%; overflow-x: hidden;">
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-user-tie me-2"></i>Tableau de bord secrétaire</h1>
                <p class="header-subtitle">Bienvenue, <strong>{{ $user->name }}</strong> ! Suivez les opérations clés</p>
            </div>
            <div class="header-actions">
                <span class="badge rounded-pill bg-light text-dark me-2">
                    <i class="fas fa-calendar me-1"></i>
                    {{ $anneeEnCours->name ?? 'Année non définie' }}
                </span>
                <span class="text-muted">{{ \Carbon\Carbon::now()->isoFormat('dddd D MMMM YYYY') }}</span>
            </div>
        </div>

        @if($pendingInscriptionsCount > 0)
            <div class="card-moderne mb-4 notification-card">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                    <div>
                        <div class="fw-semibold mb-1">
                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                            {{ $pendingInscriptionsCount }} inscription(s) en attente
                        </div>
                        <div class="text-muted small">
                            Ces dossiers requièrent une vérification pour finaliser l’admission.
                        </div>
                    </div>
                    <a href="{{ route('esbtp.inscriptions.index', ['status' => 'pending']) }}" class="btn-acasi warning">
                        <i class="fas fa-check-circle me-1"></i>Consulter
                    </a>
                </div>
            </div>
        @endif

        <div class="row g-3 mb-4">
            @if(isset($pendingInscriptionsCount))
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="card-moderne kpi-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="text-muted small text-uppercase">Inscriptions en attente</div>
                                <div class="h4 mb-1">{{ $pendingInscriptionsCount }}</div>
                                <a href="{{ route('esbtp.inscriptions.index', ['status' => 'pending']) }}" class="btn btn-sm btn-outline-warning">Valider</a>
                            </div>
                            <div class="kpi-icon" style="background: rgba(245, 158, 11, 0.12); color: var(--warning);">
                                <i class="fas fa-user-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if(isset($totalStudents))
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="card-moderne kpi-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="text-muted small text-uppercase">Étudiants</div>
                                <div class="h4 mb-1">{{ $totalStudents }}</div>
                                <a href="{{ route('esbtp.etudiants-inscriptions.index') }}" class="btn btn-sm btn-outline-primary">Gérer</a>
                            </div>
                            <div class="kpi-icon">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if(isset($todayAttendances))
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="card-moderne kpi-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="text-muted small text-uppercase">Présences aujourd'hui</div>
                                <div class="h4 mb-1">{{ $todayAttendances }}</div>
                                <a href="{{ route('esbtp.attendances.index') }}" class="btn btn-sm btn-outline-success">Suivre</a>
                            </div>
                            <div class="kpi-icon" style="background: rgba(16, 185, 129, 0.12); color: var(--success);">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if(isset($pendingJustifications))
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="card-moderne kpi-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="text-muted small text-uppercase">Justifications en attente</div>
                                <div class="h4 mb-1">{{ $pendingJustifications }}</div>
                                <a href="{{ route('esbtp.attendances.index') }}" class="btn btn-sm btn-outline-warning">Traiter</a>
                            </div>
                            <div class="kpi-icon" style="background: rgba(245, 158, 11, 0.12); color: var(--warning);">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if(isset($totalTimetables))
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="card-moderne kpi-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="text-muted small text-uppercase">Emplois du temps</div>
                                <div class="h4 mb-1">{{ $totalTimetables }}</div>
                                <a href="{{ route('esbtp.emploi-temps.index') }}" class="btn btn-sm btn-outline-info">Gérer</a>
                            </div>
                            <div class="kpi-icon" style="background: rgba(6, 182, 212, 0.12); color: var(--accent-blue);">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if(isset($todayClasses))
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="card-moderne kpi-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="text-muted small text-uppercase">Cours aujourd'hui</div>
                                <div class="h4 mb-1">{{ $todayClasses }}</div>
                                <a href="{{ route('esbtp.timetables.today') }}" class="btn btn-sm btn-outline-primary">Voir</a>
                            </div>
                            <div class="kpi-icon">
                                <i class="fas fa-chalkboard"></i>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if(isset($pendingBulletins))
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="card-moderne kpi-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="text-muted small text-uppercase">Bulletins en attente</div>
                                <div class="h4 mb-1">{{ $pendingBulletins }}</div>
                                <a href="{{ route('esbtp.bulletins.pending') }}" class="btn btn-sm btn-outline-danger">Traiter</a>
                            </div>
                            <div class="kpi-icon" style="background: rgba(239, 68, 68, 0.12); color: var(--danger);">
                                <i class="fas fa-file-alt"></i>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="row g-3">
            <div class="col-lg-7 col-12">
                <div class="card-moderne">
                    <div class="p-lg">
                        <div class="section-title mb-md">
                            <i class="fas fa-user-graduate me-2"></i>Étudiants récemment inscrits
                        </div>
                        @if(isset($recentStudents) && $recentStudents->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Photo</th>
                                            <th>Nom</th>
                                            <th>Prénom</th>
                                            <th>Matricule</th>
                                            <th>Classe</th>
                                            <th>Date d'inscription</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentStudents as $etudiant)
                                            @php
                                                $currentYearId = $anneeEnCours->id ?? null;
                                                $latestInscription = $etudiant->inscriptions
                                                    ->sortByDesc('created_at')
                                                    ->first();
                                                $currentInscription = $currentYearId
                                                    ? $etudiant->inscriptions
                                                        ->where('annee_universitaire_id', $currentYearId)
                                                        ->sortByDesc('created_at')
                                                        ->first()
                                                    : null;
                                                $displayInscription = $currentInscription ?: $latestInscription;
                                                $displayDate = $displayInscription?->date_inscription ?? $displayInscription?->created_at;
                                                $classeName = $displayInscription?->classe?->name ?? $displayInscription?->classe?->nom;
                                            @endphp
                                            <tr>
                                                <td>
                                                    @if(!empty($etudiant->photo_url))
                                                        <img src="{{ $etudiant->photo_url }}" alt="Photo de profil" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                                    @else
                                                        <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                            <i class="fas fa-user text-muted"></i>
                                                        </div>
                                                    @endif
                                                </td>
                                                <td>{{ $etudiant->nom }}</td>
                                                <td>{{ $etudiant->prenoms }}</td>
                                                <td>{{ $etudiant->matricule }}</td>
                                                <td>
                                                    @if($displayInscription)
                                                        {{ $classeName ?? 'Classe non définie' }}
                                                        @if($displayInscription->status === 'pending' || $displayInscription->status === 'en_attente')
                                                            <span class="text-warning"> — En attente</span>
                                                        @endif
                                                        @if(!$currentInscription && $latestInscription)
                                                            <span class="text-muted">
                                                                — Hors année
                                                                @if(!empty($latestInscription->anneeUniversitaire?->name))
                                                                    ({{ $latestInscription->anneeUniversitaire->name }})
                                                                @endif
                                                            </span>
                                                        @endif
                                                    @else
                                                        <span class="text-muted">Aucune inscription</span>
                                                    @endif
                                                </td>
                                                <td>{{ $displayDate?->format('d/m/Y') ?? 'N/A' }}</td>
                                                <td>
                                                    <a href="{{ route('esbtp.etudiants.show', $etudiant->id) }}" class="btn btn-sm btn-outline-info">Détails</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-muted">Aucun étudiant récent pour le moment.</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-5 col-12">
                <div class="card-moderne">
                    <div class="p-lg">
                        <div class="section-title mb-md">
                            <i class="fas fa-envelope me-2"></i>Messages récents
                        </div>
                        @if(isset($recentMessages) && $recentMessages->count() > 0)
                            <div class="list-group list-group-flush">
                                @foreach($recentMessages as $message)
                                    <a href="{{ route('messages.show', $message->id) }}" class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between">
                                            <strong>{{ $message->subject }}</strong>
                                            <small class="text-muted">{{ $message->created_at->diffForHumans() }}</small>
                                        </div>
                                        <div class="small text-muted">{{ Str::limit($message->content, 90) }}</div>
                                        <div class="small">De: {{ $message->sender->name ?? 'Système' }}</div>
                                    </a>
                                @endforeach
                            </div>
                            <div class="mt-3 text-end">
                                <a href="{{ route('messages.index') }}" class="btn btn-sm btn-outline-primary">Voir tous les messages</a>
                            </div>
                        @else
                            <div class="text-muted">Aucun message récent.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card-moderne mt-4">
            <div class="p-lg">
                <div class="section-title mb-md">
                    <i class="fas fa-paper-plane me-2"></i>Envoyer un message
                </div>
                <form action="{{ route('esbtp.annonces.store') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="recipient_type" class="form-label">Destinataire</label>
                            <select class="form-select" id="recipient_type" name="recipient_type" required>
                                <option value="">Sélectionner un destinataire</option>
                                <option value="all">Tous les utilisateurs</option>
                                <option value="etudiant">Tous les étudiants</option>
                                <option value="specific_user">Étudiant spécifique</option>
                                <option value="specific_class">Classe spécifique</option>
                            </select>
                        </div>

                        <div class="col-md-4" id="specific_user_container" style="display: none;">
                            <label for="recipient_id" class="form-label">Sélectionner un étudiant</label>
                            <select class="form-select" id="recipient_id" name="recipient_id">
                                <option value="">Choisir un étudiant</option>
                                @foreach(App\Models\ESBTPEtudiant::all() as $etudiant)
                                    <option value="{{ $etudiant->user_id }}">{{ $etudiant->nom }} {{ $etudiant->prenoms }} ({{ $etudiant->matricule }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4" id="specific_class_container" style="display: none;">
                            <label for="recipient_group" class="form-label">Sélectionner une classe</label>
                            <select class="form-select" id="recipient_group" name="recipient_group">
                                <option value="">Choisir une classe</option>
                                @foreach(App\Models\ESBTPClasse::all() as $classe)
                                    <option value="{{ $classe->id }}">{{ $classe->nom }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="subject" class="form-label">Sujet</label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                        </div>
                        <div class="col-12">
                            <label for="content" class="form-label">Message</label>
                            <textarea class="form-control" id="content" name="content" rows="3" required></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn-acasi primary">
                                <i class="fas fa-paper-plane me-1"></i>Envoyer
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@if(($pendingCurrentYearInscriptionsCount ?? 0) > 0 && $anneeEnCours)
<div class="modal fade" id="pendingInscriptionsReminderModal" tabindex="-1" role="dialog" aria-labelledby="pendingInscriptionsReminderModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pendingInscriptionsReminderModalLabel">
                    Inscriptions en attente - {{ $anneeEnCours->name }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>{{ $pendingCurrentYearInscriptionsCount }}</strong> inscription(s) sont en attente de validation pour l'année universitaire courante.</p>
                @if(!empty($pendingCurrentYearInscriptionsByStep))
                    <div style="background: #f8fafc; padding: 12px; border-radius: 8px; margin: 12px 0;">
                        <div style="font-weight: 600; margin-bottom: 6px;">Répartition par étape :</div>
                        <ul style="padding-left: 20px; margin: 0;">
                            <li>Prospect : <strong>{{ $pendingCurrentYearInscriptionsByStep['prospect'] ?? 0 }}</strong></li>
                            <li>Documents complets : <strong>{{ $pendingCurrentYearInscriptionsByStep['documents_complets'] ?? 0 }}</strong></li>
                            <li>En validation : <strong>{{ $pendingCurrentYearInscriptionsByStep['en_validation'] ?? 0 }}</strong></li>
                        </ul>
                    </div>
                @endif
                <ol style="padding-left: 20px; line-height: 1.6; margin: 15px 0;">
                    <li>Les dossiers dont le workflow n'est pas à <strong>etudiant_cree</strong> restent en attente.</li>
                    <li>Ces étudiants ne seront pas comptés dans KLASSCI pour l'année <strong>{{ $anneeEnCours->name }}</strong>.</li>
                    <li>Validez ou complétez les dossiers pour finaliser l'inscription.</li>
                </ol>
                <div style="background: #f3f4f6; padding: 12px; border-radius: 6px; margin-top: 15px;">
                    <strong>Astuce :</strong><br>
                    Le workflow doit atteindre <strong>etudiant_cree</strong> pour activer l'étudiant dans l'année courante.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" onclick="sessionStorage.removeItem('pendingInscriptionsReminder.secretaire')">
                    Rappeler plus tard
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <a href="{{ route('esbtp.inscriptions.administration', ['annee' => $anneeEnCours->id]) }}" class="btn btn-primary">
                    <i class="fas fa-check-circle"></i> Consulter les inscriptions
                </a>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const pendingModalElement = document.getElementById('pendingInscriptionsReminderModal');
        if (pendingModalElement && typeof bootstrap !== 'undefined') {
            const reminderKey = 'pendingInscriptionsReminder.secretaire';
            if (!sessionStorage.getItem(reminderKey)) {
                const pendingModal = new bootstrap.Modal(pendingModalElement);
                pendingModal.show();
                sessionStorage.setItem(reminderKey, 'shown');
            }
        }

        // Afficher/masquer les conteneurs de sélection spécifiques
        const recipientType = document.getElementById('recipient_type');
        const specificUserContainer = document.getElementById('specific_user_container');
        const specificClassContainer = document.getElementById('specific_class_container');

        recipientType.addEventListener('change', function() {
            if (this.value === 'specific_user') {
                specificUserContainer.style.display = 'block';
                specificClassContainer.style.display = 'none';
            } else if (this.value === 'specific_class') {
                specificUserContainer.style.display = 'none';
                specificClassContainer.style.display = 'block';
            } else {
                specificUserContainer.style.display = 'none';
                specificClassContainer.style.display = 'none';
            }
        });
    });
</script>
@endpush
@endsection
