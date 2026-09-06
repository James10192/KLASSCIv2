@extends('layouts.app')

@section('title', 'Configuration des relances')

@section('content')
@php
    // Shell mobile : le DOM de bureau reste dans .m-only-desktop, l'écran mobile
    // (app bar + segments Modèles / Envoi / Planification) vit à côté, sur le
    // MÊME état Alpine (window.rlcConfig) : un modèle édité en feuille mobile
    // est le même objet que la zone de texte du bureau.
    $rlcShell = ($mobileShellEnabled ?? false) && ($mobileProfile ?? null);
    $rlcEcole = \App\Helpers\SettingsHelper::getSchoolInfo();
    $rlcEcoleNom = $rlcEcole['name'] ?: ($rlcEcole['acronym'] ?: config('app.name'));

    // Même garde que les routes POST config/templates et config/parametres.
    $rlcPeutEnregistrer = auth()->user()?->can('comptabilite.relances.send') ?? false;

    $rlcNiveaux = [1 => '1er rappel', 2 => '2e rappel', 3 => 'Dernière relance'];
    $rlcCanaux = [
        'email'    => ['label' => 'E-mail',   'icon' => 'fa-envelope', 'hint' => 'Sujet et texte, variables autorisées.'],
        'sms'      => ['label' => 'SMS',      'icon' => 'fa-sms',      'hint' => 'Message court, 160 caractères par SMS.'],
        'courrier' => ['label' => 'Courrier', 'icon' => 'fa-file-pdf', 'hint' => 'Texte du courrier imprimé (PDF).'],
    ];

    // Variables réellement remplacées par l'aperçu serveur (previewTemplate) :
    // on ne propose rien que le moteur ne sache substituer.
    $rlcVariables = [
        'Étudiant'      => ['{nom}', '{prenom}', '{nom_complet}', '{email}', '{telephone}'],
        'Situation'     => ['{montant_dette}', '{date_echeance}', '{jours_retard}', '{niveau_relance}'],
        'Établissement' => ['{nom_ecole}', '{adresse_ecole}', '{telephone_ecole}', '{email_ecole}', '{date_aujourdhui}'],
    ];

    // Modèles : une entrée par canal et par niveau, toujours présente (sujet
    // seulement pour l'e-mail) pour que l'état Alpine soit complet dès le départ.
    $rlcTemplates = [];
    foreach (array_keys($rlcCanaux) as $rlcCanal) {
        foreach (array_keys($rlcNiveaux) as $rlcN) {
            $rlcTemplates[$rlcCanal][$rlcN] = [
                'contenu' => (string) ($templates[$rlcCanal][$rlcN]['contenu'] ?? ''),
                'sujet'   => $rlcCanal === 'email' ? (string) ($templates[$rlcCanal][$rlcN]['sujet'] ?? '') : null,
            ];
        }
    }

    $rlcParams = [
        'delai_niveau_1'        => $parametres['delai_niveau_1'] ?? null,
        'delai_niveau_2'        => $parametres['delai_niveau_2'] ?? null,
        'delai_niveau_3'        => $parametres['delai_niveau_3'] ?? null,
        'montant_minimum'       => $parametres['montant_minimum'] ?? null,
        'relances_automatiques' => (bool) ($parametres['relances_automatiques'] ?? false),
        'heure_envoi'           => $parametres['heure_envoi'] ?? '',
    ];

    $rlcCfg = [
        'templates' => $rlcTemplates,
        'params' => $rlcParams,
        'niveaux' => $rlcNiveaux,
        'urls' => [
            'templates'  => route('esbtp.comptabilite.relances.config.templates'),
            'parametres' => route('esbtp.comptabilite.relances.config.parametres'),
            'preview'    => route('esbtp.comptabilite.relances.config.preview'),
        ],
        'csrf' => csrf_token(),
        'peutEnregistrer' => $rlcPeutEnregistrer,
        'smsMax' => 160,
    ];
@endphp

<div class="container-fluid rlc-page" x-data="rlcConfig({{ \Illuminate\Support\Js::from($rlcCfg) }})">

