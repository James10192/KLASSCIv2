@extends('layouts.app')

@section('title', 'Saisie Notes LMD | KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .lmd-hero { background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); border-radius: 16px; padding: 1.5rem 2rem; color: white; margin-bottom: 1.5rem; }
    .lmd-hero-title { font-size: 1.35rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem; }
    .lmd-hero-subtitle { opacity: 0.85; margin-top: 0.25rem; font-size: 0.9rem; }
    .lmd-hero-meta { display: flex; gap: 1.5rem; margin-top: 0.75rem; }
    .lmd-hero-meta span { font-size: 0.85rem; opacity: 0.9; }
    .lmd-notes-table { width: 100%; border-collapse: collapse; }
    .lmd-notes-table th { background: #f1f5f9; padding: 10px 12px; font-size: 0.8rem; font-weight: 600; color: #475569; text-align: left; border-bottom: 2px solid #e2e8f0; }
    .lmd-notes-table td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .lmd-notes-table tr:hover td { background: #f8fafc; }
    .lmd-note-input { width: 80px; padding: 6px 8px; border: 1.5px solid #e2e8f0; border-radius: 8px; text-align: center; font-size: 0.9rem; font-weight: 600; transition: all 0.2s; }
    .lmd-note-input:focus { border-color: #0453cb; outline: none; box-shadow: 0 0 0 3px rgba(4,83,203,0.12); }
    .lmd-note-input.note-high { border-color: #10b981; background: #f0fdf4; }
    .lmd-note-input.note-low { border-color: #ef4444; background: #fef2f2; }
    .lmd-absent-check { width: 18px; height: 18px; accent-color: #ef4444; cursor: pointer; }
    .lmd-student-num { font-size: 0.8rem; color: #94a3b8; font-weight: 500; width: 35px; }
    .lmd-student-name { font-weight: 600; color: #1e293b; }
    .lmd-student-matricule { font-size: 0.8rem; color: #64748b; }
</style>
@endpush

@section('content')
<div class="lmd-page">

    {{-- Hero --}}
    <div class="lmd-hero">
        <div class="lmd-hero-title">
            <i class="fas fa-edit"></i> Saisie des Notes — {{ $evaluation->matiere->name ?? '' }}
        </div>
        <div class="lmd-hero-subtitle">
            @if($evaluation->matiere->uniteEnseignement)
                UE : {{ $evaluation->matiere->uniteEnseignement->code }} — {{ $evaluation->matiere->uniteEnseignement->name }}
            @endif
        </div>
        <div class="lmd-hero-meta">
            <span><i class="fas fa-chalkboard"></i> {{ $evaluation->classe->name ?? '' }}</span>
            <span><i class="fas fa-file-alt"></i> {{ ucfirst($evaluation->type) }} — Coeff. {{ $evaluation->coefficient }}</span>
            <span><i class="fas fa-ruler"></i> Barème: /{{ $evaluation->bareme }}</span>
            <span><i class="fas fa-calendar"></i> {{ $evaluation->date_evaluation ? \Carbon\Carbon::parse($evaluation->date_evaluation)->format('d/m/Y') : '' }}</span>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Form --}}
    <div class="main-card">
        <form action="{{ route('esbtp.lmd.notes.save-bulk') }}" method="POST" id="notesForm">
            @csrf
            <input type="hidden" name="evaluation_id" value="{{ $evaluation->id }}">

            <table class="lmd-notes-table">
                <thead>
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>Étudiant</th>
                        <th style="width: 100px;">Matricule</th>
                        <th style="width: 120px; text-align: center;">Note /{{ $evaluation->bareme }}</th>
                        <th style="width: 80px; text-align: center;">Absent</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($etudiants as $index => $etudiant)
                        <tr>
                            <td class="lmd-student-num">{{ $index + 1 }}</td>
                            <td>
                                <span class="lmd-student-name">{{ $etudiant->nom }} {{ $etudiant->prenoms }}</span>
                            </td>
                            <td class="lmd-student-matricule">{{ $etudiant->matricule }}</td>
                            <td style="text-align: center;">
                                <input type="hidden" name="notes[{{ $index }}][etudiant_id]" value="{{ $etudiant->id }}">
                                <input type="number"
                                       name="notes[{{ $index }}][note]"
                                       class="lmd-note-input"
                                       value="{{ $notesExistantes[$etudiant->id] ?? '' }}"
                                       min="0" max="{{ $evaluation->bareme }}" step="0.01"
                                       inputmode="decimal" lang="fr"
                                       placeholder="--"
                                       data-bareme="{{ $evaluation->bareme }}"
                                       oninput="colorNote(this)">
                            </td>
                            <td style="text-align: center;">
                                <input type="checkbox"
                                       name="notes[{{ $index }}][is_absent]"
                                       value="1"
                                       class="lmd-absent-check"
                                       {{ ($absencesExistantes[$etudiant->id] ?? false) ? 'checked' : '' }}
                                       onchange="toggleAbsent(this, {{ $index }})">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; padding-top: 1rem; border-top: 2px solid #f1f5f9;">
                <a href="{{ route('esbtp.lmd.notes.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour
                </a>
                <button type="submit" class="btn-acasi primary">
                    <i class="fas fa-save me-1"></i> Enregistrer les notes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function colorNote(input) {
    const val = parseFloat(input.value);
    const bareme = parseFloat(input.dataset.bareme) || 20;
    const pct = val / bareme;
    input.classList.remove('note-high', 'note-low');
    if (!isNaN(val)) {
        input.classList.add(pct >= 0.5 ? 'note-high' : 'note-low');
    }
}

function toggleAbsent(checkbox, index) {
    const noteInput = document.querySelector(`input[name="notes[${index}][note]"]`);
    if (checkbox.checked) {
        noteInput.value = '';
        noteInput.disabled = true;
        noteInput.classList.remove('note-high', 'note-low');
    } else {
        noteInput.disabled = false;
    }
}

// Init coloring on page load
document.querySelectorAll('.lmd-note-input').forEach(input => {
    if (input.value) colorNote(input);
});
</script>
@endpush
