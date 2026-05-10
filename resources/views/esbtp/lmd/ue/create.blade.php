@extends('layouts.app')

@section('title', isset($ue) ? 'Modifier l\'UE — ' . $ue->name : 'Nouvelle Unite d\'Enseignement')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* LMD UE Create/Edit — namespaced */
    .lmd-hero {
        background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
        border-radius: 1rem;
        padding: 2rem;
        color: #ffffff;
        margin-bottom: 1.5rem;
    }
    .lmd-hero-title {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
    }
    .lmd-hero-subtitle {
        opacity: 0.85;
        font-size: 0.9rem;
    }
    .lmd-form-card {
        background: #ffffff;
        border-radius: 0.75rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .lmd-section-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #eff6ff;
    }
    .lmd-ecue-table {
        width: 100%;
        border-collapse: collapse;
    }
    .lmd-ecue-table th {
        background: #f8fafc;
        padding: 0.6rem 0.75rem;
        font-size: 0.8rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        border-bottom: 1px solid #e2e8f0;
    }
    .lmd-ecue-table td {
        padding: 0.5rem 0.75rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
    }
    .lmd-ecue-table input {
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        padding: 0.4rem 0.6rem;
        font-size: 0.875rem;
        width: 100%;
    }
    .lmd-ecue-table input:focus {
        outline: none;
        border-color: #0453cb;
        box-shadow: 0 0 0 2px rgba(4,83,203,0.15);
    }
    .lmd-remove-btn {
        background: none;
        border: none;
        color: #ef4444;
        cursor: pointer;
        font-size: 1rem;
        padding: 0.25rem;
        border-radius: 0.375rem;
        transition: background 0.15s;
    }
    .lmd-remove-btn:hover {
        background: #fef2f2;
    }
    .lmd-empty-ecue {
        text-align: center;
        padding: 1.5rem;
        color: #94a3b8;
        font-size: 0.875rem;
    }
    .lmd-form-footer {
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        margin-top: 1.5rem;
        padding-top: 1rem;
        border-top: 1px solid #f1f5f9;
    }
    .lmd-auto-code {
        position: relative;
    }
    .lmd-auto-code .lmd-auto-hint {
        position: absolute;
        right: 0.5rem;
        top: 50%;
        transform: translateY(-50%);
        font-size: 0.7rem;
        color: #94a3b8;
        pointer-events: none;
    }
</style>
@endpush

