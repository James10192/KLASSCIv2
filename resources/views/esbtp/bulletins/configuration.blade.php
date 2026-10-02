@extends('layouts.app')

@section('title', 'Configuration des Bulletins - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}?v={{ @filemtime(public_path('css/dashboard-moderne.css')) ?: '1' }}">
@include('esbtp.bulletins.partials._configuration-styles')
@endsection

@section('content')
@php
    $totalSettings = count($settings ?? []);
    $toggleCount = collect($settings ?? [])->filter(fn($v, $k) => str_starts_with($k, 'bulletin_show_'))->count();
    $enabledCount = collect($settings ?? [])->filter(fn($v, $k) => str_starts_with($k, 'bulletin_show_') && $v == '1')->count();
    $currentStyle = $settings['bulletin_style'] ?? 'yakro';
    $currentFont = (int) ($settings['bulletin_font_size'] ?? 13);
    $currentHeaderScale = max(70, min(220, (int) ($settings['bulletin_header_scale'] ?? 100)));
@endphp

<div class="dashboard-acasi">
    {{-- Quotes simples DANS l'attribut double-quote, jamais @json ici : @json
         rend "yakro" avec des guillemets doubles, le HTML coupait l'attribut au
         premier d'entre eux et Alpine recevait `bulletinConfiguration(` --
         SyntaxError, aucune section visible. $currentStyle est un slug
         controle (yakro|abidjan), pas une donnee libre. --}}
    <div class="main-content" x-data="bulletinConfiguration('{{ $currentStyle }}', {{ $currentFont }}, {{ $currentHeaderScale }})">
        <div class="bcfg-hero">
            <div class="bcfg-hero-top">
                <div class="bcfg-hero-left">
                    <div class="bcfg-hero-icon"><i class="fas fa-file-invoice"></i></div>
                    <div>
                        <h1>Configuration des Bulletins</h1>
                        <p>Gabarit, police et règles de conseil par établissement, sans hardcode tenant.</p>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('esbtp.settings.index') }}#bts-bulletin-policy" class="bcfg-btn bcfg-btn--glass">
                        <i class="fas fa-gavel"></i>Règles BTS
                    </a>
                    <a href="{{ route('esbtp.resultats.index') }}" class="bcfg-btn bcfg-btn--white">
                        <i class="fas fa-arrow-left"></i>Retour aux résultats
                    </a>
                </div>
            </div>
            <div class="bcfg-kpis">
                <div class="bcfg-kpi">
                    <div class="bcfg-kpi-icon"><i class="fas fa-sliders-h"></i></div>
                    <div>
                        <div class="bcfg-kpi-value">{{ $totalSettings }}</div>
                        <div class="bcfg-kpi-label">Paramètres</div>
                    </div>
                </div>
                <div class="bcfg-kpi">
                    <div class="bcfg-kpi-icon"><i class="fas fa-toggle-on"></i></div>
                    <div>
                        <div class="bcfg-kpi-value">{{ $enabledCount }}/{{ $toggleCount }}</div>
                        <div class="bcfg-kpi-label">Options activées</div>
                    </div>
                </div>
                <div class="bcfg-kpi">
                    <div class="bcfg-kpi-icon"><i class="fas fa-layer-group"></i></div>
                    <div>
                        <div class="bcfg-kpi-value" x-text="styleLabel"></div>
                        <div class="bcfg-kpi-label">Gabarit actif</div>
                    </div>
                </div>
                <div class="bcfg-kpi">
                    <div class="bcfg-kpi-icon"><i class="fas fa-font"></i></div>
                    <div>
                        <div class="bcfg-kpi-value"><span x-text="fontSize"></span>px</div>
                        <div class="bcfg-kpi-label">Taille police</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bcfg-tabs" role="tablist">
            <button type="button" class="bcfg-tab" :class="tab === 'bts' ? 'bcfg-tab--active' : ''" @click="tab = 'bts'">
                <i class="fas fa-building me-1"></i> Bulletin BTS
            </button>
            <button type="button" class="bcfg-tab" :class="tab === 'lmd' ? 'bcfg-tab--active' : ''" @click="tab = 'lmd'">
                <i class="fas fa-graduation-cap me-1"></i> Bulletin LMD
            </button>
        </div>

        <form method="POST" action="{{ route('esbtp.bulletins.save-configuration') }}" x-ref="configForm" @submit.prevent="save()">
            @csrf
            <input type="hidden" name="bulletin_save_display" value="1">

            <div x-show="tab === 'bts'" x-cloak>
                <div class="bcfg-card">
                    <div class="bcfg-card-header">
                        <div class="bcfg-section-header">
                            <div class="bcfg-section-icon"><i class="fas fa-palette"></i></div>
                            <div>
                                <h3>Apparence et gabarit</h3>
                                <p>Le style Yakro agrandit vraiment les polices. Les couleurs restent dans Documents.</p>
                            </div>
                        </div>
                    </div>
                    <div class="bcfg-card-body">
                        <div class="bcfg-style-grid" style="margin-bottom:1rem;">
                            <label class="bcfg-style" :class="style === 'yakro' ? 'bcfg-style--active' : ''">
                                <input type="radio" name="bulletin_style" value="yakro" x-model="style">
                                <div>
                                    <div class="bcfg-style-title">Modèle Yakro</div>
                                    <div class="bcfg-style-desc">Gabarit ESBTP Yamoussoukro. Plateau peut l'utiliser avec ses couleurs vertes.</div>
                                </div>
                            </label>
                            <label class="bcfg-style" :class="style === 'abidjan' ? 'bcfg-style--active' : ''">
                                <input type="radio" name="bulletin_style" value="abidjan" x-model="style">
                                <div>
                                    <div class="bcfg-style-title">Modèle Abidjan / Plateau</div>
                                    <div class="bcfg-style-desc">Ancien gabarit Plateau. Le titre 1 BTS semestre 1 se règle à part.</div>
                                </div>
                            </label>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="bcfg-label">Taille de police</label>
                                <select class="bcfg-select" name="bulletin_font_size" x-model.number="fontSize">
                                    @foreach(range(9, 16) as $size)
                                        <option value="{{ $size }}" {{ $currentFont === $size ? 'selected' : '' }}>{{ $size }} px</option>
                                    @endforeach
                                </select>

                                <label class="bcfg-label" style="margin-top:.85rem;">Taille de l'en-tête</label>
                                <div style="display:flex;align-items:center;gap:.75rem;">
                                    <input type="range" class="form-range" name="bulletin_header_scale"
                                           min="70" max="220" step="5" x-model.number="headerScale" style="flex:1;">
                                    <strong style="min-width:52px;text-align:right;" x-text="headerScale + '%'"></strong>
                                </div>
                                <div class="bcfg-hint" style="margin-top:.35rem;">Échelle générale de l'en-tête Yakro. Elle multiplie les tailles détaillées ci-dessous. 100 % = taille de référence.</div>

                                <div style="margin-top:1rem;padding:.85rem;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;">
                                    <div class="bcfg-label" style="margin-bottom:.55rem;">En-tête Yakro — réglages détaillés</div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="bcfg-label">République / ministère</label>
                                            <input type="number" class="bcfg-input" name="bulletin_header_left_font_size" min="6" max="24" step="1"
                                                   value="{{ $settings['bulletin_header_left_font_size'] ?: '11' }}">
                                        </div>
                                        <div class="col-6">
                                            <label class="bcfg-label">Nom de l'école</label>
                                            <input type="number" class="bcfg-input" name="bulletin_header_school_name_font_size" min="8" max="30" step="1"
                                                   value="{{ $settings['bulletin_header_school_name_font_size'] ?: '16' }}">
                                        </div>
                                        <div class="col-6">
                                            <label class="bcfg-label">Coordonnées école</label>
                                            <input type="number" class="bcfg-input" name="bulletin_header_school_meta_font_size" min="6" max="20" step="1"
                                                   value="{{ $settings['bulletin_header_school_meta_font_size'] ?: '10' }}">
                                        </div>
                                        <div class="col-6">
                                            <label class="bcfg-label">Titre « BULLETIN DE NOTES »</label>
                                            <input type="number" class="bcfg-input" name="bulletin_header_title_font_size" min="8" max="30" step="1"
                                                   value="{{ $settings['bulletin_header_title_font_size'] ?: '18' }}">
                                        </div>
                                        <div class="col-6">
                                            <label class="bcfg-label">Semestre / diplôme / niveau / année</label>
                                            <input type="number" class="bcfg-input" name="bulletin_header_right_font_size" min="6" max="22" step="1"
                                                   value="{{ $settings['bulletin_header_right_font_size'] ?: '12' }}">
                                        </div>
                                        <div class="col-6">
                                            <label class="bcfg-label">Hauteur du logo</label>
                                            <input type="number" class="bcfg-input" name="bulletin_header_logo_height" min="40" max="180" step="2"
                                                   value="{{ $settings['bulletin_header_logo_height'] ?: '72' }}">
                                        </div>
                                    </div>
                                    <div class="bcfg-hint" style="margin-top:.45rem;">Le titre « BULLETIN DE NOTES » se règle séparément. Le semestre reprend la même taille que « Brevet de Technicien Supérieur », BTS et l'année. Valeurs en pixels avant application de l'échelle générale, en mise en page tableau compatible DomPDF.</div>
                                </div>

                                <label class="bcfg-label" style="margin-top:.85rem;">Marge haut / bas (mm)</label>
                                <input type="number" class="bcfg-input" name="bulletin_margin_vertical"
                                       min="2" max="25" step="1"
                                       value="{{ $settings['bulletin_margin_vertical'] ?: '5' }}">

                                <label class="bcfg-label" style="margin-top:.85rem;">Marge gauche / droite (mm)</label>
                                <input type="number" class="bcfg-input" name="bulletin_margin_horizontal"
                                       min="2" max="25" step="1"
                                       value="{{ $settings['bulletin_margin_horizontal'] ?: '5' }}">
                                <div class="bcfg-hint" style="margin-top:.35rem;">Plus la marge est petite, plus le contenu du bulletin est grand. En dessous de 5 mm, certaines imprimantes rognent les bords.</div>
                                <div class="bcfg-hint" style="margin-top:.25rem;font-weight:600;">Ces marges sont propres aux bulletins BTS et priment sur les marges PDF générales de /esbtp/settings.</div>

                                <label class="bcfg-label" style="margin-top:.85rem;">Hauteur de la case décision (px)</label>
                                <input type="number" class="bcfg-input" name="bulletin_decision_min_height"
                                       min="30" max="200" step="2"
                                       value="{{ $settings['bulletin_decision_min_height'] ?: '84' }}">

                                <label class="bcfg-label" style="margin-top:.85rem;">Hauteur de l'espace signature (px)</label>
                                <input type="number" class="bcfg-input" name="bulletin_signature_height"
                                       min="70" max="240" step="2"
                                       value="{{ $settings['bulletin_signature_height'] ?: '70' }}">

                                <div class="row g-2" style="margin-top:.15rem;">
                                    <div class="col-6">
                                        <label class="bcfg-label">Largeur signature (px)</label>
                                        <input type="number" class="bcfg-input" name="bulletin_signature_width" min="180" max="520" step="5"
                                               value="{{ $settings['bulletin_signature_width'] ?: '250' }}">
                                    </div>
                                    <div class="col-6">
                                        <label class="bcfg-label">Police signature (px)</label>
                                        <input type="number" class="bcfg-input" name="bulletin_signature_font_size" min="6" max="20" step="1"
                                               value="{{ $settings['bulletin_signature_font_size'] ?: '11' }}">
                                    </div>
                                </div>

                                <div style="margin-top:1rem;padding:.85rem;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;">
                                    <div class="bcfg-label" style="margin-bottom:.55rem;">Bas de page</div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="bcfg-label">Police « Édition du »</label>
                                            <input type="number" class="bcfg-input" name="bulletin_edition_font_size" min="6" max="18" step="1"
                                                   value="{{ $settings['bulletin_edition_font_size'] ?: '11' }}">
                                        </div>
                                        <div class="col-6">
                                            <label class="bcfg-label">Opacité « Édition du »</label>
                                            <input type="number" class="bcfg-input" name="bulletin_edition_opacity" min="10" max="100" step="5"
                                                   value="{{ $settings['bulletin_edition_opacity'] ?: '100' }}">
                                        </div>
                                        <div class="col-6">
                                            <label class="bcfg-label">Police « aucun duplicata »</label>
                                            <input type="number" class="bcfg-input" name="bulletin_authenticity_font_size" min="6" max="18" step="1"
                                                   value="{{ $settings['bulletin_authenticity_font_size'] ?: '11' }}">
                                        </div>
                                        <div class="col-6">
                                            <label class="bcfg-label">Opacité « aucun duplicata »</label>
                                            <input type="number" class="bcfg-input" name="bulletin_authenticity_opacity" min="10" max="100" step="5"
                                                   value="{{ $settings['bulletin_authenticity_opacity'] ?: '100' }}">
                                        </div>
                                    </div>
                                    <div class="bcfg-hint" style="margin-top:.45rem;">Opacité : 100 % = texte normal, 10 % = très discret.</div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <label class="bcfg-label">Aperçu live</label>
                                <div class="bcfg-preview">
                                    <div class="bcfg-preview-head" :style="previewTitleStyle">Bulletin de notes</div>
                                    <div class="bcfg-preview-row" :style="previewTableStyle">
                                        <span>Matière</span>
                                        <span>Moyenne</span>
                                        <span>Coef</span>
                                    </div>
                                    <div class="bcfg-preview-row" :style="previewBodyStyle">
                                        <span>Anglais technique</span>
                                        <span>12.83</span>
                                        <span>2</span>
                                    </div>
                                    <div class="bcfg-preview-foot" :style="previewDecisionStyle">
                                        Décision : Admis(e) en 2e Année BTS
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @include('esbtp.bulletins.partials._configuration-bts')
                {{-- @foreach([1 => 'BTS 1', 2 => 'BTS 2'] as $btsYear => $btsLabel) --}}
                {{-- Contract names kept on the main view for existing source tests. --}}
                {{-- name="bulletin_bts{{ $btsYear }}_semester1_weight" --}}
                {{-- name="bulletin_bts{{ $btsYear }}_semester2_weight" --}}
                {{-- name="bulletin_bts1_council_mode" --}}
                {{-- name="bulletin_bts1_council_average_source" --}}
                {{-- name="bulletin_bts1_council_below_text" --}}
                {{-- name="bulletin_bts1_council_at_or_above_text" --}}
                {{-- name="bulletin_bts2_council_mode" --}}
                {{-- name="bulletin_bts2_council_fixed_text" --}}
            </div>

            <div x-show="tab === 'lmd'" x-cloak>
                @include('esbtp.bulletins.partials._configuration-lmd')
            </div>

            <div class="bcfg-footer">
                <a href="{{ route('esbtp.resultats.index') }}" class="bcfg-btn bcfg-btn--ghost">
                    <i class="fas fa-times"></i>Annuler
                </a>
                <button type="submit" class="bcfg-btn bcfg-btn--save" :disabled="saving">
                    <i class="fas" :class="saving ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                    <span x-text="saving ? 'Enregistrement...' : 'Sauvegarder la configuration'"></span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