<div class="{{ $rlcShell ? 'm-only-desktop' : '' }}">

    {{-- En-tête standard des formulaires (pas de hero sur une page de réglages) --}}
    <div class="dashboard-header rlc-header">
        <div class="header-left">
            <h1><i class="fas fa-sliders-h me-2"></i>Configuration des relances</h1>
            <p class="header-subtitle">Délais, modèles de messages et règles d'envoi automatique · {{ $rlcEcoleNom }}</p>
        </div>
        <div class="header-actions">
            @can('comptabilite.relances.send')
                <a href="{{ route('esbtp.comptabilite.relances.index') }}" class="btn-acasi primary">
                    <i class="fas fa-paper-plane"></i>Relances en cours
                </a>
            @endcan
            <a href="{{ route('esbtp.comptabilite.relances.index') }}" class="btn-acasi secondary">
                <i class="fas fa-arrow-left"></i>Retour
            </a>
        </div>
    </div>

    {{-- Bandeau des délais : reflète l'état saisi, pas seulement l'état enregistré --}}
    <div class="rlc-strip">
        @foreach($rlcNiveaux as $rlcN => $rlcLabel)
            <div class="rlc-strip-card">
                <div class="rlc-strip-num">{{ $rlcN }}</div>
                <div>
                    <div class="rlc-strip-value">
                        <span x-text="afficherDelai({{ $rlcN }})">{{ $rlcParams['delai_niveau_' . $rlcN] !== null ? $rlcParams['delai_niveau_' . $rlcN] . ' j' : '—' }}</span>
                    </div>
                    <div class="rlc-strip-label">{{ $rlcLabel }} · jours de retard</div>
                </div>
            </div>
        @endforeach
        <div class="rlc-strip-card">
            <div class="rlc-strip-num"><i class="fas fa-robot"></i></div>
            <div>
                <div class="rlc-strip-value" x-text="params.relances_automatiques ? 'Activé' : 'Manuel'">{{ $rlcParams['relances_automatiques'] ? 'Activé' : 'Manuel' }}</div>
                <div class="rlc-strip-label">Envoi automatique <span x-show="params.relances_automatiques && params.heure_envoi" x-text="'· ' + params.heure_envoi"></span></div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- ── Modèles ── --}}
        <div class="col-lg-8">
            <div class="rlc-card">
                <div class="rlc-card-head">
                    <div class="rlc-card-icon"><i class="fas fa-file-alt"></i></div>
                    <div>
                        <h5 class="rlc-card-title">Modèles de message</h5>
                        <div class="rlc-card-sub">Un texte par canal et par niveau. Cliquez une variable à droite pour l'insérer dans le champ actif.</div>
                    </div>
                </div>
                <div class="rlc-card-body">

                    <div class="rlc-tabs" role="tablist" aria-label="Canal du modèle">
                        @foreach($rlcCanaux as $rlcKey => $rlcCanal)
                            <button type="button" role="tab" class="rlc-tab"
                                    x-bind:class="canal === '{{ $rlcKey }}' ? 'rlc-tab--on' : ''"
                                    x-bind:aria-selected="canal === '{{ $rlcKey }}' ? 'true' : 'false'"
                                    x-on:click="canal = '{{ $rlcKey }}'">
                                <i class="fas {{ $rlcCanal['icon'] }}"></i> {{ $rlcCanal['label'] }}
                            </button>
                        @endforeach
                    </div>

                    @foreach($rlcCanaux as $rlcKey => $rlcCanal)
                        <div x-show="canal === '{{ $rlcKey }}'" x-cloak class="rlc-pane">
                            <p class="rlc-pane-hint"><i class="fas fa-info-circle"></i> {{ $rlcCanal['hint'] }}</p>

                            @foreach($rlcNiveaux as $rlcN => $rlcLabel)
                                <div class="rlc-tpl">
                                    <div class="rlc-tpl-head">
                                        <span class="rlc-lvl rlc-lvl--{{ $rlcN }}"><span class="rlc-lvl-dot"></span>Niveau {{ $rlcN }} · {{ $rlcLabel }}</span>
                                        <div class="rlc-tpl-tools">
                                            @if($rlcKey === 'sms')
                                                <span class="rlc-count" x-bind:class="smsLen({{ $rlcN }}) > cfg.smsMax ? 'rlc-count--over' : ''"
                                                      x-text="smsLen({{ $rlcN }}) + '/' + cfg.smsMax"></span>
                                            @endif
                                            <button type="button" class="rlc-ghost" x-on:click="apercu('{{ $rlcKey }}', {{ $rlcN }})">
                                                <i class="fas fa-eye"></i> Aperçu
                                            </button>
                                        </div>
                                    </div>
                                    <div class="rlc-tpl-body">
                                        @if($rlcKey === 'email')
                                            <div class="rlc-field">
                                                <label class="rlc-label" for="rlc-email-sujet-{{ $rlcN }}">Sujet de l'e-mail</label>
                                                <input type="text" class="rlc-input" id="rlc-email-sujet-{{ $rlcN }}"
                                                       x-model="templates.email[{{ $rlcN }}].sujet"
                                                       placeholder="Ex. : Rappel de paiement · {nom_ecole}"
                                                       @if(!$rlcPeutEnregistrer) readonly @endif>
                                            </div>
                                        @endif
                                        <div class="rlc-field">
                                            <label class="rlc-label" for="rlc-{{ $rlcKey }}-contenu-{{ $rlcN }}">
                                                {{ $rlcKey === 'sms' ? 'Message' : 'Contenu' }}
                                            </label>
                                            <textarea class="rlc-input rlc-textarea" id="rlc-{{ $rlcKey }}-contenu-{{ $rlcN }}"
                                                      rows="{{ $rlcKey === 'sms' ? 4 : ($rlcKey === 'courrier' ? 9 : 7) }}"
                                                      x-model="templates.{{ $rlcKey }}[{{ $rlcN }}].contenu"
                                                      x-on:focus="setFocus($event.target)"
                                                      placeholder="Texte du modèle, avec les variables entre accolades…"
                                                      @if(!$rlcPeutEnregistrer) readonly @endif></textarea>
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            @can('comptabilite.relances.send')
                                <div class="rlc-actions">
                                    <button type="button" class="rlc-save" x-bind:disabled="saving.{{ $rlcKey }}"
                                            x-on:click="enregistrerTemplates('{{ $rlcKey }}')">
                                        <i class="fas" x-bind:class="saving.{{ $rlcKey }} ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                                        <span x-text="saving.{{ $rlcKey }} ? 'Enregistrement…' : 'Enregistrer les modèles {{ $rlcCanal['label'] }}'"></span>
                                    </button>
                                </div>
                            @else
                                <p class="rlc-readonly"><i class="fas fa-lock"></i> Lecture seule : l'enregistrement des modèles demande le droit d'envoyer des relances.</p>
                            @endcan
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ── Variables + Paramètres ── --}}
        <div class="col-lg-4">
            <div class="rlc-sticky">

                <div class="rlc-card">
                    <div class="rlc-card-head">
                        <div class="rlc-card-icon"><i class="fas fa-tags"></i></div>
                        <div>
                            <h5 class="rlc-card-title">Variables</h5>
                            <div class="rlc-card-sub">Remplacées automatiquement pour chaque étudiant</div>
                        </div>
                    </div>
                    <div class="rlc-card-body rlc-card-body--tight">
                        @foreach($rlcVariables as $rlcGroupe => $rlcListe)
                            <div class="rlc-var-group">
                                <div class="rlc-var-title">{{ $rlcGroupe }}</div>
                                <div class="rlc-var-list">
                                    @foreach($rlcListe as $rlcVar)
                                        <button type="button" class="rlc-var" x-on:click="insererVariable('{{ $rlcVar }}')">{{ $rlcVar }}</button>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rlc-card">
                    <div class="rlc-card-head">
                        <div class="rlc-card-icon"><i class="fas fa-cog"></i></div>
                        <div>
                            <h5 class="rlc-card-title">Paramètres d'envoi</h5>
                            <div class="rlc-card-sub">Délais, seuil et envoi automatique</div>
                        </div>
                    </div>
                    <div class="rlc-card-body">

                        <div class="rlc-alert rlc-alert--warn" x-show="nonConfigure" x-cloak>
                            <i class="fas fa-exclamation-triangle"></i>
                            <div>Aucun délai enregistré : le système de relances est inactif tant que les trois délais ne sont pas renseignés.</div>
                        </div>

                        <fieldset class="rlc-fieldset" @if(!$rlcPeutEnregistrer) disabled @endif>
                            <div class="rlc-field">
                                <label class="rlc-label">Jours de retard avant chaque relance</label>
                                <div class="rlc-delays">
                                    @foreach($rlcNiveaux as $rlcN => $rlcLabel)
                                        <div class="rlc-delay">
                                            <div class="rlc-delay-num rlc-delay-num--{{ $rlcN }}">{{ $rlcN }}</div>
                                            <label class="rlc-delay-label" for="rlc-delai-{{ $rlcN }}">{{ $rlcLabel }}</label>
                                            <div class="rlc-delay-input">
                                                <input type="number" id="rlc-delai-{{ $rlcN }}" min="1" max="365" inputmode="numeric" placeholder="—"
                                                       x-model.number="params.delai_niveau_{{ $rlcN }}"
                                                       x-bind:class="errors.delai_niveau_{{ $rlcN }} ? 'rlc-invalid' : ''">
                                                <span>jours</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="rlc-hint">Comptés après la date d'échéance, elle-même calculée depuis l'inscription et le délai de paiement configuré dans les frais.</div>
                            </div>

                            <div class="rlc-field">
                                <label class="rlc-label" for="rlc-montant-min">Dette minimale relancée (FCFA)</label>
                                <input type="number" class="rlc-input" id="rlc-montant-min" min="0" step="1" inputmode="numeric" placeholder="Ex. : 10 000"
                                       x-model.number="params.montant_minimum"
                                       x-bind:class="errors.montant_minimum ? 'rlc-invalid' : ''">
                                <div class="rlc-hint">En dessous de ce reste dû, aucun étudiant n'est relancé. « 0 » relance toute dette.</div>
                            </div>

                            <label class="rlc-toggle" for="rlc-auto">
                                <span class="rlc-switch">
                                    <input type="checkbox" id="rlc-auto" x-model="params.relances_automatiques">
                                    <span class="rlc-switch-track"><span class="rlc-switch-knob"></span></span>
                                </span>
                                <span>
                                    <span class="rlc-toggle-label">Relances automatiques</span>
                                    <span class="rlc-toggle-hint">Chaque jour, les échéances dépassées sont vérifiées et les relances envoyées selon les délais ci-dessus.</span>
                                </span>
                            </label>

                            <div class="rlc-field">
                                <label class="rlc-label" for="rlc-heure">Heure d'envoi automatique</label>
                                <input type="time" class="rlc-input" id="rlc-heure"
                                       x-model="params.heure_envoi"
                                       x-bind:class="errors.heure_envoi ? 'rlc-invalid' : ''">
                                <div class="rlc-hint">Heure quotidienne d'envoi quand l'automatisme est activé.</div>
                            </div>

                            <div class="rlc-alert rlc-alert--bad" x-show="premiereErreur" x-cloak>
                                <i class="fas fa-times-circle"></i>
                                <div x-text="premiereErreur"></div>
                            </div>
                        </fieldset>

                        @can('comptabilite.relances.send')
                            <button type="button" class="rlc-save rlc-save--full" x-bind:disabled="saving.params" x-on:click="enregistrerParametres()">
                                <i class="fas" x-bind:class="saving.params ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                                <span x-text="saving.params ? 'Enregistrement…' : 'Enregistrer les paramètres'"></span>
                            </button>
                        @else
                            <p class="rlc-readonly"><i class="fas fa-lock"></i> Lecture seule : la modification des paramètres demande le droit d'envoyer des relances.</p>
                        @endcan
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Aperçu (bureau) : fenêtre Alpine, fermée par la touche Échap ou le voile --}}
    <div class="rlc-overlay" x-show="preview.openDesktop" x-cloak x-transition.opacity
         x-on:keydown.escape.window="preview.openDesktop = false">
        <div class="rlc-overlay-scrim" x-on:click="preview.openDesktop = false"></div>
        <div class="rlc-dialog" role="dialog" aria-modal="true" aria-labelledby="rlc-dialog-title">
            <div class="rlc-dialog-head">
                <h5 id="rlc-dialog-title"><i class="fas fa-eye"></i> <span x-text="titreApercu()"></span></h5>
                <button type="button" class="rlc-dialog-close" x-on:click="preview.openDesktop = false" aria-label="Fermer"><i class="fas fa-times"></i></button>
            </div>
            <div class="rlc-dialog-body">
                <div class="rlc-loading" x-show="preview.loading"><i class="fas fa-spinner fa-spin"></i> Génération de l'aperçu…</div>
                <div x-show="!preview.loading" x-html="preview.html"></div>
            </div>
            <div class="rlc-dialog-foot">
                <button type="button" class="rlc-ghost" x-on:click="preview.openDesktop = false">Fermer</button>
            </div>
        </div>
    </div>

    {{-- Toast de bureau ; sous 992px avec le shell, le toast sombre du socle prend le relais --}}
    <div class="rlc-toast" x-show="toast" x-cloak x-transition x-bind:class="'rlc-toast--' + toastType">
        <i class="fas" x-bind:class="toastType === 'error' ? 'fa-times-circle' : (toastType === 'warning' ? 'fa-exclamation-triangle' : 'fa-check-circle')"></i>
        <span x-text="toast"></span>
    </div>
