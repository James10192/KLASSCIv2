@extends('layouts.app')
@section('title', 'Modifier examen — '.$examen->titre)

@push('styles')
<style>
.exp-form-hero { background:linear-gradient(135deg,#0a3d8f,#0453cb,#3b7ddb);border-radius:18px;
    padding:1.5rem 2rem;color:#fff;margin-bottom:1.25rem;}
.exp-form-card { background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:1.5rem;}
.exp-form-grid { display:grid;grid-template-columns:repeat(2,1fr);gap:1rem; }
.exp-form-grid .full { grid-column:1/-1; }
.exp-form-field label { display:block;font-size:.75rem;color:#475569;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.35rem;}
.exp-form-field input, .exp-form-field select, .exp-form-field textarea {
    width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:.55rem .7rem;font-size:.88rem;background:#fff;color:#1e293b;}
.exp-form-actions { margin-top:1.5rem;display:flex;gap:.5rem;justify-content:flex-end;}
.btn-primary{background:#0453cb;color:#fff;border:none;padding:.6rem 1.2rem;border-radius:10px;font-weight:600;cursor:pointer;}
.btn-secondary{background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;padding:.6rem 1.2rem;border-radius:10px;font-weight:600;text-decoration:none;}
@media(max-width:768px){.exp-form-grid{grid-template-columns:1fr;}}
</style>
@endpush

@section('content')
<div class="exp-form-hero">
    <h1 style="margin:0;font-size:1.3rem;"><i class="fas fa-pen-to-square me-2"></i> Modifier examen</h1>
    <p style="margin:.25rem 0 0;color:rgba(255,255,255,.7);font-size:.85rem;">
        {{ $examen->numero_convocation ?? '—' }} · {{ $examen->classe->name ?? '' }} · {{ $examen->matiere->name ?? '' }}
    </p>
</div>

<form method="POST" action="{{ route('esbtp.examens.update', $examen) }}" class="exp-form-card">
    @csrf
    @method('PUT')

    <div class="exp-form-grid">
        <div class="exp-form-field full">
            <label>Titre *</label>
            <input type="text" name="titre" value="{{ old('titre', $examen->titre) }}" required maxlength="255">
        </div>
        <div class="exp-form-field">
            <label>Date début *</label>
            <input type="datetime-local" name="date_debut" value="{{ optional($examen->date_debut)->format('Y-m-d\TH:i') }}" required>
        </div>
        <div class="exp-form-field">
            <label>Date fin *</label>
            <input type="datetime-local" name="date_fin" value="{{ optional($examen->date_fin)->format('Y-m-d\TH:i') }}" required>
        </div>
        <div class="exp-form-field">
            <label>Durée (minutes)</label>
            <input type="number" name="duree_minutes" min="15" max="360" value="{{ old('duree_minutes', $examen->duree_minutes) }}">
        </div>
        <div class="exp-form-field">
            <label>Salle</label>
            <input type="text" name="salle" value="{{ old('salle', $examen->salle) }}" maxlength="100">
        </div>
        <div class="exp-form-field">
            <label>Coefficient</label>
            <input type="number" name="coefficient" step="0.5" min="0" max="99" value="{{ old('coefficient', $examen->coefficient) }}">
        </div>
        <div class="exp-form-field">
            <label>Barème</label>
            <input type="number" name="bareme" step="1" min="1" max="100" value="{{ old('bareme', $examen->bareme) }}">
        </div>
        <div class="exp-form-field">
            <label>Statut</label>
            <select name="status">
                @foreach(['draft', 'planned', 'in_progress', 'completed', 'cancelled'] as $s)
                <option value="{{ $s }}" @selected(old('status', $examen->status) == $s)>{{ ucfirst(str_replace('_',' ', $s)) }}</option>
                @endforeach
            </select>
        </div>
        <div class="exp-form-field">
            <label style="display:flex;align-items:center;gap:.5rem;text-transform:none;font-size:.85rem;font-weight:500;color:#1e293b;">
                <input type="checkbox" name="is_anonymous" value="1" {{ old('is_anonymous', $examen->is_anonymous) ? 'checked' : '' }} style="width:auto;">
                Anonymiser les copies
            </label>
        </div>
        <div class="exp-form-field full">
            <label>Description</label>
            <textarea name="description" rows="3" maxlength="1000">{{ old('description', $examen->description) }}</textarea>
        </div>
    </div>

    @if($errors->any())
    <div style="margin-top:1rem;padding:.75rem 1rem;background:rgba(220,38,38,.08);border:1px solid rgba(220,38,38,.2);border-radius:10px;color:#b91c1c;font-size:.85rem;">
        <ul style="margin:0;padding-left:1.2rem;">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
    </div>
    @endif

    <div class="exp-form-actions">
        <a href="{{ route('esbtp.examens.show', $examen) }}" class="btn-secondary"><i class="fas fa-xmark"></i> Annuler</a>
        <button type="submit" class="btn-primary"><i class="fas fa-check"></i> Enregistrer</button>
    </div>
</form>
@endsection
