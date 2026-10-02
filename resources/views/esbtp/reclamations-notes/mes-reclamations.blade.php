@extends('layouts.app')

@section('title', 'Mes réclamations')

@push('styles')
@include('esbtp.reclamations-notes.partials._styles')
@endpush

@section('content')
@php
    $nbOuvertes = collect($reclamations)->where('ouverte', true)->count();
    $nbCorrigees = collect($reclamations)->where('statut', 'acceptee')->count();
    $nbMaintenues = collect($reclamations)->where('statut', 'rejetee')->count();
    $optionsNotes = collect($notesContestables)->mapWithKeys(fn ($n) => [$n['id'] => $n['label'].' · '.$n['sous_titre']])->all();
    $donneesPage = [
        'reclamations' => $reclamations,
        'noteChoisie' => $noteChoisie && array_key_exists($noteChoisie, $optionsNotes) ? (string) $noteChoisie : '',
        'urlEnvoi' => route('esbtp.mes-reclamations.store'),
    ];
@endphp
<div class="rcl" x-data="mesReclamations()" data-page='@json($donneesPage)'>
    <section class="rcl-hero">
        <div class="rcl-hero-top">
            <div class="rcl-hero-left">
                <div class="rcl-hero-icon"><i class="fas fa-flag"></i></div>
                <div class="rcl-hero-titres">
                    <h1>Mes réclamations</h1>
                    <p>Contestez une note dans les {{ $delaiJours }} jours, photo de la copie à l'appui.</p>
                </div>
            </div>
            <a href="{{ route('esbtp.mes-notes.index') }}" class="rcl-btn rcl-btn--glass"><i class="fas fa-arrow-left"></i><span>Mes notes</span></a>
        </div>
        <div class="rcl-kpis">
            <div class="rcl-kpi"><span class="rcl-kpi-val" x-text="compte('ouvertes')">{{ $nbOuvertes }}</span><span class="rcl-kpi-lbl">En cours</span></div>
            <div class="rcl-kpi"><span class="rcl-kpi-val" x-text="compte('acceptee')">{{ $nbCorrigees }}</span><span class="rcl-kpi-lbl">Notes corrigées</span></div>
            <div class="rcl-kpi"><span class="rcl-kpi-val" x-text="compte('rejetee')">{{ $nbMaintenues }}</span><span class="rcl-kpi-lbl">Notes maintenues</span></div>
        </div>
    </section>

    <div class="rcl-grille">
        <section class="rcl-card">
            <header class="rcl-card-head">
                <div class="rcl-section-icon"><i class="fas fa-pen"></i></div>
                <div>
                    <h2>Contester une note</h2>
                    <p>L'enseignant donne son avis, puis le personnel habilité tranche. Une réclamation par note : soyez précis (quelle question, quel point oublié).</p>
                </div>
            </header>

            @if(! $actives)
                <div class="rcl-vide"><i class="fas fa-lock"></i><p>Les réclamations de notes ne sont pas ouvertes dans votre établissement.</p></div>
            @elseif(empty($optionsNotes))
                <div class="rcl-vide"><i class="fas fa-circle-check"></i><p>Aucune note contestable pour l'instant. Une note se conteste dans les {{ $delaiJours }} jours après sa saisie.</p></div>
            @else
                <form class="rcl-form" @submit.prevent="envoyer($event)" novalidate>
                    <div class="rcl-champ">
                        <span class="rcl-label">Note contestée</span>
                        <x-au-select class="rcl-au-full" name="note_id" :options="$optionsNotes" :value="$donneesPage['noteChoisie']"
                                     placeholder="Choisissez la note" icon="fa-clipboard-list" :searchable="count($optionsNotes) > 6" x-model="form.note_id" />
                        <small class="rcl-erreur" x-show="erreurs.note_id" x-text="erreurs.note_id" x-cloak></small>
                    </div>

                    <label class="rcl-champ">
                        <span class="rcl-label">Votre explication</span>
                        <textarea name="motif" rows="4" maxlength="2000" x-model="form.motif" class="rcl-textarea"
                                  placeholder="Ex. : à l'exercice 2, ma réponse est juste mais n'a pas été comptée."></textarea>
                        <small class="rcl-aide"><span x-text="form.motif.trim().length"></span> / 10 caractères minimum</small>
                        <small class="rcl-erreur" x-show="erreurs.motif" x-text="erreurs.motif" x-cloak></small>
                    </label>

                    <div class="rcl-champ">
                        <span class="rcl-label">Photo de votre copie corrigée <em>(obligatoire)</em></span>
                        <label class="rcl-depot" :class="{ 'rcl-depot--plein': apercu || nomFichier }">
                            <input type="file" accept="image/*,application/pdf" x-ref="photo" @change="choisirPhoto($event)">
                            <template x-if="apercu"><img :src="apercu" alt="Aperçu de la copie" class="rcl-depot-img"></template>
                            <span class="rcl-depot-texte" x-show="!apercu">
                                <i class="fas" :class="nomFichier ? 'fa-file-pdf' : 'fa-camera'"></i>
                                <span x-text="nomFichier || 'Prendre ou choisir une photo'"></span>
                            </span>
                        </label>
                        <small class="rcl-aide">JPG, PNG, WEBP, HEIC ou PDF, 8 Mo au plus.</small>
                        <small class="rcl-erreur" x-show="erreurs.photo" x-text="erreurs.photo" x-cloak></small>
                    </div>

                    <button type="submit" class="rcl-btn rcl-btn--primaire" :disabled="envoi">
                        <span x-show="!envoi"><i class="fas fa-paper-plane"></i> Envoyer la réclamation</span>
                        <span x-show="envoi" x-cloak><i class="fas fa-spinner fa-spin"></i> Envoi…</span>
                    </button>
                </form>
            @endif
        </section>

        <section class="rcl-card">
            <header class="rcl-card-head">
                <div class="rcl-section-icon"><i class="fas fa-list-check"></i></div>
                <div>
                    <h2>Suivi</h2>
                    <p>Vous êtes prévenu à chaque étape.</p>
                </div>
            </header>

            <div class="rcl-vide" x-show="liste.length === 0"><i class="fas fa-inbox"></i><p>Vous n'avez déposé aucune réclamation.</p></div>

            <ul class="rcl-liste" x-show="liste.length > 0">
                <template x-for="r in liste" :key="r.id">
                    <li class="rcl-item">
                        <div class="rcl-item-haut">
                            <div class="rcl-item-titres">
                                <strong x-text="r.matiere"></strong>
                                <span x-text="r.evaluation"></span>
                            </div>
                            <span class="rcl-badge" :class="'rcl-badge--' + r.ton" x-text="r.statut_label"></span>
                        </div>
                        <div class="rcl-notes">
                            <span class="rcl-note"><span x-text="fmt(r.note_initiale)"></span><small x-text="'/' + fmt(r.bareme)"></small></span>
                            <template x-if="r.note_finale !== null && r.statut === 'acceptee'">
                                <span class="rcl-note rcl-note--apres"><i class="fas fa-arrow-right"></i><span x-text="fmt(r.note_finale)"></span><small x-text="'/' + fmt(r.bareme)"></small></span>
                            </template>
                        </div>
                        <p class="rcl-motif" x-text="r.motif"></p>
                        <p class="rcl-decision" x-show="r.commentaire_decision" x-text="r.commentaire_decision" x-cloak></p>
                        <div class="rcl-item-bas">
                            <span><i class="fas fa-clock"></i> <span x-text="'Envoyée le ' + r.depose_le"></span></span>
                            <a :href="r.photo_url" target="_blank" rel="noopener"><i class="fas fa-image"></i> Ma copie</a>
                        </div>
                    </li>
                </template>
            </ul>
        </section>
    </div>
