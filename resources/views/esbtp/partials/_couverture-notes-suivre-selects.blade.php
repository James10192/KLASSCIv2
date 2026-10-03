{{--
    Fait suivre au bandeau de couverture deux sélecteurs natifs de la page.

    Les formulaires d'évaluation ne sont pas pilotés par Alpine : leur classe et
    leur période vivent dans des `<select>` (ceux que `<x-au-select>` garde
    cachés et sur lesquels il émet un `change`). Ce petit pont écoute ces deux
    champs et annonce le contexte, plutôt que d'exiger du bandeau qu'il connaisse
    la structure de chaque page hôte.

    Sans `@once` : ces formulaires sont aussi chargés en modale AJAX, où un
    `@push` serait avalé. Le garde par identifiant évite le double branchement.

    @param string   $selectClasse   sélecteur CSS du champ classe
    @param string   $selectPeriode  sélecteur CSS du champ période (optionnel)
    @param int|null $anneeId
    @param string   $periodeDefaut  période quand le champ est vide
--}}
@php
    $_cvnPont = [
        'classe' => $selectClasse ?? '#classe_id',
        'periode' => $selectPeriode ?? null,
        'anneeId' => isset($anneeId) && $anneeId ? (int) $anneeId : null,
        'periodeDefaut' => $periodeDefaut ?? 'annuel',
    ];
    $_cvnLmdClasses = isset($classes)
        ? $classes->where('systeme_academique', 'LMD')->mapWithKeys(fn ($classe) => [(string) $classe->id => [
            'parcours_id' => $classe->parcours_id,
            'filiere_id' => $classe->filiere_id,
            'niveau_id' => $classe->niveau_etude_id,
            'annee_universitaire_id' => $_cvnPont['anneeId'],
        ]])->all()
        : [];