</div>

@if($rlcShell)
{{-- ============================ ÉCRAN MOBILE (shell m-*) ============================ --}}
{{-- La barre d'onglets et la navbar mobile sont rendues par le layout. --}}
<div class="m-only-mobile m-screen rlcm-screen">
    <x-m.appbar title="Configuration des relances"
                :sub="$rlcEcoleNom"
                :back="route('esbtp.comptabilite.relances.index')" />

    <div class="m-body">
        <div class="m-seg" role="tablist" aria-label="Section">
            <button type="button" role="tab" x-bind:class="mSeg === 'modeles' ? 'on' : ''"
                    x-bind:aria-selected="mSeg === 'modeles' ? 'true' : 'false'" x-on:click="mSeg = 'modeles'">Modèles</button>
            <button type="button" role="tab" x-bind:class="mSeg === 'envoi' ? 'on' : ''"
                    x-bind:aria-selected="mSeg === 'envoi' ? 'true' : 'false'" x-on:click="mSeg = 'envoi'">Envoi</button>
            @can('comptabilite.relances.send')
                <button type="button" role="tab" x-bind:class="mSeg === 'planification' ? 'on' : ''"
                        x-bind:aria-selected="mSeg === 'planification' ? 'true' : 'false'" x-on:click="mSeg = 'planification'">Planification</button>
            @endcan
        </div>

        {{-- ── Modèles ── --}}
        <div class="rlcm-pane" x-show="mSeg === 'modeles'" x-cloak>
            <div class="m-seg rlcm-seg-canal" role="tablist" aria-label="Canal">
                @foreach($rlcCanaux as $rlcKey => $rlcCanal)
                    <button type="button" role="tab" x-bind:class="mCanal === '{{ $rlcKey }}' ? 'on' : ''"
                            x-bind:aria-selected="mCanal === '{{ $rlcKey }}' ? 'true' : 'false'"
                            x-on:click="mCanal = '{{ $rlcKey }}'">{{ $rlcCanal['label'] }}</button>
                @endforeach
            </div>

            <div class="m-list one">
                @foreach($rlcNiveaux as $rlcN => $rlcLabel)
                    <button type="button" class="m-row rlcm-tpl" x-on:click="ouvrirEdition(mCanal, {{ $rlcN }})">
                        <div class="av rlcm-av rlcm-av--{{ $rlcN }}" aria-hidden="true">{{ $rlcN }}</div>
                        <div class="tt">
                            <b>{{ $rlcLabel }}</b>
                            <span x-text="extrait(mCanal, {{ $rlcN }}) || 'Aucun texte pour ce niveau'"></span>
                        </div>
                        <div class="tr">
                            <span class="m-chip" x-bind:class="extrait(mCanal, {{ $rlcN }}) ? 'ok' : 'warn'"
                                  x-text="extrait(mCanal, {{ $rlcN }}) ? 'Prêt' : 'À écrire'"></span>
                        </div>
                    </button>
                @endforeach
            </div>
            <p class="rlcm-hint">Touchez un niveau pour modifier son message, voir l'aperçu et enregistrer.</p>
        </div>

        {{-- ── Envoi ── --}}
        <div class="rlcm-pane" x-show="mSeg === 'envoi'" x-cloak>
            <div class="rlcm-warn" x-show="nonConfigure" x-cloak>
                <x-m.icon name="alert" />
                <span>Aucun délai enregistré : les relances ne partent pas tant que les trois délais ne sont pas renseignés.</span>
            </div>

            <fieldset class="rlcm-fieldset" @if(!$rlcPeutEnregistrer) disabled @endif>
                <div class="rlcm-block">
                    <div class="rlcm-block-title">Jours de retard avant chaque relance</div>
                    <div class="rlcm-notes">
                        @foreach($rlcNiveaux as $rlcN => $rlcLabel)
                            <label class="m-note" for="rlcm-delai-{{ $rlcN }}">
                                <div class="av" aria-hidden="true">{{ $rlcN }}</div>
                                <div>
                                    <div class="nm">{{ $rlcLabel }}</div>
                                    <div class="mt">jours après l'échéance</div>
                                </div>
                                <input type="number" class="in rlcm-in-num" id="rlcm-delai-{{ $rlcN }}" min="1" max="365" inputmode="numeric" placeholder="—"
                                       x-model.number="params.delai_niveau_{{ $rlcN }}"
                                       x-bind:class="errors.delai_niveau_{{ $rlcN }} ? 'rlcm-invalid' : ''">
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="m-field">
                    <label for="rlcm-montant-min">Dette minimale relancée (FCFA)</label>
                    <input type="number" class="m-in" id="rlcm-montant-min" min="0" step="1" inputmode="numeric" placeholder="Ex. : 10 000"
                           x-model.number="params.montant_minimum"
                           x-bind:class="errors.montant_minimum ? 'rlcm-invalid' : ''">
                    <span class="rlcm-hint">« 0 » relance toute dette, si petite soit-elle.</span>
                </div>

                <label class="rlcm-switch-row" for="rlcm-auto">
                    <span>
                        <b>Relances automatiques</b>
                        <span>Vérification quotidienne des échéances dépassées</span>
                    </span>
                    <span class="rlc-switch">
                        <input type="checkbox" id="rlcm-auto" x-model="params.relances_automatiques">
                        <span class="rlc-switch-track"><span class="rlc-switch-knob"></span></span>
                    </span>
                </label>

                <div class="m-field">
                    <label for="rlcm-heure">Heure d'envoi automatique</label>
                    <input type="time" class="m-in" id="rlcm-heure" x-model="params.heure_envoi"
                           x-bind:class="errors.heure_envoi ? 'rlcm-invalid' : ''">
                </div>

                <div class="rlcm-err" x-show="premiereErreur" x-cloak x-text="premiereErreur" role="alert"></div>
            </fieldset>

            @cannot('comptabilite.relances.send')
                <p class="rlcm-hint"><x-m.icon name="lock" /> Lecture seule : la modification demande le droit d'envoyer des relances.</p>
            @endcannot
        </div>

        {{-- ── Planification (pas-à-pas) ── --}}
        @can('comptabilite.relances.send')
            <div class="rlcm-pane" x-show="mSeg === 'planification'" x-cloak>
                @include('esbtp.comptabilite.relances.partials._planification-mobile')
            </div>
        @endcan
    </div>

    @can('comptabilite.relances.send')
        <x-m.actionbar x-show="mSeg === 'envoi'" x-cloak>
            <button type="button" class="m-btn p" x-bind:disabled="saving.params" x-on:click="enregistrerParametres()">
                <span x-show="!saving.params"><x-m.icon name="check" /></span>
                <span x-text="saving.params ? 'Enregistrement…' : 'Enregistrer les paramètres'"></span>
            </button>
        </x-m.actionbar>
    @endcan

    {{-- Feuille : édition d'un modèle --}}
    <x-m.sheet id="rlc-edit" title="Modifier le modèle">
        <template x-if="edit.type">
            <div class="rlcm-edit">
                <div class="rlcm-edit-head">
                    <span class="m-chip info" x-text="libelleCanal(edit.type)"></span>
                    <span class="m-chip mute" x-text="'Niveau ' + edit.niveau + ' · ' + cfg.niveaux[edit.niveau]"></span>
                    <span class="m-chip" x-show="edit.type === 'sms'"
                          x-bind:class="smsLen(edit.niveau) > cfg.smsMax ? 'bad' : 'mute'"
                          x-text="smsLen(edit.niveau) + '/' + cfg.smsMax"></span>
                </div>

                <div class="m-field" x-show="edit.type === 'email'">
                    <label for="rlcm-edit-sujet">Sujet</label>
                    <input type="text" class="m-in" id="rlcm-edit-sujet" placeholder="Ex. : Rappel de paiement"
                           x-model="templates[edit.type][edit.niveau].sujet"
                           @if(!$rlcPeutEnregistrer) readonly @endif>
                </div>

                <div class="m-field">
                    <label for="rlcm-edit-contenu">Message</label>
                    <textarea class="m-in ta rlcm-ta" id="rlcm-edit-contenu" rows="7" x-ref="mEditArea"
                              placeholder="Texte du modèle, avec les variables entre accolades…"
                              x-model="templates[edit.type][edit.niveau].contenu"
                              x-on:focus="setFocus($event.target)"
                              @if(!$rlcPeutEnregistrer) readonly @endif></textarea>
                </div>

                <div class="rlcm-vars">
                    <div class="rlcm-block-title">Variables disponibles</div>
                    <div class="rlcm-vars-scroll">
                        @foreach($rlcVariables as $rlcGroupe => $rlcListe)
                            @foreach($rlcListe as $rlcVar)
                                <button type="button" class="rlc-var" x-on:click="insererVariable('{{ $rlcVar }}', $refs.mEditArea)">{{ $rlcVar }}</button>
                            @endforeach
                        @endforeach
                    </div>
                </div>

                <div class="rlcm-sheet-acts">
                    <button type="button" class="m-btn g" x-on:click="apercu(edit.type, edit.niveau)">
                        <x-m.icon name="search" /> Aperçu
                    </button>
                    @can('comptabilite.relances.send')
                        <button type="button" class="m-btn p" x-bind:disabled="saving[edit.type]"
                                x-on:click="enregistrerTemplates(edit.type).then(function (ok) { if (ok) { hide(); } })">
                            <span x-show="!saving[edit.type]"><x-m.icon name="check" /></span>
                            <span x-text="saving[edit.type] ? 'Enregistrement…' : 'Enregistrer'"></span>
                        </button>
                    @endcan
                </div>
            </div>
        </template>
    </x-m.sheet>

    {{-- Feuille : aperçu du modèle --}}
    <x-m.sheet id="rlc-apercu" title="Aperçu">
        <div class="m-skel" x-show="preview.loading"><i></i><i></i></div>
        <div class="rlcm-apercu" x-show="!preview.loading" x-html="preview.html"></div>
        <button type="button" class="m-btn g" x-on:click="hide()">Fermer</button>
    </x-m.sheet>
</div>
@endif

</div>
@endsection

@push('styles')
<style>
/* ── Configuration des relances (bureau) — namespace rlc-* ─────────────── */
.rlc-page { --rlc-primary: #0453cb; --rlc-accent: #3b7ddb; --rlc-soft: #5e91de; --rlc-text: #1e293b; --rlc-muted: #64748b; --rlc-subtle: #94a3b8; --rlc-border: #e2e8f0; --rlc-surface: #f8fafc; }
.rlc-header .header-actions { flex-wrap: wrap; }

.rlc-strip { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 1.5rem; }
.rlc-strip-card { background: #fff; border: 1px solid var(--rlc-border); border-radius: 14px; padding: 1rem 1.1rem; display: flex; align-items: center; gap: .85rem; box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); }
.rlc-strip-num { width: 42px; height: 42px; border-radius: 12px; background: linear-gradient(135deg, var(--rlc-primary), var(--rlc-accent)); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1rem; flex-shrink: 0; }
.rlc-strip-value { font-size: 1.35rem; font-weight: 800; color: var(--rlc-text); line-height: 1.1; font-variant-numeric: tabular-nums; }
.rlc-strip-label { font-size: .72rem; color: var(--rlc-muted); margin-top: .15rem; font-weight: 600; }

.rlc-card { background: #fff; border: 1px solid var(--rlc-border); border-radius: 14px; box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); margin-bottom: 1.25rem; }
.rlc-card-head { display: flex; align-items: center; gap: .75rem; padding: 1.1rem 1.4rem; border-bottom: 1px solid #f1f5f9; }
.rlc-card-icon { width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, var(--rlc-primary), var(--rlc-accent)); display: flex; align-items: center; justify-content: center; color: #fff; font-size: .95rem; flex-shrink: 0; }
.rlc-card-title { margin: 0; font-size: 1rem; font-weight: 700; color: var(--rlc-text); }
.rlc-card-sub { font-size: .76rem; color: var(--rlc-muted); margin-top: .1rem; }
.rlc-card-body { padding: 1.4rem; }
.rlc-card-body--tight { padding: 1rem 1.4rem 1.2rem; }

.rlc-tabs { display: inline-flex; background: var(--rlc-surface); border: 1px solid var(--rlc-border); border-radius: 12px; padding: 4px; gap: 4px; margin-bottom: 1.1rem; }
.rlc-tab { border: 0; background: transparent; color: var(--rlc-muted); font-weight: 600; font-size: .82rem; padding: .5rem .95rem; border-radius: 9px; cursor: pointer; display: inline-flex; align-items: center; gap: .4rem; transition: background .15s, color .15s; }
.rlc-tab:hover { color: var(--rlc-primary); }
.rlc-tab--on { background: #fff; color: var(--rlc-primary); box-shadow: 0 1px 3px rgba(15,23,42,.1); }
.rlc-pane-hint { font-size: .78rem; color: var(--rlc-muted); margin: 0 0 1rem; }
.rlc-pane-hint i { color: var(--rlc-accent); margin-right: .3rem; }

.rlc-tpl { border: 1px solid var(--rlc-border); border-radius: 12px; margin-bottom: 1rem; overflow: hidden; }
.rlc-tpl-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .7rem 1rem; background: var(--rlc-surface); border-bottom: 1px solid var(--rlc-border); flex-wrap: wrap; }
.rlc-tpl-tools { display: flex; align-items: center; gap: .6rem; }
.rlc-tpl-body { padding: 1rem; }
.rlc-lvl { display: inline-flex; align-items: center; gap: .45rem; font-size: .76rem; font-weight: 700; padding: .3rem .7rem; border-radius: 999px; border: 1px solid rgba(4,83,203,.18); }
.rlc-lvl-dot { width: 8px; height: 8px; border-radius: 50%; background: currentColor; }
.rlc-lvl--1 { background: rgba(94,145,222,.10); color: var(--rlc-soft); }
.rlc-lvl--2 { background: rgba(59,125,219,.12); color: var(--rlc-accent); }
.rlc-lvl--3 { background: rgba(4,83,203,.14); color: var(--rlc-primary); }
.rlc-count { font-size: .72rem; color: var(--rlc-subtle); font-variant-numeric: tabular-nums; font-weight: 600; }
.rlc-count--over { color: #b42318; }

.rlc-field { margin-bottom: .9rem; }
.rlc-field:last-child { margin-bottom: 0; }
.rlc-label { display: block; font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #475569; margin-bottom: .4rem; }
.rlc-input { width: 100%; border: 1.5px solid #dde5f0; border-radius: 10px; padding: .6rem .85rem; font-size: .88rem; color: var(--rlc-text); background: #fff; transition: border-color .2s, box-shadow .2s; }
.rlc-input:focus { outline: none; border-color: var(--rlc-primary); box-shadow: 0 0 0 3px rgba(4,83,203,.1); }
.rlc-input[readonly] { background: var(--rlc-surface); color: #475569; }
.rlc-textarea { resize: vertical; line-height: 1.55; font-family: inherit; }
.rlc-invalid { border-color: #b42318 !important; }
.rlc-hint { font-size: .74rem; color: var(--rlc-subtle); margin-top: .4rem; line-height: 1.5; }

.rlc-ghost { border: 1px solid rgba(4,83,203,.25); background: #fff; color: var(--rlc-primary); font-weight: 600; font-size: .78rem; padding: .4rem .8rem; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: .35rem; transition: background .15s; }
.rlc-ghost:hover { background: rgba(4,83,203,.06); }
.rlc-actions { display: flex; justify-content: flex-end; margin-top: .25rem; }
.rlc-save { border: 0; background: linear-gradient(135deg, var(--rlc-primary), var(--rlc-accent)); color: #fff; font-weight: 700; font-size: .85rem; padding: .65rem 1.3rem; border-radius: 10px; cursor: pointer; display: inline-flex; align-items: center; gap: .5rem; box-shadow: 0 6px 18px rgba(4,83,203,.22); transition: opacity .15s, box-shadow .15s; }
.rlc-save:hover:not(:disabled) { box-shadow: 0 8px 24px rgba(4,83,203,.3); }
.rlc-save:disabled { opacity: .65; cursor: wait; }
.rlc-save--full { width: 100%; justify-content: center; margin-top: 1rem; }
.rlc-readonly { font-size: .78rem; color: var(--rlc-muted); background: var(--rlc-surface); border: 1px dashed var(--rlc-border); border-radius: 10px; padding: .65rem .9rem; margin: .5rem 0 0; }
.rlc-readonly i { color: var(--rlc-subtle); margin-right: .35rem; }
.rlc-fieldset { border: 0; padding: 0; margin: 0; min-width: 0; }
.rlc-fieldset:disabled { opacity: .7; }

.rlc-var-group { margin-bottom: .9rem; }
.rlc-var-group:last-child { margin-bottom: 0; }
.rlc-var-title { font-size: .66rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: var(--rlc-subtle); margin-bottom: .4rem; }
.rlc-var-list { display: flex; flex-wrap: wrap; gap: .3rem; }
.rlc-var { border: 1px solid #c7d8f8; background: #eef3ff; color: var(--rlc-primary); padding: .25rem .6rem; border-radius: 6px; font-size: .72rem; font-weight: 600; font-family: 'SF Mono', Menlo, Consolas, monospace; cursor: pointer; transition: background .15s, color .15s; }
.rlc-var:hover { background: var(--rlc-primary); color: #fff; border-color: var(--rlc-primary); }

.rlc-delays { border: 1px solid var(--rlc-border); border-radius: 12px; padding: .2rem .9rem; }
.rlc-delay { display: flex; align-items: center; gap: .85rem; padding: .8rem 0; border-bottom: 1px solid #f1f5f9; }
.rlc-delay:last-child { border-bottom: 0; }
.rlc-delay-num { width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .75rem; font-weight: 800; flex-shrink: 0; color: #fff; }
.rlc-delay-num--1 { background: var(--rlc-soft); }
.rlc-delay-num--2 { background: var(--rlc-accent); }
.rlc-delay-num--3 { background: var(--rlc-primary); }
.rlc-delay-label { flex: 1; font-size: .83rem; font-weight: 600; color: #475569; margin: 0; }
.rlc-delay-input { display: flex; align-items: center; gap: .4rem; }
.rlc-delay-input input { width: 74px; border: 1.5px solid #dde5f0; border-radius: 9px; padding: .45rem .5rem; text-align: center; font-weight: 700; font-size: .88rem; color: var(--rlc-text); }
.rlc-delay-input input:focus { outline: none; border-color: var(--rlc-primary); box-shadow: 0 0 0 3px rgba(4,83,203,.1); }
.rlc-delay-input span { font-size: .72rem; color: var(--rlc-subtle); font-weight: 600; }

.rlc-toggle { display: flex; align-items: flex-start; gap: .85rem; padding: .9rem 1rem; background: var(--rlc-surface); border: 1px solid var(--rlc-border); border-radius: 12px; cursor: pointer; margin: 0 0 .9rem; }
.rlc-toggle-label { display: block; font-size: .86rem; font-weight: 700; color: var(--rlc-text); }
.rlc-toggle-hint { display: block; font-size: .73rem; color: var(--rlc-subtle); margin-top: .15rem; line-height: 1.45; }
.rlc-switch { position: relative; display: inline-block; width: 44px; height: 26px; flex-shrink: 0; margin-top: 2px; }
.rlc-switch input { position: absolute; opacity: 0; width: 1px; height: 1px; pointer-events: none; }
.rlc-switch-track { position: absolute; inset: 0; border-radius: 999px; background: #cbd5e1; transition: background .2s; }
.rlc-switch-knob { position: absolute; top: 3px; left: 3px; width: 20px; height: 20px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(15,23,42,.25); transition: transform .2s; }
.rlc-switch input:checked + .rlc-switch-track { background: var(--rlc-primary); }
.rlc-switch input:checked + .rlc-switch-track .rlc-switch-knob { transform: translateX(18px); }
.rlc-switch input:focus-visible + .rlc-switch-track { box-shadow: 0 0 0 3px rgba(4,83,203,.2); }

.rlc-alert { display: flex; gap: .65rem; align-items: flex-start; border-radius: 12px; padding: .85rem 1rem; font-size: .82rem; margin-bottom: 1rem; line-height: 1.45; }
.rlc-alert i { margin-top: .15rem; flex-shrink: 0; }
.rlc-alert--warn { background: #fff8e6; border: 1px solid #f5d48a; color: #7a4b00; }
.rlc-alert--bad { background: #fdecea; border: 1px solid #f5b5ad; color: #a12016; }

@media (min-width: 992px) { .rlc-sticky { position: sticky; top: 80px; } }

/* Aperçu (fenêtre de bureau) */
.rlc-overlay { position: fixed; inset: 0; z-index: 1085; display: flex; align-items: center; justify-content: center; padding: 1rem; }
.rlc-overlay-scrim { position: absolute; inset: 0; background: rgba(15,23,42,.45); }
.rlc-dialog { position: relative; width: min(760px, 100%); max-height: 90vh; background: #fff; border-radius: 18px; box-shadow: 0 30px 80px rgba(15,23,42,.35); display: flex; flex-direction: column; overflow: hidden; }
.rlc-dialog-head { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.4rem; background: linear-gradient(135deg, #0a3d8f, var(--rlc-primary)); color: #fff; }
.rlc-dialog-head h5 { margin: 0; font-size: .98rem; font-weight: 700; display: flex; align-items: center; gap: .5rem; }
.rlc-dialog-close { border: 0; background: rgba(255,255,255,.15); color: #fff; width: 34px; height: 34px; border-radius: 9px; cursor: pointer; }
.rlc-dialog-body { padding: 1.4rem; overflow-y: auto; }
.rlc-dialog-foot { display: flex; justify-content: flex-end; padding: .85rem 1.4rem; border-top: 1px solid var(--rlc-border); }
.rlc-loading { text-align: center; color: var(--rlc-muted); padding: 2rem 0; font-size: .88rem; }
.rlc-loading i { color: var(--rlc-primary); margin-right: .4rem; }

/* Aperçu (fragment renvoyé par config/preview, bureau et mobile) */
.rlc-pv { border: 1px solid var(--rlc-border, #e2e8f0); border-radius: 14px; overflow: hidden; background: #fff; }
.rlc-pv-head { display: flex; align-items: center; gap: .6rem; padding: .8rem 1.1rem; background: linear-gradient(135deg, #0a3d8f, #0453cb); color: #fff; font-weight: 700; font-size: .85rem; }
.rlc-pv-body { padding: 1.25rem; font-size: .9rem; line-height: 1.65; color: #1e293b; min-height: 120px; white-space: normal; }
.rlc-pv-body--sms { background: #f4f6fb; }
.rlc-pv-bubble { position: relative; max-width: 82%; background: #fff; border-radius: 16px; padding: .85rem 1rem; box-shadow: 0 1px 3px rgba(15,23,42,.1); white-space: pre-wrap; word-break: break-word; }
.rlc-pv-body--courrier { font-family: Georgia, 'Times New Roman', serif; }
.rlc-pv-letterhead { text-align: center; margin-bottom: 1.25rem; display: grid; gap: .15rem; }
.rlc-pv-letterhead b { font-size: 1rem; color: #0f172a; }
.rlc-pv-letterhead span { font-size: .8rem; color: #64748b; }
.rlc-pv-letter { text-align: justify; }
.rlc-pv-foot { padding: .7rem 1.1rem; border-top: 1px solid #eef2f7; font-size: .74rem; color: #64748b; display: flex; gap: .4rem; align-items: center; }
.rlc-pv-foot i { color: #3b7ddb; }

/* Toast de bureau */
.rlc-toast { position: fixed; top: 20px; right: 20px; z-index: 1300; min-width: 280px; max-width: 420px; background: #fff; border: 1.5px solid var(--rlc-border); border-radius: 14px; padding: .9rem 1.1rem; display: flex; gap: .65rem; align-items: flex-start; font-size: .84rem; font-weight: 600; color: var(--rlc-text); box-shadow: 0 10px 35px rgba(15,23,42,.14); }
.rlc-toast i { margin-top: .1rem; color: var(--rlc-primary); }
.rlc-toast--success { border-color: rgba(16,185,129,.35); }
.rlc-toast--success i { color: #047857; }
.rlc-toast--error { border-color: rgba(220,38,38,.35); }
.rlc-toast--error i { color: #b42318; }
.rlc-toast--warning { border-color: rgba(245,158,11,.4); }
.rlc-toast--warning i { color: #b45309; }

@media (max-width: 1199.98px) { .rlc-strip { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 575.98px) { .rlc-strip { grid-template-columns: 1fr; } .rlc-card-body { padding: 1rem; } }

/* ── Écran mobile — namespace rlcm-* (sur le socle m-*) ────────────────── */
.rlcm-screen { font-family: var(--m-font); }
.rlcm-pane { display: grid; gap: 14px; }
.rlcm-seg-canal { background: #fff; border: 1px solid #e6eaf2; }
.rlcm-tpl { width: 100%; text-align: left; font: inherit; cursor: pointer; -webkit-tap-highlight-color: transparent; border: 1px solid #e6eaf2; }
.rlcm-tpl .tt span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; }
.rlcm-av { font-weight: 800; color: #fff; }
.rlcm-av--1 { background: #5e91de; }
.rlcm-av--2 { background: #3b7ddb; }
.rlcm-av--3 { background: #0453cb; }
.rlcm-hint { font-size: 12.5px; color: #64748b; margin: 0; display: flex; gap: 6px; align-items: center; line-height: 1.45; }
.rlcm-hint svg { width: 16px; height: 16px; flex: 0 0 16px; }
.rlcm-warn { background: #fff3df; color: #8a5200; border-radius: 12px; padding: 10px 12px; font-size: 13px; font-weight: 600; display: flex; gap: 8px; align-items: flex-start; line-height: 1.4; }
.rlcm-warn svg { width: 18px; height: 18px; flex: 0 0 18px; margin-top: 1px; }
.rlcm-err { background: #fdecea; color: #a12016; border-radius: 12px; padding: 10px 12px; font-size: 13px; font-weight: 600; line-height: 1.4; }
.rlcm-fieldset { border: 0; padding: 0; margin: 0; min-width: 0; display: grid; gap: 14px; }
.rlcm-fieldset:disabled { opacity: .7; }
.rlcm-block { display: grid; gap: 8px; }
.rlcm-block-title { font-size: 12px; font-weight: 700; color: #475569; letter-spacing: .04em; text-transform: uppercase; }
.rlcm-notes { display: grid; gap: 8px; }
.rlcm-notes .m-note { cursor: pointer; }
.rlcm-in-num { font: inherit; font-size: 18px; font-weight: 800; text-align: center; padding: 0; -moz-appearance: textfield; }
.rlcm-in-num::-webkit-outer-spin-button, .rlcm-in-num::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.rlcm-in-num:focus { outline: none; box-shadow: 0 0 0 3px rgba(4,83,203,.12); }
.rlcm-invalid { border-color: #a12016 !important; }
.rlcm-switch-row { display: grid; grid-template-columns: 1fr auto; gap: 12px; align-items: center; background: #fff; border: 1.5px solid #e6eaf2; border-radius: 14px; padding: 12px 14px; min-height: 56px; cursor: pointer; }
.rlcm-switch-row > span:first-child b { display: block; font-weight: 600; font-size: 14.5px; color: #0f172a; }
.rlcm-switch-row > span:first-child span { font-size: 12px; color: #64748b; display: block; }
.rlcm-switch-row .rlc-switch { width: 52px; height: 32px; margin: 0; }
.rlcm-switch-row .rlc-switch-knob { width: 26px; height: 26px; }
.rlcm-switch-row .rlc-switch input:checked + .rlc-switch-track .rlc-switch-knob { transform: translateX(20px); }
.rlcm-edit { display: grid; gap: 14px; padding-bottom: 4px; }
.rlcm-edit-head { display: flex; gap: 6px; flex-wrap: wrap; }
.rlcm-ta { min-height: 150px; line-height: 1.5; font: inherit; font-size: 15px; }
.rlcm-vars { display: grid; gap: 8px; }
.rlcm-vars-scroll { display: flex; gap: 6px; overflow-x: auto; padding-bottom: 4px; -webkit-overflow-scrolling: touch; scrollbar-width: none; }
.rlcm-vars-scroll::-webkit-scrollbar { display: none; }
.rlcm-vars-scroll .rlc-var { flex: 0 0 auto; min-height: 36px; font-size: 12.5px; padding: 0 10px; }
.rlcm-sheet-acts { display: grid; gap: 8px; }
.rlcm-apercu { display: grid; gap: 8px; }
</style>
@endpush

@push('scripts')
<script>
if (typeof window.rlcConfig !== 'function') {
window.rlcConfig = function (cfg) {
    return {
        cfg: cfg,
        templates: cfg.templates,
        params: cfg.params,

        canal: 'email',           /* onglet de bureau */
        mSeg: 'modeles',          /* segment mobile */
        mCanal: 'email',          /* canal affiché en mobile */
        edit: { type: null, niveau: null },
        preview: { openDesktop: false, loading: false, html: '', type: null, niveau: null },
        saving: { email: false, sms: false, courrier: false, params: false },
        errors: {},
        focused: null,
        toast: null,
        toastType: 'info',

        get nonConfigure() {
            return this.estVide(this.params.delai_niveau_1)
                && this.estVide(this.params.delai_niveau_2)
                && this.estVide(this.params.delai_niveau_3);
        },

        get premiereErreur() {
            var cles = Object.keys(this.errors || {});
            if (!cles.length) { return ''; }
            var v = this.errors[cles[0]];
            return Array.isArray(v) ? v[0] : String(v);
        },

        estVide(v) { return v === null || v === undefined || v === ''; },

        afficherDelai(n) {
            var v = this.params['delai_niveau_' + n];
            return this.estVide(v) ? '—' : v + ' j';
        },

        libelleCanal(type) {
            return { email: 'E-mail', sms: 'SMS', courrier: 'Courrier' }[type] || type;
        },

        titreApercu() {
            if (!this.preview.type) { return 'Aperçu du modèle'; }
            return 'Aperçu · ' + this.libelleCanal(this.preview.type) + ' · niveau ' + this.preview.niveau;
        },

        smsLen(n) {
            var t = this.templates.sms && this.templates.sms[n] ? this.templates.sms[n].contenu : '';
            return (t || '').length;
        },

        extrait(type, n) {
            var t = this.templates[type] && this.templates[type][n] ? this.templates[type][n].contenu : '';
            var c = (t || '').replace(/\s+/g, ' ').trim();
            if (!c) { return ''; }
            return c.length > 90 ? c.slice(0, 90) + '…' : c;
        },

        setFocus(el) { this.focused = el; },

        /* Insère la variable au curseur du champ visé (ou du dernier champ actif)
           et prévient Alpine par un évènement input : le modèle suit. */
        insererVariable(variable, cible) {
            var el = cible || this.focused;
            if (!el) {
                this.notifier('Cliquez d\'abord dans un champ de texte.', 'warning');
                return;
            }
            var debut = el.selectionStart !== null && el.selectionStart !== undefined ? el.selectionStart : el.value.length;
            var fin = el.selectionEnd !== null && el.selectionEnd !== undefined ? el.selectionEnd : debut;
            el.value = el.value.substring(0, debut) + variable + el.value.substring(fin);
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.focus();
            var pos = debut + variable.length;
            try { el.setSelectionRange(pos, pos); } catch (e) { /* champ sans sélection */ }
        },

        estShellMobile() {
            return document.body.classList.contains('has-m-shell')
                && window.matchMedia && window.matchMedia('(max-width: 991.98px)').matches;
        },

        ouvrirFeuille(id) {
            window.dispatchEvent(new CustomEvent('m-sheet:open', { detail: { id: id } }));
        },

        ouvrirEdition(type, niveau) {
            this.edit = { type: type, niveau: niveau };
            this.ouvrirFeuille('rlc-edit');
        },

        /* Sous 992px avec le shell : toast sombre du socle ; sinon le toast de bureau. */
        notifier(message, type) {
            type = type || 'info';
            if (this.estShellMobile()) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: type, message: message } }));
                return;
            }
            this.toast = message;
            this.toastType = type;
            clearTimeout(this._toastTimer);
            this._toastTimer = setTimeout(() => { this.toast = null; }, 3800);
        },

        async requete(url, body, accept) {
            var reponse = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.cfg.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': accept || 'application/json',
                },
                body: JSON.stringify(body),
            });
            return reponse;
        },

        async lireErreur(reponse) {
            var data = null;
            try { data = await reponse.json(); } catch (e) { data = null; }
            if (data && data.errors) { this.errors = data.errors; }
            if (data && data.message) { return data.message; }
            if (reponse.status === 403) { return 'Vous n\'avez pas le droit d\'effectuer cette action.'; }
            return 'Erreur ' + reponse.status + ' : la demande n\'a pas abouti.';
        },

        /* Enregistre les trois niveaux d'un canal (seuls les niveaux remplis partent :
           le serveur refuse un contenu vide). Renvoie true si tout s'est bien passé. */
        async enregistrerTemplates(type) {
            if (!this.cfg.peutEnregistrer || this.saving[type]) { return false; }
            var liste = [];
            [1, 2, 3].forEach((n) => {
                var t = this.templates[type][n] || {};
                if ((t.contenu || '').trim() === '') { return; }
                var item = { niveau: n, contenu: t.contenu };
                if (type === 'email') { item.sujet = t.sujet || ''; }
                liste.push(item);
            });
            if (!liste.length) {
                this.notifier('Saisissez au moins un modèle ' + this.libelleCanal(type) + ' avant d\'enregistrer.', 'warning');
                return false;
            }
            this.saving[type] = true;
            try {
                var reponse = await this.requete(this.cfg.urls.templates, { type: type, templates: liste });
                if (!reponse.ok) {
                    this.notifier(await this.lireErreur(reponse), 'error');
                    return false;
                }
                var data = await reponse.json();
                if (data && data.success === false) {
                    this.notifier(data.message || 'Enregistrement refusé.', 'error');
                    return false;
                }
                this.notifier((data && data.message) || 'Modèles enregistrés.', 'success');
                return true;
            } catch (e) {
                this.notifier('Connexion impossible : les modèles n\'ont pas été enregistrés.', 'error');
                return false;
            } finally {
                this.saving[type] = false;
            }
        },

        async enregistrerParametres() {
            if (!this.cfg.peutEnregistrer || this.saving.params) { return false; }
            this.errors = {};
            this.saving.params = true;
            try {
                var reponse = await this.requete(this.cfg.urls.parametres, {
                    delai_niveau_1: this.params.delai_niveau_1,
                    delai_niveau_2: this.params.delai_niveau_2,
                    delai_niveau_3: this.params.delai_niveau_3,
                    montant_minimum: this.params.montant_minimum,
                    heure_envoi: this.params.heure_envoi,
                    relances_automatiques: !!this.params.relances_automatiques,
                });
                if (!reponse.ok) {
                    this.notifier(await this.lireErreur(reponse), 'error');
                    return false;
                }
                var data = await reponse.json();
                this.notifier((data && data.message) || 'Paramètres enregistrés.', 'success');
                return true;
            } catch (e) {
                this.notifier('Connexion impossible : les paramètres n\'ont pas été enregistrés.', 'error');
                return false;
            } finally {
                this.saving.params = false;
            }
        },

        /* Aperçu serveur (HTML) : fenêtre sur bureau, feuille sur mobile. */
        async apercu(type, niveau) {
            var t = this.templates[type][niveau] || {};
            if ((t.contenu || '').trim() === '') {
                this.notifier('Saisissez un texte avant de demander l\'aperçu.', 'warning');
                return;
            }
            this.preview.type = type;
            this.preview.niveau = niveau;
            this.preview.loading = true;
            this.preview.html = '';
            if (this.estShellMobile()) { this.ouvrirFeuille('rlc-apercu'); } else { this.preview.openDesktop = true; }
            try {
                var reponse = await this.requete(this.cfg.urls.preview, {
                    type: type, niveau: niveau, contenu: t.contenu, sujet: t.sujet || null,
                }, 'text/html');
                if (!reponse.ok) {
                    this.preview.html = '<div class="rlcm-err">' + this.echapper(await this.lireErreur(reponse)) + '</div>';
                    return;
                }
                this.preview.html = await reponse.text();
            } catch (e) {
                this.preview.html = '<div class="rlcm-err">Connexion impossible : aperçu indisponible.</div>';
            } finally {
                this.preview.loading = false;
            }
        },

        echapper(s) {
            var d = document.createElement('div');
            d.textContent = String(s);
            return d.innerHTML;
        },
    };
};
}
</script>
@endpush