</div>
@include('partials._klassci_toast')
@endsection

@push('scripts')
<script>
if (typeof window.mesReclamations !== 'function') {
    window.mesReclamations = function () {
        return {
            liste: [],
            form: { note_id: '', motif: '' },
            fichier: null,
            apercu: null,
            nomFichier: '',
            erreurs: {},
            envoi: false,
            urlEnvoi: '',
            init() {
                const d = JSON.parse(this.$root.dataset.page);
                this.liste = d.reclamations;
                this.form.note_id = d.noteChoisie || '';
                this.urlEnvoi = d.urlEnvoi;
            },
            compte(quoi) {
                return quoi === 'ouvertes'
                    ? this.liste.filter(r => r.ouverte).length
                    : this.liste.filter(r => r.statut === quoi).length;
            },
            fmt(v) {
                if (v === null || v === undefined) return 'Abs.';
                return Number(v).toLocaleString('fr-FR', { maximumFractionDigits: 2 });
            },
            choisirPhoto(ev) {
                const f = ev.target.files[0] || null;
                this.fichier = f;
                this.apercu = null;
                this.nomFichier = '';
                if (!f) return;
                if (f.type && f.type.startsWith('image/') && !/hei[cf]/i.test(f.type)) {
                    this.apercu = URL.createObjectURL(f);
                } else {
                    this.nomFichier = f.name;
                }
            },
            async envoyer() {
                this.erreurs = {};
                if (!this.form.note_id) this.erreurs.note_id = 'Choisissez la note contestée.';
                if (this.form.motif.trim().length < 10) this.erreurs.motif = 'Expliquez votre réclamation en au moins 10 caractères.';
                if (!this.fichier) this.erreurs.photo = 'La photo de votre copie corrigée est obligatoire.';
                if (Object.keys(this.erreurs).length) return;

                const corps = new FormData();
                corps.append('note_id', this.form.note_id);
                corps.append('motif', this.form.motif);
                corps.append('photo', this.fichier);
                this.envoi = true;
                try {
                    const res = await fetch(this.urlEnvoi, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                        body: corps,
                    });
                    const data = await res.json().catch(() => ({}));
                    if (res.status === 422) {
                        for (const [cle, msgs] of Object.entries(data.errors || {})) this.erreurs[cle] = msgs[0];
                        return;
                    }
                    if (!res.ok) throw new Error(data.message || 'Envoi impossible, réessayez.');
                    this.liste.unshift(data.reclamation);
                    this.form = { note_id: '', motif: '' };
                    this.fichier = null; this.apercu = null; this.nomFichier = '';
                    this.$refs.photo.value = '';
                    window.klassciToast('success', data.message);
                } catch (e) {
                    window.klassciToast('error', e.message);
                } finally {
                    this.envoi = false;
                }
            },
        };
    };
}
</script>
@endpush
