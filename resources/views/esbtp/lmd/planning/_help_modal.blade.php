{{-- Modal Aide — glossaire UEMOA + comment lire la maquette.
     Extracted from index.blade.php for no-god-code compliance. --}}
<div class="lp-help-backdrop" id="lpHelpBackdrop">
    <div class="lp-help-modal" role="dialog" aria-modal="true" aria-labelledby="lpHelpTitle">
        <div class="lp-help-header">
            <h3 id="lpHelpTitle"><i class="fas fa-question-circle"></i> Aide — Planning LMD</h3>
            <button type="button" class="lp-help-close" data-help-close aria-label="Fermer">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="lp-help-body">
            <h4>Glossaire UEMOA</h4>
            <dl class="lp-help-glossary">
                <dt>UE</dt>
                <dd>Unité d'Enseignement — un regroupement cohérent d'ECUE (ex : « Mathématiques fondamentales »).</dd>
                <dt>ECUE</dt>
                <dd>Élément Constitutif d'une UE — l'équivalent d'une matière (ex : « Algèbre linéaire »).</dd>
                <dt>CECT</dt>
                <dd>Crédit Européen de Capitalisation et de Transfert — quantifie la charge de travail. 1 CECT ≈ 25 à 30 heures.</dd>
                <dt>CM</dt><dd>Cours Magistral — heures d'enseignement frontal.</dd>
                <dt>TD</dt><dd>Travaux Dirigés — heures d'application en petit groupe.</dd>
                <dt>TP</dt><dd>Travaux Pratiques — heures de laboratoire ou d'atelier.</dd>
                <dt>Projet</dt><dd>Heures de projet encadré (généralement en groupe).</dd>
                <dt>TPE</dt><dd>Travail Personnel de l'Étudiant — heures attendues hors présentiel.</dd>
            </dl>
            <h4>Comment lire la maquette</h4>
            <ol>
                <li>Choisis un <strong>parcours</strong> dans le filtre du haut. Tant qu'aucun parcours n'est sélectionné, la liste reste vide.</li>
                <li>Restreins ensuite par <strong>niveau</strong> (L1, L2, L3, M1...). Le filtre <strong>semestre</strong> se met automatiquement à jour pour ne proposer que les semestres réellement importés pour cette année.</li>
                <li>Chaque ligne UE est dépliable : clique dessus pour voir ses ECUE et leurs volumes horaires.</li>
                <li>Les colonnes CM / TD / TP / Projet / TPE / Total viennent des planifications académiques. Si une ECUE n'a pas de planification, elle apparaît avec « non planifié ».</li>
            </ol>
            <h4>Modifier les volumes horaires</h4>
            <p>Pour modifier les volumes horaires (CM, TD, TP, Projet, TPE) et les crédits ECTS d'un ECUE en cliquant directement sur la cellule, <strong>3 conditions cumulatives</strong> doivent être réunies :</p>
            <ol class="lp-help-conditions">
                <li><i class="fas fa-key"></i> Vous avez la <strong>permission <code>lmd.planning.edit</code></strong> (accordée par défaut aux <em>SuperAdmin</em>, <em>Service Technique</em> et <em>Coordinateurs</em>).</li>
                <li><i class="fas fa-layer-group"></i> Un <strong>niveau</strong> est sélectionné dans le filtre (Licence 1, Licence 2, Licence 3, Master 1, Master 2, etc.).</li>
                <li><i class="fas fa-calendar-week"></i> Un <strong>sémestre</strong> est sélectionné dans le filtre (S1 à S6 selon le niveau).</li>
            </ol>
            <p class="lp-help-tip"><i class="fas fa-lightbulb"></i> <strong>Astuce.</strong> Un encart bleu apparaît au-dessus du tableau si l'édition est désactivée à cause de filtres manquants.</p>

            <h4>Importer une maquette</h4>
            <p>Les UE et ECUE sont importées via la commande CLI dédiée :</p>
            <ul>
                <li><code>klassci lmd:import &lt;tenant&gt; --file=maquette.json</code> — extraction depuis un JSON spec.</li>
                <li>Une fois importée, le bouton « Modifier les UE » permet d'ajuster le rattachement UE↔Parcours par semestre.</li>
            </ul>
            <h4>Astuce</h4>
            <p>Le bouton <strong>Guide</strong> en haut à droite lance un tour interactif qui surligne chaque zone de la page.</p>
        </div>
    </div>
</div>

{{-- Tour overlay (backdrop sombre activé via .lp-tour-open) --}}
<div class="lp-tour-overlay" id="lpTourOverlay" aria-hidden="true"></div>