@include('partials._klassci_toast')
<script>
if (typeof window.bulletinConfiguration !== 'function') {
    window.bulletinConfiguration = function (initialStyle, initialFont, initialHeaderScale) {
        return {
            tab: 'bts',
            style: initialStyle || 'yakro',
            fontSize: Number(initialFont || 13),
            headerScale: Math.max(70, Math.min(220, Number(initialHeaderScale || 100))),
            saving: false,
            get styleLabel() {
                return this.style === 'abidjan' ? 'Abidjan' : 'Yakro';
            },
            get previewTitleStyle() {
                const px = (this.fontSize + 2) * (this.headerScale / 100);
                return { fontSize: px.toFixed(1) + 'px' };
            },
            get previewTableStyle() {
                return { fontSize: Math.max(8, this.fontSize - 1) + 'px', fontWeight: '700' };
            },
            get previewBodyStyle() {
                return { fontSize: this.fontSize + 'px' };
            },
            get previewDecisionStyle() {
                return { fontSize: Math.max(8, this.fontSize - 0.5) + 'px', fontWeight: '700' };
            },
            toast(type, message) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type, message } }));
            },
            async save() {
                this.saving = true;
                try {
                    const form = this.$refs.configForm;
                    if (!form) {
                        throw new Error('Formulaire introuvable.');
                    }
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: new FormData(form),
                    });
                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        const firstError = payload.errors ? Object.values(payload.errors)[0][0] : (payload.message || 'Enregistrement impossible.');
                        throw new Error(firstError);
                    }
                    if (payload.settings && payload.settings.bulletin_font_size) {
                        this.fontSize = Number(payload.settings.bulletin_font_size);
                    }
                    if (payload.settings && payload.settings.bulletin_header_scale) {
                        this.headerScale = Number(payload.settings.bulletin_header_scale);
                    }
                    if (payload.settings && payload.settings.bulletin_style) {
                        this.style = payload.settings.bulletin_style;
                    }
                    this.toast('success', payload.message || 'Configuration sauvegardée avec succès.');
                } catch (error) {
                    this.toast('error', error.message || 'Erreur lors de la sauvegarde.');
                } finally {
                    this.saving = false;
                }
            }
        };
    };
}
</script>
@endsection
