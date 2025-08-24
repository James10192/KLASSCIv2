@extends('layouts.app')

@section('title', 'Détails de la présence')

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-calendar-check me-2"></i>Détails de la présence</h1>
                <p class="header-subtitle">Informations sur la présence de {{ $attendance->etudiant->nom_complet }}</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.attendances.edit', $attendance) }}" class="btn-acasi warning me-2">
                    <i class="fas fa-edit"></i>Modifier
                </a>
                <a href="{{ route('esbtp.attendances.index') }}" class="btn-acasi primary">
                    <i class="fas fa-arrow-left"></i>Retour à la liste
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-info-circle"></i>
                            Informations sur la présence
                        </div>
                    </div>
                    <div class="main-card-body">
                        <div class="d-flex justify-content-end mb-4">
                            <a href="{{ route('esbtp.attendances.edit', $attendance) }}" class="btn-acasi warning btn-sm me-2">
                                <i class="fas fa-edit"></i>Modifier
                            </a>
                            <form action="{{ route('esbtp.attendances.destroy', $attendance) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-acasi danger btn-sm" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette présence ?')">
                                    <i class="fas fa-trash"></i>Supprimer
                                </button>
                            </form>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Étudiant</div>
                            <div class="info-value">{{ $attendance->etudiant->nom_complet }}</div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Classe</div>
                            <div class="info-value">{{ $attendance->seanceCours->emploiTemps->classe->name }}</div>
                                </tr>
                                <tr>
                                    <th>Matière</th>
                                    <td>{{ $attendance->seanceCours->matiere->nom }}</td>
                                </tr>
                                <tr>
                                    <th>Séance</th>
                                    <td>{{ $attendance->seanceCours->jour }} - {{ $attendance->seanceCours->plage_horaire }}</td>
                                </tr>
                                <tr>
                                    <th>Date</th>
                                    <td>{{ $attendance->date->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Statut</th>
                                    <td>
                                        @if($attendance->statut == 'present')
                                            <span class="badge badge-success">Présent</span>
                                        @elseif($attendance->statut == 'absent')
                                            <span class="badge badge-danger">Absent</span>
                                        @elseif($attendance->statut == 'retard')
                                            <span class="badge badge-warning">En retard</span>
                                        @elseif($attendance->statut == 'excuse')
                                            <span class="badge badge-info">Excusé</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Commentaire</th>
                                    <td>{{ $attendance->commentaire ?? 'Aucun commentaire' }}</td>
                                </tr>
                                <tr>
                                    <th>Créé par</th>
                                    <td>{{ $attendance->createdBy->name }} le {{ $attendance->created_at->format('d/m/Y à H:i') }}</td>
                                </tr>
                                @if($attendance->updated_by)
                                <tr>
                                    <th>Dernière modification</th>
                                    <td>{{ $attendance->updatedBy->name }} le {{ $attendance->updated_at->format('d/m/Y à H:i') }}</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        <a href="{{ route('esbtp.attendances.index') }}" class="btn btn-light">
                            <i class="mdi mdi-arrow-left"></i> Retour à la liste
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Informations sur l'étudiant</h4>

                    <div class="text-center mb-4">
                        @if($attendance->etudiant->photo)
                            <img src="{{ asset('storage/' . $attendance->etudiant->photo) }}" alt="Photo de l'étudiant" class="rounded-circle img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
                        @else
                            <img src="{{ asset('assets/images/avatar.jpg') }}" alt="Photo par défaut" class="rounded-circle img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
                        @endif
                    </div>

                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Matricule</span>
                            <span class="badge badge-primary">{{ $attendance->etudiant->matricule }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Téléphone</span>
                            <span>{{ $attendance->etudiant->telephone }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Email</span>
                            <span>{{ $attendance->etudiant->email_personnel }}</span>
                        </li>
                    </ul>

                    <div class="mt-4">
                        <a href="{{ route('esbtp.etudiants.show', $attendance->etudiant) }}" class="btn btn-gradient-primary btn-sm">
                            <i class="mdi mdi-account"></i> Voir le profil complet
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
