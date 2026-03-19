@extends('layouts.app')

@section('title', 'Générer un bulletin LMD — KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* ── LMD Bulletin Select/Generate ── */
    .lmd-hero {
        background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
        border-radius: 16px;
        padding: 2rem 2.5rem;
        color: #fff;
        margin-bottom: 1.5rem;
    }
    .lmd-hero-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .lmd-hero-left {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .lmd-hero-avatar {
        width: 56px;
        height: 56px;
        border-radius: 14px;
        background: rgba(255,255,255,.15);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    .lmd-hero-info h1 {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0 0 .25rem;
        color: #fff;
    }
    .lmd-hero-info p {
        margin: 0;
        opacity: .85;
        font-size: .9rem;
    }
    .lmd-hero-breadcrumb {
        display: flex;
        align-items: center;
        gap: .4rem;
        margin-top: .4rem;
        font-size: .8rem;
        opacity: .75;
    }
    .lmd-hero-breadcrumb a { color: #fff; text-decoration: underline; }
    .lmd-hero-actions {
        display: flex;
        gap: .5rem;
    }
    .lmd-hero-btn {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .55rem 1.1rem;
        border-radius: 10px;
        font-size: .85rem;
        font-weight: 600;
        border: 1.5px solid rgba(255,255,255,.35);
        color: #fff;
        background: transparent;
        text-decoration: none;
        transition: all .2s;
    }
    .lmd-hero-btn:hover { background: rgba(255,255,255,.15); color: #fff; text-decoration: none; }

    /* Form card */
    .lmd-form-card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(0,0,0,.06);
        max-width: 680px;
        margin: 0 auto;
        overflow: hidden;
    }
    .lmd-form-header {
        padding: 1.5rem 2rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .lmd-form-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 .25rem;
    }
    .lmd-form-subtitle {
        font-size: .85rem;
        color: #64748b;
        margin: 0;
    }
    .lmd-form-body {
        padding: 2rem;
    }
    .lmd-form-group {
        margin-bottom: 1.5rem;
    }
    .lmd-form-label {
        display: block;
        font-size: .85rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: .4rem;
    }
    .lmd-form-label .lmd-required {
        color: #dc2626;
        margin-left: .15rem;
    }
    .lmd-form-select,
    .lmd-form-input {
        width: 100%;
        padding: .65rem 1rem;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        font-size: .9rem;
        color: #1e293b;
        background: #fff;
        transition: border-color .2s, box-shadow .2s;
    }
    .lmd-form-select:focus,
    .lmd-form-input:focus {
        outline: none;
        border-color: #0453cb;
        box-shadow: 0 0 0 3px rgba(4,83,203,.1);
    }
    .lmd-form-hint {
        font-size: .78rem;
        color: #64748b;
        margin-top: .3rem;
    }

    /* Radio cards */
    .lmd-radio-group {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: .75rem;
    }
    .lmd-radio-card {
        position: relative;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 1.25rem;
        cursor: pointer;
        transition: all .2s;
        text-align: center;
    }
    .lmd-radio-card:hover { border-color: #94b8e8; }
    .lmd-radio-card.lmd-radio-active {
        border-color: #0453cb;
        background: rgba(4,83,203,.04);
    }
    .lmd-radio-card input[type="radio"] {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    .lmd-radio-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        margin: 0 auto .75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        background: rgba(4,83,203,.08);
        color: #0453cb;
    }
    .lmd-radio-card.lmd-radio-active .lmd-radio-icon {
        background: #0453cb;
        color: #fff;
    }
    .lmd-radio-title {
        font-size: .9rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: .2rem;
    }
    .lmd-radio-desc {
        font-size: .78rem;
        color: #64748b;
    }

    /* Submit */
    .lmd-form-footer {
        padding: 1.5rem 2rem;
        border-top: 1px solid #f1f5f9;
        display: flex;
        justify-content: flex-end;
        gap: .75rem;
    }
    .lmd-submit-btn {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .65rem 1.5rem;
        border-radius: 10px;
        font-size: .9rem;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all .2s;
        text-decoration: none;
    }
    .lmd-submit-btn--primary {
        background: #0453cb;
        color: #fff;
    }
    .lmd-submit-btn--primary:hover { background: #0340a0; }
    .lmd-submit-btn--primary:disabled { background: #94b8e8; cursor: not-allowed; }
    .lmd-submit-btn--secondary {
        background: #f1f5f9;
        color: #64748b;
    }
    .lmd-submit-btn--secondary:hover { background: #e2e8f0; color: #1e293b; }

    /* Student select (conditional) */
    .lmd-student-field {
        overflow: hidden;
        transition: max-height .3s ease, opacity .3s ease;
    }
    .lmd-student-field.lmd-hidden {
        max-height: 0;
        opacity: 0;
        margin-bottom: 0;
    }
    .lmd-student-field.lmd-visible {
        max-height: 200px;
        opacity: 1;
    }

    @media (max-width: 768px) {
        .lmd-hero { padding: 1.5rem; }
        .lmd-hero-content { flex-direction: column; align-items: flex-start; }
        .lmd-form-body { padding: 1.5rem; }
        .lmd-form-footer { padding: 1.25rem 1.5rem; }
        .lmd-radio-group { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<div class="lmd-page" x-data="lmdBulletinSelect()">
    <div class="main-content">

        {{-- Hero --}}
        <div class="lmd-hero">
            <div class="lmd-hero-content">
                <div class="lmd-hero-left">
                    <div class="lmd-hero-avatar"><i class="fas fa-file-export"></i></div>
                    <div class="lmd-hero-info">
                        <h1>Générer un bulletin LMD</h1>
                        <p>Sélectionnez les paramètres pour générer des bulletins semestriels</p>
                        <div class="lmd-hero-breadcrumb">
                            <a href="{{ route('esbtp.lmd.bulletins.index') }}">Bulletins LMD</a>
                            <i class="fas fa-chevron-right"></i>
                            <span>Générer</span>
                        </div>
                    </div>
                </div>
                <div class="lmd-hero-actions">
                    <a href="{{ route('esbtp.lmd.bulletins.index') }}" class="lmd-hero-btn">
                        <i class="fas fa-arrow-left"></i>Retour
                    </a>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Erreurs de validation :</strong>
                <ul class="mb-0 mt-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Form --}}
        <div class="lmd-form-card">
            <div class="lmd-form-header">
                <h2 class="lmd-form-title"><i class="fas fa-cog me-2" style="color:#0453cb;"></i>Paramètres de génération</h2>
                <p class="lmd-form-subtitle">Choisissez la classe, l'année, le semestre et la cible.</p>
            </div>

            <div class="lmd-form-body">

                {{-- Classe --}}
                <div class="lmd-form-group">
                    <label class="lmd-form-label">
                        Classe <span class="lmd-required">*</span>
                    </label>
                    <select class="lmd-form-select" x-model="classeId" @change="onClasseChange()" required>
                        <option value="">— Sélectionner une classe —</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}">
                                {{ $c->name }}
                                @if($c->filiere) ({{ $c->filiere->name }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Année universitaire --}}
                <div class="lmd-form-group">
                    <label class="lmd-form-label">
                        Année universitaire <span class="lmd-required">*</span>
                    </label>
                    <select class="lmd-form-select" x-model="anneeId" required>
                        <option value="">— Sélectionner une année —</option>
                        @foreach($annees as $annee)
                            <option value="{{ $annee->id }}" {{ ($annee->is_current ?? false) ? 'selected' : '' }}>
                                {{ $annee->name ?? $annee->libelle ?? $annee->id }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Semestre --}}
                <div class="lmd-form-group">
                    <label class="lmd-form-label">
                        Semestre <span class="lmd-required">*</span>
                    </label>
                    <select class="lmd-form-select" x-model="semestre" required>
                        <option value="">— Sélectionner —</option>
                        @for($s = 1; $s <= 10; $s++)
                            <option value="{{ $s }}">Semestre {{ $s }}</option>
                        @endfor
                    </select>
                    <div class="lmd-form-hint">
                        L1 : S1-S2 &middot; L2 : S3-S4 &middot; L3 : S5-S6 &middot; M1 : S7-S8 &middot; M2 : S9-S10
                    </div>
                </div>

                {{-- Mode : single or class --}}
                <div class="lmd-form-group">
                    <label class="lmd-form-label">Cible</label>
                    <div class="lmd-radio-group">
                        <label class="lmd-radio-card" :class="{ 'lmd-radio-active': mode === 'etudiant' }"
                               @click="mode = 'etudiant'">
                            <input type="radio" name="generation_mode" value="etudiant" x-model="mode">
                            <div class="lmd-radio-icon"><i class="fas fa-user"></i></div>
                            <div class="lmd-radio-title">Un étudiant</div>
                            <div class="lmd-radio-desc">Générer le bulletin d'un seul étudiant</div>
                        </label>
                        <label class="lmd-radio-card" :class="{ 'lmd-radio-active': mode === 'classe' }"
                               @click="mode = 'classe'">
                            <input type="radio" name="generation_mode" value="classe" x-model="mode">
                            <div class="lmd-radio-icon"><i class="fas fa-users"></i></div>
                            <div class="lmd-radio-title">Toute la classe</div>
                            <div class="lmd-radio-desc">Générer les bulletins de tous les étudiants</div>
                        </label>
                    </div>
                </div>

                {{-- Student select (shown only for single mode) --}}
                <div class="lmd-form-group lmd-student-field" :class="mode === 'etudiant' ? 'lmd-visible' : 'lmd-hidden'">
                    <label class="lmd-form-label">
                        Étudiant <span class="lmd-required">*</span>
                    </label>
                    <select class="lmd-form-select" x-model="etudiantId" x-ref="etudiantSelect"
                            :required="mode === 'etudiant'" :disabled="mode !== 'etudiant'">
                        <option value="">— Sélectionner un étudiant —</option>
                        <template x-for="etu in etudiants" :key="etu.id">
                            <option :value="etu.id" x-text="etu.matricule + ' — ' + etu.nom + ' ' + (etu.prenoms || etu.prenom || '')"></option>
                        </template>
                    </select>
                    <div class="lmd-form-hint" x-show="loading">
                        <i class="fas fa-spinner fa-spin me-1"></i>Chargement des étudiants...
                    </div>
                    <div class="lmd-form-hint" x-show="!loading && classeId && etudiants.length === 0">
                        Aucun étudiant inscrit dans cette classe.
                    </div>
                </div>

            </div>

            <div class="lmd-form-footer">
                <a href="{{ route('esbtp.lmd.bulletins.index') }}" class="lmd-submit-btn lmd-submit-btn--secondary">
                    <i class="fas fa-times"></i>Annuler
                </a>

                {{-- Form classe --}}
                <form x-show="mode === 'classe'" action="{{ route('esbtp.lmd.bulletins.generer-classe') }}" method="POST" style="display:inline;">
                    @csrf
                    <input type="hidden" name="classe_id" :value="classeId">
                    <input type="hidden" name="annee_universitaire_id" :value="anneeId">
                    <input type="hidden" name="semestre" :value="semestre">
                    <button type="submit" class="lmd-submit-btn lmd-submit-btn--primary"
                            :disabled="!classeId || !anneeId || !semestre">
                        <i class="fas fa-file-export"></i>Générer les bulletins
                    </button>
                </form>

                {{-- Form etudiant --}}
                <form x-show="mode === 'etudiant'" action="{{ route('esbtp.lmd.bulletins.generer') }}" method="POST" style="display:inline;">
                    @csrf
                    <input type="hidden" name="classe_id" :value="classeId">
                    <input type="hidden" name="annee_universitaire_id" :value="anneeId">
                    <input type="hidden" name="semestre" :value="semestre">
                    <input type="hidden" name="etudiant_id" :value="etudiantId">
                    <button type="submit" class="lmd-submit-btn lmd-submit-btn--primary"
                            :disabled="!classeId || !anneeId || !semestre || !etudiantId">
                        <i class="fas fa-user-check"></i>Générer le bulletin
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
function lmdBulletinSelect() {
    return {
        mode: 'classe',
        classeId: '',
        anneeId: '{{ $annees->where("is_current", true)->first()?->id ?? "" }}',
        semestre: '',
        etudiantId: '',
        etudiants: [],
        loading: false,

        onClasseChange() {
            this.etudiants = [];
            this.etudiantId = '';
            if (!this.classeId) return;
            if (this.mode === 'etudiant') {
                this.fetchEtudiants();
            }
        },

        async fetchEtudiants() {
            this.loading = true;
            try {
                const resp = await fetch(`/esbtp/resultats/load-etudiants?classe_id=${this.classeId}`);
                const data = await resp.json();
                this.etudiants = data.etudiants || data.data || data || [];
            } catch (err) {
                console.error('Erreur chargement étudiants:', err);
            } finally {
                this.loading = false;
            }
        },

        init() {
            this.$watch('mode', (val) => {
                if (val === 'etudiant' && this.classeId && this.etudiants.length === 0) {
                    this.fetchEtudiants();
                }
            });
        }
    };
}
</script>
@endpush
