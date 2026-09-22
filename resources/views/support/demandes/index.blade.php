@extends('layouts.app')

@section('title', 'Mes demandes de support')

@push('styles')
<style>
    .sd-hero { background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%); border-radius: 18px; padding: 2rem 2.5rem 1.5rem; color: #fff; margin-bottom: 1.25rem; }
    .sd-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
    .sd-hero-left { display: flex; align-items: center; gap: 1rem; }
    .sd-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.15);
        display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0; color: #fff; }
    .sd-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
    .sd-hero p { color: rgba(255,255,255,.7); font-size: .88rem; margin: 0; }
    .sd-btn { display: inline-flex; align-items: center; gap: .45rem; border-radius: 10px; padding: .55rem 1rem; font-size: .84rem; font-weight: 600; text-decoration: none; border: 1px solid transparent; }
    .sd-btn--blanc { background: #fff; color: #0453cb; }
    .sd-btn--blanc:hover { color: #033a8e; }
    .sd-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; }
    .sd-kpi { flex: 1; min-width: 140px; background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15); border-radius: 12px; padding: .9rem 1rem; }
    .sd-kpi-value { font-size: 1.35rem; font-weight: 700; color: #fff; }
    .sd-kpi-label { font-size: .72rem; color: rgba(255,255,255,.65); margin-top: .15rem; }
    .sd-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); }
    .sd-onglets { display: flex; gap: .35rem; padding: .75rem .75rem 0; border-bottom: 1px solid #e2e8f0; }
    .sd-onglet { padding: .55rem .9rem; font-size: .84rem; font-weight: 600; color: #64748b; text-decoration: none; border-bottom: 2px solid transparent; margin-bottom: -1px; }
    .sd-onglet--actif { color: #0453cb; border-bottom-color: #0453cb; }
    .sd-liste { padding: .5rem; transition: opacity .15s ease; }
    .sd-liste.is-chargement { opacity: .5; }
    .sd-ligne { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .9rem 1rem; border-radius: 10px; text-decoration: none; color: inherit; }
    .sd-ligne + .sd-ligne { border-top: 1px solid #f1f5f9; }
    .sd-ligne:hover { background: #f8fafc; }
    .sd-ligne-principal { display: flex; flex-direction: column; gap: .15rem; min-width: 0; }
    .sd-ref { font-family: 'Courier New', monospace; font-size: .74rem; color: #0453cb; font-weight: 700; }
    .sd-titre { font-weight: 600; color: #1e293b; font-size: .92rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sd-meta { font-size: .76rem; color: #64748b; }
    .sd-reponse { font-size: .8rem; color: #475569; }
    .sd-reponse i { color: #0453cb; margin-right: .25rem; }
    .sd-statut { flex-shrink: 0; font-size: .74rem; font-weight: 700; padding: .3rem .6rem; border-radius: 999px; white-space: nowrap; }
    .sd-statut--info { background: rgba(4,83,203,.08); color: #0453cb; }
    .sd-statut--attention { background: rgba(245,158,11,.12); color: #b45309; }
    .sd-statut--succes { background: rgba(16,185,129,.12); color: #047857; }
    .sd-statut--neutre { background: #f1f5f9; color: #64748b; }
    .sd-vide { text-align: center; padding: 2.5rem 1rem; color: #64748b; }
    .sd-vide i { font-size: 1.8rem; color: #94a3b8; margin-bottom: .6rem; }
    .sd-vide strong { color: #1e293b; }
    .sd-attente { list-style: none; margin-bottom: 1rem; padding: .85rem 1rem; border-radius: 12px; background: rgba(245,158,11,.08); border: 1px solid rgba(245,158,11,.25); color: #92400e; font-size: .84rem; }
    .sd-pagination { display: flex; gap: .3rem; justify-content: center; padding: .75rem; }
    .sd-page { min-width: 32px; text-align: center; padding: .3rem .5rem; border-radius: 8px; font-size: .82rem; color: #475569; text-decoration: none; border: 1px solid #e2e8f0; }
    .sd-page--active { background: #0453cb; color: #fff; border-color: #0453cb; }
    @media (max-width: 768px) {
        .sd-hero { padding: 1.4rem 1.25rem 1.2rem; }
        .sd-ligne { flex-direction: column; align-items: flex-start; }
        .sd-titre { white-space: normal; }
    }
</style>
@endpush

@section('content')
<div class="sd-page-wrap">
    <div class="sd-hero">
        <div class="sd-hero-top">
            <div class="sd-hero-left">
                <div class="sd-hero-icon"><i class="fas fa-life-ring"></i></div>
                <div>
                    <h1>Demandes de support</h1>
                    <p>Suivez ce que vous avez signalé à l'équipe KLASSCI, et nos réponses.</p>
                </div>
            </div>
            @if($signalementOuvert)
                <a href="mailto:{{ config('app.support_email') }}" class="sd-btn sd-btn--blanc" data-support-ouvrir>
                    <i class="fas fa-plus"></i> Signaler un problème
                </a>
            @endif
        </div>
        <div class="sd-kpis">
            <div class="sd-kpi"><div class="sd-kpi-value">{{ $indisponible ? '—' : ($meta['total'] ?? 0) }}</div><div class="sd-kpi-label">{{ $portee === 'school' ? "Demandes de l'établissement" : 'Mes demandes' }}</div></div>
            <div class="sd-kpi"><div class="sd-kpi-value">{{ $boiteEnvoi->whereNull('sent_at')->whereNull('abandoned_at')->count() }}</div><div class="sd-kpi-label">En attente d'envoi</div></div>
        </div>
    </div>

    @if($boiteEnvoi->isNotEmpty())
        <ul class="sd-attente">
            @foreach($boiteEnvoi as $ligne)
                <li>
                    @if($ligne->abandoned_at)
                        <i class="fas fa-circle-exclamation me-1"></i>
                        <strong>Non transmise</strong> — « {{ $ligne->extrait() }} ».
                        Écrivez-nous à <a href="mailto:{{ config('app.support_email') }}">{{ config('app.support_email') }}</a>.
                    @elseif($ligne->sent_at)
                        <i class="fas fa-check me-1"></i>
                        <strong>Transmise</strong>
                        @if($ligne->reference)
                            sous la référence <a href="{{ route('support.demandes.show', $ligne->reference) }}">{{ $ligne->reference }}</a>
                        @endif
                        — « {{ $ligne->extrait() }} ».
                    @else
                        <i class="fas fa-cloud-arrow-up me-1"></i>
                        <strong>En attente d'envoi</strong> — « {{ $ligne->extrait() }} ». Elle partira dès que la connexion sera rétablie.
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    <div class="sd-card">
        @if($peutVoirEcole)
            <div class="sd-onglets" role="tablist">
                <a href="{{ route('support.demandes.index') }}" class="sd-onglet {{ $portee === 'mine' ? 'sd-onglet--actif' : '' }}" data-sd-lien data-sd-portee="mine" role="tab">Mes demandes</a>
                <a href="{{ route('support.demandes.index', ['portee' => 'ecole']) }}" class="sd-onglet {{ $portee === 'school' ? 'sd-onglet--actif' : '' }}" data-sd-lien data-sd-portee="school" role="tab">Tout l'établissement</a>
            </div>
        @endif
        @include('support.demandes._liste')
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    /* Onglets et pagination sans rechargement : seule la liste est remplacee. */
    function charger(url, pousser) {
        var liste = document.getElementById('sd-liste');
        if (!liste) { return; }
        liste.classList.add('is-chargement');
        fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (r) { if (!r.ok) { throw new Error(r.status); } return r.json(); })
            .then(function (d) {
                liste.outerHTML = d.liste;
                document.querySelectorAll('.sd-onglet').forEach(function (o) {
                    o.classList.toggle('sd-onglet--actif', o.getAttribute('data-sd-portee') === d.portee);
                });
                if (pousser) { history.pushState({ sd: true }, '', url); }
            })
            .catch(function () { window.location.href = url; });
    }
    document.addEventListener('click', function (ev) {
        var lien = ev.target.closest('[data-sd-lien]');
        if (!lien || ev.metaKey || ev.ctrlKey) { return; }
        ev.preventDefault();
        charger(lien.getAttribute('href'), true);
    });
    window.addEventListener('popstate', function () { charger(window.location.href, false); });
    /* Une demande envoyee depuis cette page apparait tout de suite dans la liste. */
    document.addEventListener('support:demande-envoyee', function () { charger(window.location.href, false); });
})();
</script>
@endpush
