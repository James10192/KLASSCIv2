{{-- ═══ Règles universelles KLASSCI — comportements de page, avant Bootstrap ═══

     Trois garanties que toute page du produit doit offrir, sans que chaque vue
     ait à y penser. Elles vivaient dans layouts/app.blade.php, qui approchait
     les 4 600 lignes : chaque règle ajoutée y était noyée, et la suivante s'y
     ajoutait par mimétisme.

     L'ORDRE COMPTE. Ce fichier doit être inclus APRÈS jQuery et AVANT
     bootstrap.bundle : le troisième bloc pose les attributs data-bs-* que
     Bootstrap lit au moment où il instancie les dropdowns. Inclus après, il
     arriverait trop tard. Le pendant post-Bootstrap est resté dans le layout,
     juste après le chargement de la bibliothèque.
--}}
    {{-- ═══ Règle universelle KLASSCI : une réponse en erreur dit POURQUOI ═══

         Le motif fautif, repété sept fois dans les vues :

             if (!response.ok) { throw new Error('HTTP error! status: ' + status); }

         Un 422 de validation est `!response.ok`. On jetait donc l'exception
         AVANT de lire le corps — et le corps est précisément l'endroit où le
         serveur explique ce qui ne va pas. Le caissier qui rejetait un paiement
         avec un motif trop court lisait « Erreur lors du rejet », jamais « le
         motif doit faire au moins 10 caractères ». Il réessayait à l'identique.

         Ce helper lit le corps, en tire le message du serveur ou la première
         erreur de validation, et ne retombe sur le code HTTP que si la réponse
         ne dit rien d'exploitable. --}}
    <script>
    window.klassciErreurReponse = function (reponse) {
        return reponse.json().catch(function () { return {}; }).then(function (corps) {
            var message = corps && corps.message ? corps.message : null;

            if (! message && corps && corps.errors) {
                var premieres = Object.keys(corps.errors)
                    .map(function (cle) {
                        var v = corps.errors[cle];
                        return Array.isArray(v) ? v[0] : v;
                    })
                    .filter(Boolean);

                if (premieres.length) {
                    message = premieres[0];
                }
            }

            throw new Error(message || ('Erreur ' + reponse.status + '.'));
        });
    };
    </script>

    {{-- ═══ Règle universelle KLASSCI : la molette ne modifie jamais un montant ═══

         Un <input type="number"> focalisé s'incrémente et se décrémente quand on
         fait défiler la page au-dessus de lui. C'est un comportement natif du
         navigateur, silencieux, et sans annulation : le champ affiche une valeur
         que personne n'a tapée, et rien ne la distingue d'une saisie.

         Sur une application de scolarité, ce champ porte des montants. Un frais
         saisi à 3 000 F et neuf crans de molette plus tard enregistré à 2 991 F
         ne se voit qu'au moment où une caissière encaisse — et à ce moment-là,
         c'est l'école qui perd la différence sur chaque élève.

         On rend la main au navigateur en retirant le focus plutôt qu'en bloquant
         l'événement : la page continue de défiler normalement, seule la valeur
         cesse de bouger. Un preventDefault() exigerait un écouteur non passif et
         saccaderait le défilement de toutes les pages du produit.

         Posé sur le document en capture pour couvrir aussi les champs injectés
         après coup — modals AJAX, lignes de tranches ajoutées à la volée. --}}
    <script>
    (function () {
        document.addEventListener('wheel', function (ev) {
            var actif = document.activeElement;

            if (!actif || actif.type !== 'number') {
                return;
            }

            // Uniquement si la molette tourne AU-DESSUS du champ focalise :
            // defiler ailleurs dans la page ne doit pas lui retirer le focus,
            // sinon on interrompt quelqu'un en pleine saisie.
            if (actif === ev.target || actif.contains(ev.target)) {
                actif.blur();
            }
        }, { passive: true, capture: true });
    })();
    </script>

    {{-- ═══ Règle universelle KLASSCI : auto-attach Popper config sur tous les dropdowns
         AVANT le chargement de Bootstrap pour que la conf soit lue à l'instanciation.
         - data-bs-strategy="fixed" → Popper position:fixed → ignore overflow:hidden parents
         - data-bs-boundary="viewport" → boundary = viewport, auto-flip réel
         - data-bs-display="dynamic" → Popper recalcule à chaque ouverture (flip up auto)
    --}}
    <script>
    (function() {
        function applyDropdownDefaults(scope) {
            scope.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(function(trigger) {
                if (trigger.dataset.klassciDropdownInit === '1') return;
                trigger.dataset.klassciDropdownInit = '1';
                if (!trigger.hasAttribute('data-bs-boundary')) trigger.setAttribute('data-bs-boundary', 'viewport');
                if (!trigger.hasAttribute('data-bs-display')) trigger.setAttribute('data-bs-display', 'dynamic');
            });
        }

        // Force Popper strategy:'fixed' sur TOUS les dropdowns Bootstrap pour que le menu
        // soit placé dans le stacking context ROOT du viewport (au-dessus des navbar sticky,
        // sidebar, etc. dont les contextes locaux ont z-index moindre).
        // À appeler APRÈS bootstrap.bundle chargé.
        function forceFixedStrategyOnAllDropdowns() {
            if (!window.bootstrap || !bootstrap.Dropdown) return;
            document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(function(trigger) {
                if (trigger.dataset.klassciStrategyFixed === '1') return;
                trigger.dataset.klassciStrategyFixed = '1';
                try {
                    const existing = bootstrap.Dropdown.getInstance(trigger);
                    if (existing) existing.dispose();
                    new bootstrap.Dropdown(trigger, {
                        popperConfig: function(defaultConfig) {
                            return Object.assign({}, defaultConfig, { strategy: 'fixed' });
                        }
                    });
                } catch(e) {}
            });
        }
        // Auto-flip via dropup class : si pas assez d'espace en bas, mettre la classe dropup
        // sur le parent .dropdown AVANT le click pour que Bootstrap calcule placement up.
        function detectAndFlipDropdown(trigger) {
            const dropdownParent = trigger.closest('.dropdown, .btn-group, .dropdown-center');
            if (!dropdownParent) return;
            const menu = dropdownParent.querySelector('.dropdown-menu');
            if (!menu) return;
            // Estimation hauteur menu (peut être imprécise si menu jamais affiché)
            const estHeight = menu.scrollHeight || menu.offsetHeight || 200;
            const triggerRect = trigger.getBoundingClientRect();
            const spaceBelow = window.innerHeight - triggerRect.bottom;
            const spaceAbove = triggerRect.top;
            // Flip up si pas assez d'espace en bas ET assez en haut
            if (spaceBelow < estHeight + 20 && spaceAbove > estHeight + 20) {
                dropdownParent.classList.add('dropup');
            } else {
                dropdownParent.classList.remove('dropup');
            }
        }
        // Listener global avant le click (capture phase pour précéder Bootstrap)
        document.addEventListener('mousedown', function(ev) {
            const trigger = ev.target.closest('[data-bs-toggle="dropdown"]');
            if (trigger) detectAndFlipDropdown(trigger);
        }, true);

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() { applyDropdownDefaults(document); });
        } else {
            applyDropdownDefaults(document);
        }
        new MutationObserver(function(mutations) {
            mutations.forEach(function(m) {
                m.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) applyDropdownDefaults(node);
                });
            });
        }).observe(document.documentElement, { childList: true, subtree: true });

        // Expose forceFixedStrategyOnAllDropdowns au scope global pour qu'on puisse
        // l'appeler après le chargement de bootstrap.bundle (cf. script suivant).
        window.__klassciForceFixedDropdowns = forceFixedStrategyOnAllDropdowns;
    })();
    </script>
