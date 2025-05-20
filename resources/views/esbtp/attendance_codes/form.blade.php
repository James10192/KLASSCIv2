@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Marquer votre présence</h5>
                </div>
                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('esbtp.attendance-codes.submit') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="code" class="form-label">Code d'assiduité</label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror"
                                   id="code" name="code" required maxlength="6"
                                   placeholder="Entrez le code à 6 caractères"
                                   style="text-transform: uppercase;">
                            @error('code')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                Valider ma présence
                            </button>
                        </div>
                    </form>

                    <div class="mt-4">
                        <h6>Instructions :</h6>
                        <ul class="text-muted">
                            <li>Le code est valable uniquement pour la journée en cours</li>
                            <li>Vous avez droit à 3 tentatives maximum</li>
                            <li>Une fois utilisé, le code ne peut plus être réutilisé</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('code').addEventListener('input', function(e) {
    this.value = this.value.toUpperCase();
});
</script>
@endpush
