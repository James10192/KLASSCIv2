{{-- Rendez-vous au guichet.

     Troisieme carte du portail public, apres la reinscription en ligne et les
     candidatures. Elle ne cree pas le PREMIER jour de reception : c'est le
     champ « Debut des inscriptions sur place » de la carte du dessus, que le
     portail publie deja pour repondre a « je viens quand ? ». Deux dates pour
     une seule rentree finiraient par diverger, et la famille lirait deux
     instructions contradictoires sur le meme ecran.

     Le nombre de personnes recues dans la journee ne se saisit pas : il
     s'affiche. Les heures et la duree le determinent deja, et le demander en
     plus donnerait deux chiffres pour une seule question. Ce que l'ecole
     declare, c'est combien de familles elle recoit A LA FOIS — autrement dit
     combien de guichets elle tient. --}}
@php
    $_cfgRdv = \App\Services\RendezVous\ConfigurationRendezVous::class;
    $_rdvActif = \App\Helpers\SettingsHelper::get($_cfgRdv::REGLAGE_ACTIF, '0') == '1';
    $_rdvJours = array_filter(explode(',', (string) \App\Helpers\SettingsHelper::get($_cfgRdv::REGLAGE_JOURS_OUVERTS, '')));
    $_rdvNomsJours = [1 => 'Lun', 2 => 'Mar', 3 => 'Mer', 4 => 'Jeu', 5 => 'Ven', 6 => 'Sam', 7 => 'Dim'];
@endphp

