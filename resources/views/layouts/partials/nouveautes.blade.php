{{--
    Fenêtre « Nouveautés » : une entrée à la fois, avec ses captures avant/après
    quand elle en a. Contenu : resources/data/nouveautes.php, filtré par les
    permissions du compte (App\Support\Nouveautes). Aucune entrée pour ce
    compte = aucune fenêtre.

    Les identifiants whatsNewModal / whatsNewCloseBtn / whatsNewRemindLaterBtn /
    whatsNewDismissBtn sont lus par le script du layout qui décide d'ouvrir la
    fenêtre et retient le choix de l'utilisateur. Ne pas les renommer.
--}}
@php
    $nvx = \App\Support\Nouveautes::pour(auth()->user());
    $nvxTotal = count($nvx['entrees']);
@endphp

@if($nvxTotal > 0)
<style>
    .nvx-content { border: none; border-radius: 18px; overflow: hidden; box-shadow: 0 24px 60px rgba(15,23,42,.22); }
    .nvx-hero { flex-shrink: 0; background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 45%, #3b7ddb 100%); color: #fff; padding: 1.25rem 1.4rem 1rem; }
    .nvx-bandeau { display: flex; align-items: flex-start; gap: .85rem; }
    .nvx-hero-icone { width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.2); font-size: 1.1rem; }
    .nvx-surtitre { font-size: .7rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: rgba(255,255,255,.72); }
    .nvx-titre { font-size: 1.3rem; font-weight: 700; margin: .05rem 0 0; color: #fff; }
    .nvx-fermer { margin-left: auto; }
    .nvx-puces { display: flex; gap: .4rem; overflow-x: auto; margin-top: 1rem; padding-bottom: .15rem; scrollbar-width: none; }
    .nvx-puces::-webkit-scrollbar { display: none; }
    .nvx-puce { flex-shrink: 0; border: 1px solid rgba(255,255,255,.22); background: rgba(255,255,255,.08); color: rgba(255,255,255,.85); border-radius: 999px; padding: .32rem .75rem; font-size: .76rem; font-weight: 600; white-space: nowrap; transition: background .2s ease, color .2s ease; }
    .nvx-puce:hover { background: rgba(255,255,255,.16); }
    .nvx-puce--active { background: #fff; color: #0453cb; border-color: #fff; }

    .nvx-corps { padding: 1.25rem 1.4rem .5rem; min-height: 230px; overflow-y: auto; }
    .nvx-entree-tete { display: flex; align-items: center; gap: .75rem; margin-bottom: .6rem; }
    .nvx-entree-icone { width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #0453cb, #3b7ddb); color: #fff; font-size: .9rem; }
    .nvx-entree-titre { font-size: 1.05rem; font-weight: 700; color: #0f172a; margin: 0; }
    .nvx-entree-texte { font-size: .9rem; line-height: 1.55; color: #475569; margin: 0; }

    .nvx-comparer { margin: 1rem auto 0; }
    .nvx-comparer--telephone { max-width: 270px; }
    .nvx-cadre { position: relative; overflow: hidden; border-radius: 14px; border: 1px solid #e2e8f0; background: #f8fafc; box-shadow: 0 8px 26px rgba(4,83,203,.1); user-select: none; }
    .nvx-cadre img { display: block; width: 100%; height: auto; max-height: 46vh; object-fit: cover; object-position: top; }
    .nvx-cadre .nvx-avant { position: absolute; inset: 0; height: 100%; object-fit: cover; object-position: top; }
    .nvx-etiquette { position: absolute; top: .6rem; padding: .18rem .55rem; border-radius: 999px; font-size: .7rem; font-weight: 700; color: #fff; pointer-events: none; }
    .nvx-etiquette--avant { left: .6rem; background: rgba(15,23,42,.75); }
    .nvx-etiquette--apres { right: .6rem; background: #0453cb; }
    .nvx-trait { position: absolute; top: 0; bottom: 0; width: 2px; background: #fff; box-shadow: 0 0 0 1px rgba(4,83,203,.35); pointer-events: none; }
    .nvx-poignee { position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); width: 34px; height: 34px; border-radius: 999px; background: #fff; color: #0453cb; border: 1px solid rgba(4,83,203,.3); display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(15,23,42,.18); font-size: .72rem; }
    .nvx-curseur { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: ew-resize; margin: 0; }
    .nvx-curseur:focus-visible + .nvx-trait .nvx-poignee { outline: 3px solid rgba(4,83,203,.45); outline-offset: 2px; }
    .nvx-legende { margin-top: .5rem; font-size: .78rem; color: #64748b; text-align: center; }

    .nvx-pied { position: relative; z-index: 2; flex-shrink: 0; background: #fff; display: flex; align-items: center; gap: .6rem; padding: .85rem 1.4rem 1.1rem; border-top: 1px solid #eef2f7; }
    .nvx-points { display: flex; gap: .3rem; margin-right: auto; }
    .nvx-point { width: 7px; height: 7px; border-radius: 999px; background: #cbd5e1; border: none; padding: 0; transition: width .2s ease, background .2s ease; }
    .nvx-point--active { width: 20px; background: #0453cb; }
    .nvx-btn { border-radius: 10px; padding: .5rem .95rem; font-size: .84rem; font-weight: 600; border: 1px solid #dbe3ef; background: #fff; color: #334155; transition: background .2s ease, border-color .2s ease; }
    .nvx-btn:hover { background: #f1f5f9; }
    .nvx-btn--principal { background: #0453cb; border-color: #0453cb; color: #fff; }
    .nvx-btn--principal:hover { background: #033a8e; border-color: #033a8e; }
    .nvx-btn--discret { border-color: transparent; color: #64748b; }

    @media (max-width: 575.98px) {
        .nvx-hero { padding: 1rem 1rem .85rem; }
        .nvx-corps { padding: 1rem; }
        .nvx-pied { flex-wrap: wrap; padding: .75rem 1rem calc(.9rem + env(safe-area-inset-bottom, 0px)); }
        .nvx-points { width: 100%; justify-content: center; margin: 0 0 .35rem; }
        .nvx-pied .nvx-btn { flex: 1; }
    }
</style>

<div class="modal fade" id="whatsNewModal" tabindex="-1" aria-labelledby="whatsNewModalLabel" aria-hidden="true" data-bs-backdrop="static" data-pref-key="{{ $cleVersion }}.user.{{ auth()->id() }}"
     data-total="{{ $nvxTotal }}"
     x-data="{ i: 0, n: Number($el.dataset.total), aller(k) { this.i = Math.max(0, Math.min(this.n - 1, k)); } }"
     x-on:keydown.arrow-right="aller(i + 1)"
     x-on:keydown.arrow-left="aller(i - 1)">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content nvx-content">
            <div class="nvx-hero">
                <div class="nvx-bandeau">
                    <div class="nvx-hero-icone"><i class="fas fa-wand-magic-sparkles"></i></div>
                    <div>
                        <div class="nvx-surtitre">Nouveautés</div>
                        <h5 class="nvx-titre" id="whatsNewModalLabel">{{ $nvx['titre'] }}</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white nvx-fermer" data-bs-dismiss="modal" aria-label="Fermer" id="whatsNewCloseBtn"></button>
                </div>
                @if($nvxTotal > 1)
                    <div class="nvx-puces" role="tablist" aria-label="Nouveautés">
                        @foreach($nvx['entrees'] as $k => $entree)
                            <button type="button" class="nvx-puce" role="tab"
                                    :class="i === {{ $k }} ? 'nvx-puce--active' : ''"
                                    :aria-selected="i === {{ $k }} ? 'true' : 'false'"
                                    x-on:click="aller({{ $k }})">{{ $entree['titre'] }}</button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="modal-body nvx-corps">
                @foreach($nvx['entrees'] as $k => $entree)
                    <section x-show="i === {{ $k }}" @if($k > 0) x-cloak @endif role="tabpanel">
                        <div class="nvx-entree-tete">
                            <div class="nvx-entree-icone"><i class="fas {{ $entree['icone'] ?? 'fa-star' }}"></i></div>
                            <h6 class="nvx-entree-titre">{{ $entree['titre'] }}</h6>
                        </div>
                        <p class="nvx-entree-texte">{{ $entree['texte'] }}</p>

                        @if(!empty($entree['captures']))
                            @php $cap = $entree['captures']; @endphp
                            <figure class="nvx-comparer {{ ($cap['format'] ?? '') === 'telephone' ? 'nvx-comparer--telephone' : '' }}" x-data="{ pos: 50 }">
                                <div class="nvx-cadre">
                                    <img src="{{ asset($cap['apres']) }}" alt="{{ $entree['titre'] }} : après" loading="lazy" draggable="false">
                                    <img class="nvx-avant" src="{{ asset($cap['avant']) }}" alt="{{ $entree['titre'] }} : avant" loading="lazy" draggable="false"
                                         :style="'clip-path: inset(0 ' + (100 - pos) + '% 0 0)'" style="clip-path: inset(0 50% 0 0)">
                                    <span class="nvx-etiquette nvx-etiquette--avant">Avant</span>
                                    <span class="nvx-etiquette nvx-etiquette--apres">Après</span>
                                    <input type="range" min="0" max="100" step="1" class="nvx-curseur" x-model.number="pos"
                                           x-on:keydown.arrow-left.stop x-on:keydown.arrow-right.stop aria-label="Comparer avant et après">
                                    <div class="nvx-trait" :style="'left: ' + pos + '%'" style="left: 50%">
                                        <span class="nvx-poignee"><i class="fas fa-arrows-left-right"></i></span>
                                    </div>
                                </div>
                                @if(!empty($cap['legende']))
                                    <figcaption class="nvx-legende">{{ $cap['legende'] }}</figcaption>
                                @endif
                            </figure>
                        @endif
                    </section>
                @endforeach
            </div>

            <div class="nvx-pied">
                @if($nvxTotal > 1)
                    <div class="nvx-points" aria-hidden="true">
                        @foreach($nvx['entrees'] as $k => $entree)
                            <button type="button" tabindex="-1" class="nvx-point" :class="i === {{ $k }} ? 'nvx-point--active' : ''" x-on:click="aller({{ $k }})"></button>
                        @endforeach
                    </div>
                @endif
                <button type="button" class="nvx-btn nvx-btn--discret" id="whatsNewRemindLaterBtn" data-bs-dismiss="modal">Plus tard</button>
                <button type="button" class="nvx-btn" x-show="i > 0" x-cloak x-on:click="aller(i - 1)">Précédent</button>
                <button type="button" class="nvx-btn nvx-btn--principal" x-show="i < n - 1" @if($nvxTotal <= 1) x-cloak @endif x-on:click="aller(i + 1)">Suivant</button>
                <button type="button" class="nvx-btn nvx-btn--principal" id="whatsNewDismissBtn" data-bs-dismiss="modal" x-show="i === n - 1" @if($nvxTotal > 1) x-cloak @endif>J’ai compris</button>
            </div>
        </div>
    </div>
</div>
@endif
