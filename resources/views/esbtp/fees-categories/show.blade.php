@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Détails de la catégorie de frais</h4>
            <div>
                <a href="{{ route('esbtp.fee-categories.edit', $fee_category) }}" class="btn btn-primary btn-sm">Éditer</a>
                <a href="{{ route('esbtp.fee-categories.index') }}" class="btn btn-secondary btn-sm">Retour</a>
            </div>
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">Nom</dt>
                <dd class="col-sm-9">{{ $fee_category->name }}</dd>
                <dt class="col-sm-3">Code</dt>
                <dd class="col-sm-9">{{ $fee_category->code }}</dd>
                <dt class="col-sm-3">Description</dt>
                <dd class="col-sm-9">{{ $fee_category->description ?? '-' }}</dd>
                <dt class="col-sm-3">Montant par défaut</dt>
                <dd class="col-sm-9">{{ $fee_category->default_amount ? number_format($fee_category->default_amount, 0, ',', ' ') . ' FCFA' : '-' }}</dd>
                <dt class="col-sm-3">Active</dt>
                <dd class="col-sm-9">
                    @if($fee_category->is_active)
                        <span class="badge bg-success">Oui</span>
                    @else
                        <span class="badge bg-danger">Non</span>
                    @endif
                </dd>
                <dt class="col-sm-3">Type</dt>
                <dd class="col-sm-9">
                    @if($fee_category->is_mandatory)
                        <span class="badge bg-primary">Obligatoire</span>
                    @else
                        <span class="badge bg-secondary">Optionnel</span>
                    @endif
                </dd>
            </dl>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Règles de paramétrage</h5>
            <a href="#" class="btn btn-success btn-sm disabled">Ajouter une règle</a> <!-- À activer plus tard -->
        </div>
        <div class="card-body p-0">
            @if($fee_category->rules->count())
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Filière</th>
                                <th>Niveau</th>
                                <th>Année Univ.</th>
                                <th>Montant</th>
                                <th>Échéancier</th>
                                <th>Échéances autorisées</th>
                                <th>Montant min. échéance</th>
                                <th>Pénalité retard</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($fee_category->rules as $rule)
                            <tr>
                                <td>{{ $rule->filiere?->nom ?? '-' }}</td>
                                <td>{{ $rule->niveau?->nom ?? '-' }}</td>
                                <td>{{ $rule->anneeUniversitaire?->libelle ?? '-' }}</td>
                                <td>{{ number_format($rule->amount, 0, ',', ' ') }} FCFA</td>
                                <td>{{ __($rule->payment_schedule) }}</td>
                                <td>{{ $rule->installments_allowed ? 'Oui' : 'Non' }}</td>
                                <td>{{ $rule->min_installment_amount ? number_format($rule->min_installment_amount, 0, ',', ' ') . ' FCFA' : '-' }}</td>
                                <td>{{ $rule->late_fee ? number_format($rule->late_fee, 0, ',', ' ') . ' FCFA' : '-' }}</td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-primary disabled">Éditer</a>
                                    <a href="#" class="btn btn-sm btn-danger disabled">Supprimer</a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-3 text-center text-muted">Aucune règle de paramétrage définie pour cette catégorie.</div>
            @endif
        </div>
    </div>
</div>
@endsection