<div class="bc-card rdv-card">
    <div class="bc-icon"><i class="fas fa-calendar-check"></i></div>
    <div class="bc-body">
        <div class="bc-label">Rendez-vous au guichet</div>
        <div class="bc-desc">
            Propose un creneau a la famille juste apres son depot en ligne, pour etaler la file
            au lieu de la subir le premier matin. Desactive par defaut. Le <strong>premier jour</strong>
            de reception est celui saisi ci-dessus, « Debut des inscriptions sur place ».
        </div>

        <div class="row g-2 rdv-champs" style="margin-top:.6rem;max-width:640px;">
            <div class="col-6">
                <label class="bc-desc" for="rdv-dernier-jour" style="display:block;margin-bottom:.2rem;">Dernier jour de reception</label>
                <input type="date" class="form-control form-control-sm rdv-input" id="rdv-dernier-jour"
                       name="{{ $_cfgRdv::REGLAGE_DERNIER_JOUR }}"
                       value="{{ \App\Helpers\SettingsHelper::get($_cfgRdv::REGLAGE_DERNIER_JOUR, '') }}">
            </div>
            <div class="col-6">
                <label class="bc-desc" style="display:block;margin-bottom:.2rem;">Jours de reception</label>
                <div class="rdv-jours">
                    @foreach($_rdvNomsJours as $_num => $_nom)
                        <label class="rdv-jour">
                            <input type="checkbox" class="rdv-jour-case" value="{{ $_num }}"
                                   {{ in_array((string) $_num, $_rdvJours, true) ? 'checked' : '' }}>
                            <span>{{ $_nom }}</span>
                        </label>
                    @endforeach
                </div>
                {{-- Les cases pilotent ce champ cache, qui est le seul poste.
                     Si le script ne tourne pas, il garde la valeur enregistree :
                     l'ecole ne perd pas sa configuration, elle ne peut
                     simplement pas la changer d'ici. --}}
                <input type="hidden" class="rdv-input" id="rdv-jours-ouverts"
                       name="{{ $_cfgRdv::REGLAGE_JOURS_OUVERTS }}"
                       value="{{ \App\Helpers\SettingsHelper::get($_cfgRdv::REGLAGE_JOURS_OUVERTS, '') }}">
            </div>

            <div class="col-6 col-md-3">
                <label class="bc-desc" for="rdv-ouverture" style="display:block;margin-bottom:.2rem;">Ouverture</label>
                <input type="time" class="form-control form-control-sm rdv-input" id="rdv-ouverture"
                       name="{{ $_cfgRdv::REGLAGE_OUVERTURE }}"
                       value="{{ \App\Helpers\SettingsHelper::get($_cfgRdv::REGLAGE_OUVERTURE, '') }}">
            </div>
            <div class="col-6 col-md-3">
                <label class="bc-desc" for="rdv-fermeture" style="display:block;margin-bottom:.2rem;">Fermeture</label>
                <input type="time" class="form-control form-control-sm rdv-input" id="rdv-fermeture"
                       name="{{ $_cfgRdv::REGLAGE_FERMETURE }}"
                       value="{{ \App\Helpers\SettingsHelper::get($_cfgRdv::REGLAGE_FERMETURE, '') }}">
            </div>
            <div class="col-6 col-md-3">
                <label class="bc-desc" for="rdv-pause-debut" style="display:block;margin-bottom:.2rem;">Pause, de</label>
                <input type="time" class="form-control form-control-sm rdv-input" id="rdv-pause-debut"
                       name="{{ $_cfgRdv::REGLAGE_PAUSE_DEBUT }}"
                       value="{{ \App\Helpers\SettingsHelper::get($_cfgRdv::REGLAGE_PAUSE_DEBUT, '') }}">
            </div>
            <div class="col-6 col-md-3">
                <label class="bc-desc" for="rdv-pause-fin" style="display:block;margin-bottom:.2rem;">a</label>
                <input type="time" class="form-control form-control-sm rdv-input" id="rdv-pause-fin"
                       name="{{ $_cfgRdv::REGLAGE_PAUSE_FIN }}"
                       value="{{ \App\Helpers\SettingsHelper::get($_cfgRdv::REGLAGE_PAUSE_FIN, '') }}">
            </div>

            <div class="col-6">
                <label class="bc-desc" for="rdv-duree" style="display:block;margin-bottom:.2rem;">Temps par famille (minutes)</label>
                <input type="number" min="5" max="480" step="5" class="form-control form-control-sm rdv-input" id="rdv-duree"
                       name="{{ $_cfgRdv::REGLAGE_DUREE }}"
                       value="{{ \App\Helpers\SettingsHelper::get($_cfgRdv::REGLAGE_DUREE, '') }}">
                <div class="bc-desc" style="margin-top:.2rem;">Une estimation suffit : elle fixe la duree des creneaux.</div>
            </div>
            <div class="col-6">
                <label class="bc-desc" for="rdv-capacite" style="display:block;margin-bottom:.2rem;">Familles recues en meme temps</label>
                <input type="number" min="1" max="50" step="1" class="form-control form-control-sm rdv-input" id="rdv-capacite"
                       name="{{ $_cfgRdv::REGLAGE_CAPACITE }}"
                       value="{{ \App\Helpers\SettingsHelper::get($_cfgRdv::REGLAGE_CAPACITE, '') }}">
                <div class="bc-desc" style="margin-top:.2rem;">Autrement dit, combien de guichets vous tenez en parallele.</div>
            </div>
        </div>

        {{-- Le resultat. Rempli par le serveur a chaque modification : le
             recopier en JavaScript donnerait deux arithmetiques pour une seule
             regle, et cet ecran finirait par annoncer des places que la grille
             reelle ne produit pas. --}}
        <div class="rdv-resultat" id="rdv-resultat" data-url="{{ route('esbtp.settings.rdv.apercu') }}" hidden>
            <div class="rdv-chiffres">
                <div class="rdv-chiffre">
                    <span class="rdv-valeur" data-rdv="places_par_jour">—</span>
                    <span class="rdv-legende">personnes par jour</span>
                </div>
                <div class="rdv-chiffre">
                    <span class="rdv-valeur" data-rdv="creneaux_par_jour">—</span>
                    <span class="rdv-legende">creneaux par jour</span>
                </div>
                <div class="rdv-chiffre">
                    <span class="rdv-valeur" data-rdv="jours_de_reception">—</span>
                    <span class="rdv-legende">jours de reception</span>
                </div>
                <div class="rdv-chiffre">
                    <span class="rdv-valeur" data-rdv="places_sur_la_campagne">—</span>
                    <span class="rdv-legende">places en tout</span>
                </div>
            </div>
            <div class="rdv-verdict" id="rdv-verdict" hidden></div>
            <ul class="rdv-problemes" id="rdv-problemes" hidden></ul>
        </div>
    </div>
    <div class="bc-toggle">
        <label class="form-switch-modern">
            <input type="checkbox" class="rdv-input" id="rdv-actif"
                   name="{{ $_cfgRdv::REGLAGE_ACTIF }}" value="1" {{ $_rdvActif ? 'checked' : '' }}>
            <span class="slider"></span>
        </label>
    </div>
</div>

@push('styles')
<style>
.rdv-jours { display:flex; flex-wrap:wrap; gap:.3rem; }
.rdv-jour { display:inline-flex; align-items:center; gap:.25rem; font-size:.74rem; color:#475569;
            border:1px solid #e2e8f0; border-radius:7px; padding:.25rem .45rem; cursor:pointer;
            background:#fff; margin:0; }
.rdv-jour input { margin:0; }
.rdv-resultat { margin-top:.9rem; padding:.85rem 1rem; border:1px solid #e2e8f0; border-radius:12px;
                background:#f8fafc; max-width:640px; }
.rdv-chiffres { display:flex; flex-wrap:wrap; gap:1.25rem; }
.rdv-chiffre { display:flex; flex-direction:column; min-width:92px; }
.rdv-valeur { font-size:1.35rem; font-weight:700; color:#0453cb; line-height:1.1; }
.rdv-legende { font-size:.68rem; color:#64748b; text-transform:uppercase; letter-spacing:.4px; margin-top:.15rem; }
.rdv-verdict { margin-top:.75rem; font-size:.82rem; color:#1e293b; line-height:1.45; }
.rdv-verdict.rdv-verdict--court { color:#b45309; font-weight:600; }
.rdv-problemes { margin:.75rem 0 0; padding-left:1.1rem; font-size:.8rem; color:#b45309; }
.rdv-problemes li { margin-bottom:.2rem; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var resultat = document.getElementById('rdv-resultat');
    if (!resultat) { return; }

    var carte = resultat.closest('.rdv-card');
    var verdict = document.getElementById('rdv-verdict');
    var problemes = document.getElementById('rdv-problemes');
    var champJours = document.getElementById('rdv-jours-ouverts');
    var jeton = document.querySelector('meta[name="csrf-token"]');
    var enCours = null;
    var minuteur = null;

    function synchroniserLesJours() {
        var coches = [];
        carte.querySelectorAll('.rdv-jour-case').forEach(function (case_) {
            if (case_.checked) { coches.push(case_.value); }
        });
        champJours.value = coches.join(',');
    }

    function valeurs() {
        var charge = {};
        carte.querySelectorAll('.rdv-input').forEach(function (champ) {
            if (!champ.name) { return; }
            charge[champ.name] = (champ.type === 'checkbox') ? (champ.checked ? '1' : '0') : champ.value;
        });

        // Le premier jour vit dans la carte du dessus : c'est le reglage des
        // inscriptions sur place, et il n'est pas duplique ici.
        var premierJour = document.getElementById('ci-physiques');
        if (premierJour) {
            charge['{{ $_cfgRdv::REGLAGE_PREMIER_JOUR }}'] = premierJour.value;
        }

        return charge;
    }

    function afficher(reponse) {
        resultat.hidden = false;

        Object.keys(reponse).forEach(function (cle) {
            var cible = resultat.querySelector('[data-rdv="' + cle + '"]');
            if (cible) { cible.textContent = reponse.complete ? reponse[cle] : '—'; }
        });

        problemes.innerHTML = '';
        if (reponse.problemes && reponse.problemes.length) {
            reponse.problemes.forEach(function (texte) {
                var ligne = document.createElement('li');
                ligne.textContent = texte;
                problemes.appendChild(ligne);
            });
            problemes.hidden = false;
        } else {
            problemes.hidden = true;
        }

        if (reponse.verdict) {
            verdict.textContent = reponse.verdict;
            // Le ton change quand la periode ne suffit pas : c'est le seul cas
            // ou l'ecole doit agir, et il se noierait dans une phrase neutre.
            var court = reponse.jours_necessaires !== null
                && reponse.jours_de_reception > 0
                && reponse.jours_necessaires > reponse.jours_de_reception;
            verdict.classList.toggle('rdv-verdict--court', court);
            verdict.hidden = false;
        } else {
            verdict.hidden = true;
        }
    }

    function rafraichir() {
        if (enCours) { enCours.abort(); }
        enCours = new AbortController();

        fetch(resultat.dataset.url, {
            method: 'POST',
            signal: enCours.signal,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': jeton ? jeton.getAttribute('content') : ''
            },
            body: JSON.stringify(valeurs())
        })
            .then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); })
            .then(afficher)
            .catch(function (erreur) {
                // Un apercu indisponible n'empeche pas d'enregistrer : on se
                // tait plutot que d'afficher une erreur qui ferait croire que
                // la configuration est refusee.
                if (erreur !== 'AbortError' && !(erreur instanceof DOMException)) {
                    resultat.hidden = true;
                }
            });
    }

    function programmer() {
        synchroniserLesJours();
        clearTimeout(minuteur);
        minuteur = setTimeout(rafraichir, 250);
    }

    carte.addEventListener('input', programmer);
    carte.addEventListener('change', programmer);

    var premierJour = document.getElementById('ci-physiques');
    if (premierJour) { premierJour.addEventListener('change', programmer); }

    rafraichir();
})();
</script>
@endpush
