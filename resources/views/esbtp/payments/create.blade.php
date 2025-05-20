@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Ajouter un paiement</h2>
    <form action="{{ route('esbtp.payments.store') }}" method="POST">
        @csrf
        @if(isset($inscriptionId) && $inscriptionId)
            <input type="hidden" name="inscription_id" value="{{ $inscriptionId }}">
        @endif

        <div class="mb-3">
            <label for="student_id" class="form-label">Étudiant</label>
            <select name="student_id" id="student_id" class="form-select" required>
                <option value="">-- Sélectionner --</option>
                @foreach($students as $student)
                    <option value="{{ $student->id }}" {{ old('student_id') == $student->id ? 'selected' : '' }}>
                        {{ $student->nom_complet ?? ($student->prenoms . ' ' . $student->nom) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="amount" class="form-label">Montant</label>
            <input type="number" step="0.01" name="amount" id="amount" class="form-control" value="{{ old('amount') }}" required>
        </div>

        <div class="mb-3">
            <label for="payment_date" class="form-label">Date du paiement</label>
            <input type="date" name="payment_date" id="payment_date" class="form-control" value="{{ old('payment_date', date('Y-m-d')) }}" required>
        </div>

        <div class="mb-3">
            <label for="payment_method" class="form-label">Méthode de paiement</label>
            <select name="payment_method" id="payment_method" class="form-select" required>
                <option value="">-- Sélectionner --</option>
                <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>Espèces</option>
                <option value="bank_transfer" {{ old('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Virement</option>
                <option value="check" {{ old('payment_method') == 'check' ? 'selected' : '' }}>Chèque</option>
                <option value="mobile_money" {{ old('payment_method') == 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="reference_number" class="form-label">Référence</label>
            <input type="text" name="reference_number" id="reference_number" class="form-control" value="{{ old('reference_number') }}">
        </div>

        <div class="mb-3">
            <label for="category_id" class="form-label">Catégorie de paiement</label>
            <select name="category_id" id="category_id" class="form-select" required>
                <option value="">-- Sélectionner --</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="status" class="form-label">Statut</label>
            <select name="status" id="status" class="form-select" required>
                <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>En attente</option>
                <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Validé</option>
                <option value="failed" {{ old('status') == 'failed' ? 'selected' : '' }}>Échoué</option>
                <option value="refunded" {{ old('status') == 'refunded' ? 'selected' : '' }}>Remboursé</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea name="description" id="description" class="form-control">{{ old('description') }}</textarea>
        </div>

        @if(isset($fees) && $fees->count())
            <div class="mb-3">
                <label for="fee_id" class="form-label">Échéance à régler</label>
                <select name="fee_id" id="fee_id" class="form-select" required>
                    <option value="">-- Sélectionner une échéance --</option>
                    @foreach($fees as $fee)
                        <option value="{{ $fee->id }}" {{ old('fee_id') == $fee->id ? 'selected' : '' }}>
                            {{ $fee->description }} | {{ number_format($fee->amount, 0, ',', ' ') }} FCFA | Échéance : {{ $fee->due_date ? $fee->due_date->format('d/m/Y') : 'N/A' }} | Statut : {{ ucfirst($fee->status) }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="{{ url()->previous() }}" class="btn btn-secondary">Annuler</a>
    </form>
</div>
@endsection
