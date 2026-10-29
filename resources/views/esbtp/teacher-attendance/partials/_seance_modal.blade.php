{{-- Modal aperçu d'une séance (rempli en JS depuis les data-attributes de la ligne).
     Partagé page report + fiche enseignant. --}}
<style>
    .tsm-overlay { position: fixed; inset: 0; background: rgba(15,23,42,.5); backdrop-filter: blur(2px); display: none; align-items: center; justify-content: center; padding: 1rem; z-index: 1085; }
    .tsm-overlay.show { display: flex; }
    .tsm-modal { background: #fff; border-radius: 16px; width: 100%; max-width: 480px; box-shadow: 0 24px 60px rgba(15,23,42,.25); overflow: hidden; }
    .tsm-head { display: flex; align-items: center; gap: .7rem; padding: 1.1rem 1.4rem; background: linear-gradient(135deg, #0a3d8f, #0453cb); color: #fff; }
    .tsm-head-ico { width: 40px; height: 40px; border-radius: 11px; background: rgba(255,255,255,.16); display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
    .tsm-title { font-size: 1rem; font-weight: 700; }
    .tsm-sub { font-size: .74rem; color: rgba(255,255,255,.75); margin-top: .1rem; }
    .tsm-close { margin-left: auto; border: none; background: rgba(255,255,255,.16); color: #fff; width: 32px; height: 32px; border-radius: 8px; cursor: pointer; }
    .tsm-body { padding: 1.1rem 1.4rem; }
    .tsm-row { display: flex; align-items: center; justify-content: space-between; padding: .55rem 0; border-bottom: 1px solid #f4f6fa; font-size: .85rem; }
    .tsm-row:last-child { border-bottom: none; }
    .tsm-row > span { color: #64748b; }
    .tsm-row > strong { color: #1e293b; font-weight: 700; text-align: right; }
    .tsm-badge { font-size: .72rem; font-weight: 700; padding: .2rem .55rem; border-radius: 6px; }
    .tsm-typechip { display: inline-flex; align-items: center; gap: .3rem; font-size: .68rem; font-weight: 700; padding: .15rem .45rem; border-radius: 6px; }
    .tsm-foot { padding: 1rem 1.4rem; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: .6rem; }
    .tsm-btn { display: inline-flex; align-items: center; gap: .45rem; border-radius: 10px; padding: .55rem 1rem; font-size: .82rem; font-weight: 700; text-decoration: none; cursor: pointer; border: 1px solid transparent; }
    .tsm-btn--ghost { background: #fff; color: #475569; border-color: #e2e8f0; }
    .tsm-btn--primary { background: #0453cb; color: #fff; }
    .tsm-btn--primary:hover { background: #033a8e; color: #fff; }
</style>
<div class="tsm-overlay" id="tarSeanceModal" onclick="if(event.target===this) closeSeanceModal()">
    <div class="tsm-modal">
        <div class="tsm-head">
            <div class="tsm-head-ico"><i class="fas fa-calendar-day"></i></div>
            <div>
                <div class="tsm-title" id="tsmMatiere">Séance</div>
                <div class="tsm-sub"><span class="tsm-typechip" id="tsmType"></span></div>
            </div>
            <button type="button" class="tsm-close" onclick="closeSeanceModal()"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="tsm-body">
            <div class="tsm-row"><span>Statut émargement</span><span class="tsm-badge" id="tsmStatut">—</span></div>
            <div class="tsm-row"><span>Enseignant</span><strong id="tsmTeacher">—</strong></div>
            <div class="tsm-row"><span>Classe</span><strong id="tsmClasse">—</strong></div>
            <div class="tsm-row"><span>Date</span><strong id="tsmDate">—</strong></div>
            <div class="tsm-row"><span>Horaire</span><strong id="tsmHoraire">—</strong></div>
            <div class="tsm-row"><span>Durée précise</span><strong id="tsmDuree">—</strong></div>
            <div class="tsm-row" id="tsmSalleRow"><span>Salle</span><strong id="tsmSalle">—</strong></div>
        </div>
        <div class="tsm-foot">
            <button type="button" class="tsm-btn tsm-btn--ghost" onclick="closeSeanceModal()">Fermer</button>
            <a id="tsmShowLink" class="tsm-btn tsm-btn--primary" href="#"><i class="fas fa-up-right-from-square"></i> Ouvrir la séance</a>
        </div>
    </div>
</div>
