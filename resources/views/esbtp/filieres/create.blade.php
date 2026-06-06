@extends('layouts.app')

@section('title', 'Créer une filière - KLASSCI')

@push('styles')
<style>
    /* Namespace fe-* : Filière Edit / Create (premium redesign) */
    .fe-wrap { max-width: 1100px; margin: 0 auto; }

    .fe-hero {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px;
        padding: 2rem 2.5rem 1.5rem;
        color: #fff;
        margin-bottom: 1.25rem;
        box-shadow: 0 8px 30px rgba(4,83,203,.18);
    }
    .fe-hero-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .fe-hero-left {
        display: flex;
        align-items: center;
        gap: 1rem;
        min-width: 0;
    }
    .fe-hero-icon {
        width: 52px; height: 52px;
        border-radius: 14px;
        background: rgba(255,255,255,.12);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.15);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.35rem; flex-shrink: 0; color: #fff;
    }
    .fe-hero h1 {
        font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0;
    }
    .fe-hero p {
        color: rgba(255,255,255,.7); font-size: .88rem; margin: .15rem 0 0;
    }
    .fe-hero-actions { display: flex; gap: .5rem; flex-wrap: wrap; }

    .fe-btn--glass {
        background: rgba(255,255,255,.15);
        color: #fff;
        border: 1px solid rgba(255,255,255,.2);
        border-radius: 10px;
        padding: .5rem 1rem;
        font-size: .82rem;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex; align-items: center; gap: .4rem;
        transition: background .15s, border-color .15s;
    }
    .fe-btn--glass:hover {
        background: rgba(255,255,255,.22);
        border-color: rgba(255,255,255,.32);
        color: #fff;
    }

    .fe-alert {
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-left: 4px solid #dc2626;
        color: #7f1d1d;
        border-radius: 12px;
        padding: 1rem 1.25rem;
        margin-bottom: 1rem;
        display: flex; align-items: flex-start; gap: .75rem;
    }
    .fe-alert i { color: #dc2626; font-size: 1.15rem; margin-top: .15rem; flex-shrink: 0; }
    .fe-alert ul { margin: 0; padding-left: 1rem; font-size: .88rem; line-height: 1.5; }

    .fe-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 1.5rem 1.75rem;
        margin-bottom: 1rem;
        box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    }
    .fe-section-header {
        display: flex; align-items: center; gap: .75rem;
        margin-bottom: 1.25rem;
        padding-bottom: .85rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .fe-section-icon {
        width: 40px; height: 40px;
        border-radius: 10px;
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: .95rem;
        box-shadow: 0 2px 8px rgba(4,83,203,.25);
        flex-shrink: 0;
    }
    .fe-section-title { font-size: 1.05rem; font-weight: 700; color: #1e293b; margin: 0; line-height: 1.2; }
    .fe-section-subtitle { font-size: .78rem; color: #64748b; margin: .1rem 0 0; }

    .fe-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1rem 1.25rem;
    }
    .fe-grid--full { grid-template-columns: 1fr; }
    .fe-field { display: flex; flex-direction: column; gap: .4rem; }
    .fe-field label {
        font-size: .8rem; font-weight: 600; color: #1e293b;
        display: flex; align-items: center; gap: .35rem;
    }
    .fe-field label .req { color: #dc2626; font-weight: 700; }
    .fe-field input[type="text"],
    .fe-field input[type="number"],
    .fe-field textarea {
        padding: .6rem .9rem;
        border: 1.5px solid #cbd5e1;
        border-radius: 9px;
        font-size: .9rem;
        color: #1e293b;
        background: #fff;
        min-height: 42px;
        transition: border-color .15s, box-shadow .15s;
        font-family: inherit;
    }
    .fe-field textarea { min-height: 90px; resize: vertical; }
    .fe-field input:focus,
    .fe-field textarea:focus {
        outline: none;
        border-color: #0453cb;
        box-shadow: 0 0 0 3px rgba(4,83,203,.12);
    }
    .fe-field input.is-invalid,
    .fe-field textarea.is-invalid {
        border-color: #dc2626;
    }
    .fe-hint { font-size: .72rem; color: #64748b; line-height: 1.4; }
    .fe-error { font-size: .76rem; color: #dc2626; font-weight: 500; }

    .fe-switch-row {
        display: flex; align-items: flex-start; gap: .85rem;
        padding: .85rem 1rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        cursor: pointer;
        transition: background .15s, border-color .15s;
    }
    .fe-switch-row:hover { background: #f1f5f9; border-color: #cbd5e1; }
    .fe-switch-row--active { background: rgba(4,83,203,.06); border-color: rgba(4,83,203,.3); }
    .fe-switch-row input { position: absolute; opacity: 0; pointer-events: none; }
    .fe-switch-pill {
        width: 42px; height: 24px;
        background: #cbd5e1;
        border-radius: 999px;
        position: relative;
        flex-shrink: 0;
        transition: background .15s;
        margin-top: 2px;
    }
    .fe-switch-pill::after {
        content: '';
        position: absolute;
        top: 2px; left: 2px;
        width: 20px; height: 20px;
        background: #fff;
        border-radius: 999px;
        transition: transform .18s ease;
        box-shadow: 0 1px 3px rgba(15,23,42,.2);
    }
    .fe-switch-row input:checked + .fe-switch-pill { background: #0453cb; }
    .fe-switch-row input:checked + .fe-switch-pill::after { transform: translateX(18px); }
    .fe-switch-body { flex: 1; min-width: 0; }
    .fe-switch-title { font-size: .9rem; font-weight: 600; color: #1e293b; line-height: 1.3; }
    .fe-switch-hint { font-size: .76rem; color: #64748b; margin-top: .15rem; line-height: 1.4; }

    .fe-subblock {
        margin-top: 1rem;
        padding: 1rem 1.25rem;
        background: linear-gradient(135deg, rgba(4,83,203,.04), rgba(59,125,219,.06));
        border: 1px solid rgba(4,83,203,.18);
        border-radius: 10px;
    }
    .fe-subblock-title {
        font-size: .78rem; font-weight: 700; color: #0453cb;
        text-transform: uppercase; letter-spacing: .5px;
        margin-bottom: .65rem;
        display: flex; align-items: center; gap: .4rem;
    }

    .fe-actions {
        display: flex; gap: .75rem; justify-content: flex-end;
        margin-top: 1rem; padding: 1rem 0;
        flex-wrap: wrap;
    }
    .fe-btn {
        border: none; cursor: pointer;
        border-radius: 10px;
        padding: .65rem 1.5rem;
        font-size: .9rem; font-weight: 600;
        display: inline-flex; align-items: center; gap: .45rem;
        text-decoration: none;
        transition: background .15s, color .15s, border-color .15s, box-shadow .15s;
        font-family: inherit;
    }
    .fe-btn--primary {
        background: #0453cb; color: #fff;
        box-shadow: 0 2px 8px rgba(4,83,203,.25);
    }
    .fe-btn--primary:hover {
        background: #033a8e;
        color: #fff;
        box-shadow: 0 4px 14px rgba(4,83,203,.35);
    }
    .fe-btn--ghost {
        background: #fff;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }
    .fe-btn--ghost:hover {
        background: #f8fafc;
        color: #1e293b;
        border-color: #cbd5e1;
    }

    @media (max-width: 768px) {
        .fe-hero { padding: 1.5rem 1.25rem 1.25rem; }
        .fe-hero h1 { font-size: 1.25rem; }
        .fe-card { padding: 1.25rem 1rem; }
        .fe-grid { grid-template-columns: 1fr; }
        .fe-actions { flex-direction: column-reverse; }
        .fe-actions .fe-btn { width: 100%; justify-content: center; }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="fe-wrap">
        <div class="fe-hero">
            <div class="fe-hero-top">
                <div class="fe-hero-left">
                    <div class="fe-hero-icon"><i class="fas fa-sitemap"></i></div>
                    <div>
                        <h1>Créer une filière</h1>
                        <p>Définissez une nouvelle filière BTS ou un tronc commun.</p>
                    </div>
                </div>
                <div class="fe-hero-actions">
                    <a href="{{ route('esbtp.filieres.index') }}" class="fe-btn--glass">
                        <i class="fas fa-arrow-left"></i> Retour à la liste
                    </a>
                </div>
            </div>
        </div>

        @if ($errors->any())
            <div class="fe-alert">
                <i class="fas fa-exclamation-triangle"></i>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- EXCEPTION ajax-no-reload-premium : création initiale entité majeure
             = reload acceptable (rule premium-redesign exception #1). --}}
        <form action="{{ route('esbtp.filieres.store') }}" method="POST" x-data="feFiliereForm()">
            @csrf

            {{-- Section Identité --}}
            <div class="fe-card">
                <div class="fe-section-header">
                    <div class="fe-section-icon"><i class="fas fa-id-card"></i></div>
                    <div>
                        <h2 class="fe-section-title">Identité</h2>
                        <p class="fe-section-subtitle">Nom, code et description de la filière.</p>
                    </div>
                </div>

                <div class="fe-grid">
                    <div class="fe-field">
                        <label for="name">Nom de la filière <span class="req">*</span></label>
                        <input type="text"
                               id="name" name="name"
                               class="@error('name') is-invalid @enderror"
                               value="{{ old('name') }}"
                               maxlength="255" required
                               placeholder="Ex: Bâtiment et Travaux Publics">
                        @error('name')<div class="fe-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="fe-field">
                        <label for="code">Code <span class="req">*</span></label>
                        <input type="text"
                               id="code" name="code"
                               class="@error('code') is-invalid @enderror"
                               value="{{ old('code') }}"
                               maxlength="50" required
                               placeholder="Ex: BTP">
                        <div class="fe-hint">Identifiant court (auto-généré depuis le nom si vide).</div>
                        @error('code')<div class="fe-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="fe-grid fe-grid--full" style="margin-top:1rem;">
                    <div class="fe-field">
                        <label for="description">Description</label>
                        <textarea id="description" name="description"
                                  class="@error('description') is-invalid @enderror"
                                  rows="3"
                                  placeholder="Décrivez brièvement la filière, ses débouchés…">{{ old('description') }}</textarea>
                        <div class="fe-hint">Texte libre — décrit la filière et ses objectifs pédagogiques.</div>
                        @error('description')<div class="fe-error">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            {{-- Section Hiérarchie & Tronc commun : toujours affichée (bug 8a, juin 2026).
                 Auparavant gated derrière SettingsHelper::get('tronc_commun_enabled'). --}}
            <div class="fe-card">
                <div class="fe-section-header">
                    <div class="fe-section-icon"><i class="fas fa-network-wired"></i></div>
                    <div>
                        <h2 class="fe-section-title">Hiérarchie & tronc commun</h2>
                        <p class="fe-section-subtitle">Rattachement parent ou statut de tronc commun BTS.</p>
                    </div>
                </div>

                <div class="fe-grid">
                    <label class="fe-switch-row" :class="isTroncCommun ? 'fe-switch-row--active' : ''">
                        <input type="checkbox"
                               name="is_tronc_commun" value="1"
                               x-model="isTroncCommun"
                               {{ old('is_tronc_commun') ? 'checked' : '' }}>
                        <span class="fe-switch-pill"></span>
                        <div class="fe-switch-body">
                            <div class="fe-switch-title">Cette filière est un tronc commun</div>
                            <div class="fe-switch-hint">Les étudiants choisiront une spécialisation après le(s) semestre(s) de tronc commun.</div>
                        </div>
                    </label>

                    <div class="fe-field" x-show="isTroncCommun" x-cloak>
                        <label for="semestres_tronc_commun">Nombre de semestres tronc commun</label>
                        <input type="number"
                               id="semestres_tronc_commun" name="semestres_tronc_commun"
                               value="{{ old('semestres_tronc_commun', 1) }}"
                               min="1" max="4">
                        <div class="fe-hint">Durée du tronc commun avant orientation (1 à 4 semestres).</div>
                    </div>
                </div>

                {{-- Filière parente — TOUJOURS visible (cf. edit.blade.php bug juin 2026 Marcel). --}}
                <div class="fe-subblock">
                    <div class="fe-subblock-title">
                        <i class="fas fa-sitemap"></i> Filière parente
                    </div>
                    <div class="fe-grid">
                        <div class="fe-field">
                            <label for="parent_id">Rattacher à un tronc commun</label>
                            @php
                                $_parentOptions = ['' => '— Aucune (filière indépendante) —'];
                                $_parentsAvailable = ($filieresParents ?? null)
                                    ?? collect($filieres ?? [])->where('is_tronc_commun', true);
                                foreach ($_parentsAvailable as $_f) {
                                    $_parentOptions[$_f->id] = $_f->name . ' (' . $_f->code . ')';
                                }
                            @endphp
                            <x-au-select
                                name="parent_id"
                                :value="old('parent_id', request('parent_id'))"
                                placeholder="— Aucune (filière indépendante) —"
                                icon="fa-sitemap"
                                :searchable="count($_parentOptions) > 8"
                                :options="$_parentOptions" />
                            <div class="fe-hint" x-show="!isTroncCommun" x-cloak>Sélectionner la filière tronc commun dont celle-ci est une spécialisation (ex : <em>Génie Civil — Option Bâtiment</em> rattaché à <em>Tronc commun BTS1</em>).</div>
                            <div class="fe-hint" x-show="isTroncCommun" x-cloak>Optionnel pour un tronc commun : utiliser uniquement si ce TC est lui-même rattaché à un tronc commun supérieur (hiérarchie multi-niveaux).</div>
                            @error('parent_id')<div class="fe-error">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section Statut --}}
            <div class="fe-card">
                <div class="fe-section-header">
                    <div class="fe-section-icon"><i class="fas fa-toggle-on"></i></div>
                    <div>
                        <h2 class="fe-section-title">Statut</h2>
                        <p class="fe-section-subtitle">Disponibilité de la filière dans le système.</p>
                    </div>
                </div>

                <label class="fe-switch-row" :class="isActive ? 'fe-switch-row--active' : ''">
                    <input type="checkbox"
                           name="is_active" value="1"
                           x-model="isActive"
                           {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                    <span class="fe-switch-pill"></span>
                    <div class="fe-switch-body">
                        <div class="fe-switch-title">Filière active</div>
                        <div class="fe-switch-hint">Active par défaut. Désactivez pour masquer la filière dans la création de classes.</div>
                    </div>
                </label>
                @error('is_active')<div class="fe-error" style="margin-top:.5rem;">{{ $message }}</div>@enderror
            </div>

            {{-- Actions footer --}}
            <div class="fe-actions">
                <a href="{{ route('esbtp.filieres.index') }}" class="fe-btn fe-btn--ghost">
                    <i class="fas fa-times"></i> Annuler
                </a>
                <button type="submit" class="fe-btn fe-btn--primary">
                    <i class="fas fa-save"></i> Créer la filière
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    if (typeof window.feFiliereForm !== 'function') {
        window.feFiliereForm = function () {
            return {
                isTroncCommun: {{ old('is_tronc_commun') ? 'true' : 'false' }},
                isActive: {{ old('is_active', '1') == '1' ? 'true' : 'false' }},
            };
        };
    }

    // Auto-fill code from name (premium UX preserved)
    document.addEventListener('DOMContentLoaded', function () {
        var nameInput = document.getElementById('name');
        var codeInput = document.getElementById('code');
        if (nameInput && codeInput) {
            nameInput.addEventListener('blur', function () {
                if (codeInput.value === '') {
                    var words = nameInput.value.split(/\s+/).filter(Boolean);
                    var code = '';
                    words.forEach(function (w) { code += w.charAt(0).toUpperCase(); });
                    if (code.length < 2 && words[0] && words[0].length > 1) {
                        code += words[0].charAt(1).toUpperCase();
                    }
                    codeInput.value = code;
                }
            });
        }
    });
</script>
@endpush
