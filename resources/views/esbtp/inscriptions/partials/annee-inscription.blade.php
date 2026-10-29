{{-- Le choix de l'annee de l'inscription, et sa confirmation quand elle est
     terminee. Sortis de create.blade.php, deja au-dela de mille lignes. --}}
<label for="annee_universitaire_id">
    <i class="fas fa-calendar-alt me-1"></i> Année universitaire <span class="text-danger">*</span>
</label>
<select class="form-select @error('annee_universitaire_id') is-invalid @enderror"
        name="annee_universitaire_id"
        id="annee_universitaire_id"
        required>
    @foreach($academicYears->sortByDesc('start_date') as $annee)
        <option value="{{ $annee->id }}"
            {{ (old('annee_universitaire_id', $anneeEnCours->id ?? '') == $annee->id) ? 'selected' : '' }}
            data-is-current="{{ $annee->is_current ? '1' : '0' }}"
            data-start-date="{{ $annee->start_date?->format('Y-m-d') ?? '' }}">
            {{ $annee->name }}
            @if($annee->is_current) (Année courante) @endif
        </option>
    @endforeach
</select>
@error('annee_universitaire_id')
    <div class="invalid-feedback">{{ $message }}</div>
@enderror
{{-- Inscription sur une annee terminee : possible (un dossier en retard), mais
     confirmee. Le serveur dit quelles annees sont terminees
     (ESBTPAnneeUniversitaire::estTerminee, la regle de la barre du haut et de
     « Accepter et inscrire ») : aucun calcul de date dans le navigateur. --}}
@php
    $_echues = collect($academicYears)->filter->estTerminee()
        ->mapWithKeys(fn ($a) => [(string) $a->id => ['nom' => $a->name, 'fin' => $a->end_date->translatedFormat('j F Y')]]);
@endphp
<div class="form-check mt-2 p-2 ps-4 rounded" id="annee-echue-block" data-annees-echues='@json($_echues)' style="display:none;background:#fffcf5;border:1px solid #fde7c2">
    <input class="form-check-input" type="checkbox" id="annee_echue_confirmee">
    <label class="form-check-label" for="annee_echue_confirmee">
        L'année <strong data-annee-echue-nom></strong> est <strong>terminée</strong> (fin le <span data-annee-echue-fin></span>). Je confirme inscrire l'étudiant sur cette année.
    </label>
</div>
<script>
(function () {
    const bloc = document.getElementById('annee-echue-block');
    const champ = document.getElementById('annee_universitaire_id');
    if (!bloc || !champ) return;
    const echues = JSON.parse(bloc.dataset.anneesEchues || '{}');
    const caseConfirme = document.getElementById('annee_echue_confirmee');
    function maj() {
        const a = echues[champ.value] || null;
        bloc.style.display = a ? '' : 'none';
        caseConfirme.required = !!a;
        if (!a) { caseConfirme.checked = false; return; }
        bloc.querySelector('[data-annee-echue-nom]').textContent = a.nom;
        bloc.querySelector('[data-annee-echue-fin]').textContent = a.fin;
    }
    champ.addEventListener('change', maj);
    maj();
})();
</script>
