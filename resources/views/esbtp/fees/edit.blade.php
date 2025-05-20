@extends('layouts.app')

@section('title', 'Modifier les Frais de Scolarité')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Modifier les Frais de Scolarité</h3>
                    <div class="card-tools">
                        <a href="{{ route('esbtp.fees.index') }}" class="btn btn-default">
                            <i class="fas fa-arrow-left"></i> Retour
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('esbtp.fees.update', $fee) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="class_id">Classe</label>
                                    <select name="class_id" id="class_id" class="form-control @error('class_id') is-invalid @enderror" required>
                                        <option value="">Sélectionner une classe</option>
                                        @foreach($classes as $class)
                                            <option value="{{ $class->id }}" {{ old('class_id', $fee->class_id) == $class->id ? 'selected' : '' }}>
                                                {{ $class->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('class_id')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="academic_year_id">Année Académique</label>
                                    <select name="academic_year_id" id="academic_year_id" class="form-control @error('academic_year_id') is-invalid @enderror" required>
                                        <option value="">Sélectionner une année académique</option>
                                        @foreach($academicYears as $year)
                                            <option value="{{ $year->id }}" {{ old('academic_year_id', $fee->academic_year_id) == $year->id ? 'selected' : '' }}>
                                                {{ $year->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('academic_year_id')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="amount">Montant</label>
                                    <div class="input-group">
                                        <input type="number" name="amount" id="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount', $fee->amount) }}" required step="0.01" min="0">
                                        <div class="input-group-append">
                                            <span class="input-group-text">FCFA</span>
                                        </div>
                                    </div>
                                    @error('amount')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="due_date">Date d'échéance</label>
                                    <input type="date" name="due_date" id="due_date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date', $fee->due_date->format('Y-m-d')) }}" required>
                                    @error('due_date')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="payment_schedule">Mode de Paiement</label>
                                    <select name="payment_schedule" id="payment_schedule" class="form-control @error('payment_schedule') is-invalid @enderror" required>
                                        <option value="one_time" {{ old('payment_schedule', $fee->payment_schedule) == 'one_time' ? 'selected' : '' }}>Paiement unique</option>
                                        <option value="monthly" {{ old('payment_schedule', $fee->payment_schedule) == 'monthly' ? 'selected' : '' }}>Mensuel</option>
                                        <option value="termly" {{ old('payment_schedule', $fee->payment_schedule) == 'termly' ? 'selected' : '' }}>Trimestriel</option>
                                        <option value="yearly" {{ old('payment_schedule', $fee->payment_schedule) == 'yearly' ? 'selected' : '' }}>Annuel</option>
                                    </select>
                                    @error('payment_schedule')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status">Statut</label>
                                    <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                                        <option value="active" {{ old('status', $fee->status) == 'active' ? 'selected' : '' }}>Actif</option>
                                        <option value="inactive" {{ old('status', $fee->status) == 'inactive' ? 'selected' : '' }}>Inactif</option>
                                    </select>
                                    @error('status')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="installments_allowed" name="installments_allowed" value="1" {{ old('installments_allowed', $fee->installments_allowed) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="installments_allowed">Autoriser les versements</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="min_installment_amount">Montant minimum par versement</label>
                                    <div class="input-group">
                                        <input type="number" name="min_installment_amount" id="min_installment_amount" class="form-control @error('min_installment_amount') is-invalid @enderror" value="{{ old('min_installment_amount', $fee->min_installment_amount) }}" step="0.01" min="0">
                                        <div class="input-group-append">
                                            <span class="input-group-text">FCFA</span>
                                        </div>
                                    </div>
                                    @error('min_installment_amount')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="late_fee">Pénalité de retard</label>
                                    <div class="input-group">
                                        <input type="number" name="late_fee" id="late_fee" class="form-control @error('late_fee') is-invalid @enderror" value="{{ old('late_fee', $fee->late_fee) }}" step="0.01" min="0">
                                        <div class="input-group-append">
                                            <span class="input-group-text">FCFA</span>
                                        </div>
                                    </div>
                                    @error('late_fee')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $fee->description) }}</textarea>
                                    @error('description')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="fee_category_id" class="form-label">Catégorie de frais</label>
                                    <select name="fee_category_id" id="fee_category_id" class="form-select" required>
                                        <option value="">-- Sélectionner --</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}" {{ old('fee_category_id', $fee->fee_category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="filiere_id" class="form-label">Filière</label>
                                    <select name="filiere_id" id="filiere_id" class="form-select" required>
                                        <option value="">-- Sélectionner --</option>
                                        @foreach($filieres as $filiere)
                                            <option value="{{ $filiere->id }}" {{ old('filiere_id', $fee->filiere_id) == $filiere->id ? 'selected' : '' }}>{{ $filiere->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="niveau_id" class="form-label">Niveau d'étude</label>
                                    <select name="niveau_id" id="niveau_id" class="form-select" required>
                                        <option value="">-- Sélectionner --</option>
                                        @foreach($niveaux as $niveau)
                                            <option value="{{ $niveau->id }}" {{ old('niveau_id', $fee->niveau_id) == $niveau->id ? 'selected' : '' }}>{{ $niveau->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="annee_universitaire_id" class="form-label">Année universitaire</label>
                                    <select name="annee_universitaire_id" id="annee_universitaire_id" class="form-select" required>
                                        <option value="">-- Sélectionner --</option>
                                        @foreach($annees as $annee)
                                            <option value="{{ $annee->id }}" {{ old('annee_universitaire_id', $fee->annee_universitaire_id) == $annee->id ? 'selected' : '' }}>{{ $annee->annee }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Enregistrer les modifications
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Toggle min_installment_amount field based on installments_allowed
        $('#installments_allowed').change(function() {
            if ($(this).is(':checked')) {
                $('#min_installment_amount').prop('required', true);
            } else {
                $('#min_installment_amount').prop('required', false);
            }
        });

        // Trigger change event on page load to set initial state
        $('#installments_allowed').trigger('change');
    });
</script>
@endpush
