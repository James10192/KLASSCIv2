@extends('layouts.app')

@section('title', 'Retrouver un rendez-vous - KLASSCI')

@push('styles')
@include('esbtp.rendez-vous.partials._styles')
<style>
/* ===== Retrouver un rendez-vous — namespace rdr-* (s'appuie sur rdv-*) ===== */
.rdr-barre { display: flex; gap: .75rem; flex-wrap: wrap; align-items: center; margin-bottom: 1rem; }
.rdr-recherche { flex: 1 1 320px; display: flex; align-items: center; gap: .5rem; background: #fff; border: 1px solid var(--rdv-line); border-radius: 12px; padding: 0 .85rem; min-height: 46px; transition: border-color .2s ease, box-shadow .2s ease; }
.rdr-recherche:focus-within { border-color: var(--rdv-primary); box-shadow: 0 0 0 3px rgba(4,83,203,.15); }
.rdr-recherche i { color: var(--rdv-muted); }
.rdr-recherche input { flex: 1; border: none; outline: none; font-size: .9rem; background: transparent; min-width: 0; color: var(--rdv-text); }
.rdr-filtres { display: flex; gap: .5rem; flex-wrap: wrap; }
.rdr-groupe { display: inline-flex; background: #fff; border: 1px solid var(--rdv-line); border-radius: 10px; padding: 3px; gap: 2px; }
.rdr-groupe label { margin: 0; }
.rdr-groupe input { position: absolute; opacity: 0; pointer-events: none; }
.rdr-groupe span { display: inline-block; padding: .4rem .75rem; border-radius: 8px; font-size: .8rem; font-weight: 600; color: var(--rdv-muted); cursor: pointer; transition: background .2s ease, color .2s ease; white-space: nowrap; }
.rdr-groupe input:checked + span { background: var(--rdv-primary); color: #fff; }
.rdr-groupe input:focus-visible + span { outline: 3px solid rgba(4,83,203,.35); outline-offset: 1px; }

.rdr-carte { background: #fff; border: 1px solid var(--rdv-line); border-radius: 14px; box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); }
.rdr-carte-tete { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .9rem 1.25rem; border-bottom: 1px solid var(--rdv-line); font-size: .85rem; color: var(--rdv-muted); }
.rdr-carte-tete strong { color: var(--rdv-dark); }
.rdr-lignes { list-style: none; margin: 0; padding: 0; }
.rdr-ligne { display: grid; grid-template-columns: 64px minmax(0, 1fr) auto auto; align-items: center; gap: 1rem; padding: .85rem 1.25rem; border-bottom: 1px solid #f1f5f9; }
.rdr-ligne:last-child { border-bottom: none; }
.rdr-ligne:hover { background: #fafcff; }
.rdr-ligne--hors .rdr-nom strong { color: var(--rdv-muted); }
.rdr-quand { display: flex; flex-direction: column; align-items: center; justify-content: center; border: 1px solid rgba(4,83,203,.18); background: rgba(4,83,203,.05); border-radius: 12px; padding: .35rem .25rem; line-height: 1.1; }
.rdr-quand-jour { font-size: 1.2rem; font-weight: 700; color: var(--rdv-primary); font-variant-numeric: tabular-nums; }
.rdr-quand-mois { font-size: .68rem; font-weight: 700; text-transform: uppercase; color: var(--rdv-muted); letter-spacing: .4px; }
.rdr-quand-heure { font-size: .72rem; font-weight: 600; color: var(--rdv-dark); margin-top: .15rem; font-variant-numeric: tabular-nums; }
.rdr-qui { min-width: 0; }
.rdr-nom { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
.rdr-nom strong { font-size: .92rem; color: var(--rdv-dark); }
.rdr-type { font-size: .68rem; font-weight: 700; color: var(--rdv-primary); background: rgba(4,83,203,.08); border: 1px solid rgba(4,83,203,.2); border-radius: 6px; padding: .1rem .45rem; }
.rdr-details { display: flex; gap: .85rem; flex-wrap: wrap; margin-top: .25rem; font-size: .78rem; color: var(--rdv-muted); }
.rdr-details span, .rdr-details a { display: inline-flex; align-items: center; gap: .3rem; font-variant-numeric: tabular-nums; }
.rdr-details a { color: var(--rdv-muted); text-decoration: none; }
.rdr-details a:hover { color: var(--rdv-primary); }
.rdr-details i { font-size: .7rem; }
.rdr-ref { font-family: 'Courier New', monospace; font-weight: 700; color: var(--rdv-primary); background: rgba(4,83,203,.07); padding: 0 .4rem; border-radius: 5px; }
.rdr-statut { display: flex; flex-direction: column; align-items: flex-end; gap: .15rem; text-align: right; }
.rdr-statut small { font-size: .7rem; color: var(--rdv-muted); }
.rdr-actions { display: flex; gap: .4rem; justify-content: flex-end; flex-wrap: wrap; }
.rdr-vide { text-align: center; padding: 2.5rem 1.25rem; color: var(--rdv-muted); }
.rdr-vide i { font-size: 1.6rem; color: #94a3b8; display: block; margin-bottom: .6rem; }
.rdr-vide h3 { font-size: 1rem; color: var(--rdv-dark); margin: 0 0 .35rem; }
.rdr-vide p { font-size: .85rem; margin: 0 auto; max-width: 420px; }
#rdr-resultats.is-chargement { opacity: .55; pointer-events: none; transition: opacity .2s ease; }

@media (max-width: 768px) {
    .rdr-ligne { grid-template-columns: 56px minmax(0, 1fr); gap: .6rem .85rem; padding: .8rem 1rem; }
    .rdr-statut { grid-column: 2; align-items: flex-start; text-align: left; }
    .rdr-actions { grid-column: 1 / -1; justify-content: flex-start; }
    .rdr-filtres { width: 100%; }
    .rdr-groupe { flex: 1 1 auto; flex-wrap: wrap; }
}
</style>
@endpush

@section('content')
<div class="container-fluid rdv-page rdr-page">

    <div class="rdv-hero">
        <div class="rdv-hero-top">
            <div class="rdv-hero-left">
                <div class="rdv-hero-icon"><i class="fas fa-magnifying-glass"></i></div>
                <div>
                    <h1>Retrouver un rendez-vous</h1>
                    <p>Le rendez-vous d'une famille, quel que soit le jour : par nom, téléphone, référence du dossier ou matricule.</p>
                </div>
            </div>
            <div class="rdv-hero-actions">
                @can('inscriptions.rdv.accueil')
                    <a class="rdv-btn rdv-btn--glass" href="{{ route('esbtp.rendez-vous.accueil.index') }}"><i class="fas fa-clipboard-check"></i>Accueil du jour</a>
                @endcan
                @can('inscriptions.rdv.view')
                    <a class="rdv-btn rdv-btn--white" href="{{ route('esbtp.rendez-vous.index') }}"><i class="fas fa-calendar-week"></i>Planning</a>
                @endcan
            </div>
        </div>
    </div>

    <form class="rdr-barre" method="GET" action="{{ route('esbtp.rendez-vous.recherche') }}" data-rdr-form>
        <label class="rdr-recherche">
            <i class="fas fa-magnifying-glass"></i>
            <span class="visually-hidden">Rechercher</span>
            <input type="search" name="q" value="{{ $filtres['q'] }}" placeholder="Nom, téléphone, référence ou matricule…" autocomplete="off" autofocus>
        </label>
        <div class="rdr-filtres">
            <div class="rdr-groupe" role="radiogroup" aria-label="Période">
                @foreach(['a_venir' => 'À venir', 'passes' => 'Passés', 'tous' => 'Tous'] as $_v => $_l)
                    <label><input type="radio" name="quand" value="{{ $_v }}" @checked($filtres['quand'] === $_v)><span>{{ $_l }}</span></label>
                @endforeach
            </div>
            <div class="rdr-groupe" role="radiogroup" aria-label="Dossier">
                @foreach(['' => 'Tous dossiers', 'candidature' => 'Nouvelles inscriptions', 'reinscription' => 'Réinscriptions'] as $_v => $_l)
                    <label><input type="radio" name="type" value="{{ $_v }}" @checked($filtres['type'] === $_v)><span>{{ $_l }}</span></label>
                @endforeach
            </div>
            <div class="rdr-groupe" role="radiogroup" aria-label="Statut">
                <label><input type="radio" name="statut" value="" @checked($filtres['statut'] === '')><span>Tous statuts</span></label>
                @foreach(\App\Enums\StatutReservationRdv::cases() as $_statut)
                    <label><input type="radio" name="statut" value="{{ $_statut->value }}" @checked($filtres['statut'] === $_statut->value)><span>{{ $_statut->libelleFiltre() }}</span></label>
                @endforeach
            </div>
        </div>
    </form>

    <div id="rdr-resultats" aria-live="polite">
        <section class="rdr-carte">
            @if($reservations->total() === 0)
                <div class="rdr-vide">
                    <i class="fas fa-magnifying-glass"></i>
                    @if($filtres['q'] !== '')
                        <h3>Aucun rendez-vous pour « {{ $filtres['q'] }} »</h3>
                        <p>Vérifiez l'orthographe, cherchez par téléphone ou par référence, ou élargissez la période à « Tous ». Une famille qui n'a jamais réservé n'apparaît pas ici.</p>
                    @else
                        <h3>Aucun rendez-vous pour ces filtres</h3>
                        <p>Changez la période ou le statut pour voir d'autres rendez-vous.</p>
                    @endif
                </div>
            @else
                <div class="rdr-carte-tete">
                    <span><strong>{{ number_format($reservations->total(), 0, ',', ' ') }}</strong> rendez-vous{{ $filtres['q'] !== '' ? ' pour « '.$filtres['q'].' »' : '' }}</span>
                </div>
                <ul class="rdr-lignes" id="rdr-lignes">
                    @foreach($reservations as $resa)
                        @include('esbtp.rendez-vous.recherche._ligne')
                    @endforeach
                </ul>
                <x-liste-infinie :paginateur="$reservations" cible="#rdr-lignes" libelle="rendez-vous"
                                 :url="route('esbtp.rendez-vous.recherche')" />
            @endif
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    // Filtrer sans recharger : la page se redemande avec les memes filtres et
    // seuls les resultats sont remplaces. Le bas de liste nouveau se branche seul.
    const form = document.querySelector('[data-rdr-form]');
    const zone = document.getElementById('rdr-resultats');
    if (!form || !zone) return;
    let numero = 0;
    let minuterie = null;

    function adresse() {
        const params = new URLSearchParams(new FormData(form));
        [...params.keys()].forEach((k) => { if (!params.get(k)) params.delete(k); });
        const q = params.toString();
        return form.action + (q ? '?' + q : '');
    }

    async function rafraichir(url, empiler = true) {
        const n = ++numero;
        zone.classList.add('is-chargement');
        try {
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const html = await res.text();
            if (n !== numero) return;
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const neuf = doc.getElementById('rdr-resultats');
            if (neuf) zone.innerHTML = neuf.innerHTML;
            if (empiler) window.history.pushState({}, '', url);
        } catch (e) {
            if (n === numero) window.location.href = url;
        } finally {
            if (n === numero) zone.classList.remove('is-chargement');
        }
    }

    form.addEventListener('submit', (ev) => { ev.preventDefault(); rafraichir(adresse()); });
    // « À venir » est un defaut pour la liste vide, pas un choix : des qu'on
    // tape un nom, on cherche partout — la famille a peut-etre deja ete recue.
    // Une periode choisie a la main, elle, est respectee.
    let periodeChoisie = {{ request()->filled('quand') ? 'true' : 'false' }};
    form.addEventListener('change', (ev) => {
        if (ev.target.type !== 'radio') return;
        if (ev.target.name === 'quand') periodeChoisie = true;
        rafraichir(adresse());
    });
    const champ = form.querySelector('input[name="q"]');
    champ.addEventListener('input', () => {
        if (!periodeChoisie) {
            const cible = form.querySelector('input[name="quand"][value="' + (champ.value.trim() ? 'tous' : 'a_venir') + '"]');
            if (cible) cible.checked = true;
        }
        clearTimeout(minuterie);
        minuterie = setTimeout(() => rafraichir(adresse()), 350);
    });
    // « Precedent » : le formulaire reprend les filtres de l'adresse avant de
    // relire la liste, sinon il afficherait d'autres filtres que les resultats.
    window.addEventListener('popstate', () => {
        const params = new URLSearchParams(window.location.search);
        champ.value = params.get('q') || '';
        ['quand', 'type', 'statut'].forEach((nom) => {
            const valeur = params.get(nom) || (nom === 'quand' ? (champ.value.trim() ? 'tous' : 'a_venir') : '');
            const radio = form.querySelector('input[name="' + nom + '"][value="' + valeur + '"]');
            if (radio) radio.checked = true;
        });
        periodeChoisie = params.has('quand');
        rafraichir(window.location.href, false);
    });
})();
</script>
@endpush
