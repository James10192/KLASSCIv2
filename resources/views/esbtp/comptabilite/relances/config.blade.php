@extends('layouts.app')

@section('title', 'Configuration des Relances')

@push('styles')
<style>
/* ── HERO ── */
.cfg-hero {
    background: linear-gradient(135deg, #0f1e3d 0%, #0453cb 60%, #1a3a6e 100%);
    padding: 2rem 2.5rem 1.6rem;
    margin: -1.5rem -1.5rem 2rem;
    border-radius: 0 0 20px 20px;
    position: relative;
    overflow: hidden;
}
.cfg-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse 70% 80% at 85% 50%, rgba(94,145,222,.18) 0%, transparent 70%);
    pointer-events: none;
}
.cfg-hero-breadcrumb {
    font-size: .72rem;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: rgba(255,255,255,.5);
    margin-bottom: .5rem;
    display: flex;
    align-items: center;
    gap: .4rem;
}
.cfg-hero-breadcrumb a { color: rgba(255,255,255,.55); text-decoration: none; }
.cfg-hero-breadcrumb a:hover { color: #fff; }
.cfg-hero-title {
    font-size: 1.55rem;
    font-weight: 700;
    color: #fff;
    margin: 0 0 .3rem;
    letter-spacing: -.01em;
}
.cfg-hero-sub {
    font-size: .83rem;
    color: rgba(255,255,255,.6);
    margin: 0;
}
.cfg-hero-back {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.2);
    color: #fff;
    font-size: .8rem;
    font-weight: 500;
    padding: .45rem 1rem;
    border-radius: 8px;
    text-decoration: none;
    transition: background .2s;
}
.cfg-hero-back:hover { background: rgba(255,255,255,.22); color: #fff; }

/* ── SECTION CARD ── */
.cfg-card {
    background: #fff;
    border: 1px solid #e8edf5;
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(4,83,203,.07);
    margin-bottom: 1.5rem;
    overflow: hidden;
}
.cfg-card-header {
    padding: 1.1rem 1.5rem;
    border-bottom: 1px solid #e8edf5;
    display: flex;
    align-items: center;
    gap: .75rem;
    background: #f8faff;
}
.cfg-card-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: linear-gradient(135deg, #0453cb, #5e91de);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: .85rem;
    flex-shrink: 0;
}
.cfg-card-title {
    font-size: .95rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}
.cfg-card-body { padding: 1.5rem; }

/* ── TEMPLATE TABS ── */
.cfg-tabs {
    display: flex;
    gap: .5rem;
    border-bottom: 2px solid #e8edf5;
    margin-bottom: 1.5rem;
    padding-bottom: 0;
}
.cfg-tab-btn {
    background: none;
    border: none;
    padding: .55rem 1rem;
    font-size: .82rem;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: all .2s;
    display: flex;
    align-items: center;
    gap: .4rem;
}
.cfg-tab-btn:hover { color: #0453cb; }
.cfg-tab-btn.active { color: #0453cb; border-bottom-color: #0453cb; }

/* ── TEMPLATE LEVEL BADGE ── */
.lvl-badge {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    padding: .25rem .7rem;
    border-radius: 20px;
}
.lvl-1 { background: rgba(16,185,129,.12); color: #059669; }
.lvl-2 { background: rgba(4,83,203,.1); color: #0453cb; }
.lvl-3 { background: rgba(30,41,59,.1); color: #1e293b; }

/* ── TEMPLATE EDITOR ── */
.tpl-editor {
    border: 1px solid #e8edf5;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 1.25rem;
}
.tpl-editor-head {
    background: #f8faff;
    padding: .75rem 1.1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #e8edf5;
}
.tpl-editor-body { padding: 1.1rem; }
.tpl-preview-btn {
    background: #fff;
    border: 1px solid #d1ddf8;
    color: #0453cb;
    font-size: .75rem;
    font-weight: 600;
    padding: .3rem .75rem;
    border-radius: 7px;
    cursor: pointer;
    transition: all .2s;
    display: flex;
    align-items: center;
    gap: .4rem;
}
.tpl-preview-btn:hover { background: #0453cb; color: #fff; border-color: #0453cb; }

/* ── FORM FIELDS ── */
.cfg-label {
    font-size: .78rem;
    font-weight: 600;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: .4rem;
    display: block;
}
.cfg-input {
    border: 1.5px solid #dde5f0;
    border-radius: 9px;
    padding: .55rem .85rem;
    font-size: .88rem;
    color: #1e293b;
    width: 100%;
    transition: border-color .2s, box-shadow .2s;
    background: #fff;
}
.cfg-input:focus {
    outline: none;
    border-color: #0453cb;
    box-shadow: 0 0 0 3px rgba(4,83,203,.1);
}
.cfg-input.is-invalid { border-color: #dc3545; }
.cfg-hint {
    font-size: .73rem;
    color: #94a3b8;
    margin-top: .3rem;
}
.invalid-feedback { font-size: .75rem; color: #dc3545; margin-top: .25rem; }

/* ── PARAMS SAVE BTN ── */
.cfg-save-btn {
    width: 100%;
    background: linear-gradient(135deg, #0453cb, #5e91de);
    border: none;
    color: #fff;
    font-weight: 700;
    font-size: .9rem;
    padding: .75rem 1.5rem;
    border-radius: 10px;
    cursor: pointer;
    transition: opacity .2s, transform .15s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    letter-spacing: .01em;
}
.cfg-save-btn:hover { opacity: .92; transform: translateY(-1px); }

/* ── VARIABLES SIDEBAR ── */
.var-section { margin-bottom: 1.25rem; }
.var-section-title {
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: #94a3b8;
    margin-bottom: .55rem;
}
.var-tag {
    display: inline-block;
    background: #eef3ff;
    color: #0453cb;
    border: 1px solid #c7d8f8;
    padding: .2rem .6rem;
    border-radius: 6px;
    font-size: .72rem;
    font-weight: 600;
    margin: .2rem .15rem;
    cursor: pointer;
    font-family: 'SF Mono', 'Monaco', monospace;
    transition: all .18s;
    letter-spacing: -.01em;
}
.var-tag:hover { background: #0453cb; color: #fff; border-color: #0453cb; }

/* ── TOGGLE ── */
.cfg-toggle-wrap {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .85rem 1rem;
    background: #f8faff;
    border: 1px solid #e8edf5;
    border-radius: 10px;
}
.cfg-toggle-wrap input[type=checkbox] {
    width: 40px;
    height: 22px;
    accent-color: #0453cb;
    cursor: pointer;
}
.cfg-toggle-label {
    font-size: .85rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}
.cfg-toggle-hint {
    font-size: .73rem;
    color: #94a3b8;
    margin-top: .1rem;
}

/* ── DELAY METER ── */
.delay-row {
    display: flex;
    align-items: center;
    gap: .85rem;
    padding: .9rem 0;
    border-bottom: 1px solid #f1f5f9;
}
.delay-row:last-child { border-bottom: none; padding-bottom: 0; }
.delay-num {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .72rem;
    font-weight: 800;
    flex-shrink: 0;
}
.delay-num-1 { background: rgba(16,185,129,.12); color: #059669; }
.delay-num-2 { background: rgba(4,83,203,.1); color: #0453cb; }
.delay-num-3 { background: rgba(30,41,59,.1); color: #1e293b; }
.delay-label {
    flex: 1;
    font-size: .82rem;
    font-weight: 600;
    color: #475569;
}
.delay-input-wrap { position: relative; width: 110px; }
.delay-input-wrap input {
    border: 1.5px solid #dde5f0;
    border-radius: 8px;
    padding: .45rem .6rem .45rem .7rem;
    font-size: .85rem;
    font-weight: 600;
    color: #1e293b;
    width: 100%;
    text-align: center;
    transition: border-color .2s, box-shadow .2s;
}
.delay-input-wrap input:focus {
    outline: none;
    border-color: #0453cb;
    box-shadow: 0 0 0 3px rgba(4,83,203,.1);
}
.delay-unit {
    font-size: .72rem;
    color: #94a3b8;
    text-align: center;
    margin-top: .2rem;
    font-weight: 500;
}

/* ── ALERTS ── */
.cfg-alert-warning {
    background: linear-gradient(90deg, #fffbeb, #fef9e7);
    border: 1.5px solid #fbbf24;
    border-radius: 10px;
    padding: .85rem 1rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: flex-start;
    gap: .6rem;
    color: #92400e;
    font-size: .83rem;
}
.cfg-alert-success {
    background: linear-gradient(90deg, #f0fdf4, #dcfce7);
    border: 1.5px solid #10b981;
    border-radius: 10px;
    padding: .85rem 1rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: flex-start;
    gap: .6rem;
    color: #065f46;
    font-size: .83rem;
}

/* ── MODAL ── */
.cfg-modal-header {
    background: linear-gradient(135deg, #0f1e3d, #0453cb);
    padding: 1.1rem 1.5rem;
    border: none;
}
.cfg-modal-title { color: #fff; font-weight: 700; font-size: 1rem; }
.cfg-modal-header .btn-close { filter: invert(1) brightness(2); }

/* ── STICKY SIDEBAR ── */
@media (min-width: 992px) {
    .cfg-sticky { position: sticky; top: 80px; }
}
</style>
@endpush

@section('content')

{{-- ── HERO ── --}}
<div class="cfg-hero">
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
        <div>
            <div class="cfg-hero-breadcrumb">
                <a href="{{ route('esbtp.comptabilite.relances.index') }}">Relances</a>
                <i class="fas fa-chevron-right" style="font-size:.55rem;"></i>
                Configuration
            </div>
            <h1 class="cfg-hero-title">
                <i class="fas fa-sliders-h me-2" style="font-size:1.2rem;opacity:.8;"></i>
                Configuration des Relances
            </h1>
            <p class="cfg-hero-sub">Paramètres de délais et templates de messages</p>
        </div>
        <a href="{{ route('esbtp.comptabilite.relances.index') }}" class="cfg-hero-back">
            <i class="fas fa-arrow-left"></i> Retour aux relances
        </a>
    </div>
</div>

<div class="row g-4">
    {{-- ── COL GAUCHE : Templates ── --}}
    <div class="col-lg-8">

        {{-- Templates de relance --}}
        <div class="cfg-card">
            <div class="cfg-card-header">
                <div class="cfg-card-icon"><i class="fas fa-file-alt"></i></div>
                <div>
                    <h5 class="cfg-card-title">Templates de Relance</h5>
                    <div style="font-size:.75rem;color:#64748b;margin-top:.1rem;">Personnalisez les messages selon le canal et le niveau d'urgence</div>
                </div>
            </div>
            <div class="cfg-card-body">

                {{-- Tabs --}}
                <div class="cfg-tabs" id="templateTabs" role="tablist">
                    <button class="cfg-tab-btn active" id="tab-email" data-tab="email" type="button">
                        <i class="fas fa-envelope"></i> Email
                    </button>
                    <button class="cfg-tab-btn" id="tab-sms" data-tab="sms" type="button">
                        <i class="fas fa-sms"></i> SMS
                    </button>
                    <button class="cfg-tab-btn" id="tab-courrier" data-tab="courrier" type="button">
                        <i class="fas fa-file-pdf"></i> Courrier
                    </button>
                </div>

                {{-- Email --}}
                <div id="pane-email">
                    <form id="formTemplatesEmail">
                        @foreach([1 => ['1er rappel', 'lvl-1'], 2 => ['2ème rappel', 'lvl-2'], 3 => 'Dernière relance'] as $niveau => $info)
                            @php
                                $lbl   = is_array($info) ? $info[0] : $info;
                                $cls   = is_array($info) ? $info[1] : 'lvl-3';
                            @endphp
                            <div class="tpl-editor">
                                <div class="tpl-editor-head">
                                    <span class="lvl-badge {{ $cls }}">
                                        <i class="fas fa-circle" style="font-size:.45rem;"></i>
                                        Niveau {{ $niveau }} — {{ $lbl }}
                                    </span>
                                    <button type="button" class="tpl-preview-btn" onclick="previewTemplate('email', {{ $niveau }})">
                                        <i class="fas fa-eye"></i> Aperçu
                                    </button>
                                </div>
                                <div class="tpl-editor-body">
                                    <div class="mb-3">
                                        <label class="cfg-label" for="email_sujet_{{ $niveau }}">Sujet de l'email</label>
                                        <input type="text" class="cfg-input" id="email_sujet_{{ $niveau }}"
                                               name="email_sujet[{{ $niveau }}]"
                                               value="{{ $templates['email'][$niveau]['sujet'] ?? '' }}"
                                               placeholder="Ex: Rappel de paiement - ESBTP">
                                    </div>
                                    <div>
                                        <label class="cfg-label" for="email_contenu_{{ $niveau }}">Contenu</label>
                                        <textarea class="cfg-input" id="email_contenu_{{ $niveau }}"
                                                  name="email_contenu[{{ $niveau }}]" rows="7"
                                                  style="resize:vertical;"
                                                  placeholder="Contenu du template avec variables...">{!! $templates['email'][$niveau]['contenu'] ?? '' !!}</textarea>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="cfg-save-btn" style="width:auto;padding:.6rem 1.5rem;">
                                <i class="fas fa-save"></i> Sauvegarder templates Email
                            </button>
                        </div>
                    </form>
                </div>

                {{-- SMS --}}
                <div id="pane-sms" style="display:none;">
                    <form id="formTemplatesSms">
                        @foreach([1 => ['1er rappel', 'lvl-1'], 2 => ['2ème rappel', 'lvl-2'], 3 => ['Dernière relance', 'lvl-3']] as $niveau => $info)
                            <div class="tpl-editor">
                                <div class="tpl-editor-head">
                                    <span class="lvl-badge {{ $info[1] }}">
                                        <i class="fas fa-circle" style="font-size:.45rem;"></i>
                                        Niveau {{ $niveau }} — {{ $info[0] }}
                                    </span>
                                    <div class="d-flex align-items-center gap-2">
                                        <span id="sms_count_{{ $niveau }}" style="font-size:.72rem;color:#94a3b8;">0/160</span>
                                        <button type="button" class="tpl-preview-btn" onclick="previewTemplate('sms', {{ $niveau }})">
                                            <i class="fas fa-eye"></i> Aperçu
                                        </button>
                                    </div>
                                </div>
                                <div class="tpl-editor-body">
                                    <label class="cfg-label" for="sms_contenu_{{ $niveau }}">Message SMS</label>
                                    <textarea class="cfg-input sms-template" id="sms_contenu_{{ $niveau }}"
                                              name="sms_contenu[{{ $niveau }}]" rows="4" maxlength="160"
                                              data-counter="sms_count_{{ $niveau }}"
                                              style="resize:vertical;"
                                              placeholder="Message court...">{!! $templates['sms'][$niveau]['contenu'] ?? '' !!}</textarea>
                                    <div class="cfg-hint">Maximum 160 caractères</div>
                                </div>
                            </div>
                        @endforeach
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="cfg-save-btn" style="width:auto;padding:.6rem 1.5rem;">
                                <i class="fas fa-save"></i> Sauvegarder templates SMS
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Courrier --}}
                <div id="pane-courrier" style="display:none;">
                    <form id="formTemplatesCourrier">
                        @foreach([1 => ['1er rappel', 'lvl-1'], 2 => ['2ème rappel', 'lvl-2'], 3 => ['Dernière relance', 'lvl-3']] as $niveau => $info)
                            <div class="tpl-editor">
                                <div class="tpl-editor-head">
                                    <span class="lvl-badge {{ $info[1] }}">
                                        <i class="fas fa-circle" style="font-size:.45rem;"></i>
                                        Niveau {{ $niveau }} — {{ $info[0] }}
                                    </span>
                                    <button type="button" class="tpl-preview-btn" onclick="previewTemplate('courrier', {{ $niveau }})">
                                        <i class="fas fa-eye"></i> Aperçu PDF
                                    </button>
                                </div>
                                <div class="tpl-editor-body">
                                    <label class="cfg-label" for="courrier_contenu_{{ $niveau }}">Contenu du courrier</label>
                                    <textarea class="cfg-input" id="courrier_contenu_{{ $niveau }}"
                                              name="courrier_contenu[{{ $niveau }}]" rows="10"
                                              style="resize:vertical;"
                                              placeholder="Contenu avec mise en forme HTML...">{!! $templates['courrier'][$niveau]['contenu'] ?? '' !!}</textarea>
                                </div>
                            </div>
                        @endforeach
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="cfg-save-btn" style="width:auto;padding:.6rem 1.5rem;">
                                <i class="fas fa-save"></i> Sauvegarder templates Courrier
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

    {{-- ── COL DROITE : Variables + Paramètres ── --}}
    <div class="col-lg-4">
        <div class="cfg-sticky">

            {{-- Variables disponibles --}}
            <div class="cfg-card">
                <div class="cfg-card-header">
                    <div class="cfg-card-icon"><i class="fas fa-tags"></i></div>
                    <div>
                        <h5 class="cfg-card-title">Variables</h5>
                        <div style="font-size:.73rem;color:#64748b;margin-top:.1rem;">Clic pour insérer dans le template actif</div>
                    </div>
                </div>
                <div class="cfg-card-body" style="padding:1.1rem 1.2rem;">
                    <div class="var-section">
                        <div class="var-section-title">Étudiant</div>
                        <span class="var-tag" onclick="insertVariable('{nom}')">{nom}</span>
                        <span class="var-tag" onclick="insertVariable('{prenom}')">{prenom}</span>
                        <span class="var-tag" onclick="insertVariable('{nom_complet}')">{nom_complet}</span>
                        <span class="var-tag" onclick="insertVariable('{email}')">{email}</span>
                        <span class="var-tag" onclick="insertVariable('{telephone}')">{telephone}</span>
                        <span class="var-tag" onclick="insertVariable('{numero_etudiant}')">{numero_etudiant}</span>
                    </div>
                    <div class="var-section">
                        <div class="var-section-title">Financier</div>
                        <span class="var-tag" onclick="insertVariable('{montant_dette}')">{montant_dette}</span>
                        <span class="var-tag" onclick="insertVariable('{montant_dette_formatte}')">{montant_dette_formatte}</span>
                        <span class="var-tag" onclick="insertVariable('{date_echeance}')">{date_echeance}</span>
                        <span class="var-tag" onclick="insertVariable('{jours_retard}')">{jours_retard}</span>
                    </div>
                    <div class="var-section">
                        <div class="var-section-title">Relance</div>
                        <span class="var-tag" onclick="insertVariable('{niveau_relance}')">{niveau_relance}</span>
                        <span class="var-tag" onclick="insertVariable('{type_relance}')">{type_relance}</span>
                        <span class="var-tag" onclick="insertVariable('{date_relance}')">{date_relance}</span>
                    </div>
                    <div class="var-section">
                        <div class="var-section-title">Établissement</div>
                        <span class="var-tag" onclick="insertVariable('{nom_ecole}')">{nom_ecole}</span>
                        <span class="var-tag" onclick="insertVariable('{adresse_ecole}')">{adresse_ecole}</span>
                        <span class="var-tag" onclick="insertVariable('{telephone_ecole}')">{telephone_ecole}</span>
                        <span class="var-tag" onclick="insertVariable('{email_ecole}')">{email_ecole}</span>
                        <span class="var-tag" onclick="insertVariable('{date_aujourdhui}')">{date_aujourdhui}</span>
                        <span class="var-tag" onclick="insertVariable('{annee_academique}')">{annee_academique}</span>
                    </div>
                </div>
            </div>

            {{-- Paramètres de relance --}}
            <div class="cfg-card">
                <div class="cfg-card-header">
                    <div class="cfg-card-icon"><i class="fas fa-sliders-h"></i></div>
                    <div>
                        <h5 class="cfg-card-title">Paramètres</h5>
                        <div style="font-size:.73rem;color:#64748b;margin-top:.1rem;">Délais et règles de déclenchement</div>
                    </div>
                </div>
                <div class="cfg-card-body">

                    @php
                        $nonConfigured = is_null($parametres['delai_niveau_1'])
                                      && is_null($parametres['delai_niveau_2'])
                                      && is_null($parametres['delai_niveau_3']);
                    @endphp

                    @if($nonConfigured)
                    <div class="cfg-alert-warning">
                        <i class="fas fa-exclamation-triangle" style="margin-top:.1rem;flex-shrink:0;"></i>
                        <div>Aucune configuration enregistrée. Renseignez les délais ci-dessous pour activer le système de relances.</div>
                    </div>
                    @endif

                    @if(session('success'))
                    <div class="cfg-alert-success">
                        <i class="fas fa-check-circle" style="margin-top:.1rem;flex-shrink:0;"></i>
                        <div>{{ session('success') }}</div>
                    </div>
                    @endif

                    <form method="POST" action="{{ route('esbtp.comptabilite.relances.config.parametres') }}">
                        @csrf

                        {{-- Délais --}}
                        <div class="mb-3">
                            <label class="cfg-label">Délais de relance (jours de retard)</label>
                            <div style="border:1px solid #e8edf5;border-radius:10px;padding:.25rem .85rem;">
                                @foreach([1 => ['1er rappel', 'delay-num-1', 'delai_niveau_1'], 2 => ['2ème rappel', 'delay-num-2', 'delai_niveau_2'], 3 => ['Dernière relance', 'delay-num-3', 'delai_niveau_3']] as $n => $d)
                                <div class="delay-row">
                                    <div class="delay-num {{ $d[1] }}">{{ $n }}</div>
                                    <div class="delay-label">{{ $d[0] }}</div>
                                    <div class="delay-input-wrap">
                                        <input type="number"
                                               name="{{ $d[2] }}"
                                               value="{{ old($d[2], $parametres[$d[2]]) }}"
                                               min="1" max="365"
                                               placeholder="—"
                                               class="@error($d[2]) is-invalid @enderror">
                                        <div class="delay-unit">jours</div>
                                        @error($d[2])<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <div class="cfg-hint">Nombre de jours de retard avant chaque niveau de relance</div>
                        </div>

                        {{-- Montant minimum --}}
                        <div class="mb-3">
                            <label class="cfg-label" for="montant_minimum">Montant minimum (FCFA)</label>
                            <input type="number"
                                   class="cfg-input @error('montant_minimum') is-invalid @enderror"
                                   id="montant_minimum" name="montant_minimum"
                                   value="{{ old('montant_minimum', $parametres['montant_minimum']) }}"
                                   min="0" step="1000"
                                   placeholder="Ex : 10 000">
                            <div class="cfg-hint">Seuil minimum pour déclencher une relance</div>
                            @error('montant_minimum')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Toggle relances auto --}}
                        <div class="mb-3">
                            <div class="cfg-toggle-wrap">
                                <input class="form-check-input" type="checkbox" id="relances_automatiques"
                                       name="relances_automatiques"
                                       {{ $parametres['relances_automatiques'] ? 'checked' : '' }}>
                                <div>
                                    <div class="cfg-toggle-label">Relances automatiques</div>
                                    <div class="cfg-toggle-hint">Planification auto selon les délais configurés</div>
                                </div>
                            </div>
                        </div>

                        {{-- Heure d'envoi --}}
                        <div class="mb-4">
                            <label class="cfg-label" for="heure_envoi">Heure d'envoi automatique</label>
                            <input type="time"
                                   class="cfg-input @error('heure_envoi') is-invalid @enderror"
                                   id="heure_envoi" name="heure_envoi"
                                   value="{{ old('heure_envoi', $parametres['heure_envoi'] ?? '') }}">
                            <div class="cfg-hint">Heure quotidienne pour l'envoi automatique</div>
                            @error('heure_envoi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <button type="submit" class="cfg-save-btn">
                            <i class="fas fa-save"></i> Enregistrer les paramètres
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- ── MODAL APERÇU ── --}}
<div class="modal fade" id="modalApercu" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border:none;border-radius:16px;overflow:hidden;">
            <div class="modal-header cfg-modal-header">
                <h5 class="modal-title cfg-modal-title">
                    <i class="fas fa-eye me-2"></i> Aperçu du Template
                </h5>
                <button type="button" class="btn-close cfg-modal-header" data-bs-dismiss="modal" style="filter:invert(1) brightness(2);"></button>
            </div>
            <div class="modal-body" style="padding:1.5rem;">
                <div id="apercu-content"></div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #e8edf5;padding:.85rem 1.5rem;">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:8px;font-size:.85rem;">Fermer</button>
                <button type="button" onclick="envoyerTestTemplate()" style="background:linear-gradient(135deg,#0453cb,#5e91de);color:#fff;border:none;border-radius:8px;font-size:.85rem;font-weight:600;padding:.5rem 1.2rem;cursor:pointer;">
                    <i class="fas fa-paper-plane me-1"></i> Envoyer un test
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    // ── Tab switching ──
    document.querySelectorAll('.cfg-tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.cfg-tab-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const tab = this.getAttribute('data-tab');
            ['email','sms','courrier'].forEach(t => {
                document.getElementById('pane-' + t).style.display = (t === tab) ? '' : 'none';
            });
        });
    });

    // ── SMS character counter ──
    document.querySelectorAll('.sms-template').forEach(textarea => {
        const counterId = textarea.getAttribute('data-counter');
        const counter   = document.getElementById(counterId);
        function updateCounter() {
            const len = textarea.value.length;
            counter.textContent = `${len}/160`;
            counter.style.color = len > 140 ? (len > 160 ? '#dc3545' : '#f59e0b') : '#94a3b8';
        }
        textarea.addEventListener('input', updateCounter);
        updateCounter();
    });

    // ── Template form submissions ──
    ['Email', 'Sms', 'Courrier'].forEach(type => {
        const form = document.getElementById('formTemplates' + type);
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                sauvegarderTemplates(type.toLowerCase());
            });
        }
    });

    // ── Active textarea tracking ──
    document.addEventListener('focusin', function(e) {
        if (e.target.tagName === 'TEXTAREA') {
            currentFocusedTextarea = e.target;
        }
    });
});

let currentFocusedTextarea = null;

function insertVariable(variable) {
    if (!currentFocusedTextarea) {
        showToast('warning', 'Cliquez dans un champ texte avant d\'insérer une variable.');
        return;
    }
    const start = currentFocusedTextarea.selectionStart;
    const end   = currentFocusedTextarea.selectionEnd;
    const text  = currentFocusedTextarea.value;
    currentFocusedTextarea.value = text.substring(0, start) + variable + text.substring(end);
    currentFocusedTextarea.focus();
    const newPos = start + variable.length;
    currentFocusedTextarea.setSelectionRange(newPos, newPos);
    currentFocusedTextarea.dispatchEvent(new Event('input'));
}

function sauvegarderTemplates(type) {
    const key  = 'formTemplates' + type.charAt(0).toUpperCase() + type.slice(1);
    const form = document.getElementById(key);
    const btn  = form.querySelector('button[type="submit"]');
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Sauvegarde...';
    btn.disabled  = true;

    fetch(`{{ route('esbtp.comptabilite.relances.config.templates') }}`, {
        method: 'POST',
        body: new FormData(form),
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    })
    .then(r => r.json())
    .then(data => showToast(data.success ? 'success' : 'error', data.message))
    .catch(() => showToast('error', 'Erreur lors de la sauvegarde'))
    .finally(() => { btn.innerHTML = orig; btn.disabled = false; });
}

function previewTemplate(type, niveau) {
    const container = document.getElementById('apercu-content');
    const modal = new bootstrap.Modal(document.getElementById('modalApercu'));
    container.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin text-primary" style="font-size:1.5rem;"></i></div>';
    modal.show();

    let contenu = '', sujet = null;
    if (type === 'email') {
        sujet   = document.getElementById(`email_sujet_${niveau}`)?.value;
        contenu = document.getElementById(`email_contenu_${niveau}`)?.value;
    } else if (type === 'sms') {
        contenu = document.getElementById(`sms_contenu_${niveau}`)?.value;
    } else if (type === 'courrier') {
        contenu = document.getElementById(`courrier_contenu_${niveau}`)?.value;
    }

    fetch(`{{ route('esbtp.comptabilite.relances.config.preview') }}`, {
        method: 'POST',
        body: JSON.stringify({ type, niveau, contenu, sujet }),
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(r => r.text())
    .then(html => { container.innerHTML = html; })
    .catch(() => { container.innerHTML = '<div class="text-center text-danger py-3">Erreur lors de la génération de l\'aperçu</div>'; });
}

function envoyerTestTemplate() {
    showToast('info', 'Fonctionnalité d\'envoi de test en développement...');
}

function showToast(type, message) {
    const colors = {
        success: { bg: '#dcfce7', border: '#10b981', text: '#065f46', icon: 'check-circle' },
        error:   { bg: '#fee2e2', border: '#dc3545', text: '#7f1d1d', icon: 'times-circle' },
        warning: { bg: '#fef9e7', border: '#f59e0b', text: '#92400e', icon: 'exclamation-triangle' },
        info:    { bg: '#eff6ff', border: '#0453cb', text: '#1e3a8a', icon: 'info-circle' },
    };
    const c = colors[type] || colors.info;
    const div = document.createElement('div');
    div.style.cssText = `position:fixed;top:20px;right:20px;z-index:9999;min-width:300px;max-width:420px;
        background:${c.bg};border:1.5px solid ${c.border};border-radius:12px;
        padding:.85rem 1.1rem;display:flex;align-items:flex-start;gap:.6rem;
        color:${c.text};font-size:.83rem;font-weight:500;
        box-shadow:0 8px 30px rgba(0,0,0,.12);animation:slideInRight .25s ease;`;
    div.innerHTML = `<i class="fas fa-${c.icon}" style="margin-top:.15rem;flex-shrink:0;"></i><span>${message}</span>`;
    document.body.appendChild(div);
    setTimeout(() => { div.style.opacity = '0'; div.style.transition = 'opacity .3s'; setTimeout(() => div.remove(), 300); }, 4500);
}
</script>
<style>
@keyframes slideInRight {
    from { transform: translateX(30px); opacity: 0; }
    to   { transform: translateX(0);    opacity: 1; }
}
</style>
@endpush
