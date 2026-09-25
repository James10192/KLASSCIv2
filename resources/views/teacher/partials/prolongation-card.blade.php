{{-- Demande de prolongation du cours du jour. Décision : coordination, avec contrôle des conflits. --}}
@php
    $plgEnAttente = $prolongations->firstWhere('statut', \App\Models\ESBTPProlongationSeance::EN_ATTENTE);
    $plgDerniere = $prolongations->first();
    $plgUrl = route('teacher.prolongation.demander', $seance->id);
@endphp
<style>
    .plg-card { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:1.25rem 1.5rem; margin-bottom:1.5rem; box-shadow:0 1px 3px rgba(15,23,42,.04); }
    .plg-head { display:flex; align-items:center; gap:.75rem; margin-bottom:.75rem; }
    .plg-icon { width:40px; height:40px; border-radius:10px; background:linear-gradient(135deg,#0453cb,#3b7ddb); color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .plg-head h3 { font-size:1rem; font-weight:700; color:#1e293b; margin:0; }
    .plg-head p { font-size:.8rem; color:#64748b; margin:0; }
    .plg-form { display:grid; grid-template-columns:140px 1fr auto; gap:.75rem; align-items:end; }
    .plg-form label { font-size:.72rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.4px; display:block; margin-bottom:.3rem; }
    .plg-form input { width:100%; border:1px solid #cbd5e1; border-radius:10px; padding:.55rem .75rem; font-size:.9rem; }
    .plg-btn { background:#0453cb; color:#fff; border:none; border-radius:10px; padding:.6rem 1.1rem; font-weight:600; font-size:.85rem; white-space:nowrap; }
    .plg-btn:disabled { opacity:.6; }
    .plg-etat { display:flex; gap:.6rem; align-items:flex-start; background:rgba(4,83,203,.05); border:1px solid rgba(4,83,203,.18); border-radius:10px; padding:.75rem 1rem; font-size:.85rem; color:#1e293b; }
    .plg-etat--ok { background:rgba(16,185,129,.07); border-color:rgba(16,185,129,.3); }
    .plg-etat--ko { background:rgba(220,38,38,.05); border-color:rgba(220,38,38,.25); }
    .plg-msg { margin-top:.6rem; font-size:.82rem; }
    @@media (max-width: 576px) { .plg-form { grid-template-columns:1fr; } }
</style>
<div class="plg-card" x-data="{
        minutes: 15, motif: '', envoi: false, message: '', ok: null,
        etat: @js($plgDerniere ? ['statut' => $plgDerniere->statut, 'libelle' => $plgDerniere->libelleStatut(), 'fin' => substr((string) $plgDerniere->heure_fin_demandee, 0, 5), 'motif_decision' => $plgDerniere->motif_decision] : null),
        async envoyer() {
            this.envoi = true; this.message = '';
            try {
                const r = await fetch(@js($plgUrl), { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify({ minutes: this.minutes, motif: this.motif }) });
                const d = await r.json().catch(() => ({}));
                this.ok = r.ok && d.success; this.message = d.message || (d.errors ? Object.values(d.errors).flat()[0] : 'Erreur ' + r.status);
                if (this.ok && d.prolongation) { this.etat = { statut: d.prolongation.statut, libelle: d.prolongation.statut_libelle, fin: d.prolongation.heure_fin, motif_decision: null }; this.motif = ''; }
            } catch (e) { this.ok = false; this.message = 'Connexion impossible. Réessayez.'; }
            finally { this.envoi = false; }
        }
    }">
    <div class="plg-head">
        <div class="plg-icon"><i class="fas fa-clock-rotate-left"></i></div>
        <div>
            <h3>Besoin de plus de temps ?</h3>
            <p>Fin prévue aujourd’hui : <strong>{{ $heureFin->format('H:i') }}</strong>. La coordination vérifie que la salle, la classe et vos autres cours sont libres avant d’accorder.</p>
        </div>
    </div>

    <template x-if="etat && etat.statut === 'en_attente'">
        <div class="plg-etat"><i class="fas fa-hourglass-half"></i><div>Demande en attente : prolongation jusqu’à <strong x-text="etat.fin"></strong>. Vous serez prévenu de la décision.</div></div>
    </template>
    <template x-if="etat && etat.statut === 'accordee'">
        <div class="plg-etat plg-etat--ok"><i class="fas fa-check"></i><div>Prolongation accordée : le cours se termine à <strong x-text="etat.fin"></strong>. L’émargement de fin suit cette heure.</div></div>
    </template>
    <template x-if="etat && etat.statut === 'refusee'">
        <div class="plg-etat plg-etat--ko"><i class="fas fa-xmark"></i><div>Dernière demande refusée<span x-show="etat.motif_decision"> : <span x-text="etat.motif_decision"></span></span>.</div></div>
    </template>

    <form class="plg-form" x-show="!etat || etat.statut !== 'en_attente'" x-on:submit.prevent="envoyer()" style="margin-top:.75rem">
        <div>
            <label for="plg-minutes">Minutes</label>
            <input id="plg-minutes" type="number" min="5" max="{{ \App\Domain\EmploiTemps\ProlongationDeSeance::MINUTES_MAX }}" step="5" x-model.number="minutes" required>
        </div>
        <div>
            <label for="plg-motif">Motif</label>
            <input id="plg-motif" type="text" maxlength="500" x-model="motif" placeholder="Ex. : fin du chapitre, arrivée tardive de la classe" required>
        </div>
        <button type="submit" class="plg-btn" :disabled="envoi">
            <span x-show="!envoi">Demander</span><span x-show="envoi" x-cloak>Envoi…</span>
        </button>
    </form>
    <div class="plg-msg" x-show="message" x-cloak :class="ok ? 'text-success' : 'text-danger'" x-text="message"></div>
</div>
