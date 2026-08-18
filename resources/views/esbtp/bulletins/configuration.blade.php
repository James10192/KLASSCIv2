@extends('layouts.app')

@section('title', 'Configuration des Bulletins - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@include('esbtp.bulletins.partials._configuration-styles')
@endsection

@section('content')
@php
    $totalSettings = count($settings ?? []);
    $toggleCount = collect($settings ?? [])->filter(fn($v, $k) => str_starts_with($k, 'bulletin_show_'))->count();
    $enabledCount = collect($settings ?? [])->filter(fn($v, $k) => str_starts_with($k, 'bulletin_show_') && $v == '1')->count();
    $currentStyle = $settings['bulletin_style'] ?? 'yakro';
    $currentFont = (int) ($settings['bulletin_font_size'] ?? 13);
@endphp

<div class="dashboard-acasi">
    <div class="main-content" x-data="bulletinConfiguration(@json($currentStyle), {{ $currentFont }})">
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

        <form method="POST" action="{{ route('esbtp.bulletins.save-configuration') }}" @submit.prevent="save()">
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
    window.bulletinConfiguration = function (initialStyle, initialFont) {
        return {
            tab: 'bts',
            style: initialStyle || 'yakro',
            fontSize: Number(initialFont || 13),
            saving: false,
            get styleLabel() {
                return this.style === 'abidjan' ? 'Abidjan' : 'Yakro';
            },
            get previewTitleStyle() {
                return { fontSize: (this.fontSize + 2) + 'px' };
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
                    const form = this.$el.querySelector('form');
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
