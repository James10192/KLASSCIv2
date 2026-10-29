{{--
    Ossature d'un tableau de bord de rôle.

    Un tableau de bord n'est pas une grille de cartes égales : c'est une
    hiérarchie de lecture. De haut en bas et de gauche à droite :

      1. le hero et ses chiffres de tête   → où j'en suis
      2. le bandeau d'alertes              → ce qui exige une action, et rien d'autre
      3. le bloc focal, dominant           → la question que le rôle doit trancher
      4. le rail latéral, secondaire       → la file de travail et les raccourcis

    C'est cette asymétrie qui distingue un tableau de bord d'un écran de liste.
    Le bloc focal occupe deux tiers de la largeur ; le rail suit dessous sur
    mobile plutôt que de se comprimer.

    @slot alerts  Optionnel, masqué si vide
    @slot focal   Le bloc dominant
    @slot rail    La colonne secondaire
--}}

<div class="dsh">
    @isset($alerts)
        <div class="dsh-alerts">{{ $alerts }}</div>
    @endisset

    <div class="dsh-body">
        <div class="dsh-focal">{{ $focal }}</div>
        @isset($rail)
            <aside class="dsh-rail">{{ $rail }}</aside>
        @endisset
    </div>
</div>

@once
@push('styles')
<style>
    /* ===== Ossature de tableau de bord ===== */
    .dsh { display: flex; flex-direction: column; gap: 1.25rem; }

    .dsh-alerts { display: flex; flex-direction: column; gap: .6rem; }

    /* Asymétrie volontaire : le focal domine, le rail accompagne. */
    .dsh-body {
        display: grid;
        grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
        gap: 1.25rem;
        align-items: start;
    }
    .dsh-focal { min-width: 0; display: flex; flex-direction: column; gap: 1.25rem; }
    .dsh-rail { min-width: 0; display: flex; flex-direction: column; gap: 1rem; }

    /* ===== Bandeau d'alerte actionnable ===== */
    .dsh-alert {
        display: flex; align-items: center; gap: .85rem;
        padding: .85rem 1.1rem;
        background: #fff; border: 1px solid #e2e8f0;
        border-left: 4px solid #0453cb; border-radius: 12px;
        text-decoration: none;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .04);
        transition: box-shadow .2s ease, transform .2s ease;
    }
    .dsh-alert:hover { box-shadow: 0 6px 20px rgba(4, 83, 203, .08); }
    .dsh-alert-icon {
        width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; font-size: .9rem;
        background: rgba(4, 83, 203, .08); color: #0453cb;
    }
    .dsh-alert-body { display: flex; flex-direction: column; min-width: 0; }
    .dsh-alert-title { font-size: .9rem; font-weight: 700; color: #1e293b; }
    .dsh-alert-text { font-size: .78rem; color: #64748b; margin-top: .1rem; }
    .dsh-alert-go { margin-left: auto; color: #94a3b8; font-size: .75rem; }
    .dsh-alert--warning { border-left-color: #f59e0b; }
    .dsh-alert--warning .dsh-alert-icon { background: rgba(245, 158, 11, .12); color: #b45309; }
    .dsh-alert--danger { border-left-color: #dc2626; }
    .dsh-alert--danger .dsh-alert-icon { background: rgba(220, 38, 38, .1); color: #dc2626; }

    /* ===== Chiffres de synthèse sous un graphique ===== */
    .dsh-figures {
        display: grid; grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .75rem; margin-top: 1rem;
        padding-top: 1rem; border-top: 1px solid #eef2f7;
    }
    .dsh-figure { text-align: center; }
    .dsh-figure-value { display: block; font-size: 1.5rem; font-weight: 700; color: #1e293b; line-height: 1; }
    .dsh-figure-label { display: block; font-size: .74rem; color: #64748b; margin-top: .25rem; }
    .dsh-figure--warn .dsh-figure-value { color: #b45309; }
    .dsh-figure--ok .dsh-figure-value { color: #047857; }

    /* ===== Légende de lecture d'un graphique ===== */
    .dsh-legend {
        display: flex; flex-wrap: wrap; gap: .75rem;
        margin-top: .85rem; font-size: .74rem; color: #64748b;
    }
    .dsh-legend-item { display: inline-flex; align-items: center; gap: .35rem; }
    .dsh-legend-dot { width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0; }

    @media (max-width: 1100px) {
        .dsh-body { grid-template-columns: 1fr; }
    }
    @media (max-width: 768px) {
        .dsh-figures { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
</style>
@endpush
@endonce
