@extends('layouts.app')

@section('title', 'Détails des Frais')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Détails des Frais</h3>
                    <div class="card-tools">
                        <a href="{{ route('esbtp.fees.index') }}" class="btn btn-default">
                            <i class="fas fa-arrow-left"></i> Retour
                        </a>
                        <a href="{{ route('esbtp.fees.edit', $fee) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Modifier
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="30%">ID:</th>
                                    <td>{{ $fee->id }}</td>
                                </tr>
                                <tr>
                                    <th>Catégorie:</th>
                                    <td>
                                        @if($fee->category)
                                            <span class="badge badge-info">{{ $fee->category->name }}</span>
                                            <small class="text-muted">({{ $fee->category->code }})</small>
                                        @else
                                            <span class="text-muted">Non définie</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Classe:</th>
                                    <td>
                                        @if($fee->class)
                                            {{ $fee->class->name }}
                                        @else
                                            <span class="text-muted">Non définie</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Année Universitaire:</th>
                                    <td>
                                        @if($fee->academicYear)
                                            {{ $fee->academicYear->name }}
                                        @else
                                            <span class="text-muted">Non définie</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Inscription:</th>
                                    <td>
                                        @if($fee->inscription && $fee->inscription->etudiant)
                                            <strong>{{ $fee->inscription->etudiant->nom }} {{ $fee->inscription->etudiant->prenom }}</strong>
                                            <br><small class="text-muted">Matricule: {{ $fee->inscription->etudiant->matricule }}</small>
                                        @else
                                            <span class="text-muted">Aucune inscription spécifique</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="30%">Montant:</th>
                                    <td>
                                        <h4 class="text-primary">{{ number_format($fee->amount, 0, ',', ' ') }} FCFA</h4>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Date d'échéance:</th>
                                    <td>
                                        @if($fee->due_date)
                                            {{ \Carbon\Carbon::parse($fee->due_date)->format('d/m/Y') }}
                                            @if(\Carbon\Carbon::parse($fee->due_date)->isPast())
                                                <span class="badge badge-danger">Échue</span>
                                            @else
                                                <span class="badge badge-success">À venir</span>
                                            @endif
                                        @else
                                            <span class="text-muted">Non définie</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Calendrier de paiement:</th>
                                    <td>
                                        @switch($fee->payment_schedule)
                                            @case('one_time')
                                                <span class="badge badge-primary">Paiement unique</span>
                                                @break
                                            @case('monthly')
                                                <span class="badge badge-info">Mensuel</span>
                                                @break
                                            @case('termly')
                                                <span class="badge badge-warning">Trimestriel</span>
                                                @break
                                            @case('yearly')
                                                <span class="badge badge-secondary">Annuel</span>
                                                @break
                                            @default
                                                <span class="text-muted">{{ $fee->payment_schedule }}</span>
                                        @endswitch
                                    </td>
                                </tr>
                                <tr>
                                    <th>Statut:</th>
                                    <td>
                                        @switch($fee->status)
                                            @case('active')
                                                <span class="badge badge-success">Actif</span>
                                                @break
                                            @case('pending')
                                                <span class="badge badge-warning">En attente</span>
                                                @break
                                            @case('inactive')
                                                <span class="badge badge-secondary">Inactif</span>
                                                @break
                                            @default
                                                <span class="badge badge-light">{{ $fee->status }}</span>
                                        @endswitch
                                    </td>
                                </tr>
                                <tr>
                                    <th>Versements autorisés:</th>
                                    <td>
                                        @if($fee->installments_allowed)
                                            <span class="badge badge-success">Oui</span>
                                            @if($fee->min_installment_amount)
                                                <br><small class="text-muted">Montant minimum: {{ number_format($fee->min_installment_amount, 0, ',', ' ') }} FCFA</small>
                                            @endif
                                        @else
                                            <span class="badge badge-secondary">Non</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    @if($fee->description)
                    <div class="row mt-3">
                        <div class="col-12">
                            <h5>Description:</h5>
                            <div class="card card-outline card-info">
                                <div class="card-body">
                                    {{ $fee->description }}
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    @if($fee->late_fee && $fee->late_fee > 0)
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="alert alert-warning">
                                <h6><i class="fas fa-exclamation-triangle"></i> Frais de retard</h6>
                                <p class="mb-0">Des frais de retard de <strong>{{ number_format($fee->late_fee, 0, ',', ' ') }} FCFA</strong> s'appliquent en cas de paiement tardif.</p>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    <div class="row mt-3">
                        <div class="col-12">
                            <h5>Informations système:</h5>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="20%">Créé le:</th>
                                    <td>{{ $fee->created_at->format('d/m/Y à H:i') }}</td>
                                </tr>
                                <tr>
                                    <th>Modifié le:</th>
                                    <td>{{ $fee->updated_at->format('d/m/Y à H:i') }}</td>
                                </tr>
                                @if($fee->deleted_at)
                                <tr>
                                    <th>Supprimé le:</th>
                                    <td class="text-danger">{{ $fee->deleted_at->format('d/m/Y à H:i') }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-6">
                            <a href="{{ route('esbtp.fees.edit', $fee) }}" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Modifier
                            </a>
                            <a href="{{ route('esbtp.fees.index') }}" class="btn btn-secondary">
                                <i class="fas fa-list"></i> Liste des frais
                            </a>
                        </div>
                        <div class="col-md-6 text-right">
                            <form action="{{ route('esbtp.fees.destroy', $fee) }}" method="POST" class="d-inline" 
                                  onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ces frais ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-trash"></i> Supprimer
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