@endphp
<script>
(function () {
    var pont = @js($_cvnPont);
    var lmdClasses = @js($_cvnLmdClasses);
    var evaluationForm = document.getElementById('evaluationCreateForm');
    var lmdRequestToken = 0;
    var lmdPromptKey = null;

    function annoncer() {
        var champClasse = document.querySelector(pont.classe);
        var champPeriode = pont.periode ? document.querySelector(pont.periode) : null;

        window.dispatchEvent(new CustomEvent('couverture:contexte', {
            detail: {
                classe_id: champClasse && champClasse.value ? champClasse.value : null,
                annee_universitaire_id: pont.anneeId,
                periode: (champPeriode && champPeriode.value) || pont.periodeDefaut,
            },
        }));
    }

    window.addEventListener('couverture:periode-change', function (event) {
        var periode = event.detail && event.detail.periode;
        var champPeriode = pont.periode ? document.querySelector(pont.periode) : null;
        if (!champPeriode || !periode || champPeriode.value === periode) return;
        champPeriode.value = periode;
        champPeriode.dispatchEvent(new Event('change', { bubbles: true }));
    });

    function optionsHtml(items, placeholder) {
        return '<option value="" data-placeholder="1">' + placeholder + '</option>'
            + items.map(function (item) {
                var suffix = item.hors_maquette ? ' — hors maquette' : '';
                return '<option value="' + Number(item.id) + '">' + echapper(item.name + suffix) + '</option>';
            }).join('');
    }

    function echapper(value) {
        var div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    function numeroSemestre(value) {
        var match = String(value || '').match(/(\d{1,2})/);
        return match ? Number(match[1]) : null;
    }

    async function hydraterClasseLmd(classeId) {
        if (!evaluationForm || !lmdClasses[String(classeId)]) return false;
        var token = ++lmdRequestToken;
        var matiere = document.getElementById('matiere_id');
        var periode = document.getElementById('periode');
        if (!matiere || !periode) return false;

        matiere.disabled = true;
        var loading = document.getElementById('matiere-loading');
        if (loading) loading.style.display = 'inline-block';

        try {
            var responses = await Promise.all([
                fetch('/api/classes/' + encodeURIComponent(classeId) + '/matieres', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                }),
                fetch('/esbtp/classes/' + encodeURIComponent(classeId) + '/semestres-lmd', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                }),
            ]);
            if (token !== lmdRequestToken) return true;
            if (!responses[0].ok || !responses[1].ok) throw new Error('Référentiel LMD indisponible.');

            var matieres = await responses[0].json();
            var semestresPayload = await responses[1].json();
            if (token !== lmdRequestToken) return true;

            var anciennesValeurs = { matiere: matiere.value, periode: periode.value };
            var lignes = Array.isArray(matieres.matieres) ? matieres.matieres : [];
            var semestres = Array.isArray(semestresPayload.semestres) ? semestresPayload.semestres : [];

            matiere.innerHTML = optionsHtml(lignes, lignes.length ? 'Sélectionner un ECUE' : 'Aucun ECUE dans la maquette');
            matiere.disabled = false;
            if (lignes.some(function (item) { return String(item.id) === String(anciennesValeurs.matiere); })) {
                matiere.value = anciennesValeurs.matiere;
            }
            matiere.dispatchEvent(new Event('change', { bubbles: true }));

            periode.innerHTML = '<option value="" data-placeholder="1">Sélectionner une période</option>'
                + semestres.map(function (s) {
                    return '<option value="semestre' + Number(s) + '">Semestre ' + Number(s) + '</option>';
                }).join('');
            if ([...periode.options].some(function (opt) { return opt.value === anciennesValeurs.periode; })) {
                periode.value = anciennesValeurs.periode;
            }
            periode.dispatchEvent(new Event('change', { bubbles: true }));
            annoncer();
            return true;
        } catch (error) {
            matiere.disabled = false;
            window.dispatchEvent(new CustomEvent('toast', { detail: {
                type: 'error',
                message: error.message || 'Impossible de charger la maquette LMD.',
            } }));
            return true;
        } finally {
            if (loading) loading.style.display = 'none';
        }
    }

    async function enseignantPlanifie(options) {
        if (!options || !window.lmdTeacherQuick) return null;
        if (!options.context.parcours_id) return null;
        var params = new URLSearchParams({
            parcours_id: options.context.parcours_id,
            niveau_id: options.context.niveau_id || '',
            semestre: options.context.semestre || '',
        });
        try {
            var response = await fetch(@json(route('esbtp.lmd.planning.partial')) + '?' + params.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) return null;
            var doc = new DOMParser().parseFromString(await response.text(), 'text/html');
            var row = doc.querySelector('[data-lpe-ecue-id="' + String(options.ecueId).replace(/"/g, '') + '"]');
            if (!row) return { id: null, name: '' };
            return {
                id: row.dataset.lpeTeacherId ? Number(row.dataset.lpeTeacherId) : null,
                name: row.dataset.lpeTeacherName || '',
            };
        } catch (_) {
            return null;
        }
    }

    function planningOptions() {
        if (!evaluationForm) return null;
        var classe = document.querySelector(pont.classe);
        var matiere = document.getElementById('matiere_id');
        var periode = document.getElementById('periode');
        if (!classe || !matiere || !periode) return null;
        var base = lmdClasses[String(classe.value)] || null;
        var semestre = numeroSemestre(periode.value);
        if (!base || !matiere.value || !semestre) return null;
        var label = matiere.options[matiere.selectedIndex]?.textContent || ('ECUE #' + matiere.value);
        return {
            ecueId: Number(matiere.value),
            label: label,
            context: Object.assign({}, base, { semestre: semestre }),
        };
    }

    function afficherPlanningTeacher(current, options) {
        if (!evaluationForm) return;
        var host = evaluationForm.querySelector('[name="enseignant_id"]')?.closest('.ec-field');
        if (!host) return;
        var status = document.getElementById('genericLmdPlanningTeacher');
        if (!status) {
            status = document.createElement('div');
            status.id = 'genericLmdPlanningTeacher';
            status.className = 'ec-info';
            status.style.marginTop = '.55rem';
            host.appendChild(status);
        }
        status.style.display = 'flex';
        status.innerHTML = current?.id
            ? '<i class="fas fa-user-check"></i><span><strong>Planning LMD :</strong> ' + echapper(current.name || ('enseignant #' + current.id)) + ' · <button type="button" id="genericLmdPlanningTeacherEdit" class="ec-link" style="border:0;background:transparent;padding:0;">Modifier</button></span>'
            : '<i class="fas fa-user-clock"></i><span><strong>Planning LMD :</strong> aucun enseignant principal · <button type="button" id="genericLmdPlanningTeacherEdit" class="ec-link" style="border:0;background:transparent;padding:0;">Assigner maintenant</button></span>';
        document.getElementById('genericLmdPlanningTeacherEdit')?.addEventListener('click', function () {
            window.lmdTeacherQuick.open(Object.assign({}, options, {
                currentTeacherId: current?.id || null,
                currentTeacherName: current?.name || '',
                onSaved: function () { verifierPlanningTeacher(false); },
            }));
        });
    }

    async function verifierPlanningTeacher(autoPrompt) {
        var options = planningOptions();
        var status = document.getElementById('genericLmdPlanningTeacher');
        if (!options || !window.lmdTeacherQuick) {
            if (status) status.style.display = 'none';
            return;
        }
        var current = await enseignantPlanifie(options);
        if (current === null) return;
        afficherPlanningTeacher(current, options);
        var key = [document.querySelector(pont.classe)?.value, options.ecueId, options.context.semestre].join(':');
        if (!current.id && autoPrompt && lmdPromptKey !== key) {
            lmdPromptKey = key;
            window.lmdTeacherQuick.open(Object.assign({}, options, {
                currentTeacherId: null,
                currentTeacherName: '',
                onSaved: function () { verifierPlanningTeacher(false); },
            }));
        }
    }

    function brancherLmdEvaluation() {
        if (!evaluationForm || Object.keys(lmdClasses).length === 0) return;
        var classe = document.querySelector(pont.classe);
        var matiere = document.getElementById('matiere_id');
        var periode = document.getElementById('periode');
        if (!classe || !matiere || !periode) return;

        // Le create.blade.php historique branche ensuite un loader BTS sur le
        // meme `change`. Pour une classe LMD, ce listener capture l'evenement
        // en premier, empêche ce loader BTS de gagner la course AJAX et charge
        // la source canonique commune aux notes et evaluations.
        classe.addEventListener('change', function (event) {
            if (!lmdClasses[String(classe.value)]) {
                document.getElementById('genericLmdPlanningTeacher')?.style.setProperty('display', 'none');
                return;
            }
            event.stopImmediatePropagation();
            lmdPromptKey = null;
            hydraterClasseLmd(classe.value);
        }, true);

        matiere.addEventListener('change', function () {
            if (lmdClasses[String(classe.value)]) {
                lmdPromptKey = null;
                verifierPlanningTeacher(true);
            }
        });
        periode.addEventListener('change', function () {
            if (lmdClasses[String(classe.value)]) {
                lmdPromptKey = null;
                verifierPlanningTeacher(true);
            }
        });

        if (lmdClasses[String(classe.value)]) {
            hydraterClasseLmd(classe.value);
        }
    }

    function brancher() {
        [pont.classe, pont.periode].forEach(function (selecteur) {
            if (!selecteur) { return; }
            var champ = document.querySelector(selecteur);
            // Un champ deja branche ne doit pas l'etre deux fois : la modale
            // AJAX rejoue ce script a chaque ouverture.
            if (!champ || champ.dataset.cvnBranche === '1') { return; }
            champ.dataset.cvnBranche = '1';
            champ.addEventListener('change', annoncer);
        });

        brancherLmdEvaluation();
        annoncer();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', brancher);
    } else {
        brancher();
    }
})();
</script>

@if(isset($classes) && isset($enseignants))
    @include('esbtp.lmd.partials.teacher-quick-dialog')
@endif
