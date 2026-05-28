{{--
    FAB (Floating Action Button) "+ Encaisser" pour le module comptabilité.

    Affiche un bouton flottant en bas-droite qui pointe vers /esbtp/paiements/create.
    Visible UNIQUEMENT si l'utilisateur a la permission `paiements.create`.

    Raccourci clavier : Ctrl+E (ou Cmd+E sur macOS) — focus action immédiate.

    Usage : <x-fab-encaisser /> dans n'importe quelle vue compta.

    Position bottom-right responsive : 24px desktop, 16px mobile.
    Pas affiché si on est déjà sur /esbtp/paiements/create (évite redondance).
--}}
@can('paiements.create')
@if(!Request::routeIs('esbtp.paiements.create'))
<a href="{{ route('esbtp.paiements.create') }}" id="fab-encaisser" class="fab-encaisser"
   title="Encaisser un nouveau paiement (Ctrl+E)" aria-label="Encaisser un paiement">
    <span class="fab-encaisser-icon"><i class="fas fa-plus"></i></span>
    <span class="fab-encaisser-label">Encaisser</span>
    <span class="fab-encaisser-kbd"><kbd>Ctrl</kbd>+<kbd>E</kbd></span>
</a>

@push('styles')
<style>
body.has-fab-encaisser .chatbot-widget {
    bottom: 108px !important;
}

.fab-encaisser {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 1040;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 14px 20px 14px 16px;
    background: linear-gradient(135deg, #0453cb 0%, #033a8e 100%);
    color: #fff !important;
    border-radius: 999px;
    box-shadow: 0 8px 24px rgba(4,83,203,.35), 0 2px 6px rgba(15,23,42,.12);
    text-decoration: none;
    font-weight: 700;
    font-size: .92rem;
    letter-spacing: -.01em;
    transition: transform .15s cubic-bezier(.22,.68,0,1.2), box-shadow .15s, background .15s;
    cursor: pointer;
    overflow: hidden;
    white-space: nowrap;
}
.fab-encaisser:hover {
    transform: translateY(-3px) scale(1.02);
    box-shadow: 0 12px 32px rgba(4,83,203,.45), 0 4px 10px rgba(15,23,42,.18);
    color: #fff !important;
    text-decoration: none;
    background: linear-gradient(135deg, #033a8e 0%, #0453cb 100%);
}
.fab-encaisser:active { transform: translateY(-1px) scale(.99); }
.fab-encaisser-icon {
    display: inline-flex; align-items: center; justify-content: center;
    width: 32px; height: 32px; border-radius: 50%;
    background: rgba(255,255,255,.22);
    flex-shrink: 0;
    transition: background .15s, transform .2s;
}
.fab-encaisser:hover .fab-encaisser-icon {
    background: rgba(255,255,255,.35);
    transform: rotate(90deg);
}
.fab-encaisser-icon i { font-size: .85rem; }
.fab-encaisser-label { line-height: 1; }
.fab-encaisser-kbd {
    display: inline-flex; align-items: center; gap: 4px;
    margin-left: 6px;
    padding-left: 12px;
    border-left: 1px solid rgba(255,255,255,.25);
    font-size: .68rem;
    font-weight: 500;
    opacity: .85;
}
.fab-encaisser-kbd kbd {
    background: rgba(255,255,255,.18);
    border: 1px solid rgba(255,255,255,.25);
    border-radius: 4px;
    padding: 1px 6px;
    font-family: -apple-system, system-ui, sans-serif;
    font-size: .68rem;
    color: #fff;
    box-shadow: none;
}

/* Animation pulse subtile pour attirer l'œil au premier load */
@keyframes fabPulse {
    0%, 100% { box-shadow: 0 8px 24px rgba(4,83,203,.35), 0 2px 6px rgba(15,23,42,.12); }
    50% { box-shadow: 0 8px 24px rgba(4,83,203,.5), 0 2px 6px rgba(15,23,42,.12), 0 0 0 6px rgba(4,83,203,.10); }
}
.fab-encaisser {
    animation: fabPulse 2.4s ease-in-out 2;  /* pulse 2 fois après load puis stop */
}

/* Mobile : compacte le label et masque le raccourci clavier */
@media (max-width: 768px) {
    body.has-fab-encaisser .chatbot-widget {
        bottom: 84px !important;
    }

    .fab-encaisser {
        bottom: 16px;
        right: 16px;
        padding: 12px 16px 12px 12px;
        font-size: .85rem;
    }
    .fab-encaisser-kbd { display: none; }
}
@media (max-width: 480px) {
    .fab-encaisser-label { display: none; }
    .fab-encaisser { padding: 14px; }
    .fab-encaisser-icon { width: 28px; height: 28px; }
}

/* Reduce motion : pas d'animation pulse pour les users sensibles */
@media (prefers-reduced-motion: reduce) {
    .fab-encaisser { animation: none; transition: none; }
    .fab-encaisser:hover { transform: none; }
    .fab-encaisser:hover .fab-encaisser-icon { transform: none; }
}

/* Print : pas de FAB */
@media print {
    .fab-encaisser { display: none !important; }
}
</style>
@endpush

@push('scripts')
<script>
(function() {
    function markFabEncaisserPresence() {
        document.documentElement.classList.add('has-fab-encaisser');
        if (document.body) {
            document.body.classList.add('has-fab-encaisser');
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', markFabEncaisserPresence, { once: true });
    } else {
        markFabEncaisserPresence();
    }
    // Raccourci clavier Ctrl+E (ou Cmd+E macOS) → naviguer vers /esbtp/paiements/create
    // Skip si user est en train de saisir dans un input/textarea/contenteditable
    document.addEventListener('keydown', function(e) {
        const isCtrlE = (e.ctrlKey || e.metaKey) && (e.key === 'e' || e.key === 'E');
        if (!isCtrlE) return;

        const target = e.target;
        const isEditable = target && (
            target.tagName === 'INPUT' ||
            target.tagName === 'TEXTAREA' ||
            target.tagName === 'SELECT' ||
            target.isContentEditable
        );
        if (isEditable) return;

        const fab = document.getElementById('fab-encaisser');
        if (!fab) return;

        e.preventDefault();
        window.location.href = fab.getAttribute('href');
    });
})();
</script>
@endpush
@endif
@endcan
