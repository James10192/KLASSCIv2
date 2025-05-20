@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Éditer une règle de paramétrage</h4>
            <a href="{{ route('esbtp.fee-categories.edit', $fee_category) }}" class="btn btn-secondary btn-sm">Retour à la catégorie</a>
        </div>
        <div class="card-body">
            @if(isset($alert) && $alert)
                <div class="alert alert-warning">{{ $alert }}</div>
            @endif
            <form method="POST" action="{{ $rule->exists ? route('esbtp.fee-categories.rules.update', [$fee_category, $rule]) : route('esbtp.fee-categories.rules.store', $fee_category) }}" class="row g-3">
                @csrf
                @if($rule->exists)
                    @method('PUT')
                @endif
                <fieldset @if(isset($alert) && $alert) disabled @endif>
                    <div class="col-md-4">
                        <label for="filiere_id" class="form-label">Filière</label>
                        <select name="filiere_id" id="filiere_id" class="form-select">
                            <option value="">Toutes</option>
                            @foreach($filieres as $filiere)
                                <option value="{{ $filiere->id }}" @if($rule->filiere_id == $filiere->id) selected @endif>{{ $filiere->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="niveau_id" class="form-label">Niveau d'étude</label>
                        <select name="niveau_id" id="niveau_id" class="form-select">
                            <option value="">Tous</option>
                            @foreach($niveaux as $niveau)
                                <option value="{{ $niveau->id }}" @if($rule->niveau_id == $niveau->id) selected @endif>{{ $niveau->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="amount" class="form-label">Montant <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="amount" class="form-control" required min="0" step="1" value="{{ old('amount', $rule->amount) }}">
                    </div>
                    <div class="col-md-3">
                        <label for="payment_schedule" class="form-label">Échéancier <span class="text-danger">*</span></label>
                        <select name="payment_schedule" id="payment_schedule" class="form-select" required>
                            <option value="one_time" @if($rule->payment_schedule == 'one_time') selected @endif>Paiement unique</option>
                            <option value="monthly" @if($rule->payment_schedule == 'monthly') selected @endif>Mensuel</option>
                            <option value="termly" @if($rule->payment_schedule == 'termly') selected @endif>Trimestriel</option>
                            <option value="yearly" @if($rule->payment_schedule == 'yearly') selected @endif>Annuel</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="installments_allowed" class="form-label">Échéances autorisées</label>
                        <select name="installments_allowed" id="installments_allowed" class="form-select">
                            <option value="0" @if(!$rule->installments_allowed) selected @endif>Non</option>
                            <option value="1" @if($rule->installments_allowed) selected @endif>Oui</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="min_installment_amount" class="form-label">Montant min. échéance</label>
                        <input type="number" name="min_installment_amount" id="min_installment_amount" class="form-control" min="0" step="1" value="{{ old('min_installment_amount', $rule->min_installment_amount) }}">
                    </div>
                    <div class="col-md-2">
                        <label for="late_fee" class="form-label">Pénalité retard</label>
                        <input type="number" name="late_fee" id="late_fee" class="form-control" min="0" step="1" value="{{ old('late_fee', $rule->late_fee) }}">
                    </div>
                    <div class="col-md-4">
                        <label for="annee_universitaire_id" class="form-label">Année universitaire (optionnel)
                            <span data-bs-toggle="tooltip" title="Laisser vide pour appliquer la règle à toutes les années (récurrente). Si une règle existe pour une année précise, elle sera prioritaire." style="cursor: help; color: #0ea5e9;">&#9432;</span>
                        </label>
                        <select name="annee_universitaire_id" id="annee_universitaire_id" class="form-select">
                            <option value="">Toutes (récurrente)</option>
                            @foreach($annees as $annee)
                                <option value="{{ $annee->id }}" @if($rule->annee_universitaire_id == $annee->id) selected @endif>
                                    {{ $annee->libelle ?? (is_a($annee->start_date, 'Carbon\Carbon') ? $annee->start_date->format('Y') : (\Carbon\Carbon::parse($annee->start_date)->format('Y'))) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary">
                            {{ $rule->exists ? 'Enregistrer les modifications' : 'Créer la règle' }}
                        </button>
                    </div>
                </fieldset>
            </form>
        </div>
    </div>

    @if($rule->exists)
    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Échéances paramétrables</h5>
        </div>
        <div class="card-body p-0">
            @php $installments = $rule->installments()->orderBy('offset_days')->get(); @endphp
            @if($installments->count())
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Libellé</th>
                                <th>Décalage (mois)</th>
                                <th>Montant fixe</th>
                                <th>Pourcentage</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($installments as $inst)
                            <tr>
                                <td>{{ $inst->label ?? '-' }}</td>
                                <td>{{ $inst->offset_days ? round($inst->offset_days / 30, 1) . ' mois' : '-' }}</td>
                                <td>{{ $inst->amount ? number_format($inst->amount, 0, ',', ' ') . ' F CFA' : '-' }}</td>
                                <td>{{ $inst->pourcentage ? $inst->pourcentage . ' %' : '-' }}</td>
                                <td>
                                    <a href="{{ route('esbtp.fee-categories.rules.installments.edit', [$rule, $inst]) }}" class="btn btn-primary btn-sm">Éditer</a>
                                    <form action="{{ route('esbtp.fee-categories.rules.installments.destroy', [$rule, $inst]) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette échéance ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger btn-sm" type="submit">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-info m-3">Aucune échéance définie pour cette règle.</div>
            @endif
        </div>
        <div class="card-footer bg-light">
            <h6>Ajouter une échéance</h6>
            <form method="POST" action="{{ route('esbtp.fee-categories.rules.installments.store', $rule) }}" class="row g-3">
                @csrf
                <div class="col-md-3">
                    <label for="label" class="form-label">Libellé</label>
                    <input type="text" name="label" id="label" class="form-control" maxlength="100">
                </div>
                <div class="col-md-2">
                    <label for="offset_months" class="form-label">Décalage (mois) <span class="text-danger">*</span>
                        <span data-bs-toggle="tooltip" title="Nombre de mois après la rentrée ou la date de référence" style="cursor: help; color: #0ea5e9;">&#9432;</span>
                    </label>
                    <input type="number" name="offset_months" id="offset_months" class="form-control" required min="0">
                </div>
                <div class="col-md-2">
                    <label for="amount" class="form-label">Montant fixe</label>
                    <input type="number" name="amount" id="amount" class="form-control" min="0" step="1">
                </div>
                <div class="col-md-2">
                    <label for="pourcentage" class="form-label">Pourcentage (%)</label>
                    <input type="number" name="pourcentage" id="pourcentage" class="form-control" min="0" max="100">
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-success">Ajouter l'échéance</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection
