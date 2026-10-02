@extends('layouts.app')

@section('title', 'Réclamations de notes')

@push('styles')
@include('esbtp.reclamations-notes.partials._styles')
@endpush

@section('content')
@php
    $donneesPage = [
        'reclamations' => $reclamations,
        'peutTrancher' => $peutTrancher,
        'moi' => $moi,
        'ouverte' => $ouverte,
        'urlAvis' => route('esbtp.reclamations-notes.avis', ['id' => '__ID__']),
        'urlDecision' => route('esbtp.reclamations-notes.decision', ['id' => '__ID__']),
    ];
@endphp
<div class="rcl" x-data="traitementReclamations()" data-page='@json($donneesPage)' @keydown.escape.window="fermer()">
    <section class="rcl-hero">
        <div class="rcl-hero-top">
            <div class="rcl-hero-left">
                <div class="rcl-hero-icon"><i class="fas fa-scale-balanced"></i></div>
                <div class="rcl-hero-titres">
                    <h1>Réclamations de notes</h1>
                    <p>{{ $peutTrancher ? 'L\'enseignant donne son avis, vous tranchez.' : 'Donnez votre avis sur les notes contestées.' }}</p>
                </div>
            </div>
        </div>
        <div class="rcl-kpis">
            <button type="button" class="rcl-kpi rcl-kpi--lien rcl-kpi--bouton" :class="{ 'rcl-kpi--actif': filtre === 'avis_donne' }" @click="filtre = 'avis_donne'">
                <span class="rcl-kpi-val" x-text="compte('avis_donne')">{{ $kpis['a_traiter'] }}</span><span class="rcl-kpi-lbl">Avis reçu, à trancher</span>
            </button>
            <button type="button" class="rcl-kpi rcl-kpi--lien rcl-kpi--bouton" :class="{ 'rcl-kpi--actif': filtre === 'soumise' }" @click="filtre = 'soumise'">
                <span class="rcl-kpi-val" x-text="compte('soumise')">{{ $kpis['sans_avis'] }}</span><span class="rcl-kpi-lbl">En attente de l'enseignant</span>
            </button>
            <div class="rcl-kpi"><span class="rcl-kpi-val">{{ $kpis['acceptees_30j'] }}</span><span class="rcl-kpi-lbl">Notes corrigées (30 j)</span></div>
            <div class="rcl-kpi"><span class="rcl-kpi-val">{{ $kpis['rejetees_30j'] }}</span><span class="rcl-kpi-lbl">Notes maintenues (30 j)</span></div>
        </div>
    </section>

    <section class="rcl-card">
        <div class="rcl-filtres">
            <button type="button" class="rcl-filtre" :class="{ 'rcl-filtre--actif': filtre === 'ouvertes' }" @click="filtre = 'ouvertes'">En cours</button>
            <button type="button" class="rcl-filtre" :class="{ 'rcl-filtre--actif': filtre === 'avis_donne' }" @click="filtre = 'avis_donne'">À trancher</button>
            <button type="button" class="rcl-filtre" :class="{ 'rcl-filtre--actif': filtre === 'soumise' }" @click="filtre = 'soumise'">Sans avis</button>
            <button type="button" class="rcl-filtre" :class="{ 'rcl-filtre--actif': filtre === 'tranchees' }" @click="filtre = 'tranchees'">Tranchées</button>
            <button type="button" class="rcl-filtre" :class="{ 'rcl-filtre--actif': filtre === 'toutes' }" @click="filtre = 'toutes'">Toutes</button>
            <input type="search" class="rcl-input rcl-recherche" placeholder="Élève, matricule, classe, matière…" x-model.debounce.200ms="recherche" aria-label="Rechercher">
        </div>

        <div class="rcl-vide" x-show="visibles().length === 0">
            <i class="fas fa-circle-check"></i>
            <p x-text="liste.length === 0 ? 'Aucune réclamation pour l\'instant.' : 'Rien dans ce filtre.'"></p>
        </div>

        <ul class="rcl-liste">
            <template x-for="r in visibles()" :key="r.id">
                <li class="rcl-item rcl-item--cliquable" tabindex="0" @click="ouvrir(r)" @keydown.enter="ouvrir(r)">
                    <div class="rcl-item-haut">
                        <div class="rcl-item-titres">
                            <strong x-text="r.etudiant"></strong>
                            <span x-text="[r.matricule, r.classe].filter(Boolean).join(' · ')"></span>
                        </div>
                        <span class="rcl-badge" :class="'rcl-badge--' + r.ton" x-text="r.statut_label"></span>
                    </div>
                    <div class="rcl-meta">
                        <span><i class="fas fa-book"></i> <span x-text="r.matiere + ' — ' + r.evaluation"></span></span>
                        <span><i class="fas fa-user-tie"></i> <span x-text="r.enseignant || 'Enseignant non identifié'"></span></span>
                        <span><i class="fas fa-clock"></i> <span x-text="r.depose_il_y_a"></span></span>
                    </div>
                    <div class="rcl-notes">
                        <span class="rcl-note"><span x-text="fmt(r.note_initiale)"></span><small x-text="'/' + fmt(r.bareme)"></small></span>
                        <template x-if="r.note_proposee !== null && r.ouverte">
                            <span class="rcl-note rcl-note--apres rcl-note--proposee"><i class="fas fa-arrow-right"></i><span x-text="fmt(r.note_proposee)"></span><small>proposée</small></span>
                        </template>
                        <template x-if="r.note_finale !== null && r.statut === 'acceptee'">
                            <span class="rcl-note rcl-note--apres"><i class="fas fa-arrow-right"></i><span x-text="fmt(r.note_finale)"></span><small x-text="'/' + fmt(r.bareme)"></small></span>
                        </template>
                    </div>
                </li>
            </template>
        </ul>
    </section>

    <template x-teleport="body">
        <div class="rcl-voile" x-show="courante" x-cloak @click.self="fermer()" x-transition.opacity>
            <div class="rcl-fenetre" role="dialog" aria-modal="true" aria-labelledby="rcl-titre" x-show="courante">
                <template x-if="courante">
                    <div class="rcl-pile">
                        <div class="rcl-fenetre-tete">
                            <div>
                                <h3 id="rcl-titre" x-text="courante.etudiant + ' — ' + courante.matiere"></h3>
                                <div class="rcl-meta rcl-meta--sous-titre">
                                    <span x-text="courante.evaluation"></span>
                                    <span x-text="courante.classe"></span>
                                    <span x-text="'Déposée le ' + courante.depose_le"></span>
                                </div>
                            </div>
                            <button type="button" class="rcl-fermer" @click="fermer()" aria-label="Fermer"><i class="fas fa-times"></i></button>
                        </div>

                        <div class="rcl-fenetre-corps">
                            <a class="rcl-photo" :href="courante.photo_url" target="_blank" rel="noopener" title="Ouvrir la copie en grand">
                                <template x-if="!courante.photo_pdf"><img :src="courante.photo_url" alt="Copie de l'élève"></template>
                                <template x-if="courante.photo_pdf"><span class="rcl-vide"><i class="fas fa-file-pdf"></i><span>Ouvrir la copie (PDF)</span></span></template>
                            </a>
                            <div class="rcl-pile rcl-pile--serree">
                                <div class="rcl-notes">
                                    <span class="rcl-note"><span x-text="fmt(courante.note_initiale)"></span><small x-text="'/' + fmt(courante.bareme)"></small></span>
                                    <span class="rcl-badge" :class="'rcl-badge--' + courante.ton" x-text="courante.statut_label"></span>
                                </div>
                                <div class="rcl-bloc">
                                    <h4><i class="fas fa-comment"></i> Motif de l'élève</h4>
                                    <p class="rcl-motif" x-text="courante.motif"></p>
                                </div>
                                <div class="rcl-bloc" x-show="courante.avis">
                                    <h4><i class="fas fa-user-tie"></i> Avis de l'enseignant</h4>
                                    <p class="rcl-motif">
                                        <strong x-text="courante.avis === 'corriger' ? 'Propose ' + fmt(courante.note_proposee) : 'Confirme la note'"></strong>
                                        <span x-text="'— ' + (courante.avis_par || '') + ', ' + (courante.avis_le || '')"></span>
                                    </p>
                                    <p class="rcl-decision" x-text="courante.commentaire_enseignant"></p>
                                </div>
                                <div class="rcl-bloc" x-show="!courante.ouverte">
                                    <h4><i class="fas fa-gavel"></i> Décision</h4>
                                    <p class="rcl-motif" x-text="(courante.statut === 'acceptee' ? 'Note corrigée à ' + fmt(courante.note_finale) : 'Note maintenue') + (courante.decide_par ? ' — ' + courante.decide_par : '') + (courante.decide_le ? ', ' + courante.decide_le : '')"></p>
                                    <p class="rcl-decision" x-show="courante.commentaire_decision" x-text="courante.commentaire_decision"></p>
                                </div>
                            </div>
                        </div>

                        {{-- Avis : l'enseignant de l'évaluation, tant que c'est ouvert. --}}
                        <form class="rcl-bloc" x-show="courante.ouverte && courante.enseignant_id === moi" @submit.prevent="donnerAvis()">
                            <h4><i class="fas fa-user-tie"></i> Votre avis d'enseignant</h4>
                            <div class="rcl-choix">
                                <label :class="{ 'rcl-choix--actif': avis.avis === 'confirmer' }"><input type="radio" value="confirmer" x-model="avis.avis"> La note est juste</label>
                                <label :class="{ 'rcl-choix--actif': avis.avis === 'corriger' }"><input type="radio" value="corriger" x-model="avis.avis"> Je propose une correction</label>
                            </div>
                            <label class="rcl-champ" x-show="avis.avis === 'corriger'">
                                <span class="rcl-label" x-text="'Note proposée (sur ' + fmt(courante.bareme) + ')'"></span>
                                <input type="number" class="rcl-input" step="0.25" min="0" :max="courante.bareme" x-model="avis.note_proposee">
                            </label>
                            <label class="rcl-champ">
                                <span class="rcl-label">Explication</span>
                                <textarea class="rcl-textarea" rows="3" x-model="avis.commentaire" placeholder="Ce que vous avez vérifié sur la copie."></textarea>
                            </label>
                            <small class="rcl-erreur" x-show="erreur" x-text="erreur"></small>
                            <div class="rcl-actions">
                                <button type="submit" class="rcl-btn rcl-btn--primaire" :disabled="envoi"><i class="fas fa-paper-plane"></i> Envoyer mon avis</button>
                            </div>
                        </form>

                        {{-- Décision : le porteur de la permission. --}}
                        <form class="rcl-bloc" x-show="courante.ouverte && peutTrancher" @submit.prevent>
                            <h4><i class="fas fa-gavel"></i> Trancher</h4>
                            <label class="rcl-champ">
                                <span class="rcl-label" x-text="'Note corrigée (sur ' + fmt(courante.bareme) + ') — si vous acceptez'"></span>
                                <input type="number" class="rcl-input" step="0.25" min="0" :max="courante.bareme" x-model="decision.note_finale">
                            </label>
                            <label class="rcl-champ">
                                <span class="rcl-label">Message à l'élève <em>(obligatoire si vous maintenez la note)</em></span>
                                <textarea class="rcl-textarea" rows="3" x-model="decision.commentaire"></textarea>
                            </label>
                            <small class="rcl-erreur" x-show="erreur" x-text="erreur"></small>
                            <div class="rcl-actions">
                                <button type="button" class="rcl-btn rcl-btn--primaire" :disabled="envoi" @click="trancher('accepter')"><i class="fas fa-check"></i> Corriger la note</button>
                                <button type="button" class="rcl-btn rcl-btn--secondaire" :disabled="envoi" @click="trancher('refuser')"><i class="fas fa-ban"></i> Maintenir la note</button>
                            </div>
                        </form>
                    </div>
                </template>
            </div>
        </div>
    </template>
</div>
@include('partials._klassci_toast')
@endsection

@push('scripts')
<script>
if (typeof window.traitementReclamations !== 'function') {
    window.traitementReclamations = function () {
        return {
            liste: [], peutTrancher: false, moi: 0, urlAvis: '', urlDecision: '',
            filtre: 'ouvertes', recherche: '', courante: null, envoi: false, erreur: '',
            avis: { avis: 'confirmer', note_proposee: '', commentaire: '' },
            decision: { note_finale: '', commentaire: '' },
            init() {
                const d = JSON.parse(this.$root.dataset.page);
                Object.assign(this, { liste: d.reclamations, peutTrancher: d.peutTrancher, moi: d.moi, urlAvis: d.urlAvis, urlDecision: d.urlDecision });
                if (d.ouverte) {
                    const r = this.liste.find(x => x.id === d.ouverte);
                    if (r) { this.filtre = r.ouverte ? 'ouvertes' : 'toutes'; this.ouvrir(r); }
                }
            },
            compte(statut) { return this.liste.filter(r => r.statut === statut).length; },
            visibles() {
                const q = this.recherche.trim().toLowerCase();
                return this.liste.filter(r => {
                    const okFiltre = this.filtre === 'toutes'
                        || (this.filtre === 'ouvertes' && r.ouverte)
                        || (this.filtre === 'tranchees' && !r.ouverte)
                        || r.statut === this.filtre;
                    const okTexte = !q || [r.etudiant, r.matricule, r.classe, r.matiere, r.evaluation, r.enseignant].join(' ').toLowerCase().includes(q);
                    return okFiltre && okTexte;
                });
            },
            fmt(v) { return v === null || v === undefined || v === '' ? 'Abs.' : Number(v).toLocaleString('fr-FR', { maximumFractionDigits: 2 }); },
            ouvrir(r) {
                this.courante = r; this.erreur = '';
                this.avis = { avis: 'confirmer', note_proposee: '', commentaire: '' };
                this.decision = { note_finale: r.note_proposee ?? '', commentaire: '' };
                const url = new URL(window.location); url.searchParams.set('reclamation', r.id); history.replaceState(null, '', url);
            },
            fermer() {
                this.courante = null;
                const url = new URL(window.location); url.searchParams.delete('reclamation'); history.replaceState(null, '', url);
            },
            async poster(url, corps) {
                this.envoi = true; this.erreur = '';
                try {
                    const res = await fetch(url.replace('__ID__', this.courante.id), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: JSON.stringify(corps),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        const premiere = data.errors ? Object.values(data.errors)[0][0] : null;
                        this.erreur = premiere || data.message || 'Action impossible, réessayez.';
                        return;
                    }
                    const i = this.liste.findIndex(x => x.id === data.reclamation.id);
                    if (i !== -1) this.liste.splice(i, 1, data.reclamation);
                    this.courante = data.reclamation;
                    window.klassciToast('success', data.message);
                } catch (e) {
                    this.erreur = 'Connexion perdue, réessayez.';
                } finally {
                    this.envoi = false;
                }
            },
            donnerAvis() {
                this.poster(this.urlAvis, {
                    avis: this.avis.avis,
                    note_proposee: this.avis.avis === 'corriger' ? this.avis.note_proposee : null,
                    commentaire: this.avis.commentaire,
                });
            },
            trancher(choix) {
                this.poster(this.urlDecision, {
                    decision: choix,
                    note_finale: choix === 'accepter' ? this.decision.note_finale : null,
                    commentaire: this.decision.commentaire || null,
                });
            },
        };
    };
}
</script>
@endpush
