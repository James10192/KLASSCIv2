{{--
    Les seuils qui colorent la moyenne générale et le taux de réussite de
    /esbtp/resultats. Les clés viennent des constantes de EtatDesResultats,
    jamais de chaînes écrites ici.
--}}
@php
    $_etat = app(\App\Domain\Bulletins\EtatDesResultats::class);
    $_cleMoyenne = \App\Domain\Bulletins\EtatDesResultats::REGLAGE_MOYENNE_SATISFAISANTE;
    $_cleBon = \App\Domain\Bulletins\EtatDesResultats::REGLAGE_REUSSITE_SATISFAISANTE;
    $_cleAlerte = \App\Domain\Bulletins\EtatDesResultats::REGLAGE_REUSSITE_ALERTE;
    [$_valAlerte, $_valBon] = $_etat->seuilsDeReussite();
    $_seuil = rtrim(rtrim(number_format(\App\Domain\Bulletins\EtatDesResultats::SEUIL_REUSSITE, 2, ',', ''), '0'), ',');
@endphp

<div class="bc-card">
    <div class="bc-icon"><i class="fas fa-traffic-light"></i></div>
    <div class="bc-body">
        <div class="bc-label">Couleurs de la page Résultats</div>
        <div class="bc-desc">
            La moyenne générale et le taux de réussite passent au vert, à l'orange ou au rouge
            selon ces repères. Une moyenne sous {{ $_seuil }}/20 est toujours en rouge.
        </div>

        <div class="row g-2" style="margin-top:.6rem;max-width:560px;">
            <div class="col-12 col-md-4">
                <label class="bc-desc" for="res-moyenne" style="display:block;margin-bottom:.2rem;">Moyenne en vert dès (/20)</label>
                <input type="number" class="form-control form-control-sm" id="res-moyenne" name="{{ $_cleMoyenne }}" min="{{ \App\Domain\Bulletins\EtatDesResultats::SEUIL_REUSSITE }}" max="20" step="0.5" value="{{ $_etat->moyenneSatisfaisante() }}">
            </div>
            <div class="col-12 col-md-4">
                <label class="bc-desc" for="res-bon" style="display:block;margin-bottom:.2rem;">Réussite en vert dès (%)</label>
                <input type="number" class="form-control form-control-sm" id="res-bon" name="{{ $_cleBon }}" min="1" max="100" value="{{ $_valBon }}">
            </div>
            <div class="col-12 col-md-4">
                <label class="bc-desc" for="res-alerte" style="display:block;margin-bottom:.2rem;">Réussite en rouge sous (%)</label>
                <input type="number" class="form-control form-control-sm" id="res-alerte" name="{{ $_cleAlerte }}" min="0" max="99" value="{{ $_valAlerte }}">
            </div>
        </div>
        <div class="bc-desc" style="margin-top:.35rem;">
            Entre les deux taux, la réussite est en orange : à surveiller.
        </div>
    </div>
</div>
