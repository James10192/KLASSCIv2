{{--
    Les seuils des tableaux de bord de pilotage (pédagogique et personnel).

    Les clés viennent des constantes des services, jamais de chaînes écrites
    ici : un renommage laisserait sinon le formulaire poster une clé disparue.
--}}
@php
    $_seuils = \App\Domain\AcademicPilotage\Services\SeuilsDePilotage::class;
    $_activite = \App\Services\Personnel\ActiviteDuPersonnel::class;
    $_cleRelance = $_seuils::REGLAGE_RELANCE_JOURS;
    $_clePresence = $_seuils::REGLAGE_PRESENCE_MIN;
    $_cleAttente = $_activite::REGLAGE_ATTENTE_JOURS;
    $_valRelance = \App\Helpers\SettingsHelper::get($_cleRelance, $_seuils::RELANCE_JOURS_REPLI);
    $_valPresence = \App\Helpers\SettingsHelper::get($_clePresence, $_seuils::PRESENCE_MIN_REPLI);
    $_valAttente = \App\Helpers\SettingsHelper::get($_cleAttente, $_activite::ATTENTE_JOURS_REPLI);
@endphp

<div class="bc-card">
    <div class="bc-icon"><i class="fas fa-chart-line"></i></div>
    <div class="bc-body">
        <div class="bc-label">Seuils des tableaux de pilotage</div>
        <div class="bc-desc">
            Ce que le pilotage académique et l'activité du personnel signalent. Ce sont des repères
            de votre établissement, pas des règles : adaptez-les à votre rythme.
        </div>

        <div class="row g-2" style="margin-top:.6rem;max-width:560px;">
            <div class="col-12 col-md-4">
                <label class="bc-desc" for="pil-relance" style="display:block;margin-bottom:.2rem;">Relancer les notes après (jours)</label>
                <input type="number" class="form-control form-control-sm" id="pil-relance" name="{{ $_cleRelance }}" min="0" max="90" value="{{ $_valRelance }}">
            </div>
            <div class="col-12 col-md-4">
                <label class="bc-desc" for="pil-presence" style="display:block;margin-bottom:.2rem;">Présence minimale (%)</label>
                <input type="number" class="form-control form-control-sm" id="pil-presence" name="{{ $_clePresence }}" min="1" max="100" value="{{ $_valPresence }}">
            </div>
            <div class="col-12 col-md-4">
                <label class="bc-desc" for="pil-attente" style="display:block;margin-bottom:.2rem;">Paiement en attente après (jours)</label>
                <input type="number" class="form-control form-control-sm" id="pil-attente" name="{{ $_cleAttente }}" min="0" max="90" value="{{ $_valAttente }}">
            </div>
        </div>
        <div class="bc-desc" style="margin-top:.35rem;">
            Une évaluation plus récente que le premier délai reste hors relance : l'enseignant corrige encore.
        </div>
    </div>
</div>
