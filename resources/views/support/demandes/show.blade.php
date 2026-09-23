@extends('layouts.app')

@section('title', 'Demande '.$reference)

@push('styles')
<style>
    .sd-entete { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem; }
    .sd-entete h1 { font-size: 1.3rem; font-weight: 700; color: #1e293b; margin: .25rem 0 0; }
    .sd-ref { font-family: 'Courier New', monospace; font-size: .8rem; color: #0453cb; font-weight: 700; }
    .sd-retour { font-size: .84rem; color: #0453cb; text-decoration: none; font-weight: 600; }
    .sd-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 1.25rem 1.5rem; margin-bottom: 1rem;
        box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); }
    .sd-card h2 { font-size: .8rem; text-transform: uppercase; letter-spacing: .5px; color: #64748b; font-weight: 700; margin: 0 0 .75rem; }
    .sd-texte { white-space: pre-line; color: #1e293b; font-size: .92rem; margin: 0; }
    .sd-meta { font-size: .78rem; color: #64748b; margin-top: .75rem; }
    .sd-fil { display: flex; flex-direction: column; gap: .75rem; }
    .sd-msg { padding: .85rem 1rem; border-radius: 12px; font-size: .9rem; }
    .sd-msg--support { background: rgba(4,83,203,.06); border: 1px solid rgba(4,83,203,.15); }
    .sd-msg--ecole { background: #f8fafc; border: 1px solid #e2e8f0; }
    .sd-msg-auteur { font-size: .76rem; color: #64748b; margin-bottom: .3rem; }
    .sd-msg-auteur strong { color: #1e293b; }
    .sd-msg p { white-space: pre-line; margin: 0; color: #1e293b; }
    .sd-vide { color: #64748b; font-size: .88rem; margin: 0; }
    .sd-statut { font-size: .78rem; font-weight: 700; padding: .35rem .7rem; border-radius: 999px; white-space: nowrap; }
    .sd-statut--info { background: rgba(4,83,203,.08); color: #0453cb; }
    .sd-statut--attention { background: rgba(245,158,11,.12); color: #b45309; }
    .sd-statut--succes { background: rgba(16,185,129,.12); color: #047857; }
    .sd-statut--neutre { background: #f1f5f9; color: #64748b; }
    .sd-reponse { margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #f1f5f9; }
    .sd-reponse-label { display: block; font-size: .8rem; font-weight: 600; color: #475569; margin-bottom: .4rem; }
    .sd-reponse textarea { width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; padding: .7rem .85rem; font-size: .9rem; color: #1e293b; resize: vertical; }
    .sd-reponse textarea:focus { outline: none; border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.12); }
    .sd-erreur { margin-top: .5rem; font-size: .82rem; color: #b91c1c; }
    .sd-reponse-actions { display: flex; justify-content: flex-end; margin-top: .6rem; }
    .sd-reponse-close { margin: 0 0 .6rem; font-size: .88rem; color: #64748b; }
    .sd-envoyer { background: #0453cb; color: #fff; border: none; border-radius: 10px; padding: .55rem 1.1rem; font-size: .84rem; font-weight: 600; cursor: pointer; }
    .sd-envoyer:hover { background: #033a8e; }
    .sd-envoyer:disabled { opacity: .6; cursor: wait; }
</style>
@endpush

@section('content')
<a href="{{ route('support.demandes.index', request('portee') === 'ecole' ? ['portee' => 'ecole'] : []) }}" class="sd-retour"><i class="fas fa-arrow-left me-1"></i> Toutes mes demandes</a>

@if($indisponible)
    <div class="sd-card mt-3">
        <p class="sd-vide"><strong>Le support est momentanément injoignable.</strong> La demande {{ $reference }} est en sécurité ; réessayez dans un instant.</p>
    </div>
@else
    <div class="sd-entete mt-2">
        <div>
            <span class="sd-ref">{{ $demande['reference'] }}</span>
            <h1>{{ $demande['titre'] }}</h1>
        </div>
        <div id="sd-statut" aria-live="polite">@include('support.demandes._statut', ['statut' => $demande['statut']])</div>
    </div>

    <div class="sd-card">
        <h2>Votre signalement</h2>
        <p class="sd-texte">{{ $demande['description'] }}</p>
        <div class="sd-meta">
            {{ $demande['categorie']['libelle'] ?? '' }}
            · envoyé le {{ \App\Domain\Support\Services\DateDuMaster::afficher($demande['cree_le'] ?? null, 'd M Y à H:i') }}
            @if(!empty($demande['rapporteur']['nom'])) · par {{ $demande['rapporteur']['nom'] }} @endif
        </div>
    </div>

    <div class="sd-card">
        <h2>Échanges</h2>
        <div id="sd-fil">@include('support.demandes._fil', ['messages' => $demande['messages'] ?? []])</div>

        @if($peutRepondre)
            <form id="sd-reponse" class="sd-reponse" action="{{ route('support.demandes.repondre', $demande['reference']) }}" method="POST" novalidate>
                @csrf
                <label for="sd-reponse-corps" class="sd-reponse-label">Votre réponse</label>
                <textarea id="sd-reponse-corps" name="corps" rows="3" maxlength="{{ $limites['description_max'] }}" required
                    placeholder="Répondez au support, ou précisez ce qui se passe."></textarea>
                <div class="sd-erreur" role="alert" hidden></div>
                <div class="sd-reponse-actions">
                    <button type="submit" class="sd-envoyer">
                        <span data-sd-libelle><i class="fas fa-paper-plane me-1"></i> Envoyer</span>
                        <span data-sd-envoi hidden>Envoi…</span>
                    </button>
                </div>
            </form>
        @endif
    </div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('sd-reponse');
    if (!form) { return; }
    var champ = form.querySelector('textarea');
    var erreur = form.querySelector('.sd-erreur');
    var bouton = form.querySelector('button[type="submit"]');
    function nouvelleCle() {
        return window.crypto && crypto.randomUUID ? crypto.randomUUID()
            : 'xxxxxxxx-xxxx-4xxx-8xxx-xxxxxxxxxxxx'.replace(/x/g, function () { return (Math.random() * 16 | 0).toString(16); });
    }
    /* Une cle par brouillon : renvoyer apres une coupure ne publie pas deux fois. */
    var cle = nouvelleCle();
    champ.addEventListener('input', function () { cle = nouvelleCle(); erreur.hidden = true; });

    function montrer(message) { erreur.textContent = message; erreur.hidden = false; }
    function occupe(oui) {
        bouton.disabled = oui;
        bouton.querySelector('[data-sd-libelle]').hidden = oui;
        bouton.querySelector('[data-sd-envoi]').hidden = !oui;
    }
    function envoyer() {
        return fetch(form.action, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value },
            body: JSON.stringify({ corps: champ.value, cle: cle })
        }).then(function (r) {
            return r.json().catch(function () { return {}; }).then(function (d) {
                if (!r.ok) {
                    var premier = d.errors ? Object.values(d.errors)[0] : null;
                    var e = new Error((premier && premier[0]) || d.message || "Votre réponse n'a pas pu être envoyée.");
                    e.donnees = d;
                    throw e;
                }
                return d;
            });
        });
    }
    form.addEventListener('submit', function (ev) {
        ev.preventDefault();
        if (champ.value.trim() === '') { montrer('Écrivez votre réponse.'); champ.focus(); return; }
        occupe(true);
        envoyer().then(function (d) {
            document.getElementById('sd-fil').innerHTML = d.fil;
            document.getElementById('sd-statut').innerHTML = d.statut;
            champ.value = '';
            cle = nouvelleCle();
            if (!d.peut_repondre) { form.remove(); }
        }).catch(function (e) {
            var d = e.donnees || {};
            if (d.statut) { document.getElementById('sd-statut').innerHTML = d.statut; }
            if (d.peut_repondre === false) {
                /* Plus rien a envoyer d'ici, mais le texte reste : l'utilisateur peut le copier. */
                var avis = document.createElement('p');
                avis.className = 'sd-reponse-close';
                avis.textContent = e.message;
                form.insertBefore(avis, form.firstChild);
                champ.readOnly = true;
                bouton.remove();
                return;
            }
            montrer(e.message);
            champ.focus();
        }).finally(function () { if (form.contains(bouton)) { occupe(false); } });
    });
})();
</script>
@endpush
