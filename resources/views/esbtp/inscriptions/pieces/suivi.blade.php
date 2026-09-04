@extends('layouts.app')

@section('title', 'Pièces manquantes aux dossiers')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/inscriptions-common.css') }}">
<style>
    /* Synthèse par pièce : c'est la réponse à « il manque 34 extraits de naissance ». */
    .pm-synthese {
        display: flex;
        flex-wrap: wrap;
        gap: .6rem;
        margin-bottom: 1rem;
    }
    .pm-piece {
        display: flex;
        align-items: center;
        gap: .65rem;
        padding: .6rem .85rem;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #fff;
        cursor: pointer;
        transition: border-color .15s ease, box-shadow .15s ease;
        text-align: left;
    }
    .pm-piece:hover { border-color: #c7d4e5; box-shadow: 0 4px 16px rgba(4, 83, 203, .07); }
    .pm-piece.is-active { border-color: #0453cb; box-shadow: 0 0 0 2px rgba(4, 83, 203, .12); }
    .pm-piece-count {
        font-size: 1.2rem;
        font-weight: 700;
        color: #0453cb;
        line-height: 1;
        min-width: 2.2rem;
        text-align: center;
    }
    .pm-piece-libelle { font-size: .82rem; font-weight: 600; color: #1e293b; }
    .pm-piece-meta { font-size: .68rem; color: #64748b; margin-top: .12rem; }
    .pm-piece-req { color: #dc2626; font-weight: 700; }

    .pm-tags { display: flex; flex-wrap: wrap; gap: .3rem; }
    .pm-tag {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        padding: .16rem .45rem;
        border-radius: 6px;
        font-size: .7rem;
        font-weight: 600;
        background: rgba(4, 83, 203, .08);
        color: #0453cb;
    }
    .pm-tag--req { background: rgba(220, 38, 38, .08); color: #b91c1c; }

    .pm-empty { padding: 2.5rem 1rem; text-align: center; color: #64748b; }
    .pm-empty i { font-size: 2rem; color: #cbd5e1; display: block; margin-bottom: .6rem; }

    .pm-warn {
        display: flex;
        align-items: flex-start;
        gap: .6rem;
        padding: .7rem .9rem;
        border-radius: 10px;
        background: rgba(245, 158, 11, .1);
        color: #92400e;
        font-size: .82rem;
        margin-bottom: 1rem;
    }
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content" style="padding: 1.25rem; max-width: 100%;">

        {{-- ======================= HERO ======================= --}}
        <div class="ii-hero">
            <div class="ii-hero-top">
                <div class="ii-hero-left">
                    <div class="ii-hero-icon"><i class="fas fa-folder-open"></i></div>
                    <div>
                        <h1>Pièces manquantes aux dossiers</h1>
                        <p>Qui doit encore fournir quoi, à travers une classe, une filière ou une promotion</p>
                        <span class="ii-hero-chip">
                            <i class="fas fa-calendar-alt"></i>
                            @if(!empty($filtres['annee_id']) && ($anneeSel = $annees->firstWhere('id', (int) $filtres['annee_id'])))
                                {{ $anneeSel->name }}
                            @else
                                Toutes les années
                            @endif
                        </span>
                    </div>
                </div>
                <div class="ii-hero-actions">
                    <x-export-modal
                        button-class="ii-btn ii-btn--glass"
                        :preview-url="route('esbtp.inscriptions.pieces.preview-pdf')"
                        :pdf-url="route('esbtp.inscriptions.pieces.export-pdf')"
                        :excel-url="route('esbtp.inscriptions.pieces.export-excel')" />
                    <a href="{{ route('esbtp.inscriptions.index') }}" class="ii-btn ii-btn--glass">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>

            <div class="ii-kpis">
                <div class="ii-kpi">
                    <div class="ii-kpi-icon"><i class="fas fa-user-clock"></i></div>
                    <div class="ii-kpi-content">
                        <div class="ii-kpi-value" data-kpi="inscriptions_incompletes">{{ $kpis['inscriptions_incompletes'] }}</div>
                        <div class="ii-kpi-label">Dossiers incomplets</div>
                    </div>
                </div>
                <div class="ii-kpi">
                    <div class="ii-kpi-icon"><i class="fas fa-file-circle-exclamation"></i></div>
                    <div class="ii-kpi-content">
                        <div class="ii-kpi-value" data-kpi="pieces_manquantes">{{ $kpis['pieces_manquantes'] }}</div>
                        <div class="ii-kpi-label">Pièces manquantes</div>
                    </div>
                </div>
                <div class="ii-kpi">
                    <div class="ii-kpi-icon"><i class="fas fa-copy"></i></div>
                    <div class="ii-kpi-content">
                        <div class="ii-kpi-value" data-kpi="exemplaires_manquants">{{ $kpis['exemplaires_manquants'] }}</div>
                        <div class="ii-kpi-label">Exemplaires à réunir</div>
                    </div>
                </div>
                <div class="ii-kpi">
                    <div class="ii-kpi-icon"><i class="fas fa-list-check"></i></div>
                    <div class="ii-kpi-content">
                        <div class="ii-kpi-value" data-kpi="inscriptions_examinees">{{ $kpis['inscriptions_examinees'] }}</div>
                        <div class="ii-kpi-label">Inscriptions examinées</div>
                    </div>
                </div>
            </div>
        </div>

        @if($tronque)
            <div class="pm-warn" id="pm-warn-tronque">
                <i class="fas fa-triangle-exclamation"></i>
                <div>Le périmètre dépasse le plafond de calcul de cette instance : le résultat est partiel. Affinez les filtres (année, filière ou classe).</div>
            </div>
        @endif

        @if(empty($codesPieces))
            {{-- Catalogue vide : l'école n'a rien configuré, on ne prétend pas savoir
                 ce qui manque. Aucun écran existant ne change tant que c'est le cas. --}}
            <div class="ii-results-card">
                <div class="pm-empty">
                    <i class="fas fa-folder-plus"></i>
                    <strong>Aucune pièce n'est encore configurée</strong>
                    <div style="margin-top:.4rem;font-size:.85rem;">
                        Le catalogue des pièces à fournir est vide : aucune pièce n'est attendue pour l'instant.
                    </div>
                </div>
            </div>
        @else

        {{-- ============== SYNTHÈSE PAR PIÈCE (clic = filtre) ============== --}}
        <div class="pm-synthese" id="pm-synthese">
            @include('esbtp.inscriptions.pieces.partials.synthese', ['parPiece' => $parPiece, 'codeActif' => $filtres['piece_code']])
        </div>

        {{-- ======================= TOOLBAR ======================= --}}
        <div class="ii-toolbar">
            <div class="ii-search">
                <i class="fas fa-search"></i>
                <input type="search" id="pm-search" placeholder="Nom, prénom ou matricule..." value="{{ $filtres['search'] }}">
            </div>

            <x-au-select id="pm-annee" name="annee_id" placeholder="Toutes les années"
                icon="fa-calendar-alt"
                :value="$filtres['annee_id']"
                :options="$annees->mapWithKeys(fn ($a) => [$a->id => $a->name . ($a->is_current ? ' (courante)' : '')])->all()" />

            <x-au-select id="pm-filiere" name="filiere_id" placeholder="Toutes les filières"
                icon="fa-sitemap" :searchable="true"
                :value="$filtres['filiere_id']"
                :options="$filieres->pluck('name', 'id')->all()" />

            <x-au-select id="pm-niveau" name="niveau_id" placeholder="Tous les niveaux"
                icon="fa-layer-group"
                :value="$filtres['niveau_id']"
                :options="$niveaux->pluck('name', 'id')->all()" />

            <x-au-select id="pm-classe" name="classe_id" placeholder="Toutes les classes"
                icon="fa-users" :searchable="true"
                :value="$filtres['classe_id']"
                :options="$classes->pluck('name', 'id')->all()" />

            <x-au-select id="pm-piece" name="piece_code" placeholder="Toutes les pièces"
                icon="fa-file-lines" :searchable="true"
                :value="$filtres['piece_code']"
                :options="$codesPieces" />

            <label class="ii-btn ii-btn--ghost" style="cursor:pointer;">
                <input type="checkbox" id="pm-obligatoires" {{ $filtres['obligatoires_seulement'] ? 'checked' : '' }}
                    style="margin-right:.35rem;">
                Obligatoires seulement
            </label>

            <button type="button" class="ii-btn ii-btn--ghost" id="pm-reset">
                <i class="fas fa-rotate-left"></i> Réinitialiser
            </button>
        </div>

        {{-- ======================= RESULTS ======================= --}}
        <div class="ii-results-card">
            <div class="ii-results-header">
                <div class="ii-results-count">
                    <strong id="pm-total">{{ $lignes->total() }}</strong> dossier(s) incomplet(s)
                </div>
                <div class="ii-per-page">
                    <span>Afficher</span>
                    <select id="pm-per-page" aria-label="Lignes par page">
                        @foreach([15, 25, 50, 100] as $opt)
                            <option value="{{ $opt }}" {{ $perPage == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                    <span>par page</span>
                </div>
            </div>
            <div id="pm-results">
                @include('esbtp.inscriptions.pieces.partials.resultats', ['lignes' => $lignes])
            </div>
        </div>

        @endif
    </div>
</div>

@can('inscriptions.pieces.relancer')
<div class="ii-bulk-bar" id="pm-bulk-bar">
    <div class="ii-bulk-count">
        <i class="fas fa-check-circle me-1"></i>
        <span id="pm-bulk-count">0</span> sélectionné(s)
    </div>
    <button type="button" class="ii-btn ii-btn--white" id="pm-bulk-relancer">
        <i class="fas fa-paper-plane"></i> Relancer par email
    </button>
    <button type="button" class="ii-btn ii-btn--glass" id="pm-bulk-clear" title="Fermer">
        <i class="fas fa-times"></i>
    </button>
</div>
@endcan
@endsection

@push('scripts')
<script src="{{ asset('js/inscriptions/common.js') }}"></script>
<script>
(function () {
    'use strict';

    const ROUTES = {
        index: @json(route('esbtp.inscriptions.pieces.index')),
        relancer: @json(route('esbtp.inscriptions.pieces.relancer')),
        csrf: @json(csrf_token()),
    };

    const $ = (id) => document.getElementById(id);
    const resultats = $('pm-results');
    if (!resultats) { return; }

    // Les filtres sont la source unique de verite : l'ecran, les exports et la
    // relance lisent le meme etat, sinon on exporte autre chose que ce qu'on voit.
    function filtresCourants() {
        return {
            search: $('pm-search') ? $('pm-search').value : '',
            annee_id: $('pm-annee') ? $('pm-annee').value : '',
            filiere_id: $('pm-filiere') ? $('pm-filiere').value : '',
            niveau_id: $('pm-niveau') ? $('pm-niveau').value : '',
            classe_id: $('pm-classe') ? $('pm-classe').value : '',
            piece_code: $('pm-piece') ? $('pm-piece').value : '',
            obligatoires_seulement: ($('pm-obligatoires') && $('pm-obligatoires').checked) ? '1' : '',
            per_page: $('pm-per-page') ? $('pm-per-page').value : '',
        };
    }

    // Consomme par le composant export (x-export-modal) pour que le PDF et le
    // tableur reflètent exactement la vue affichée.
    window.exportFilters = filtresCourants;

    let requeteEnCours = null;

    function charger(page) {
        const params = new URLSearchParams();
        const filtres = filtresCourants();
        Object.keys(filtres).forEach((cle) => {
            if (filtres[cle] !== '' && filtres[cle] !== null) { params.set(cle, filtres[cle]); }
        });
        if (page) { params.set('page', page); }

        const url = ROUTES.index + '?' + params.toString();

        if (requeteEnCours) { requeteEnCours.abort(); }
        requeteEnCours = new AbortController();

        resultats.style.opacity = '.5';

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            signal: requeteEnCours.signal,
        })
            .then((r) => r.json())
            .then((data) => {
                resultats.innerHTML = data.html;
                majKpis(data.kpis);
                majSynthese(data.par_piece, filtres.piece_code);
                viderSelection();
                window.history.replaceState({}, '', data.url);
            })
            .catch((err) => {
                if (err.name !== 'AbortError' && window.showToast) {
                    window.showToast('Impossible de rafraîchir la liste.', 'error');
                }
            })
            .finally(() => { resultats.style.opacity = '1'; });
    }

    function majKpis(kpis) {
        if (!kpis) { return; }
        Object.keys(kpis).forEach((cle) => {
            const noeud = document.querySelector('[data-kpi="' + cle + '"]');
            if (noeud) { noeud.textContent = kpis[cle]; }
        });
        const total = $('pm-total');
        if (total && typeof kpis.inscriptions_incompletes !== 'undefined') {
            total.textContent = kpis.inscriptions_incompletes;
        }
    }

    function majSynthese(parPiece, codeActif) {
        const bloc = $('pm-synthese');
        if (!bloc || !Array.isArray(parPiece)) { return; }
        bloc.innerHTML = parPiece.map(function (p) {
            const actif = p.code === codeActif ? ' is-active' : '';
            const req = p.obligatoire ? '<span class="pm-piece-req">obligatoire</span> · ' : '';
            return '<button type="button" class="pm-piece' + actif + '" data-piece-code="' + p.code + '">'
                + '<span class="pm-piece-count">' + p.etudiants + '</span>'
                + '<span><span class="pm-piece-libelle">' + p.libelle + '</span>'
                + '<span class="pm-piece-meta">' + req + p.exemplaires + ' exemplaire(s)</span></span>'
                + '</button>';
        }).join('');
    }

    // ---- Filtres -----------------------------------------------------------
    let minuteur = null;
    if ($('pm-search')) {
        $('pm-search').addEventListener('input', function () {
            clearTimeout(minuteur);
            minuteur = setTimeout(() => charger(1), 350);
        });
    }

    ['pm-annee', 'pm-filiere', 'pm-niveau', 'pm-classe', 'pm-piece', 'pm-obligatoires', 'pm-per-page']
        .forEach(function (id) {
            const noeud = $(id);
            if (noeud) { noeud.addEventListener('change', () => charger(1)); }
        });

    if ($('pm-reset')) {
        $('pm-reset').addEventListener('click', function () {
            ['pm-search', 'pm-annee', 'pm-filiere', 'pm-niveau', 'pm-classe', 'pm-piece']
                .forEach(function (id) {
                    const noeud = $(id);
                    if (!noeud) { return; }
                    noeud.value = '';
                    noeud.dispatchEvent(new Event('change', { bubbles: true }));
                });
            if ($('pm-obligatoires')) { $('pm-obligatoires').checked = false; }
            charger(1);
        });
    }

    // Clic sur une pièce de la synthèse = filtre sur cette pièce (ou retrait).
    document.addEventListener('click', function (ev) {
        const carte = ev.target.closest('[data-piece-code]');
        if (carte) {
            const select = $('pm-piece');
            if (select) {
                const code = carte.getAttribute('data-piece-code');
                select.value = (select.value === code) ? '' : code;
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }
            return;
        }

        const lien = ev.target.closest('#pm-results .pagination a');
        if (lien) {
            ev.preventDefault();
            const url = new URL(lien.href, window.location.origin);
            charger(url.searchParams.get('page') || 1);
        }
    });

    // ---- Sélection + relance ----------------------------------------------
    const barre = $('pm-bulk-bar');

    function selection() {
        return Array.from(document.querySelectorAll('#pm-results .pm-check:checked'))
            .map((c) => parseInt(c.value, 10));
    }

    function majBarre() {
        if (!barre) { return; }
        const nb = selection().length;
        $('pm-bulk-count').textContent = nb;
        barre.classList.toggle('is-visible', nb > 0);
    }

    function viderSelection() {
        document.querySelectorAll('#pm-results .pm-check').forEach((c) => { c.checked = false; });
        const tout = document.querySelector('#pm-results .pm-check-all');
        if (tout) { tout.checked = false; }
        majBarre();
    }

    resultats.addEventListener('change', function (ev) {
        if (ev.target.classList.contains('pm-check-all')) {
            document.querySelectorAll('#pm-results .pm-check')
                .forEach((c) => { c.checked = ev.target.checked; });
        }
        if (ev.target.classList.contains('pm-check') || ev.target.classList.contains('pm-check-all')) {
            majBarre();
        }
    });

    if ($('pm-bulk-clear')) { $('pm-bulk-clear').addEventListener('click', viderSelection); }

    if ($('pm-bulk-relancer')) {
        $('pm-bulk-relancer').addEventListener('click', async function () {
            const ids = selection();
            if (ids.length === 0) { return; }

            const confirmer = window.iiConfirm
                ? await window.iiConfirm({
                    title: 'Relancer par email',
                    message: 'Envoyer un rappel des pièces manquantes à ' + ids.length + ' étudiant(s) ?',
                    confirmLabel: 'Envoyer',
                })
                : window.confirm('Envoyer un rappel à ' + ids.length + ' étudiant(s) ?');

            if (!confirmer) { return; }

            this.disabled = true;
            try {
                const reponse = await fetch(ROUTES.relancer, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': ROUTES.csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(Object.assign({ inscription_ids: ids }, filtresCourants())),
                });
                const data = await reponse.json();
                if (!reponse.ok || !data.success) {
                    throw new Error(data.message || 'Échec de la relance.');
                }
                if (window.showToast) { window.showToast(data.message, 'success'); }
                viderSelection();
            } catch (err) {
                if (window.showToast) { window.showToast(err.message, 'error'); }
            } finally {
                this.disabled = false;
            }
        });
    }
})();
</script>
@endpush