@section('content')
<div class="lmd-page" x-data="lmdUeForm()">

    {{-- Hero --}}
    <div class="lmd-hero">
        <div class="lmd-hero-title">
            <i class="fas fa-{{ isset($ue) ? 'edit' : 'plus-circle' }} me-2"></i>
            {{ isset($ue) ? 'Modifier l\'Unite d\'Enseignement' : 'Nouvelle Unite d\'Enseignement' }}
        </div>
        <div class="lmd-hero-subtitle">
            {{ isset($ue) ? 'Mettez a jour les informations de l\'UE et ses ECUEs' : 'Definissez l\'UE et ajoutez ses ECUEs (matieres)' }}
        </div>
    </div>

    {{-- Validation errors --}}
    @if($errors->any())
        <div class="alert alert-danger" style="border-radius: 0.5rem; margin-bottom: 1rem;">
            <strong><i class="fas fa-exclamation-triangle me-1"></i> Erreurs de validation :</strong>
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ isset($ue) ? route('esbtp.lmd.ue.update', $ue) : route('esbtp.lmd.ue.store') }}"
          method="POST">
        @csrf
        @if(isset($ue))
            @method('PUT')
        @endif

        {{-- UE Information --}}
        <div class="lmd-form-card">
            <div class="lmd-section-title"><i class="fas fa-info-circle me-2" style="color: #0453cb;"></i>Informations de l'UE</div>

            <div class="row mb-3">
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Intitule <span class="text-danger">*</span></label>
                    <input type="text"
                           name="name"
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $ue->name ?? '') }}"
                           required
                           placeholder="Ex: Mathematiques Fondamentales"
                           x-model="ueName"
                           @input="autoCode()">
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Code</label>
                    <div class="lmd-auto-code">
                        <input type="text"
                               name="code"
                               class="form-control @error('code') is-invalid @enderror"
                               value="{{ old('code', $ue->code ?? '') }}"
                               placeholder="MAG2001 (laisser vide pour UE virtuelle)"
                               x-model="ueCode">
                        <span class="lmd-auto-hint">auto</span>
                    </div>
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Optionnel — laisser vide pour les UE virtuelles UEMOA (ex: <em>UE de Méthodologie</em> sans code formel).</small>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Credits</label>
                    <input type="number"
                           name="credit"
                           class="form-control @error('credit') is-invalid @enderror"
                           value="{{ old('credit', $ue->credit ?? '') }}"
                           min="0"
                           placeholder="Ex: 6">
                    @error('credit')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Semestre</label>
                    <select name="semestre" class="form-select @error('semestre') is-invalid @enderror">
                        <option value="">-- Choisir --</option>
                        @for($s = 1; $s <= 10; $s++)
                            <option value="{{ $s }}" {{ old('semestre', $ue->semestre ?? '') == $s ? 'selected' : '' }}>
                                Semestre {{ $s }}
                            </option>
                        @endfor
                    </select>
                    @error('semestre')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Type UE</label>
                    <select name="type_ue" class="form-select @error('type_ue') is-invalid @enderror">
                        <option value="">-- Choisir --</option>
                        @php
                            $currentTypeUe = old('type_ue', isset($ue) ? ($ue->type_ue?->value ?? $ue->type_ue ?? '') : '');
                        @endphp
                        @foreach(\App\Enums\TypeUE::cases() as $type)
                            <option value="{{ $type->value }}" {{ $currentTypeUe == $type->value ? 'selected' : '' }}>
                                {{ $type->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('type_ue')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Parcours</label>
                    <select name="parcours_id" class="form-select @error('parcours_id') is-invalid @enderror">
                        <option value="">-- Choisir --</option>
                        @foreach($parcours as $p)
                            <option value="{{ $p->id }}" {{ old('parcours_id', $ue->parcours_id ?? '') == $p->id ? 'selected' : '' }}>
                                {{ $p->name ?? $p->intitule ?? $p->code }}
                            </option>
                        @endforeach
                    </select>
                    @error('parcours_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Ordre sur le bulletin</label>
                    <input type="number" name="ordre" class="form-control @error('ordre') is-invalid @enderror"
                           value="{{ old('ordre', 0) }}" min="0" placeholder="0">
                    @error('ordre')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Ordre de l'UE sur le bulletin (via le parcours)</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Filiere</label>
                    <select name="filiere_id" class="form-select @error('filiere_id') is-invalid @enderror">
                        <option value="">-- Choisir --</option>
                        @foreach($filieres as $f)
                            <option value="{{ $f->id }}" {{ old('filiere_id', $ue->filiere_id ?? '') == $f->id ? 'selected' : '' }}>
                                {{ $f->name ?? $f->intitule ?? $f->code }}
                            </option>
                        @endforeach
                    </select>
                    @error('filiere_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Niveau</label>
                    <select name="niveau_id" class="form-select @error('niveau_id') is-invalid @enderror">
                        <option value="">-- Choisir --</option>
                        @foreach($niveaux as $n)
                            <option value="{{ $n->id }}" {{ old('niveau_id', $ue->niveau_id ?? '') == $n->id ? 'selected' : '' }}>
                                {{ $n->name ?? $n->intitule ?? $n->code }}
                            </option>
                        @endforeach
                    </select>
                    @error('niveau_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-0">
                <label class="form-label fw-semibold">Description</label>
                <textarea name="description"
                          class="form-control @error('description') is-invalid @enderror"
                          rows="3"
                          placeholder="Description optionnelle de l'UE...">{{ old('description', $ue->description ?? '') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        {{-- ECUEs Section --}}
        <div class="lmd-form-card">
            <div class="lmd-section-title" style="display: flex; justify-content: space-between; align-items: center;">
                <span><i class="fas fa-list-ul me-2" style="color: #0453cb;"></i>ECUEs (Elements Constitutifs)</span>
                <button type="button" class="btn btn-acasi secondary btn-sm" @click="addEcue()">
                    <i class="fas fa-plus me-1"></i> Ajouter un ECUE
                </button>
            </div>

            <template x-if="ecues.length === 0">
                <div class="lmd-empty-ecue">
                    <i class="fas fa-inbox d-block mb-2" style="font-size: 1.5rem;"></i>
                    Aucun ECUE ajoute. Cliquez sur "Ajouter un ECUE" pour commencer.
                </div>
            </template>

            <template x-if="ecues.length > 0">
                <div class="table-responsive">
                    <table class="lmd-ecue-table">
                        <thead>
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 27%;">Nom</th>
                                <th style="width: 17%;">Code</th>
                                <th style="width: 13%;">Coefficient</th>
                                <th style="width: 13%;">Credits</th>
                                <th style="width: 10%;">Ordre</th>
                                <th style="width: 10%; text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(ecue, index) in ecues" :key="index">
                                <tr>
                                    <td x-text="index + 1" style="color: #94a3b8; font-weight: 600;"></td>
                                    <td>
                                        <input type="text"
                                               :name="'ecues[' + index + '][name]'"
                                               x-model="ecue.name"
                                               placeholder="Nom de l'ECUE"
                                               required>
                                    </td>
                                    <td>
                                        <input type="text"
                                               :name="'ecues[' + index + '][code]'"
                                               x-model="ecue.code"
                                               placeholder="Code ECUE">
                                    </td>
                                    <td>
                                        <input type="number"
                                               :name="'ecues[' + index + '][coefficient_ecue]'"
                                               x-model="ecue.coefficient_ecue"
                                               min="0"
                                               step="0.5"
                                               placeholder="1">
                                    </td>
                                    <td>
                                        <input type="number"
                                               :name="'ecues[' + index + '][credit_ecue]'"
                                               x-model="ecue.credit_ecue"
                                               min="0"
                                               placeholder="2">
                                    </td>
                                    <td>
                                        <input type="number"
                                               :name="'ecues[' + index + '][ordre_bulletin]'"
                                               x-model="ecue.ordre_bulletin"
                                               min="0"
                                               placeholder="0"
                                               style="width:60px;">
                                    </td>
                                    <td style="text-align: center;">
                                        <button type="button" class="lmd-remove-btn" @click="removeEcue(index)" title="Retirer cet ECUE">
                                            <i class="fas fa-times-circle"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>
        </div>

        {{-- Form footer --}}
        <div class="lmd-form-footer">
            <a href="{{ route('esbtp.lmd.ue.index') }}" class="btn btn-acasi secondary">
                <i class="fas fa-arrow-left me-1"></i> Annuler
            </a>
            <button type="submit" class="btn btn-acasi primary">
                <i class="fas fa-save me-1"></i> {{ isset($ue) ? 'Mettre a jour' : 'Enregistrer' }}
            </button>
        </div>

    </form>
</div>
@endsection

@php
    $initialEcues = [];
    if (isset($ue) && $ue->matieres) {
        $initialEcues = $ue->matieres->map(function ($m) {
            return [
                'name' => $m->name,
                'code' => $m->code ?? '',
                'coefficient_ecue' => $m->coefficient_ecue ?? $m->coefficient ?? '',
                'credit_ecue' => $m->credit_ecue ?? '',
                'ordre_bulletin' => $m->ordre_bulletin ?? 0,
            ];
        })->values()->toArray();
    }
@endphp

@push('scripts')
<script>
    function lmdUeForm() {
        return {
            ueName: @json(old('name', $ue->name ?? '')),
            ueCode: @json(old('code', $ue->code ?? '')),
            ecues: @json($initialEcues),

            autoCode() {
                if (!this.ueName) {
                    this.ueCode = '';
                    return;
                }
                // Generate code from name: take first letters of each word, uppercase
                const words = this.ueName.trim().split(/\s+/).filter(w => w.length > 0);
                const abbr = words.map(w => w.substring(0, 3).toUpperCase()).slice(0, 3).join('-');
                this.ueCode = 'UE-' + abbr;
            },

            addEcue() {
                this.ecues.push({
                    name: '',
                    code: '',
                    coefficient_ecue: '',
                    credit_ecue: '',
                    ordre_bulletin: 0
                });
            },

            removeEcue(index) {
                this.ecues.splice(index, 1);
            }
        }
    }
</script>
@endpush
