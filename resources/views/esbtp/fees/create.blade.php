@extends('layouts.app')

@section('title', 'Créer des Frais')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Créer des Nouveaux Frais</h3>
                    <div class="card-tools">
                        <a href="{{ route('esbtp.fees.index') }}" class="btn btn-default">
                            <i class="fas fa-arrow-left"></i> Retour
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('esbtp.fees.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="fee_category_id" class="form-label">Catégorie de frais <span class="text-danger">*</span></label>
                                    <select name="fee_category_id" id="fee_category_id" class="form-select" required>
                                        <option value="">-- Sélectionner une catégorie --</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ old('fee_category_id') == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }} ({{ $category->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('fee_category_id')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="class_id" class="form-label">Classe <span class="text-danger">*</span></label>
                                    <select name="class_id" id="class_id" class="form-select" required>
                                        <option value="">-- Sélectionner une classe --</option>
                                        @foreach($classes as $classe)
                                            <option value="{{ $classe->id }}" {{ old('class_id') == $classe->id ? 'selected' : '' }}>
                                                {{ $classe->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('class_id')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="academic_year_id" class="form-label">Année Universitaire <span class="text-danger">*</span></label>
                                    <select name="academic_year_id" id="academic_year_id" class="form-select" required>
                                        <option value="">-- Sélectionner une année --</option>
                                        @foreach($annees as $annee)
                                            <option value="{{ $annee->id }}" {{ old('academic_year_id') == $annee->id ? 'selected' : '' }}>
                                                {{ $annee->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('academic_year_id')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="inscription_id" class="form-label">Inscription (Optionnel)</label>
                                    <select name="inscription_id" id="inscription_id" class="form-select">
                                        <option value="">-- Sélectionner une inscription --</option>
                                        @foreach($inscriptions as $inscription)
                                            <option value="{{ $inscription->id }}" {{ old('inscription_id') == $inscription->id ? 'selected' : '' }}>
                                                {{ $inscription->etudiant->nom }} {{ $inscription->etudiant->prenom }} - {{ $inscription->etudiant->matricule }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('inscription_id')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="amount" class="form-label">Montant <span class="text-danger">*</span></label>
                                    <input type="number" name="amount" id="amount" class="form-control"
                                           value="{{ old('amount') }}" step="0.01" min="0" required>
                                    @error('amount')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="due_date" class="form-label">Date d'échéance</label>
                                    <input type="date" name="due_date" id="due_date" class="form-control"
                                           value="{{ old('due_date') }}">
                                    @error('due_date')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="payment_schedule" class="form-label">Calendrier de paiement</label>
                                    <select name="payment_schedule" id="payment_schedule" class="form-select">
                                        <option value="one_time" {{ old('payment_schedule') == 'one_time' ? 'selected' : '' }}>Paiement unique</option>
                                        <option value="monthly" {{ old('payment_schedule') == 'monthly' ? 'selected' : '' }}>Mensuel</option>
                                        <option value="termly" {{ old('payment_schedule') == 'termly' ? 'selected' : '' }}>Trimestriel</option>
                                        <option value="yearly" {{ old('payment_schedule') == 'yearly' ? 'selected' : '' }}>Annuel</option>
                                    </select>
                                    @error('payment_schedule')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status" class="form-label">Statut</label>
                                    <select name="status" id="status" class="form-select">
                                        <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>En attente</option>
                                        <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Actif</option>
                                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactif</option>
                                    </select>
                                    @error('status')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea name="description" id="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" name="installments_allowed" id="installments_allowed"
                                       class="form-check-input" value="1" {{ old('installments_allowed') ? 'checked' : '' }}>
                                <label for="installments_allowed" class="form-check-label">
                                    Autoriser les paiements par versements
                                </label>
                            </div>
                        </div>

                        <div class="row" id="installment_fields" style="display: none;">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="min_installment_amount" class="form-label">Montant minimum par versement</label>
                                    <input type="number" name="min_installment_amount" id="min_installment_amount"
                                           class="form-control" value="{{ old('min_installment_amount') }}" step="0.01" min="0">
                                    @error('min_installment_amount')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="late_fee" class="form-label">Frais de retard</label>
                                    <input type="number" name="late_fee" id="late_fee" class="form-control"
                                           value="{{ old('late_fee') }}" step="0.01" min="0">
                                    @error('late_fee')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-actions mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Créer les Frais
                            </button>
                            <a href="{{ route('esbtp.fees.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Annuler
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const installmentsCheckbox = document.getElementById('installments_allowed');
    const installmentFields = document.getElementById('installment_fields');

    function toggleInstallmentFields() {
        if (installmentsCheckbox.checked) {
            installmentFields.style.display = 'block';
        } else {
            installmentFields.style.display = 'none';
        }
    }

    installmentsCheckbox.addEventListener('change', toggleInstallmentFields);

    // Check initial state
    toggleInstallmentFields();
});
</script>
@endsection
