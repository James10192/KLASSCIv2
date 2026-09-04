@extends('layouts.app')

@section('title', 'Pièces à fournir - KLASSCI')

@push('styles')
<style>
/* Namespace pce-* : catalogue des pièces à fournir à l'inscription. */

.pce-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.5rem;
    color: #fff;
    margin-bottom: 1.25rem;
    box-shadow: 0 8px 30px rgba(4,83,203,.18);
}
.pce-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
.pce-hero-left { display: flex; align-items: center; gap: 1rem; }
.pce-hero-icon {
    width: 52px; height: 52px; border-radius: 14px;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0; color: #fff;
}
.pce-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
.pce-hero p { color: rgba(255,255,255,.7); font-size: .88rem; margin: 0; }
.pce-hero-actions { display: flex; gap: .6rem; flex-wrap: wrap; }

.pce-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; }
.pce-kpi {
    flex: 1; min-width: 150px;
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 12px; padding: .9rem 1rem;
}
.pce-kpi-value { font-size: 1.35rem; font-weight: 700; color: #fff; }
.pce-kpi-label { font-size: .72rem; color: rgba(255,255,255,.65); margin-top: .15rem; }

.pce-btn {
    display: inline-flex; align-items: center; gap: .45rem;
    border-radius: 10px; padding: .5rem 1rem;
    font-size: .82rem; font-weight: 600; cursor: pointer;
    border: 1px solid transparent; transition: all .2s ease;
}
.pce-btn--glass { background: rgba(255,255,255,.15); color: #fff; border-color: rgba(255,255,255,.2); }
.pce-btn--glass:hover { background: rgba(255,255,255,.24); color: #fff; }
.pce-btn--white { background: #fff; color: #0453cb; }
.pce-btn--white:hover { background: #eef4ff; color: #033a8e; }
.pce-btn--primary { background: #0453cb; color: #fff; }
.pce-btn--primary:hover { background: #033a8e; }
.pce-btn--ghost { background: #fff; color: #475569; border-color: #e2e8f0; }
.pce-btn--ghost:hover { background: #f1f5f9; color: #0453cb; }
.pce-btn:disabled { opacity: .6; cursor: wait; }

.pce-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    padding: 1.25rem 1.5rem;
}

.pce-empty { text-align: center; padding: 3rem 1.5rem; }
.pce-empty-icon {
    width: 64px; height: 64px; margin: 0 auto 1rem; border-radius: 18px;
    background: rgba(4,83,203,.08); color: #0453cb;
    display: flex; align-items: center; justify-content: center; font-size: 1.6rem;
}
.pce-empty h3 { font-size: 1.05rem; font-weight: 700; color: #1e293b; margin-bottom: .4rem; }
.pce-empty p { font-size: .86rem; color: #64748b; max-width: 620px; margin: 0 auto 1.25rem; line-height: 1.6; }
.pce-empty-actions { display: flex; gap: .6rem; justify-content: center; flex-wrap: wrap; }

.pce-row {
    display: flex; align-items: flex-start; gap: .9rem;
    padding: .95rem 1rem; border: 1px solid #e2e8f0; border-radius: 12px;
    background: #fff; transition: border-color .2s ease, box-shadow .2s ease;
}
.pce-row + .pce-row { margin-top: .6rem; }
.pce-row:hover { border-color: #c7d4e5; box-shadow: 0 4px 16px rgba(4,83,203,.06); }
.pce-row--inactive { opacity: .62; background: #f8fafc; }

.pce-rank { display: flex; flex-direction: column; gap: .2rem; padding-top: .1rem; }
.pce-rank button {
    width: 22px; height: 20px; border: 1px solid #e2e8f0; border-radius: 5px;
    background: #fff; color: #94a3b8; font-size: .6rem; cursor: pointer; line-height: 1;
}
.pce-rank button:hover:not(:disabled) { color: #0453cb; border-color: #c7d4e5; }
.pce-rank button:disabled { opacity: .35; cursor: default; }

.pce-body { flex: 1; min-width: 0; }
.pce-title { font-size: .95rem; font-weight: 700; color: #1e293b; }
.pce-desc { font-size: .8rem; color: #64748b; margin-top: .2rem; line-height: 1.5; }
.pce-scope { font-size: .74rem; color: #475569; margin-top: .4rem; }
.pce-scope i { color: #94a3b8; margin-right: .3rem; }

.pce-badges { display: flex; gap: .35rem; flex-wrap: wrap; margin-top: .5rem; }
.pce-badge {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .2rem .5rem; border-radius: 6px;
    font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .3px;
}
.pce-badge--primary { background: rgba(4,83,203,.10); color: #0453cb; border: 1px solid rgba(4,83,203,.22); }
.pce-badge--accent { background: rgba(59,125,219,.10); color: #3b7ddb; border: 1px solid rgba(59,125,219,.22); }
.pce-badge--muted { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }
.pce-badge--req { background: rgba(4,83,203,.12); color: #033a8e; border: 1px solid rgba(4,83,203,.28); }

.pce-actions { display: flex; gap: .3rem; align-items: center; }
.pce-icon-btn {
    width: 32px; height: 32px; border-radius: 8px;
    border: 1px solid #e2e8f0; background: #fff; color: #64748b;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: .8rem; cursor: pointer; transition: all .2s ease;
}
.pce-icon-btn:hover { color: #0453cb; border-color: #c7d4e5; background: #f8fafc; }
.pce-icon-btn--danger:hover { color: #dc2626; border-color: rgba(220,38,38,.3); background: rgba(220,38,38,.05); }

.pce-modal {
    position: fixed; inset: 0; z-index: 1080;
    background: rgba(15,23,42,.5);
    display: flex; align-items: flex-start; justify-content: center;
    padding: 3rem 1rem; overflow-y: auto;
}
.pce-modal-box {
    background: #fff; border-radius: 16px; width: 100%; max-width: 660px;
    box-shadow: 0 20px 60px rgba(15,23,42,.25);
}
.pce-modal-head {
    padding: 1.1rem 1.5rem; border-bottom: 1px solid #e2e8f0;
    display: flex; align-items: center; justify-content: space-between;
}
.pce-modal-head h2 { font-size: 1.02rem; font-weight: 700; color: #1e293b; margin: 0; }
.pce-modal-body { padding: 1.25rem 1.5rem; display: flex; flex-direction: column; gap: 1rem; }
.pce-modal-foot {
    padding: 1rem 1.5rem; border-top: 1px solid #e2e8f0;
    display: flex; justify-content: flex-end; gap: .6rem;
}

.pce-field { display: flex; flex-direction: column; gap: .35rem; }
.pce-label { font-size: .74rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: .4px; }
.pce-hint { font-size: .72rem; color: #94a3b8; line-height: 1.5; }
.pce-input, .pce-textarea {
    width: 100%; border: 1px solid #e2e8f0; border-radius: 10px;
    padding: .55rem .75rem; font-size: .88rem; color: #1e293b; background: #fff;
}
.pce-input:focus, .pce-textarea:focus { outline: none; border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.1); }
.pce-textarea { min-height: 66px; resize: vertical; }
.pce-grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

/* Le sélecteur premium est en inline-flex : hors d'un parent flex, il se
   rétracte à son contenu et son menu devient illisible. */
.pce-au-full { display: flex !important; width: 100%; }
.pce-au-full .au-select-trigger { width: 100%; }

.pce-chips { display: flex; flex-wrap: wrap; gap: .35rem; }
.pce-chip {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .3rem .6rem; border-radius: 8px; cursor: pointer;
    border: 1px solid #e2e8f0; background: #fff;
    font-size: .76rem; color: #475569; transition: all .15s ease;
}
.pce-chip:hover { border-color: #c7d4e5; }
.pce-chip--on { background: rgba(4,83,203,.10); border-color: rgba(4,83,203,.35); color: #0453cb; font-weight: 600; }
/* Nature d'une filiere-reflet LMD (« Parcours », « Mention »). Sans elle, un
   reflet et la filiere BTS homonyme sont deux etiquettes identiques, et l'une
   des deux ne concerne aucun dossier. */
.pce-chip-nature {
    /* Le code n'apparait que sur les homonymes : c'est la seule colonne que
       l'application impose unique, donc la seule qui les departage. Partout
       ailleurs il serait du bruit. */
    .pce-chip-code { margin-left: .35rem; padding: .05rem .3rem; border-radius: 4px; background: rgba(4,83,203,.10); color: #0453cb; font-size: .62rem; font-weight: 700; letter-spacing: .04em; font-family: 'Courier New', monospace; }
    font-size: .62rem; font-weight: 700; letter-spacing: .4px; text-transform: uppercase;
    color: #5e91de; background: rgba(94,145,222,.12);
    border: 1px solid rgba(94,145,222,.28);
    padding: .05rem .3rem; border-radius: 4px;
}
.pce-chip--on .pce-chip-nature { color: #0453cb; border-color: rgba(4,83,203,.35); background: rgba(4,83,203,.12); }

.pce-switch { display: inline-flex; align-items: center; gap: .5rem; cursor: pointer; font-size: .84rem; color: #1e293b; }
.pce-switch input { width: 16px; height: 16px; accent-color: #0453cb; cursor: pointer; }

.pce-erreurs { font-size: .78rem; color: #dc2626; line-height: 1.5; }

.pce-toasts { position: fixed; bottom: 1.25rem; right: 1.25rem; z-index: 1090; display: flex; flex-direction: column; gap: .5rem; }
.pce-toast {
    padding: .7rem 1rem; border-radius: 10px; font-size: .84rem; font-weight: 600;
    box-shadow: 0 8px 24px rgba(15,23,42,.18); background: #fff; border-left: 4px solid #0453cb; color: #1e293b;
    max-width: 380px;
}
.pce-toast--success { border-left-color: #10b981; }
.pce-toast--error { border-left-color: #dc2626; }

[x-cloak] { display: none !important; }

@@media (max-width: 768px) {
    .pce-hero { padding: 1.4rem 1.25rem 1.1rem; }
    .pce-grid2 { grid-template-columns: 1fr; }
    .pce-row { flex-wrap: wrap; }
}
</style>
@endpush

@section('content')
@php
    // Tableau préparé ici, puis passé à @json() par une variable : un littéral
    // multiligne dans la parenthèse de @json() compile sans erreur mais casse
    // au rendu (cf. .claude/rules/blade-pitfalls.md).
    $_pceData = [
        'pieces' => $pieces,
        'filieres' => $filieres,
        'niveaux' => $niveaux,
        'peutConfigurer' => (bool) $peutConfigurer,
        'nbProposees' => $nbProposees,
        'exemplairesMax' => $exemplairesMax,
        'formeDefaut' => $formeDefaut,
        'echeanceDefaut' => $echeanceDefaut,
    ];
@endphp

<div class="main-content" x-data="cataloguePiecesDossier()" x-init="init()">

    <div class="pce-hero">
        <div class="pce-hero-top">
            <div class="pce-hero-left">
                <div class="pce-hero-icon"><i class="fas fa-list-check"></i></div>
                <div>
                    <h1>Pièces à fournir</h1>
                    <p>Ce que l'école réclame à chaque inscription, et à qui</p>
                </div>
            </div>
            <div class="pce-hero-actions">
                <button type="button" class="pce-btn pce-btn--glass" @click="aideOuverte = true">
                    <i class="fas fa-question-circle"></i>Aide
                </button>
                @can('pieces_dossier.configure')
                <button type="button" class="pce-btn pce-btn--white" @click="ouvrirCreation()">
                    <i class="fas fa-plus"></i>Ajouter une pièce
                </button>
                @endcan
            </div>
        </div>

        <div class="pce-kpis">
            <div class="pce-kpi">
                <div class="pce-kpi-value" x-text="pieces.filter(p => p.is_active).length">0</div>
                <div class="pce-kpi-label">Pièces actives</div>
            </div>
            <div class="pce-kpi">
                <div class="pce-kpi-value" x-text="pieces.filter(p => p.is_active &amp;&amp; p.is_obligatoire).length">0</div>
                <div class="pce-kpi-label">Dont obligatoires</div>
            </div>
            <div class="pce-kpi">
                <div class="pce-kpi-value" x-text="pieces.filter(p => p.is_active &amp;&amp; (p.filiere_ids.length || p.niveau_ids.length)).length">0</div>
                <div class="pce-kpi-label">À portée restreinte</div>
            </div>
            <div class="pce-kpi">
                <div class="pce-kpi-value" x-text="pieces.filter(p => p.is_active &amp;&amp; p.echeance === 'avant_fin_annee').length">0</div>
                <div class="pce-kpi-label">Attendues en cours d'année</div>
            </div>
        </div>
    </div>

    <div class="pce-card">
        {{-- Catalogue vide : tant que l'école n'a rien configuré, aucun écran
             d'inscription ne change. On l'écrit noir sur blanc pour que
             personne ne croie la fonctionnalité cassée. --}}
        <template x-if="pieces.length === 0">
            <div class="pce-empty">
                <div class="pce-empty-icon"><i class="fas fa-folder-open"></i></div>
                <h3>Aucune pièce configurée</h3>
                <p>
                    Tant que ce catalogue est vide, rien ne change dans les inscriptions :
                    aucun contrôle de dossier n'est affiché. Partez du jeu proposé, puis
                    retirez ou renommez librement — c'est votre liste, pas la nôtre.
                </p>
                @can('pieces_dossier.configure')
                <div class="pce-empty-actions">
                    <button type="button" class="pce-btn pce-btn--primary" :disabled="enCours" @click="installerJeuPropose()">
                        <i class="fas fa-wand-magic-sparkles"></i>
                        <span x-text="'Partir des ' + nbProposees + ' pièces proposées'"></span>
                    </button>
                    <button type="button" class="pce-btn pce-btn--ghost" @click="ouvrirCreation()">
                        <i class="fas fa-plus"></i>Créer ma première pièce
                    </button>
                </div>
                @endcan
            </div>
        </template>

        <template x-if="pieces.length > 0">
            <div>
                <template x-for="(piece, index) in pieces" :key="piece.id">
                    <div class="pce-row" :class="piece.is_active ? '' : 'pce-row--inactive'">
                        <div class="pce-rank">
                            <button type="button" title="Monter" :disabled="index === 0 || !peutConfigurer" @click="deplacer(index, -1)">
                                <i class="fas fa-chevron-up"></i>
                            </button>
                            <button type="button" title="Descendre" :disabled="index === pieces.length - 1 || !peutConfigurer" @click="deplacer(index, 1)">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>

                        <div class="pce-body">
                            <div class="pce-title" x-text="piece.libelle"></div>
                            <div class="pce-desc" x-show="piece.description" x-text="piece.description"></div>

                            <div class="pce-badges">
                                <span class="pce-badge" :class="piece.is_obligatoire ? 'pce-badge--req' : 'pce-badge--muted'"
                                      x-text="piece.is_obligatoire ? 'Obligatoire' : 'Facultative'"></span>
                                <span class="pce-badge pce-badge--accent" x-text="piece.forme_label"></span>
                                <span class="pce-badge pce-badge--muted" x-show="piece.exemplaires_par_inscription > 1"
                                      x-text="piece.exemplaires_par_inscription + ' par inscription'"></span>
                                <span class="pce-badge pce-badge--primary" x-text="piece.echeance_label"></span>
                                <span class="pce-badge pce-badge--accent" x-text="piece.appartenance_label"></span>
                                <span class="pce-badge pce-badge--muted" x-show="piece.duree_validite_mois"
                                      x-text="'Valable ' + piece.duree_validite_mois + ' mois'"></span>
                                <span class="pce-badge pce-badge--muted" x-show="!piece.is_active">Désactivée</span>
                            </div>

                            <div class="pce-scope">
                                <i class="fas fa-crosshairs"></i><span x-text="piece.libelle_scope"></span>
                            </div>
                        </div>

                        @can('pieces_dossier.configure')
                        <div class="pce-actions">
                            <button type="button" class="pce-icon-btn" :title="piece.is_active ? 'Désactiver' : 'Réactiver'"
                                    @click="basculer(piece)">
                                <i class="fas" :class="piece.is_active ? 'fa-toggle-on' : 'fa-toggle-off'"></i>
                            </button>
                            <button type="button" class="pce-icon-btn" title="Modifier" @click="ouvrirEdition(piece)">
                                <i class="fas fa-pen"></i>
                            </button>
                            <button type="button" class="pce-icon-btn pce-icon-btn--danger" title="Retirer du catalogue"
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

    @can('pieces_dossier.configure')
    <div class="pce-modal" x-show="modalOuvert" x-cloak @keydown.escape.window="modalOuvert = false">
        <div class="pce-modal-box" @click.outside="modalOuvert = false">
            <div class="pce-modal-head">
                <h2 x-text="form.id ? 'Modifier la pièce' : 'Nouvelle pièce'"></h2>
                <button type="button" class="pce-icon-btn" @click="modalOuvert = false"><i class="fas fa-times"></i></button>
            </div>

            <form @submit.prevent="enregistrer()">
                <div class="pce-modal-body">
                    <div class="pce-field">
                        <span class="pce-label">Libellé</span>
                        <input type="text" class="pce-input" x-model="form.libelle" required maxlength="255"
                               placeholder="Extrait de naissance">
                    </div>

                    <div class="pce-field">
                        <span class="pce-label">Consigne au guichet</span>
                        <textarea class="pce-textarea" x-model="form.description" maxlength="2000"
                                  placeholder="Copie légalisée de moins de trois mois."></textarea>
                        <span class="pce-hint">Ce que l'agent doit vérifier avant d'accepter la pièce.</span>
                    </div>

                    <div class="pce-grid2">
                        <div class="pce-field">
                            <span class="pce-label">Forme attendue</span>
                            <x-au-select class="pce-au-full" x-model="form.forme_attendue"
                                         :options="$formes" placeholder="Choisir" :placeholder-is-first-option="false" />
                        </div>
                        <div class="pce-field">
                            <span class="pce-label">Exemplaires par inscription</span>
                            <input type="number" class="pce-input" x-model.number="form.exemplaires_par_inscription"
                                   min="1" :max="exemplairesMax" required>
                            <span class="pce-hint">
                                Deux photos, trois copies du diplôme : ce qu'UNE inscription consomme.
                                Sur une pièce qui dure, trois années de licence en consomment trois fois
                                autant, prélevées sur ce que l'étudiant a déposé une seule fois.
                            </span>
                        </div>
                    </div>

                    <div class="pce-grid2">
                        <div class="pce-field">
                            <span class="pce-label">La pièce dure-t-elle ?</span>
                            <x-au-select class="pce-au-full" x-model="form.appartenance"
                                         :options="$appartenances" placeholder="Choisir" :placeholder-is-first-option="false" />
                            <span class="pce-hint">
                                Un extrait de naissance, des photos, un diplôme se déposent une fois et
                                servent toute la scolarité. Un certificat de l'année, lui, se redonne à
                                chaque rentrée. C'est cette réponse qui décide de ce que vous redemandez.
                            </span>
                        </div>
                        <div class="pce-field">
                            <span class="pce-label">Validité (mois)</span>
                            <input type="number" class="pce-input" x-model="form.duree_validite_mois"
                                   min="1" max="600" placeholder="Ne périme jamais">
                            <span class="pce-hint">
                                Laissez vide pour une pièce qui ne périme jamais — c'est le cas d'un
                                extrait de naissance. Trois mois pour un certificat médical. La validité
                                court depuis la délivrance du document, pas depuis son dépôt.
                            </span>
                        </div>
                    </div>

                    <div class="pce-field">
                        <span class="pce-label">Échéance</span>
                        <x-au-select class="pce-au-full" x-model="form.echeance"
                                     :options="$echeances" placeholder="Choisir" :placeholder-is-first-option="false" />
                        <span class="pce-hint">
                            Une pièce attendue avant la fin de l'année n'est pas comptée comme manquante
                            le jour de l'inscription. À ne pas confondre avec une réserve, qui vise un
                            document pas encore délivré.
                        </span>
                    </div>

                    <div class="pce-field">
                        <span class="pce-label">Filières concernées</span>
                        <div class="pce-chips">
                            @foreach ($filieres as $filiere)
                            @php $_natureLmd = $filiere->natureLmd(); @endphp
                            <label class="pce-chip" :class="form.filiere_ids.includes({{ $filiere->id }}) ? 'pce-chip--on' : ''">
                                <input type="checkbox" hidden value="{{ $filiere->id }}"
                                       :checked="form.filiere_ids.includes({{ $filiere->id }})"
                                       @change="basculerScope('filiere_ids', {{ $filiere->id }})">
                                @if ($_natureLmd)<span class="pce-chip-nature">{{ $_natureLmd }}</span>@endif{{ $filiere->name }}@if (!empty($filiere->estHomonyme))<span class="pce-chip-code">{{ $filiere->code }}</span>@endif
                            </label>
                            @endforeach
                        </div>
                        <span class="pce-hint">
                            Aucune sélection = toutes les filières, y compris celles créées plus tard.
                            Les entrées marquées « Parcours » ou « Mention » sont les filières du système
                            LMD : ce sont elles que portent les classes LMD. Quand une filière BTS existe
                            sous le même nom, choisir la mauvaise donne une portée qu'aucun dossier ne
                            satisfera, sans que rien ne le signale.
                        </span>
                    </div>

                    <div class="pce-field">
                        <span class="pce-label">Niveaux concernés</span>
                        <div class="pce-chips">
                            @foreach ($niveaux as $niveau)
                            <label class="pce-chip" :class="form.niveau_ids.includes({{ $niveau->id }}) ? 'pce-chip--on' : ''">
                                <input type="checkbox" hidden value="{{ $niveau->id }}"
                                       :checked="form.niveau_ids.includes({{ $niveau->id }})"
                                       @change="basculerScope('niveau_ids', {{ $niveau->id }})">
                                {{ $niveau->name }}
                            </label>
                            @endforeach
                        </div>
                        <span class="pce-hint">Aucune sélection = tous les niveaux.</span>
                    </div>

                    <div class="pce-grid2">
                        <label class="pce-switch">
                            <input type="checkbox" x-model="form.is_obligatoire">
                            Pièce obligatoire
                        </label>
                        <label class="pce-switch">
                            <input type="checkbox" x-model="form.is_active">
                            Pièce active
                        </label>
                    </div>

                    <div class="pce-erreurs" x-show="erreurs.length" x-cloak>
                        <template x-for="erreur in erreurs" :key="erreur"><div x-text="erreur"></div></template>
                    </div>
                </div>

                <div class="pce-modal-foot">
                    <button type="button" class="pce-btn pce-btn--ghost" @click="modalOuvert = false">Annuler</button>
                    <button type="submit" class="pce-btn pce-btn--primary" :disabled="enCours">
                        <i class="fas fa-check"></i>
                        <span x-show="!enCours">Enregistrer</span>
                        <span x-show="enCours" x-cloak>Enregistrement…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endcan

    <div class="pce-modal" x-show="aideOuverte" x-cloak @keydown.escape.window="aideOuverte = false">
        <div class="pce-modal-box" @click.outside="aideOuverte = false">
            <div class="pce-modal-head">
                <h2>À quoi sert ce catalogue</h2>
                <button type="button" class="pce-icon-btn" @click="aideOuverte = false"><i class="fas fa-times"></i></button>
            </div>
            <div class="pce-modal-body" style="font-size:.86rem;color:#475569;line-height:1.65;">
                <p style="margin:0;">
                    Ce catalogue décrit <strong>ce que l'école réclame</strong> à chaque inscription. Il ne dit
                    pas où en est un étudiant : l'état de chaque pièce se pose sur l'inscription,
                    année par année. Un étudiant de troisième année a donc trois lignes « extrait de
                    naissance », une par année — c'est voulu, puisqu'un exemplaire part chaque année
                    au ministère.
                </p>
                <p style="margin:0;">
                    <strong>Au guichet, on coche.</strong> L'agent regarde le dossier papier qu'on lui tend
                    et dit ce qu'il a reçu, en combien d'exemplaires. Rien d'autre n'est demandé :
                    joindre un fichier numérisé restera toujours facultatif, et une école qui ne
                    numérise pas suit ses dossiers exactement de la même façon.
                </p>
                <p style="margin:0;">
                    <strong>Pièce manquante</strong> et <strong>réserve</strong> ne sont pas la même chose. Une pièce
                    manquante porte sur un document qui existe et que l'étudiant n'a pas apporté. Une
                    réserve porte sur un document qui n'existe pas encore et arrivera plus tard — un
                    relevé de baccalauréat non délivré, par exemple. La réserve se pose sur l'inscription
                    elle-même, pas ici.
                </p>
                <p style="margin:0;">
                    <strong>Portée</strong> : laisser filières et niveaux vides signifie « tout le monde ».
                    Une filière créée demain héritera automatiquement de ces pièces. Cocher des filières
                    <em>et</em> des niveaux restreint aux deux à la fois.
                </p>
                <p style="margin:0;">
                    <strong>Retirer une pièce</strong> l'archive : les dossiers déjà constitués gardent leur
                    historique. Pour la suspendre le temps d'une rentrée, préférez la désactiver.
                </p>
            </div>
        </div>
    </div>

    <div class="pce-toasts">
        <template x-for="toast in toasts" :key="toast.id">
            <div class="pce-toast" :class="'pce-toast--' + toast.type" x-text="toast.message"></div>
        </template>
    </div>
</div>

<script type="application/json" id="pce-payload">@json($_pceData)</script>
@endsection

@push('scripts')
<script>
function cataloguePiecesDossier() {
    const payload = JSON.parse(document.getElementById('pce-payload').textContent);

    return {
        pieces: payload.pieces,
        filieres: payload.filieres,
        niveaux: payload.niveaux,
        peutConfigurer: payload.peutConfigurer,
        nbProposees: payload.nbProposees,
        exemplairesMax: payload.exemplairesMax,

        modalOuvert: false,
        aideOuverte: false,
        enCours: false,
        erreurs: [],
        toasts: [],
        _prochainToast: 1,

        form: {},

        init() {
            this.form = this.formVide();
        },

        /**
         * Les valeurs proposées viennent des réglages de l'école, pas du code :
         * une école qui tolère la plupart des pièces en cours d'année ne veut
         * pas changer la même case cinquante fois de suite.
         */
        formVide() {
            return {
                id: null,
                libelle: '',
                description: '',
                is_obligatoire: true,
                forme_attendue: payload.formeDefaut,
                exemplaires_par_inscription: 1,
                echeance: payload.echeanceDefaut,
                // « À l'étudiant » d'emblée : c'est le cas normal d'un dossier
                // d'inscription, et c'est celui qui évite de redemander chaque
                // année une pièce déjà déposée.
                appartenance: 'etudiant',
                // Vide, et non zéro : vide veut dire « ne périme jamais ».
                duree_validite_mois: '',
                filiere_ids: [],
                niveau_ids: [],
                is_active: true,
            };
        },

        ouvrirCreation() {
            this.erreurs = [];
            this.form = this.formVide();
            this.modalOuvert = true;
            this.rafraichirSelectsPremium();
        },

        ouvrirEdition(piece) {
            this.erreurs = [];
            // Copie profonde des tableaux de portée : sans elle, cocher une
            // filière dans le formulaire modifierait la ligne affichée avant
            // même l'enregistrement.
            this.form = Object.assign({}, piece, {
                filiere_ids: piece.filiere_ids.slice(),
                niveau_ids: piece.niveau_ids.slice(),
            });
            this.modalOuvert = true;
            this.rafraichirSelectsPremium();
        },

        /**
         * x-model écrit dans le <select> caché du sélecteur premium sans émettre
         * d'événement : le libellé du bouton resterait sur la valeur précédente.
         * On déclenche donc le 'change' que le composant écoute.
         */
        rafraichirSelectsPremium() {
            this.$nextTick(() => {
                this.$el.querySelectorAll('.pce-au-full .au-select-native').forEach((select) => {
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });
        },

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
                ? '{{ route('esbtp.pieces-dossier.store') }}'
                : '{{ url('esbtp/pieces-dossier') }}/' + this.form.id;

            try {
                const data = await this.appel(url, creation ? 'POST' : 'PUT', {
                    libelle: this.form.libelle,
                    description: this.form.description,
                    is_obligatoire: this.form.is_obligatoire,
                    forme_attendue: this.form.forme_attendue,
                    exemplaires_par_inscription: this.form.exemplaires_par_inscription,
                    echeance: this.form.echeance,
                    appartenance: this.form.appartenance,
                    // Le champ vide part en null, jamais en chaîne vide ni en
                    // zéro : c'est ainsi que « ne périme jamais » se dit.
                    duree_validite_mois: this.form.duree_validite_mois === '' || this.form.duree_validite_mois === null
                        ? null
                        : Number(this.form.duree_validite_mois),
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
                const data = await this.appel('{{ url('esbtp/pieces-dossier') }}/' + piece.id + '/bascule', 'POST', {});
                const i = this.pieces.findIndex(p => p.id === data.piece.id);
                if (i !== -1) { this.pieces[i] = data.piece; }
                this.notifier('success', data.message);
            } catch (err) {
                this.notifier('error', err.message);
            }
        },

        async retirer(piece) {
            const question = 'Retirer « ' + piece.libelle + ' » du catalogue ? '
                + 'Les dossiers déjà constitués gardent leur historique.';
            if (!window.confirm(question)) { return; }

            try {
                const data = await this.appel('{{ url('esbtp/pieces-dossier') }}/' + piece.id, 'DELETE', {});
                this.pieces = this.pieces.filter(p => p.id !== data.id);
                this.notifier('success', data.message);
            } catch (err) {
                this.notifier('error', err.message);
            }
        },

        async deplacer(index, sens) {
            const cible = index + sens;
            if (cible < 0 || cible >= this.pieces.length) { return; }

            const avant = this.pieces.slice();
            const copie = this.pieces.slice();
            const [deplacee] = copie.splice(index, 1);
            copie.splice(cible, 0, deplacee);

            // On applique tout de suite : l'ordre est une préférence
            // d'affichage, et un aller-retour serveur avant de bouger rendrait
            // la liste poussive. En cas d'échec, on remet la liste d'avant
            // plutôt que de laisser l'écran mentir sur ce qui est enregistré.
            this.pieces = copie;

            try {
                await this.appel('{{ route('esbtp.pieces-dossier.reorder') }}', 'POST', {
                    ids: this.pieces.map(p => p.id),
                });
            } catch (err) {
                this.pieces = avant;
                this.notifier('error', err.message);
            }
        },

        async installerJeuPropose() {
            this.enCours = true;
            try {
                const data = await this.appel('{{ route('esbtp.pieces-dossier.jeu-propose') }}', 'POST', {});
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
                // 422 renvoie le détail champ par champ : le montrer évite à
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
</script>
@endpush
