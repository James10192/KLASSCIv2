@extends('layouts.app')
@section('content')
<div class="container">
    <h2>Modifier la catégorie de frais</h2>
    <form action="{{ route('esbtp.fee-categories.update', $fee_category) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label for="name" class="form-label">Nom</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $fee_category->name) }}" required>
        </div>
        <div class="mb-3">
            <label for="code" class="form-label">Code</label>
            <input type="text" name="code" id="code" class="form-control" value="{{ old('code', $fee_category->code) }}" required>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea name="description" id="description" class="form-control">{{ old('description', $fee_category->description) }}</textarea>
        </div>
        <div class="mb-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="is_mandatory" name="is_mandatory" value="1" {{ old('is_mandatory', $fee_category->is_mandatory) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_mandatory">
                    <span class="badge bg-primary">Obligatoire</span> (ce frais sera imposé à tous les étudiants concernés)
                </label>
                <div class="form-text">Laisser décoché pour un service optionnel (cantine, transport, etc.).</div>
            </div>
        </div>
        <div class="mb-3">
            <label for="default_amount" class="form-label">Prix par défaut (optionnel)</label>
            <input type="number" step="0.01" name="default_amount" id="default_amount" class="form-control" value="{{ old('default_amount', $fee_category->default_amount) }}">
        </div>
        <div class="mb-3">
            <label for="is_active" class="form-label">Statut</label>
            <select name="is_active" id="is_active" class="form-select">
                <option value="1" {{ old('is_active', $fee_category->is_active) == 1 ? 'selected' : '' }}>Actif</option>
                <option value="0" {{ old('is_active', $fee_category->is_active) == 0 ? 'selected' : '' }}>Inactif</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Mettre à jour</button>
        <a href="{{ route('esbtp.fee-categories.index') }}" class="btn btn-secondary">Annuler</a>
    </form>

    <div class="card mt-5">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Règles de paramétrage</h5>
            <a href="#form-ajout-regle" class="btn btn-success btn-sm">Ajouter une règle</a>
        </div>
        <div class="card-body p-0">
            @php $rules = $fee_category->rules()->with(['filiere', 'niveau'])->get(); @endphp
            @if($rules->count())
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Filière</th>
                                <th>Niveau</th>
                                <th>Montant</th>
                                <th>Échéancier</th>
                                <th>Échéances autorisées</th>
                                <th>Montant min. échéance</th>
                                <th>Pénalité retard</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($rules as $rule)
                            <tr>
                                <td>{{ $rule->filiere?->name ?? '-' }}</td>
                                <td>{{ $rule->niveau?->name ?? '-' }}</td>
                                <td>{{ number_format($rule->amount, 0, ',', ' ') }} F CFA</td>
                                <td>{{ __($rule->payment_schedule) }}</td>
                                <td>{{ $rule->installments_allowed ? 'Oui' : 'Non' }}</td>
                                <td>{{ $rule->min_installment_amount ? number_format($rule->min_installment_amount, 0, ',', ' ') . ' F CFA' : '-' }}</td>
                                <td>{{ $rule->late_fee ? number_format($rule->late_fee, 0, ',', ' ') . ' F CFA' : '-' }}</td>
                                <td>
                                    <a href="{{ route('esbtp.fee-categories.rules.edit', [$fee_category, $rule]) }}" class="btn btn-primary btn-sm">Éditer</a>
                                    <form action="{{ route('esbtp.fee-categories.rules.destroy', [$fee_category, $rule]) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette règle ?')">
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
                <div class="alert alert-info m-3">Aucune règle de paramétrage définie pour cette catégorie.</div>
            @endif
        </div>
        <div class="card-footer bg-light" id="form-ajout-regle">
            <h6>Ajouter une règle de paramétrage</h6>
            <form method="POST" action="{{ route('esbtp.fee-categories.rules.store', $fee_category) }}" class="row g-3">
                @csrf
                <div class="col-md-4">
                    <label for="filiere_id" class="form-label">Filière</label>
                    <select name="filiere_id" id="filiere_id" class="form-select">
                        <option value="">Toutes</option>
                        @foreach(\App\Models\ESBTPFiliere::orderBy('name')->get() as $filiere)
                            <option value="{{ $filiere->id }}" {{ old('filiere_id', request('filiere_id')) == $filiere->id ? 'selected' : '' }}>{{ $filiere->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="niveau_id" class="form-label">Niveau d'étude</label>
                    <select name="niveau_id" id="niveau_id" class="form-select">
                        <option value="">Tous</option>
                        @foreach(\App\Models\ESBTPNiveauEtude::orderBy('name')->get() as $niveau)
                            <option value="{{ $niveau->id }}" {{ old('niveau_id', request('niveau_id')) == $niveau->id ? 'selected' : '' }}>{{ $niveau->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="amount" class="form-label">Montant <span class="text-danger">*</span></label>
                    <input type="number" name="amount" id="amount" class="form-control" required min="0" step="1" value="{{ old('amount', request('amount')) }}">
                </div>
                <div class="col-md-3">
                    <label for="payment_schedule" class="form-label">Échéancier <span class="text-danger">*</span></label>
                    <select name="payment_schedule" id="payment_schedule" class="form-select" required>
                        <option value="one_time" {{ old('payment_schedule', request('payment_schedule')) == 'one_time' ? 'selected' : '' }}>Paiement unique</option>
                        <option value="monthly" {{ old('payment_schedule', request('payment_schedule')) == 'monthly' ? 'selected' : '' }}>Mensuel</option>
                        <option value="termly" {{ old('payment_schedule', request('payment_schedule')) == 'termly' ? 'selected' : '' }}>Trimestriel</option>
                        <option value="yearly" {{ old('payment_schedule', request('payment_schedule')) == 'yearly' ? 'selected' : '' }}>Annuel</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="installments_allowed" class="form-label">Échéances autorisées</label>
                    <select name="installments_allowed" id="installments_allowed" class="form-select">
                        <option value="0" {{ old('installments_allowed', request('installments_allowed')) == '0' ? 'selected' : '' }}>Non</option>
                        <option value="1" {{ old('installments_allowed', request('installments_allowed')) == '1' ? 'selected' : '' }}>Oui</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="min_installment_amount" class="form-label">Montant min. échéance</label>
                    <input type="number" name="min_installment_amount" id="min_installment_amount" class="form-control" min="0" step="1" value="{{ old('min_installment_amount', request('min_installment_amount')) }}">
                </div>
                <div class="col-md-2">
                    <label for="late_fee" class="form-label">Pénalité retard</label>
                    <input type="number" name="late_fee" id="late_fee" class="form-control" min="0" step="1" value="{{ old('late_fee', request('late_fee')) }}">
                </div>
                <div class="col-md-4">
                    <label for="annee_universitaire_id" class="form-label">Année universitaire (optionnel)
                        <span data-bs-toggle="tooltip" title="Laisser vide pour appliquer la règle à toutes les années (récurrente). Si une règle existe pour une année précise, elle sera prioritaire." style="cursor: help; color: #0ea5e9;">&#9432;</span>
                    </label>
                    <select name="annee_universitaire_id" id="annee_universitaire_id" class="form-select">
                        <option value="">Toutes (récurrente)</option>
                        @foreach($annees as $annee)
                            <option value="{{ $annee->id }}" {{ old('annee_universitaire_id', request('annee_universitaire_id')) == $annee->id ? 'selected' : '' }}>
                                {{ $annee->libelle ?? (is_a($annee->start_date, 'Carbon\Carbon') ? $annee->start_date->format('Y') : (\Carbon\Carbon::parse($annee->start_date)->format('Y'))) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-success">Ajouter la règle</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
