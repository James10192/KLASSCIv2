@extends('layouts.app')

@section('title', 'Règles académiques')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/reinscription-regles-premium.css') }}?v={{ @filemtime(public_path('css/reinscription-regles-premium.css')) ?: '1' }}">
<script src="{{ asset('js/reinscription-regles-premium.js') }}?v={{ @filemtime(public_path('js/reinscription-regles-premium.js')) ?: '1' }}"></script>
@endsection

@section('content')
<div class="dashboard-acasi arp"
     x-data="reglesPremium($el)"
     data-regles='@json($regles->values())'
     data-endpoint="{{ route('esbtp.reinscription.regles.store') }}">
    <div class="main-content">
        <header class="arp-hero">
            <div class="arp-hero-head">
                <div class="arp-title">
                    <span class="arp-title-icon" aria-hidden="true"><i class="fas fa-graduation-cap"></i></span>
                    <div>
                        <h1>Règles académiques</h1>
                        <p>Simplifions la vie de nos personnels dans l'éducation.</p>
                    </div>
                </div>
                <div class="arp-tools">
                    <a href="{{ route('esbtp.reinscription.index') }}" class="arp-button arp-button--glass">
                        <i class="fas fa-arrow-left" aria-hidden="true"></i> Réinscriptions
                    </a>
                    <button type="button" class="arp-button arp-button--light" x-on:click="create()">
                        <i class="fas fa-plus" aria-hidden="true"></i> Nouvelle règle
                    </button>
                </div>
            </div>
            <div class="arp-metrics">
                <div class="arp-metric"><i class="fas fa-sliders-h" aria-hidden="true"></i><div><strong x-text="rules.length">{{ $regles->count() }}</strong><span>Règles définies</span></div></div>
                <div class="arp-metric"><i class="fas fa-circle-check" aria-hidden="true"></i><div><strong x-text="activeCount">{{ $regles->where('actif', true)->count() }}</strong><span>Actives</span></div></div>
                <div class="arp-metric"><i class="fas fa-scale-balanced" aria-hidden="true"></i><div><strong x-text="rules.length-activeCount">{{ $regles->where('actif', false)->count() }}</strong><span>Inactives</span></div></div>
            </div>
        </header>

        <div class="arp-feedback" role="status" x-show="feedback" x-cloak>
            <span><i class="fas fa-check-circle" aria-hidden="true"></i> <span x-text="feedback"></span></span>
            <button type="button" aria-label="Fermer le message" x-on:click="feedback=''"><i class="fas fa-times"></i></button>
        </div>
        @if(session('success'))
            <div class="arp-feedback" role="status">{{ session('success') }}</div>
        @endif
        <aside class="arp-policy" role="note">
            <i class="fas fa-scale-balanced" aria-hidden="true"></i>
            <div>
                @if($conseilBts1['mode'] === 'threshold')
                    <strong>Conseil BTS 1 : seuil de {{ $conseilBts1['seuil'] }}/20 prioritaire</strong>
                    <span>La décision du bulletin utilise {{ $conseilBts1['source'] === 'annual' ? 'la moyenne annuelle' : ($conseilBts1['source'] === 'semestre2' ? 'la moyenne du semestre 2' : 'les deux moyennes') }}. Cette politique prime sur les seuils historiques de réinscription.</span>
                @else
                    <strong>Conseil BTS 1 : décision manuelle</strong>
                    <span>Les seuils ci-dessous servent aux propositions automatiques en l'absence d'une décision de conseil normalisée. Vérifiez les admissions avant la réinscription.</span>
                @endif
            </div>
            @can('bulletins.edit')
                <a href="{{ route('esbtp.bulletins.configuration') }}">Configurer le bulletin <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
            @endcan
        </aside>
        <div class="arp-panel">
            <div class="arp-panel-top">
                <div><h2>Politiques de passage et de rattrapage</h2><p>Chaque règle s'applique à un niveau et une filière de cet établissement.</p></div>
                <label class="arp-search">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <span class="sr-only">Rechercher une règle</span>
                    <input type="search" x-model.debounce.200ms="search" placeholder="Rechercher une règle…" autocomplete="off">
                </label>
            </div>
            <div class="arp-list" role="list" aria-label="Règles académiques">
                <template x-for="rule in filteredRules" :key="rule.id">
                    <div class="arp-rule" role="listitem">
                        <div class="arp-rule-title">
                            <div class="arp-rule-icon"><i class="fas fa-book-open" aria-hidden="true"></i></div>
                            <div><strong x-text="rule.niveau || 'Règle générale'"></strong><small x-text="rule.filiere || 'Toutes les filières'"></small></div>
                        </div>
                        <div class="arp-cell"><span>Passage</span><strong x-text="Number(rule.moyenne_passage).toFixed(2)+' /20'"></strong></div>
                        <div class="arp-cell"><span>Rattrapage</span><strong x-text="Number(rule.moyenne_rattrapage).toFixed(2)+' /20'"></strong></div>
                        <div class="arp-cell arp-optional"><span>Matières max.</span><strong x-text="rule.max_matieres_rattrapage"></strong></div>
                        <div class="arp-cell"><span>État</span><span class="arp-status" :class="rule.actif ? 'arp-status--active':''" x-text="rule.actif ? 'Active' : 'Inactive'"></span></div>
                        <div class="arp-actions">
                            <button type="button" class="arp-icon-button" x-on:click="edit(rule)" :aria-label="'Modifier '+(rule.niveau || 'la règle générale')" title="Modifier"><i class="fas fa-pen"></i></button>
                            <button type="button" class="arp-icon-button" x-on:click="askDelete(rule)" :aria-label="'Supprimer '+(rule.niveau || 'la règle générale')" title="Supprimer"><i class="fas fa-trash-alt"></i></button>
                        </div>
                    </div>
                </template>
                <div class="arp-empty" x-show="filteredRules.length === 0" x-cloak>
                    <i class="fas fa-inbox" aria-hidden="true"></i><strong x-text="rules.length ? 'Aucune règle trouvée' : 'Aucune règle configurée'"></strong>
                    <p x-text="rules.length ? 'Essayez une autre recherche.' : 'Ajoutez une première politique académique pour cet établissement.'"></p>
                </div>
            </div>
        </div>
    </div>

    <div class="arp-scrim" x-show="open" x-transition.opacity x-cloak
         x-on:keydown.escape.window="dismiss()" x-on:click.self="dismiss()" role="presentation">
        <div class="arp-dialog" role="dialog" aria-modal="true" aria-labelledby="arp-modal-title">
            <div class="arp-dialog-head">
                <div>
                    <div class="arp-eyebrow">Configuration académique</div>
                    <h2 id="arp-modal-title" x-text="action === 'create' ? 'Nouvelle règle' : 'Modifier la règle'"></h2>
                    <p>Les seuils sont propres à l'établissement et ne modifient pas rétroactivement les bulletins.</p>
                </div>
                <button type="button" class="arp-icon-button" aria-label="Fermer" x-on:click="dismiss()"><i class="fas fa-times"></i></button>
            </div>
            <form x-on:submit.prevent="save()" novalidate>
                <div class="arp-dialog-body">
                    <div class="arp-help"><i class="fas fa-circle-info" aria-hidden="true"></i> Pour le BTS avec décision de conseil automatique, le seuil du bulletin est prioritaire. Configurez-le depuis les paramètres du bulletin.</div>
                    <p class="arp-error" x-show="fieldError('general')" x-text="fieldError('general')" role="alert"></p>
                    <div class="arp-fields">
                        <div class="arp-field">
                            <label>Niveau</label>
                            <template x-if="open && action === 'create'">
                                <div><x-au-select name="niveau" label="Niveau d'études" icon="fa-layer-group"
                                    placeholder="Choisir un niveau" :searchable="$niveaux->count() > 8"
                                    :options="$niveaux->pluck('name', 'name')->all()"
                                    x-on:change="form.niveau=$event.target.value" /></div>
                            </template>
                            <div class="arp-readonly" x-show="action === 'edit'" x-text="form.niveau"></div>
                            <span class="arp-error" x-text="fieldError('niveau')"></span>
                        </div>
                        <div class="arp-field">
                            <label>Filière</label>
                            <template x-if="open && action === 'create'">
                                <div><x-au-select name="filiere" label="Filière" icon="fa-graduation-cap"
                                    placeholder="Choisir une filière" :searchable="$filieres->count() > 8"
                                    :options="$filieres->pluck('name', 'name')->all()"
                                    x-on:change="form.filiere=$event.target.value" /></div>
                            </template>
                            <div class="arp-readonly" x-show="action === 'edit'" x-text="form.filiere"></div>
                            <span class="arp-error" x-text="fieldError('filiere')"></span>
                        </div>
                        <div class="arp-field"><label for="arp-passage">Moyenne de passage /20</label>
                            <input class="arp-input" x-ref="passage" id="arp-passage" type="number" min="0" max="20" step="0.01" x-model="form.moyenne_passage" required>
                            <span class="arp-error" x-text="fieldError('moyenne_passage')"></span>
                        </div>
                        <div class="arp-field"><label for="arp-rattrapage">Seuil de rattrapage /20</label>
                            <input class="arp-input" id="arp-rattrapage" type="number" min="0" max="20" step="0.01" x-model="form.moyenne_rattrapage" required>
                            <span class="arp-error" x-text="fieldError('moyenne_rattrapage')"></span>
                        </div>
                        <div class="arp-field"><label for="arp-matieres">Maximum de matières en rattrapage</label>
                            <input class="arp-input" id="arp-matieres" type="number" min="0" step="1" x-model="form.max_matieres_rattrapage" required>
                            <span class="arp-error" x-text="fieldError('max_matieres_rattrapage')"></span>
                        </div>
                        <div class="arp-field"><label for="arp-redoublements">Maximum de redoublements</label>
                            <input class="arp-input" id="arp-redoublements" type="number" min="0" step="1" x-model="form.max_redoublements" required>
                            <span class="arp-error" x-text="fieldError('max_redoublements')"></span>
                        </div>
                    </div>
                    <div class="arp-checks">
                        <label class="arp-check"><input type="checkbox" x-model="form.autoriser_redoublement"> Autoriser le redoublement</label>
                        <label class="arp-check"><input type="checkbox" x-model="form.actif"> Règle active</label>
                    </div>
                    <div class="arp-field" style="margin-top:1rem"><label for="arp-conditions">Conditions particulières (facultatif)</label>
                        <textarea class="arp-input" id="arp-conditions" rows="3" maxlength="2000" x-model="form.conditions_speciales" placeholder="Précisions sur la politique académique…"></textarea>
                        <span class="arp-error" x-text="fieldError('conditions_speciales')"></span>
                    </div>
                </div>
                <div class="arp-dialog-foot">
                    <button type="button" class="arp-button arp-button--neutral" x-on:click="dismiss()">Annuler</button>
                    <button type="submit" class="arp-button arp-button--primary" :disabled="loading">
                        <i class="fas fa-save" aria-hidden="true"></i><span x-text="loading ? 'Enregistrement…' : (action === 'create' ? 'Créer la règle' : 'Enregistrer')"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div class="arp-scrim" x-show="confirmDelete" x-transition.opacity x-cloak
         x-on:keydown.escape.window="dismiss()" x-trap.noscroll="confirmDelete">
        <div class="arp-dialog arp-dialog--small" role="alertdialog" aria-modal="true" aria-labelledby="arp-delete-title">
            <div class="arp-dialog-head"><div><div class="arp-eyebrow">Confirmation</div><h2 id="arp-delete-title">Supprimer cette règle ?</h2><p>La règle ne sera plus utilisée pour les prochaines décisions.</p></div></div>
            <div class="arp-dialog-body"><strong x-text="(selected?.niveau || '')+' · '+(selected?.filiere || '')"></strong><p class="arp-error" role="alert" x-show="fieldError('general')" x-text="fieldError('general')"></p></div>
            <div class="arp-dialog-foot">
                <button type="button" class="arp-button arp-button--neutral" x-on:click="dismiss()">Conserver</button>
                <button type="button" class="arp-button arp-button--danger" :disabled="loading" x-on:click="destroy()"><i class="fas fa-trash-alt" aria-hidden="true"></i><span x-text="loading ? 'Suppression…' : 'Supprimer'"></span></button>
            </div>
        </div>
    </div>
</div>
@endsection