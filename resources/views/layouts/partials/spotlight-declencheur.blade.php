{{--
    Déclencheur de la palette de recherche, dans la barre du haut (bureau).
    Un bouton, pas un champ : taper ici ouvrirait un menu déroulant coincé sous
    la barre. Il ouvre la palette (layouts/partials/spotlight), qui écoute
    tout élément portant data-spl-ouvrir. L'indication de raccourci est
    réécrite en « ⌘ K » sur Mac par le script de la palette.
--}}
<style>
    .spl-declencheur {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: .6rem;
        width: clamp(240px, 30vw, 420px);
        height: 38px;
        padding: 0 8px 0 12px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #f8fafc;
        color: #94a3b8;
        font: 400 13.5px/1 'Inter', system-ui, sans-serif;
        text-align: left;
        cursor: text;
        transition: border-color .15s ease, background-color .15s ease, box-shadow .15s ease;
    }
    .spl-declencheur:hover { border-color: #cbd5e1; background: #fff; }
    .spl-declencheur:focus-visible {
        outline: none;
        border-color: #0453cb;
        box-shadow: 0 0 0 3px rgba(4, 83, 203, .15);
        background: #fff;
    }
    .spl-declencheur-ico { color: #64748b; font-size: 13px; flex-shrink: 0; }
    .spl-declencheur-txt { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .spl-kbd {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        height: 20px;
        padding: 0 6px;
        border: 1px solid #e2e8f0;
        border-radius: 5px;
        background: #fff;
        color: #64748b;
        font: 600 11px/1 'Inter', system-ui, sans-serif;
        box-shadow: 0 1px 0 #e2e8f0;
    }
</style>
<button type="button" class="spl-declencheur" data-spl-ouvrir aria-haspopup="dialog"
        aria-keyshortcuts="Control+K Meta+K" aria-label="Rechercher dans l'application">
    <i class="fas fa-magnifying-glass spl-declencheur-ico" aria-hidden="true"></i>
    <span class="spl-declencheur-txt">Rechercher dans l'application…</span>
    <kbd class="spl-kbd" data-spl-raccourci aria-hidden="true">Ctrl K</kbd>
</button>
