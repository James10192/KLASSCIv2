@extends('layouts.app')

@section('title', 'Pieces a fournir - KLASSCI')

@push('styles')
<style>
/* Namespace dr-* : catalogue des pieces a fournir a l'inscription. */

.dr-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.5rem;
    color: #fff;
    margin-bottom: 1.25rem;
    box-shadow: 0 8px 30px rgba(4,83,203,.18);
}
.dr-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
.dr-hero-left { display: flex; align-items: center; gap: 1rem; }
.dr-hero-icon {
    width: 52px; height: 52px; border-radius: 14px;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0; color: #fff;
}
.dr-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
.dr-hero p { color: rgba(255,255,255,.7); font-size: .88rem; margin: 0; }
.dr-hero-actions { display: flex; gap: .6rem; flex-wrap: wrap; }

.dr-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; }
.dr-kpi {
    flex: 1; min-width: 150px;
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 12px; padding: .9rem 1rem;
}
.dr-kpi-value { font-size: 1.35rem; font-weight: 700; color: #fff; }
.dr-kpi-label { font-size: .72rem; color: rgba(255,255,255,.65); margin-top: .15rem; }

.dr-btn {
    display: inline-flex; align-items: center; gap: .45rem;
    border-radius: 10px; padding: .5rem 1rem;
    font-size: .82rem; font-weight: 600; cursor: pointer;
    border: 1px solid transparent; transition: all .2s ease;
}
.dr-btn--glass { background: rgba(255,255,255,.15); color: #fff; border-color: rgba(255,255,255,.2); }
.dr-btn--glass:hover { background: rgba(255,255,255,.24); color: #fff; }
.dr-btn--white { background: #fff; color: #0453cb; }
.dr-btn--white:hover { background: #eef4ff; color: #033a8e; }
.dr-btn--primary { background: #0453cb; color: #fff; }
.dr-btn--primary:hover { background: #033a8e; }
.dr-btn--ghost { background: #fff; color: #475569; border-color: #e2e8f0; }
.dr-btn--ghost:hover { background: #f1f5f9; color: #0453cb; }
.dr-btn:disabled { opacity: .6; cursor: wait; }

.dr-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    padding: 1.25rem 1.5rem;
}

.dr-empty { text-align: center; padding: 3rem 1.5rem; }
.dr-empty-icon {
    width: 64px; height: 64px; margin: 0 auto 1rem; border-radius: 18px;
    background: rgba(4,83,203,.08); color: #0453cb;
    display: flex; align-items: center; justify-content: center; font-size: 1.6rem;
}
.dr-empty h3 { font-size: 1.05rem; font-weight: 700; color: #1e293b; margin-bottom: .4rem; }
.dr-empty p { font-size: .86rem; color: #64748b; max-width: 620px; margin: 0 auto 1.25rem; line-height: 1.6; }
.dr-empty-actions { display: flex; gap: .6rem; justify-content: center; flex-wrap: wrap; }

.dr-row {
    display: flex; align-items: flex-start; gap: .9rem;
    padding: .95rem 1rem; border: 1px solid #e2e8f0; border-radius: 12px;
    background: #fff; transition: border-color .2s ease, box-shadow .2s ease;
}
.dr-row + .dr-row { margin-top: .6rem; }
.dr-row:hover { border-color: #c7d4e5; box-shadow: 0 4px 16px rgba(4,83,203,.06); }
.dr-row--inactive { opacity: .62; background: #f8fafc; }

.dr-rank { display: flex; flex-direction: column; gap: .2rem; padding-top: .1rem; }
.dr-rank button {
    width: 22px; height: 20px; border: 1px solid #e2e8f0; border-radius: 5px;
    background: #fff; color: #94a3b8; font-size: .6rem; cursor: pointer; line-height: 1;
}
.dr-rank button:hover:not(:disabled) { color: #0453cb; border-color: #c7d4e5; }
.dr-rank button:disabled { opacity: .35; cursor: default; }

.dr-body { flex: 1; min-width: 0; }
.dr-title { font-size: .95rem; font-weight: 700; color: #1e293b; }
.dr-desc { font-size: .8rem; color: #64748b; margin-top: .2rem; line-height: 1.5; }
.dr-scope { font-size: .74rem; color: #475569; margin-top: .4rem; }
.dr-scope i { color: #94a3b8; margin-right: .3rem; }

.dr-badges { display: flex; gap: .35rem; flex-wrap: wrap; margin-top: .5rem; }
.dr-badge {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .2rem .5rem; border-radius: 6px;
    font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .3px;
}
.dr-badge--primary { background: rgba(4,83,203,.10); color: #0453cb; border: 1px solid rgba(4,83,203,.22); }
.dr-badge--accent { background: rgba(59,125,219,.10); color: #3b7ddb; border: 1px solid rgba(59,125,219,.22); }
.dr-badge--muted { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }
.dr-badge--req { background: rgba(4,83,203,.12); color: #033a8e; border: 1px solid rgba(4,83,203,.28); }

.dr-actions { display: flex; gap: .3rem; align-items: center; }
.dr-icon-btn {
    width: 32px; height: 32px; border-radius: 8px;
    border: 1px solid #e2e8f0; background: #fff; color: #64748b;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: .8rem; cursor: pointer; transition: all .2s ease;
}
.dr-icon-btn:hover { color: #0453cb; border-color: #c7d4e5; background: #f8fafc; }
.dr-icon-btn--danger:hover { color: #dc2626; border-color: rgba(220,38,38,.3); background: rgba(220,38,38,.05); }

/* Modal */
.dr-modal {
    position: fixed; inset: 0; z-index: 1080;
    background: rgba(15,23,42,.5);
    display: flex; align-items: flex-start; justify-content: center;
    padding: 3rem 1rem; overflow-y: auto;
}
.dr-modal-box {
    background: #fff; border-radius: 16px; width: 100%; max-width: 660px;
    box-shadow: 0 20px 60px rgba(15,23,42,.25);
}
.dr-modal-head {
    padding: 1.1rem 1.5rem; border-bottom: 1px solid #e2e8f0;
    display: flex; align-items: center; justify-content: space-between;
}
.dr-modal-head h2 { font-size: 1.02rem; font-weight: 700; color: #1e293b; margin: 0; }
.dr-modal-body { padding: 1.25rem 1.5rem; display: flex; flex-direction: column; gap: 1rem; }
.dr-modal-foot {
    padding: 1rem 1.5rem; border-top: 1px solid #e2e8f0;
    display: flex; justify-content: flex-end; gap: .6rem;
}

.dr-field { display: flex; flex-direction: column; gap: .35rem; }
.dr-label { font-size: .74rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: .4px; }
.dr-hint { font-size: .72rem; color: #94a3b8; line-height: 1.5; }
.dr-input, .dr-textarea {
    width: 100%; border: 1px solid #e2e8f0; border-radius: 10px;
    padding: .55rem .75rem; font-size: .88rem; color: #1e293b; background: #fff;
}
.dr-input:focus, .dr-textarea:focus { outline: none; border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.1); }
.dr-textarea { min-height: 66px; resize: vertical; }
.dr-grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

/* Le composant premium est en inline-flex : sans ca il se retracte a son contenu. */
.dr-au-full { display: flex !important; width: 100%; }
.dr-au-full .au-select-trigger { width: 100%; }

.dr-chips { display: flex; flex-wrap: wrap; gap: .35rem; }
.dr-chip {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .3rem .6rem; border-radius: 8px; cursor: pointer;
    border: 1px solid #e2e8f0; background: #fff;
    font-size: .76rem; color: #475569; transition: all .15s ease;
}
.dr-chip:hover { border-color: #c7d4e5; }
.dr-chip--on { background: rgba(4,83,203,.10); border-color: rgba(4,83,203,.35); color: #0453cb; font-weight: 600; }

.dr-switch { display: inline-flex; align-items: center; gap: .5rem; cursor: pointer; font-size: .84rem; color: #1e293b; }
.dr-switch input { width: 16px; height: 16px; accent-color: #0453cb; cursor: pointer; }

.dr-toasts { position: fixed; bottom: 1.25rem; right: 1.25rem; z-index: 1090; display: flex; flex-direction: column; gap: .5rem; }
.dr-toast {
    padding: .7rem 1rem; border-radius: 10px; font-size: .84rem; font-weight: 600;
    box-shadow: 0 8px 24px rgba(15,23,42,.18); background: #fff; border-left: 4px solid #0453cb; color: #1e293b;
    max-width: 380px;
}
.dr-toast--success { border-left-color: #10b981; }
.dr-toast--error { border-left-color: #dc2626; }

[x-cloak] { display: none !important; }

@@media (max-width: 768px) {
    .dr-hero { padding: 1.4rem 1.25rem 1.1rem; }
    .dr-grid2 { grid-template-columns: 1fr; }
    .dr-row { flex-wrap: wrap; }
}
</style>
@endpush

@section('content')
@php
    $_drData = [
        'pieces'    => $pieces,
        'filieres'  => $filieres,
        'niveaux'   => $niveaux,
        'formes'    => $formes,
        'echeances' => $echeances,
        'peutConfigurer' => (bool) $peutConfigurer,
        'nbProposees'    => count($jeuParDefaut),
    ];
@endphp

<div class="main-content" x-data="catalogueDocumentsRequis()" x-init="init()">

    <div class="dr-hero">
        <div class="dr-hero-top">
            <div class="dr-hero-left">
                <div class="dr-hero-icon"><i class="fas fa-list-check"></i></div>
                <div>
                    <h1>Pieces a fournir</h1>
                    <p>Ce que l'ecole reclame a chaque inscription, et a qui</p>
                </div>
            </div>
            @can('documents_requis.configure')
            <div class="dr-hero-actions">
                <button type="button" class="dr-btn dr-btn--glass" @click="ouvrirAide()">
                    <i class="fas fa-question-circle"></i>Aide
                </button>
                <button type="button" class="dr-btn dr-btn--white" @click="ouvrirCreation()">
                    <i class="fas fa-plus"></i>Ajouter une piece
                </button>
            </div>
            @endcan
        </div>

        <div class="dr-kpis">
            <div class="dr-kpi">
                <div class="dr-kpi-value" x-text="pieces.filter(p => p.is_active).length">0</div>
                <div class="dr-kpi-label">Pieces actives</div>
            </div>
            <div class="dr-kpi">
                <div class="dr-kpi-value" x-text="pieces.filter(p => p.is_active &amp;&amp; p.is_obligatoire).length">0</div>
                <div class="dr-kpi-label">Dont obligatoires</div>
            </div>
            <div class="dr-kpi">
                <div class="dr-kpi-value" x-text="pieces.filter(p => p.is_active &amp;&amp; (p.filiere_ids.length || p.niveau_ids.length)).length">0</div>
                <div class="dr-kpi-label">A portee restreinte</div>
            </div>
            <div class="dr-kpi">
                <div class="dr-kpi-value" x-text="pieces.filter(p => p.is_active &amp;&amp; p.echeance === 'avant_fin_annee').length">0</div>
                <div class="dr-kpi-label">Attendues en cours d'annee</div>
            </div>
        </div>
    </div>

    <div class="dr-card">
        {{-- Catalogue vide : tant que l'ecole n'a rien configure, aucun ecran
             existant ne change. On l'ecrit noir sur blanc pour que personne ne
             croie que la feature est cassee. --}}
        <template x-if="pieces.length === 0">
            <div class="dr-empty">
                <div class="dr-empty-icon"><i class="fas fa-folder-open"></i></div>
                <h3>Aucune piece configuree</h3>
                <p>
                    Tant que ce catalogue est vide, rien ne change dans les inscriptions :
                    aucun controle de dossier n'est affiche. Partez du jeu propose, puis
                    retirez ou renommez librement — c'est votre liste, pas la notre.
                </p>
                @can('documents_requis.configure')
                <div class="dr-empty-actions">
                    <button type="button" class="dr-btn dr-btn--primary" :disabled="enCours" @click="installerJeuParDefaut()">
                        <i class="fas fa-wand-magic-sparkles"></i>
                        <span x-text="'Partir des ' + nbProposees + ' pieces proposees'"></span>
                    </button>
                    <button type="button" class="dr-btn dr-btn--ghost" @click="ouvrirCreation()">
                        <i class="fas fa-plus"></i>Creer ma premiere piece
                    </button>
                </div>
                @endcan
            </div>
        </template>

        <template x-if="pieces.length > 0">
            <div>
                <template x-for="(piece, index) in pieces" :key="piece.id">
                    <div class="dr-row" :class="piece.is_active ? '' : 'dr-row--inactive'">
                        <div class="dr-rank">
                            <button type="button" title="Monter" :disabled="index === 0 || !peutConfigurer" @click="deplacer(index, -1)">
                                <i class="fas fa-chevron-up"></i>
                            </button>
                            <button type="button" title="Descendre" :disabled="index === pieces.length - 1 || !peutConfigurer" @click="deplacer(index, 1)">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>

                        <div class="dr-body">
                            <div class="dr-title" x-text="piece.libelle"></div>
                            <div class="dr-desc" x-show="piece.description" x-text="piece.description"></div>

                            <div class="dr-badges">
                                <span class="dr-badge" :class="piece.is_obligatoire ? 'dr-badge--req' : 'dr-badge--muted'"
                                      x-text="piece.is_obligatoire ? 'Obligatoire' : 'Facultative'"></span>
                                <span class="dr-badge dr-badge--accent" x-text="piece.forme_label"></span>
                                <span class="dr-badge dr-badge--muted" x-show="piece.nombre_exemplaires > 1"
                                      x-text="piece.nombre_exemplaires + ' exemplaires'"></span>
                                <span class="dr-badge dr-badge--primary" x-text="piece.echeance_label"></span>
                                <span class="dr-badge dr-badge--muted" x-show="!piece.is_active">Desactivee</span>
                            </div>

                            <div class="dr-scope">
                                <i class="fas fa-crosshairs"></i><span x-text="libelleScope(piece)"></span>
                            </div>
                        </div>

                        @can('documents_requis.configure')
                        <div class="dr-actions">
                            <button type="button" class="dr-icon-btn" :title="piece.is_active ? 'Desactiver' : 'Reactiver'"
                                    @click="basculer(piece)">
                                <i class="fas" :class="piece.is_active ? 'fa-toggle-on' : 'fa-toggle-off'"></i>
                            </button>
                            <button type="button" class="dr-icon-btn" title="Modifier" @click="ouvrirEdition(piece)">
                                <i class="fas fa-pen"></i>
                            </button>
                            <button type="button" class="dr-icon-btn dr-icon-btn--danger" title="Retirer du catalogue"
                                    @click="retirer(piece)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        @endcan
                    </div>
                </template>
            </div>
        </template>
    </div>

    {{-- Formulaire de piece --}}
    <div class="dr-modal" x-show="modalOuvert" x-cloak @keydown.escape.window="fermerModal()">
        <div class="dr-modal-box" @click.outside="fermerModal()">
            <div class="dr-modal-head">
                <h2 x-text="form.id ? 'Modifier la piece' : 'Nouvelle piece'"></h2>
                <button type="button" class="dr-icon-btn" @click="fermerModal()"><i class="fas fa-times"></i></button>
            </div>

            <form @submit.prevent="enregistrer()">
                <div class="dr-modal-body">
                    <div class="dr-field">
                        <span class="dr-label">Libelle</span>
                        <input type="text" class="dr-input" x-model="form.libelle" required maxlength="255"
                               placeholder="Extrait de naissance">
                    </div>

                    <div class="dr-field">
                        <span class="dr-label">Consigne au guichet</span>
                        <textarea class="dr-textarea" x-model="form.description" maxlength="2000"
                                  placeholder="Copie legalisee de moins de trois mois."></textarea>
                        <span class="dr-hint">Ce que l'agent doit verifier avant d'accepter la piece.</span>
                    </div>

                    <div class="dr-grid2">
                        <div class="dr-field">
                            <span class="dr-label">Forme attendue</span>
                            <x-au-select class="dr-au-full" x-model="form.forme_attendue"
                                         :options="$formes" placeholder="Choisir" :placeholder-is-first-option="false" />
                        </div>
                        <div class="dr-field">
                            <span class="dr-label">Exemplaires</span>
                            <input type="number" class="dr-input" x-model.number="form.nombre_exemplaires" min="1" max="20" required>
                            <span class="dr-hint">Pour cette inscription. Un exemplaire est repris chaque annee.</span>
                        </div>
                    </div>

                    <div class="dr-field">
                        <span class="dr-label">Echeance</span>
                        <x-au-select class="dr-au-full" x-model="form.echeance"
                                     :options="$echeances" placeholder="Choisir" :placeholder-is-first-option="false" />
                        <span class="dr-hint">
                            Une piece attendue avant la fin de l'annee n'est pas comptee comme manquante
                            le jour de l'inscription. A ne pas confondre avec une reserve, qui vise un
                            document pas encore delivre.
                        </span>
                    </div>

                    <div class="dr-field">
                        <span class="dr-label">Filieres concernees</span>
                        <div class="dr-chips">
                            @foreach ($filieres as $filiere)
                            <label class="dr-chip" :class="form.filiere_ids.includes({{ $filiere->id }}) ? 'dr-chip--on' : ''">
                                <input type="checkbox" hidden value="{{ $filiere->id }}"
                                       :checked="form.filiere_ids.includes({{ $filiere->id }})"
                                       @change="basculerScope('filiere_ids', {{ $filiere->id }})">
                                {{ $filiere->name }}
                            </label>
                            @endforeach
                        </div>
                        <span class="dr-hint">Aucune selection = toutes les filieres, y compris celles creees plus tard.</span>
                    </div>

                    <div class="dr-field">
                        <span class="dr-label">Niveaux concernes</span>
                        <div class="dr-chips">
                            @foreach ($niveaux as $niveau)
                            <label class="dr-chip" :class="form.niveau_ids.includes({{ $niveau->id }}) ? 'dr-chip--on' : ''">
                                <input type="checkbox" hidden value="{{ $niveau->id }}"
                                       :checked="form.niveau_ids.includes({{ $niveau->id }})"
                                       @change="basculerScope('niveau_ids', {{ $niveau->id }})">
                                {{ $niveau->name }}
                            </label>
                            @endforeach
                        </div>
                        <span class="dr-hint">Aucune selection = tous les niveaux.</span>
                    </div>

                    <div class="dr-grid2">
                        <label class="dr-switch">
                            <input type="checkbox" x-model="form.is_obligatoire">
                            Piece obligatoire
                        </label>
                        <label class="dr-switch">
                            <input type="checkbox" x-model="form.is_active">
                            Piece active
                        </label>
                    </div>

                    <div x-show="erreurs.length" x-cloak class="dr-hint" style="color:#dc2626;">
                        <template x-for="erreur in erreurs" :key="erreur"><div x-text="erreur"></div></template>
                    </div>
                </div>

                <div class="dr-modal-foot">
                    <button type="button" class="dr-btn dr-btn--ghost" @click="fermerModal()">Annuler</button>
                    <button type="submit" class="dr-btn dr-btn--primary" :disabled="enCours">
                        <i class="fas fa-check"></i>
                        <span x-show="!enCours">Enregistrer</span>
                        <span x-show="enCours" x-cloak>Enregistrement...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Aide --}}
    <div class="dr-modal" x-show="aideOuverte" x-cloak @keydown.escape.window="aideOuverte = false">
        <div class="dr-modal-box" @click.outside="aideOuverte = false">
            <div class="dr-modal-head">
                <h2>A quoi sert ce catalogue</h2>
                <button type="button" class="dr-icon-btn" @click="aideOuverte = false"><i class="fas fa-times"></i></button>
            </div>
            <div class="dr-modal-body" style="font-size:.86rem;color:#475569;line-height:1.65;">
                <p style="margin:0;">
                    Ce catalogue decrit <strong>ce que l'ecole reclame</strong> a chaque inscription. Il ne dit
                    pas ou en est un etudiant : l'etat « fourni / manquant » se pose sur l'inscription,
                    annee par annee. Un etudiant de troisieme annee a donc trois lignes « extrait de
                    naissance », une par annee — c'est voulu, puisqu'un exemplaire part chaque annee
                    au ministere.
                </p>
                <p style="margin:0;">
                    <strong>Piece manquante</strong> et <strong>reserve</strong> ne sont pas la meme chose. Une piece
                    manquante porte sur un document qui existe et que l'etudiant n'a pas apporte. Une
                    reserve porte sur un document qui n'existe pas encore et arrivera plus tard — un
                    releve de baccalaureat non delivre, par exemple. La reserve se pose sur l'inscription
                    elle-meme, pas ici.
                </p>
                <p style="margin:0;">
                    <strong>Portee</strong> : laisser filieres et niveaux vides signifie « tout le monde ».
                    Une filiere creee demain heritera automatiquement de ces pieces.
                </p>
                <p style="margin:0;">
                    <strong>Retirer une piece</strong> l'archive : les dossiers deja constitues gardent leur
                    historique. Pour la suspendre temporairement, preferez la desactiver.
                </p>
            </div>
        </div>
    </div>

    <div class="dr-toasts">
        <template x-for="toast in toasts" :key="toast.id">
            <div class="dr-toast" :class="'dr-toast--' + toast.type" x-text="toast.message"></div>
        </template>
    </div>
</div>

<script type="application/json" id="dr-payload">@json($_drData)</script>
@endsection

@push('scripts')
<script>
function catalogueDocumentsRequis() {
    const payload = JSON.parse(document.getElementById('dr-payload').textContent);

    return {
        pieces: payload.pieces,
        filieres: payload.filieres,
        niveaux: payload.niveaux,
        peutConfigurer: payload.peutConfigurer,
        nbProposees: payload.nbProposees,

        modalOuvert: false,
        aideOuverte: false,
        enCours: false,
        erreurs: [],
        toasts: [],
        _prochainToast: 1,

        form: formVideDocumentRequis(),

        init() {
            this.form = formVideDocumentRequis();
        },

        libelleScope(piece) {
            const noms = (ids, source) => ids
                .map(id => (source.find(e => e.id === id) || {}).name)
                .filter(Boolean)
                .join(', ');

            const f = piece.filiere_ids.length ? noms(piece.filiere_ids, this.filieres) : 'Toutes filieres';
            const n = piece.niveau_ids.length ? noms(piece.niveau_ids, this.niveaux) : 'Tous niveaux';

            return f + '  /  ' + n;
        },

        ouvrirAide() { this.aideOuverte = true; },

        ouvrirCreation() {
            this.erreurs = [];
            this.form = formVideDocumentRequis();
            this.modalOuvert = true;
            this.rafraichirSelectsPremium();
        },

        ouvrirEdition(piece) {
            this.erreurs = [];
            // Copie profonde des tableaux de portee : sans ca, cocher une filiere
            // dans le formulaire modifierait la ligne affichee avant enregistrement.
            this.form = Object.assign({}, piece, {
                filiere_ids: piece.filiere_ids.slice(),
                niveau_ids: piece.niveau_ids.slice(),
            });
            this.modalOuvert = true;
            this.rafraichirSelectsPremium();
        },

        /**
         * x-model ecrit directement dans le <select> cache du composant premium,
         * ce qui n'emet aucun evenement : le libelle du bouton resterait sur la
         * valeur precedente. On declenche donc le 'change' que le composant ecoute.
         */
        rafraichirSelectsPremium() {
            this.$nextTick(() => {
                this.$el.querySelectorAll('.dr-au-full .au-select-native').forEach((select) => {
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });
        },

        fermerModal() { this.modalOuvert = false; },

        basculerScope(champ, id) {
            const liste = this.form[champ];
            const pos = liste.indexOf(id);
            if (pos === -1) { liste.push(id); } else { liste.splice(pos, 1); }
        },

        async enregistrer() {
            this.enCours = true;
            this.erreurs = [];
            const creation = !this.form.id;
            const url = creation
                ? '{{ route('esbtp.documents-requis.store') }}'
                : '{{ url('esbtp/documents-requis') }}/' + this.form.id;

            try {
                const data = await this.appel(url, creation ? 'POST' : 'PUT', {
                    libelle: this.form.libelle,
                    description: this.form.description,
                    is_obligatoire: this.form.is_obligatoire,
                    forme_attendue: this.form.forme_attendue,
                    nombre_exemplaires: this.form.nombre_exemplaires,
                    echeance: this.form.echeance,
                    filiere_ids: this.form.filiere_ids,
                    niveau_ids: this.form.niveau_ids,
                    is_active: this.form.is_active,
                });

                if (creation) {
                    this.pieces.push(data.piece);
                } else {
                    const i = this.pieces.findIndex(p => p.id === data.piece.id);
                    if (i !== -1) { this.pieces[i] = data.piece; }
                }
                this.trier();
                this.modalOuvert = false;
                this.notifier('success', data.message);
            } catch (err) {
                this.erreurs = err.details || [err.message];
                this.notifier('error', err.message);
            } finally {
                this.enCours = false;
            }
        },

        async basculer(piece) {
            try {
                const data = await this.appel('{{ url('esbtp/documents-requis') }}/' + piece.id + '/toggle', 'POST', {});
                const i = this.pieces.findIndex(p => p.id === data.piece.id);
                if (i !== -1) { this.pieces[i] = data.piece; }
                this.notifier('success', data.message);
            } catch (err) {
                this.notifier('error', err.message);
            }
        },

        async retirer(piece) {
            if (!window.confirm('Retirer « ' + piece.libelle + ' » du catalogue ? Les dossiers deja constitues gardent leur historique.')) {
                return;
            }
            try {
                const data = await this.appel('{{ url('esbtp/documents-requis') }}/' + piece.id, 'DELETE', {});
                this.pieces = this.pieces.filter(p => p.id !== data.id);
                this.notifier('success', data.message);
            } catch (err) {
                this.notifier('error', err.message);
            }
        },

        async deplacer(index, sens) {
            const cible = index + sens;
            if (cible < 0 || cible >= this.pieces.length) { return; }

            const copie = this.pieces.slice();
            const [deplacee] = copie.splice(index, 1);
            copie.splice(cible, 0, deplacee);
            // On applique tout de suite : l'ordre est une preference d'affichage,
            // un aller-retour serveur avant de bouger rendrait la liste poussive.
            this.pieces = copie;

            try {
                await this.appel('{{ route('esbtp.documents-requis.reorder') }}', 'POST', {
                    ids: this.pieces.map(p => p.id),
                });
            } catch (err) {
                this.notifier('error', err.message);
            }
        },

        async installerJeuParDefaut() {
            this.enCours = true;
            try {
                const data = await this.appel('{{ route('esbtp.documents-requis.jeu-par-defaut') }}', 'POST', {});
                this.pieces = data.pieces;
                this.notifier('success', data.message);
            } catch (err) {
                this.notifier('error', err.message);
            } finally {
                this.enCours = false;
            }
        },

        trier() {
            this.pieces.sort((a, b) => (a.ordre - b.ordre) || a.libelle.localeCompare(b.libelle));
        },

        async appel(url, methode, corps) {
            const reponse = await fetch(url, {
                method: methode,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(corps),
            });

            const data = await reponse.json().catch(() => ({}));

            if (!reponse.ok) {
                const erreur = new Error(data.message || ('Erreur HTTP ' + reponse.status));
                // 422 renvoie le detail champ par champ : le montrer evite a
                // l'utilisateur de deviner ce qui bloque.
                if (data.errors) { erreur.details = Object.values(data.errors).flat(); }
                throw erreur;
            }

            return data;
        },

        notifier(type, message) {
            const id = this._prochainToast++;
            this.toasts.push({ id, type, message });
            setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 4000);
        },
    };
}

function formVideDocumentRequis() {
    return {
        id: null,
        libelle: '',
        description: '',
        is_obligatoire: true,
        forme_attendue: 'copie',
        nombre_exemplaires: 1,
        echeance: 'inscription',
        filiere_ids: [],
        niveau_ids: [],
        is_active: true,
    };
}
</script>
@endpush
