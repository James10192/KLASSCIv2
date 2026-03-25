@extends('layouts.app')

@section('title', 'Pré-inscription | KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .pi-page { max-width: 800px; margin: 0 auto; padding: 20px; }
    .pi-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.06); border: 1px solid rgba(0,0,0,.08); overflow: hidden; }
    .pi-header { padding: 20px 24px; border-bottom: 1px solid rgba(0,0,0,.06); display: flex; align-items: center; gap: 12px; }
    .pi-header-icon { width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.1rem; }
    .pi-header h2 { margin: 0; font-size: 1.15rem; font-weight: 700; color: #1e293b; }
    .pi-header p { margin: 2px 0 0; font-size: .82rem; color: #64748b; }
    .pi-body { padding: 24px; }
    .pi-section { margin-bottom: 24px; }
    .pi-section-title { font-size: .82rem; font-weight: 700; color: #0453cb; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 12px; padding-bottom: 6px; border-bottom: 2px solid rgba(4,83,203,.1); }
    .pi-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 12px; }
    .pi-row.full { grid-template-columns: 1fr; }
    .pi-field label { display: block; font-size: .78rem; font-weight: 600; color: #475569; margin-bottom: 4px; }
    .pi-field label .required { color: #ef4444; }
    .pi-field input, .pi-field select { width: 100%; padding: 9px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: .88rem; color: #1e293b; transition: border-color .2s, box-shadow .2s; }
    .pi-field input:focus, .pi-field select:focus { outline: none; border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.12); }
    .pi-paiement-toggle { display: flex; align-items: center; gap: 10px; padding: 12px 16px; background: rgba(16,185,129,.06); border: 1px solid rgba(16,185,129,.2); border-radius: 8px; cursor: pointer; }
    .pi-paiement-toggle input[type=checkbox] { width: 18px; height: 18px; accent-color: #059669; }
    .pi-paiement-toggle span { font-size: .88rem; font-weight: 600; color: #065f46; }
    .pi-paiement-fields { margin-top: 12px; padding: 16px; background: rgba(16,185,129,.04); border: 1px solid rgba(16,185,129,.15); border-radius: 8px; }
    .pi-submit { display: flex; justify-content: flex-end; gap: 10px; padding: 16px 24px; border-top: 1px solid rgba(0,0,0,.06); background: #f8fafc; }
    .pi-btn { padding: 10px 20px; border-radius: 8px; font-size: .88rem; font-weight: 600; cursor: pointer; border: none; transition: all .2s; }
    .pi-btn-primary { background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); color: #fff; box-shadow: 0 2px 8px rgba(4,83,203,.25); }
    .pi-btn-primary:hover { box-shadow: 0 4px 12px rgba(4,83,203,.35); transform: translateY(-1px); }
    .pi-btn-secondary { background: #fff; color: #64748b; border: 1px solid #d1d5db; }
    .pi-info { padding: 12px 16px; background: rgba(4,83,203,.06); border: 1px solid rgba(4,83,203,.15); border-radius: 8px; font-size: .82rem; color: #1e40af; display: flex; align-items: flex-start; gap: 8px; margin-bottom: 20px; }
    .pi-info i { margin-top: 2px; }
</style>
@endpush

@section('page_title', 'Pré-inscription')

@section('content')
<div class="pi-page">

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="pi-card">
        <div class="pi-header">
            <div class="pi-header-icon"><i class="fas fa-user-plus"></i></div>
            <div>
                <h2>Pré-inscription rapide</h2>
                <p>Enregistrer un nouvel étudiant avec les informations minimales et encaisser le paiement</p>
            </div>
        </div>

        <form action="{{ route('esbtp.inscriptions.store-pre-inscription') }}" method="POST">
            @csrf
            <div class="pi-body">

                <div class="pi-info">
                    <i class="fas fa-info-circle"></i>
                    <div>L'administration complètera les informations manquantes (date de naissance, parents, adresse) ultérieurement. Seuls le nom, les prénoms et la classe sont obligatoires.</div>
                </div>

                {{-- Identité --}}
                <div class="pi-section">
                    <div class="pi-section-title"><i class="fas fa-user"></i> Identité de l'étudiant</div>
                    <div class="pi-row">
                        <div class="pi-field">
                            <label>Nom <span class="required">*</span></label>
                            <input type="text" name="nom" value="{{ old('nom') }}" required placeholder="Ex: KOUASSI">
                            @error('nom') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="pi-field">
                            <label>Prénoms <span class="required">*</span></label>
                            <input type="text" name="prenoms" value="{{ old('prenoms') }}" required placeholder="Ex: Jean-Marc">
                            @error('prenoms') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                    </div>
                    <div class="pi-row">
                        <div class="pi-field">
                            <label>Téléphone</label>
                            <input type="text" name="telephone" value="{{ old('telephone') }}" placeholder="Ex: 0708091011">
                        </div>
                        <div class="pi-field">
                            <label>Classe <span class="required">*</span></label>
                            <select name="classe_id" required>
                                <option value="">-- Sélectionner une classe --</option>
                                @foreach($classes as $classe)
                                    <option value="{{ $classe->id }}" {{ old('classe_id') == $classe->id ? 'selected' : '' }}>
                                        {{ $classe->name }} ({{ $classe->filiere->name ?? '' }} - {{ $classe->niveau->name ?? '' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('classe_id') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                    </div>
                </div>

                {{-- Paiement --}}
                <div class="pi-section">
                    <div class="pi-section-title"><i class="fas fa-money-bill-wave"></i> Paiement</div>

                    <label class="pi-paiement-toggle">
                        <input type="checkbox" id="toggle-paiement" {{ old('montant_paye') ? 'checked' : '' }}>
                        <span>Enregistrer un paiement maintenant</span>
                    </label>

                    <div class="pi-paiement-fields" id="paiement-fields" style="{{ old('montant_paye') ? '' : 'display:none;' }}">
                        <div class="pi-row">
                            <div class="pi-field">
                                <label>Montant payé (FCFA)</label>
                                <input type="number" name="montant_paye" value="{{ old('montant_paye') }}" min="0" step="500" placeholder="Ex: 150000">
                            </div>
                            <div class="pi-field">
                                <label>Mode de paiement</label>
                                <select name="mode_paiement">
                                    <option value="especes" {{ old('mode_paiement') == 'especes' ? 'selected' : '' }}>Espèces</option>
                                    <option value="mobile_money" {{ old('mode_paiement') == 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
                                    <option value="cheque" {{ old('mode_paiement') == 'cheque' ? 'selected' : '' }}>Chèque</option>
                                    <option value="virement" {{ old('mode_paiement') == 'virement' ? 'selected' : '' }}>Virement</option>
                                </select>
                            </div>
                        </div>
                        <div class="pi-row full">
                            <div class="pi-field">
                                <label>Référence paiement</label>
                                <input type="text" name="reference_paiement" value="{{ old('reference_paiement') }}" placeholder="N° reçu, référence transaction...">
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="pi-submit">
                <a href="{{ route('esbtp.inscriptions.index') }}" class="pi-btn pi-btn-secondary">Annuler</a>
                <button type="submit" class="pi-btn pi-btn-primary">
                    <i class="fas fa-check-circle"></i> Enregistrer la pré-inscription
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('toggle-paiement').addEventListener('change', function() {
    document.getElementById('paiement-fields').style.display = this.checked ? '' : 'none';
    if (!this.checked) {
        document.querySelector('[name="montant_paye"]').value = '';
    }
});
</script>
@endpush
