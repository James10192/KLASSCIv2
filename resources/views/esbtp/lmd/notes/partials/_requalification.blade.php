{{-- Bandeau « régularisations → examens » de la fenêtre d'une classe.
     Balisage, style et script ensemble ; la page n'est pas chargée en AJAX. --}}
<style>
    /* ── Requalification des régularisations en examen ── */
    .ln-requal {
        display: flex; align-items: flex-start; gap: .75rem; flex-wrap: wrap;
        margin: .85rem 1.5rem 0; padding: .85rem 1rem;
        background: #fff; border: 1px solid #c7d7f2; border-left: 4px solid #0453cb; border-radius: 12px;
    }
    .ln-requal-icon {
        width: 32px; height: 32px; border-radius: 9px; background: rgba(4,83,203,.1); color: #0453cb;
        display: flex; align-items: center; justify-content: center; font-size: .8rem; flex-shrink: 0;
    }
    .ln-requal-corps { flex: 1; min-width: 220px; }
    .ln-requal-titre { font-size: .86rem; font-weight: 700; color: #1e293b; }
    .ln-requal-texte { font-size: .78rem; color: #64748b; margin-top: .15rem; }
    .ln-requal-texte--erreur { color: #dc2626; }
    .ln-requal-liste { margin: .5rem 0 0; padding-left: 1.1rem; font-size: .76rem; color: #334155; max-height: 160px; overflow-y: auto; }
    .ln-requal-actions { display: flex; gap: .5rem; align-items: center; }
    .ln-requal-actions .ln-modal-add-btn { margin-left: 0; }
    .ln-requal-actions .ln-modal-add-btn:disabled { opacity: .6; cursor: wait; }
    .ln-requal-semestres { display: flex; gap: .35rem; flex-wrap: wrap; margin-top: .5rem; }
    .ln-requal-chip {
        padding: .25rem .65rem; border-radius: 999px; font-size: .74rem; font-weight: 600; cursor: pointer;
        border: 1px solid #c7d7f2; background: #fff; color: #0453cb; transition: all .15s ease;
    }
    .ln-requal-chip:hover { background: rgba(4,83,203,.06); }
    .ln-requal-chip--actif { background: #0453cb; border-color: #0453cb; color: #fff; }
</style>

{{-- Régularisations d'un relevé qui étaient des notes d'examen --}}
<div class="ln-requal" id="requalBanner" style="display:none;">
    <div class="ln-requal-icon"><i class="fas fa-exchange-alt"></i></div>
    <div class="ln-requal-corps">
        <div class="ln-requal-titre" id="requalTitre"></div>
        <div class="ln-requal-texte" id="requalTexte"></div>
        {{-- Un semestre à la fois : un relevé d'examen ne vaut que pour son semestre. --}}
        <div class="ln-requal-semestres" id="requalSemestres"></div>
        <ul class="ln-requal-liste" id="requalListe" style="display:none;"></ul>
    </div>
    <div class="ln-requal-actions">
        <button type="button" class="ln-modal-add-btn" id="requalBtn" onclick="requalifierEnExamen(true)">
            <i class="fas fa-search"></i> Voir ce qui change
        </button>
        <button type="button" class="ln-modal-add-btn" id="requalConfirmBtn" style="display:none;" onclick="requalifierEnExamen(false)">
            <i class="fas fa-check"></i> Requalifier en examen
        </button>
    </div>
</div>

<script>
// ══ Régularisations → examens ══
// Un relevé saisi en « Régularisation » qui contenait en fait les notes
// d'examen : le titre et le type changent, aucune note ne bouge.
const peutRequalifier = @json($peutRequalifier ?? false);

let requalLignes = [];
let requalPeriode = null;

async function chargerRequalification(classeId) {
    const banner = document.getElementById('requalBanner');
    banner.style.display = 'none';
    if (!peutRequalifier) return;
    try {
        const resp = await fetch('/esbtp/lmd/notes/classe/' + classeId + '/requalification', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!resp.ok || classeId !== currentClasseId) return;
        const data = await resp.json();
        requalLignes = data.lignes || [];
        if (requalLignes.length === 0) return;
        const periodes = [...new Set(requalLignes.map(l => l.periode))].sort((a, b) => parseInt(a.replace(/\D/g, ''), 10) - parseInt(b.replace(/\D/g, ''), 10));
        document.getElementById('requalSemestres').innerHTML = periodes.map(p =>
            '<button type="button" class="ln-requal-chip" data-periode="' + escHtml(p) + '" onclick="choisirSemestreRequal(this.dataset.periode)">'
            + 'S' + escHtml(p.replace(/\D/g, '')) + ' · ' + requalLignes.filter(l => l.periode === p).length + '</button>'
        ).join('');
        banner.style.display = 'flex';
        if (periodes.length === 1) {
            choisirSemestreRequal(periodes[0]);
            return;
        }
        // Plusieurs semestres : rien n'est présélectionné, l'école choisit.
        requalPeriode = null;
        document.getElementById('requalTitre').textContent =
            requalLignes.length + ' évaluation(s) de régularisation sur ' + periodes.length + ' semestres';
        afficherRequalTexte('Choisissez le semestre dont les notes étaient celles de l’examen.', false);
        document.getElementById('requalListe').style.display = 'none';
        document.getElementById('requalBtn').style.display = 'none';
        document.getElementById('requalConfirmBtn').style.display = 'none';
    } catch (err) {
        console.error('Requalification indisponible', err);
    }
}

function choisirSemestreRequal(periode) {
    requalPeriode = periode;
    document.querySelectorAll('#requalSemestres .ln-requal-chip').forEach(c =>
        c.classList.toggle('ln-requal-chip--actif', c.dataset.periode === periode));
    const lignes = requalLignes.filter(l => l.periode === periode);
    const notes = lignes.reduce((t, l) => t + l.notes, 0);
    document.getElementById('requalTitre').textContent =
        lignes.length + ' évaluation(s) de régularisation au semestre ' + periode.replace(/\D/g, '') + ' (' + notes + ' note(s))';
    afficherRequalTexte('S’il s’agissait des notes d’examen de ce semestre, requalifiez-les : elles deviennent « Examen … », le contrôle continu restera distinct. Aucune note ne change.', false);
    document.getElementById('requalListe').style.display = 'none';
    document.getElementById('requalBtn').style.display = 'inline-flex';
    document.getElementById('requalConfirmBtn').style.display = 'none';
}

function afficherRequalTexte(texte, erreur) {
    const el = document.getElementById('requalTexte');
    el.textContent = texte;
    el.classList.toggle('ln-requal-texte--erreur', erreur);
}

async function requalifierEnExamen(simulation) {
    const btn = document.getElementById(simulation ? 'requalBtn' : 'requalConfirmBtn');
    btn.disabled = true;
    try {
        const resp = await fetch('/esbtp/lmd/notes/classe/' + currentClasseId + '/requalifier-examen', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json', 'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({ dry_run: simulation, periode: requalPeriode }),
        });
        const data = await resp.json().catch(() => ({}));
        if (!resp.ok || !data.success) {
            afficherRequalTexte(data.message || ('Erreur ' + resp.status), true);
            return;
        }
        const liste = document.getElementById('requalListe');
        liste.innerHTML = data.lignes.map(l =>
            '<li>' + escHtml(l.titre) + ' → <strong>' + escHtml(l.nouveau_titre) + '</strong> (' + l.notes + ' note(s))</li>'
        ).join('');
        liste.style.display = 'block';
        afficherRequalTexte(data.message, false);
        if (simulation) {
            document.getElementById('requalBtn').style.display = 'none';
            document.getElementById('requalConfirmBtn').style.display = 'inline-flex';
            return;
        }
        document.getElementById('requalConfirmBtn').style.display = 'none';
        document.getElementById('requalTitre').textContent = 'Requalification faite (semestre ' + requalPeriode.replace(/\D/g, '') + ')';
        requalLignes = requalLignes.filter(l => l.periode !== requalPeriode);
        document.querySelector('#requalSemestres .ln-requal-chip--actif')?.remove();
        window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: data.message } }));
        if (currentMatiereId) loadEvaluationsAndBuildGrid(currentClasseId, currentMatiereId);
    } catch (err) {
        afficherRequalTexte('Erreur réseau : ' + err.message, true);
    } finally {
        btn.disabled = false;
    }
}
</script>

@include('esbtp.lmd.partials.teacher-quick-dialog')
