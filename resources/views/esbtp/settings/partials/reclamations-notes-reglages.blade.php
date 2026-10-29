{{--
    Réclamations de notes : ouverture et délai. Les clés viennent des
    constantes de ReglagesReclamations, jamais de chaînes écrites ici.
--}}
@php
    $_rec = \App\Domain\Notes\Reclamations\ReglagesReclamations::class;
    $_recActif = $_rec::REGLAGE_ACTIF;
    $_recDelai = $_rec::REGLAGE_DELAI_JOURS;
    $_recOuvert = \App\Helpers\SettingsHelper::drapeau($_recActif, true);
    $_recJours = app($_rec)->delaiJours();
@endphp

<div class="bc-card">
    <div class="bc-icon"><i class="fas fa-flag"></i></div>
    <div class="bc-body">
        <div class="bc-label">Réclamations de notes</div>
        <div class="bc-desc">
            L'élève conteste une note depuis son espace, photo de la copie obligatoire. L'enseignant de
            l'évaluation donne son avis ; le personnel qui a la permission « Traiter les réclamations de
            notes » accepte (la note est corrigée) ou maintient.
        </div>
        <div class="row g-2" style="margin-top:.6rem;max-width:560px;">
            <div class="col-12 col-md-6">
                <label class="bc-desc" for="rec-delai" style="display:block;margin-bottom:.2rem;">Délai pour contester (jours)</label>
                <input type="number" class="form-control form-control-sm" id="rec-delai" name="{{ $_recDelai }}" min="1" max="365" value="{{ $_recJours }}">
            </div>
        </div>
    </div>
    <div class="bc-toggle">
        <label class="form-switch-modern">
            <input type="checkbox" name="{{ $_recActif }}" value="1" {{ $_recOuvert ? 'checked' : '' }}>
            <span class="slider"></span>
        </label>
    </div>
</div>
