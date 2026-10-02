{{--
    Surveillance des lenteurs : les deux seuils au-dessus desquels une page ou un
    travail laisse une trace. Les clés viennent des constantes de SeuilsDesTraces.
--}}
@php
    $_tl = app(\App\Domain\Exploitation\TracesLentes\SeuilsDesTraces::class);
    $_tlCleDuree = \App\Domain\Exploitation\TracesLentes\SeuilsDesTraces::REGLAGE_DUREE_MS;
    $_tlCleSql = \App\Domain\Exploitation\TracesLentes\SeuilsDesTraces::REGLAGE_REQUETES;
    $_tlS = \App\Domain\Exploitation\TracesLentes\SeuilsDesTraces::class;
@endphp
<div class="settings-section">
    <div class="section-header">
        <div class="section-icon"><i class="fas fa-gauge-high"></i></div>
        <div>
            <h3 class="section-title">Surveillance des lenteurs</h3>
            <p class="section-description">Une page, un export, un envoi ou une tâche planifiée plus lent que ces seuils est noté, pour que l'équipe KLASSCI le corrige. Rien n'est noté en dessous, et la personne connectée n'attend jamais cette écriture.</p>
        </div>
    </div>
    <div class="bc-grid bc-grid-1">
        <div class="bc-card">
            <div class="bc-icon"><i class="fas fa-stopwatch"></i></div>
            <div class="bc-body">
                <div class="bc-label">Seuils</div>
                <div class="bc-desc">Les erreurs signalées sont toujours notées, quel que soit le seuil ; une page arrêtée par la limite de temps ou de mémoire du serveur ne laisse pas de note. Les notes sont effacées au bout de trente jours.</div>
                <div class="row g-2" style="margin-top:.6rem;max-width:560px;">
                    <div class="col-12 col-md-6">
                        <label class="bc-desc" for="tl-duree" style="display:block;margin-bottom:.2rem;">Durée (millisecondes)</label>
                        <input type="number" class="form-control form-control-sm" id="tl-duree" name="{{ $_tlCleDuree }}" min="{{ $_tlS::DUREE_MIN_MS }}" max="{{ $_tlS::DUREE_MAX_MS }}" step="50" value="{{ $_tl->dureeMs() }}">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="bc-desc" for="tl-sql" style="display:block;margin-bottom:.2rem;">Requêtes à la base</label>
                        <input type="number" class="form-control form-control-sm" id="tl-sql" name="{{ $_tlCleSql }}" min="{{ $_tlS::REQUETES_MIN }}" max="{{ $_tlS::REQUETES_MAX }}" value="{{ $_tl->requetes() }}">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
