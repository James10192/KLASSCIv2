{{-- « Redoublant ? » au formulaire de nouvelle inscription (namespace rsr-*).
     L'élève est nouveau dans KLASSCI : rien à comparer avec l'année d'avant,
     la proposition est « Non », sauf pour un transféré qui a déclaré redoubler
     dans sa candidature. S'en écarter demande un motif, vérifié aussi par le
     serveur (StoreInscriptionRequest). --}}
@can(\App\Domain\Inscriptions\StatutRedoublant::PERMISSION)
@php
    $_redoublantPropose = \App\Domain\Inscriptions\QuestionRedoublant::propositionDeCandidature($candidatureSource ?? null) ? '1' : '0';
    $_redoublantAncien = old('redoublant', $_redoublantPropose) === '1' ? '1' : '0';
@endphp
<div class="col-12">
    <div class="rsr-carte" x-data="{ valeur: '{{ $_redoublantAncien }}', propose: '{{ $_redoublantPropose }}' }">
        <div class="rsr-tete">
            <span class="rsr-icone"><i class="fas fa-redo-alt"></i></span>
            <div>
                <div class="rsr-titre">Redoublant ?</div>
                @if($_redoublantPropose === '1')
                    <div class="rsr-aide">Proposé : oui. Dans sa candidature, il déclare recommencer l'année qu'il suivait dans son établissement d'origine.</div>
                @else
                    <div class="rsr-aide">Nouvel élève dans KLASSCI : pas d'année précédente à comparer. Répondez « Oui » s'il redouble ce niveau, par exemple en venant d'un autre établissement.</div>
                @endif
            </div>
        </div>
        <div class="rsr-choix" role="radiogroup" aria-label="Statut redoublant">
            <label class="rsr-option" :class="{ 'rsr-option--actif': valeur === '1' }">
                <input type="radio" name="redoublant" value="1" x-model="valeur"> Oui, il redouble
            </label>
            <label class="rsr-option" :class="{ 'rsr-option--actif': valeur === '0' }">
                <input type="radio" name="redoublant" value="0" x-model="valeur"> Non
            </label>
        </div>
        <div x-show="valeur !== propose" x-cloak>
            <label class="rsr-note" for="redoublant_motif"><i class="fas fa-pen"></i> Vous changez la réponse proposée : dites pourquoi (obligatoire, 10 caractères au moins).</label>
            <textarea id="redoublant_motif" name="redoublant_motif" class="rsr-motif" rows="2" maxlength="500"
                      :required="valeur !== propose" :disabled="valeur === propose" minlength="10"
                      placeholder="Exemple : redouble sa 1re année, venu d'un autre établissement">{{ old('redoublant_motif') }}</textarea>
        </div>
        @error('redoublant_motif')
            <div class="rsr-alerte">{{ $message }}</div>
        @enderror
    </div>
</div>

<style>
    .rsr-carte { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:1rem 1.25rem; box-shadow:0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); }
    .rsr-tete { display:flex; align-items:center; gap:.75rem; margin-bottom:.85rem; }
    .rsr-icone { width:40px; height:40px; border-radius:10px; background:linear-gradient(135deg,#0453cb,#3b7ddb); color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .rsr-titre { font-weight:700; color:#1e293b; }
    .rsr-aide { font-size:.82rem; color:#64748b; }
    .rsr-choix { display:flex; gap:.6rem; flex-wrap:wrap; }
    .rsr-option { display:flex; align-items:center; gap:.5rem; padding:.55rem .95rem; border:1px solid #cbd5e1; border-radius:10px; cursor:pointer; font-weight:600; color:#334155; transition:all .2s ease; margin:0; }
    .rsr-option input { accent-color:#0453cb; }
    .rsr-option--actif { border-color:#0453cb; background:rgba(4,83,203,.06); color:#0453cb; }
    .rsr-note { display:block; margin-top:.75rem; font-size:.8rem; color:#0453cb; font-weight:600; }
    .rsr-motif { width:100%; margin-top:.35rem; border:1px solid #cbd5e1; border-radius:10px; padding:.55rem .75rem; font-size:.88rem; }
    .rsr-alerte { margin-top:.65rem; font-size:.82rem; color:#b45309; }
</style>
@endcan
