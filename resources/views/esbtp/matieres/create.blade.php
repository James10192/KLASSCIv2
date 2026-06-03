@extends('layouts.app')

@section('title', 'Ajouter une matière - KLASSCI')

@section('styles')
<link href="{{ asset('css/dashboard-moderne.css') }}" rel="stylesheet">
<style>
/* ═══════════════════════════════════════════
   MATIERE CREATE — PREMIUM (mc-*)
   ═══════════════════════════════════════════ */

/* --- Hero --- */
.mc-hero {
    background: linear-gradient(135deg, #0453cb 0%, #1b64d4 50%, #5e91de 100%);
    padding: 24px 28px 20px;
    margin: -24px -24px 0;
    position: relative;
    overflow: hidden;
}
.mc-hero::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background-image: radial-gradient(circle at 20% 80%, rgba(255,255,255,0.05) 0%, transparent 50%);
}
.mc-hero-inner {
    position: relative;
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}
.mc-hero-icon {
    width: 48px; height: 48px; border-radius: 12px;
    background: rgba(255,255,255,0.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; color: #fff; flex-shrink: 0;
}
.mc-hero-text { flex: 1; min-width: 200px; }
.mc-hero-label {
    font-size: 0.68rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.08em;
    color: rgba(255,255,255,0.6); margin-bottom: 2px;
}
.mc-hero-name { font-size: 1.3rem; font-weight: 800; color: #fff; }
.mc-hero-sub { font-size: 0.82rem; color: rgba(255,255,255,0.75); margin-top: 2px; }
.mc-hero-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-left: auto; }
.mc-hero-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; border-radius: 8px;
    font-size: 0.82rem; font-weight: 600;
    border: 1px solid rgba(255,255,255,0.3);
    background: rgba(255,255,255,0.12);
    color: #fff; text-decoration: none;
    transition: all 0.2s ease;
}
.mc-hero-btn:hover { background: rgba(255,255,255,0.22); color: #fff; transform: translateY(-1px); }

/* --- Card --- */
.mc-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.04);
    margin-top: 20px;
    overflow: hidden;
    transition: box-shadow 0.25s ease;
}
.mc-card:hover { box-shadow: 0 2px 6px rgba(0,0,0,0.08), 0 8px 24px rgba(0,0,0,0.06); }
.mc-card-header {
    display: flex; align-items: center; gap: 12px;
    padding: 16px 24px;
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    border-bottom: 1px solid #e2e8f0;
}
.mc-card-header-icon {
    width: 36px; height: 36px; border-radius: 10px;
    background: linear-gradient(135deg, #0453cb, #5e91de);
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.85rem; flex-shrink: 0;
}
.mc-card-header-title { font-size: 0.95rem; font-weight: 700; color: #1e293b; }
.mc-card-header-sub { font-size: 0.78rem; color: #64748b; }
.mc-card-body { padding: 24px; }

/* --- Form elements --- */
.mc-card .form-label, .mc-card label:not(.form-check-label):not(.fn-niveau-pill) {
    font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 4px;
}
.mc-card .form-control, .mc-card .form-select {
    border: 1px solid #e2e8f0; border-radius: 10px;
    padding: 10px 14px; font-size: 0.88rem;
    transition: all 0.15s ease;
}
.mc-card .form-control:focus, .mc-card .form-select:focus {
    border-color: #0453cb;
    box-shadow: 0 0 0 3px rgba(4,83,203,0.1);
}
.mc-card .form-text { font-size: 0.75rem; color: #94a3b8; }
.mc-card .text-danger { color: #dc2626 !important; }

/* --- Alerts --- */
.mc-card .alert { border-radius: 10px; font-size: 0.85rem; border: none; }
.mc-card .alert-info { background: rgba(59,130,246,0.06); color: #1e40af; border: 1px solid rgba(59,130,246,0.12); }
.mc-card .alert-warning { background: rgba(245,158,11,0.06); color: #92400e; border: 1px solid rgba(245,158,11,0.12); }
.mc-card .alert-danger { background: rgba(239,68,68,0.06); color: #991b1b; border: 1px solid rgba(239,68,68,0.12); }
.mc-card .alert-success { background: rgba(16,185,129,0.06); color: #065f46; border: 1px solid rgba(16,185,129,0.12); }

/* --- Buttons --- */
.mc-btn-primary {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 12px 28px; border-radius: 10px;
    background: linear-gradient(135deg, #0453cb, #1b64d4);
    color: #fff; border: none; font-weight: 700; font-size: 0.88rem;
    box-shadow: 0 2px 8px rgba(4,83,203,0.25);
    transition: all 0.2s ease; cursor: pointer;
}
.mc-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(4,83,203,0.35); }
.mc-btn-secondary {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 12px 24px; border-radius: 10px;
    background: #fff; color: #64748b; border: 1px solid #e2e8f0;
    font-weight: 600; font-size: 0.88rem;
    transition: all 0.2s ease; cursor: pointer;
}
.mc-btn-secondary:hover { border-color: #cbd5e1; color: #475569; }

/* --- Color picker premium --- */
.mc-color-wrap {
    display: flex; align-items: center; gap: 12px;
}
.mc-color-wrap input[type="color"] {
    width: 44px; height: 44px; border: 2px solid #e2e8f0;
    border-radius: 10px; padding: 2px; cursor: pointer;
}
.mc-color-wrap input[type="color"]:focus { border-color: #0453cb; }

/* --- Switch premium --- */
.mc-switch-row {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 18px; background: #f8fafc;
    border: 1px solid #e2e8f0; border-radius: 12px;
}
.mc-switch-row .form-check-input { width: 3em; height: 1.5em; }
.mc-switch-row .form-check-input:checked { background-color: #0453cb; border-color: #0453cb; }
.mc-switch-text { font-size: 0.85rem; color: #334155; font-weight: 600; }
.mc-switch-hint { font-size: 0.75rem; color: #94a3b8; }

/* --- FN section (copied from matieres/index modal) --- */
.fn-section { background: #f1f5f9; border-radius: 14px; padding: 1.25rem; border: 1px solid #e2e8f0; max-height: 520px; overflow-y: auto; }
.fn-section-header { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem; padding-bottom: 1rem; border-bottom: 1px solid #e2e8f0; }
.fn-section-icon { width: 38px; height: 38px; background: linear-gradient(135deg, #0453cb, #1a6ee8); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 3px 10px rgba(4,83,203,0.28); }
.fn-section-icon i { color: #fff; font-size: 0.9rem; }
.fn-section-title { font-size: 0.95rem; font-weight: 700; color: #1e293b; margin: 0; }
.fn-section-subtitle { font-size: 0.78rem; color: #64748b; margin: 0; }
.fn-counter { margin-left: auto; background: rgba(4,83,203,0.08); color: #0453cb; font-size: 0.72rem; font-weight: 700; padding: 0.2rem 0.65rem; border-radius: 20px; border: 1px solid rgba(4,83,203,0.15); }
.fn-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; }
.fn-filiere-card { background: #fff; border-radius: 14px; box-shadow: 0 2px 8px rgba(4,83,203,0.08), 0 0 0 1px rgba(4,83,203,0.06); overflow: hidden; transition: all 0.22s cubic-bezier(0.4,0,0.2,1); animation: fn-fadeIn 0.3s ease both; }
.fn-filiere-card:hover { box-shadow: 0 6px 24px rgba(4,83,203,0.14), 0 0 0 1.5px rgba(4,83,203,0.14); transform: translateY(-1px); }
.fn-filiere-card.has-selection { box-shadow: 0 4px 20px rgba(4,83,203,0.18), 0 0 0 2px rgba(4,83,203,0.22); }
.fn-filiere-header { padding: 0.85rem 1rem; background: linear-gradient(135deg, #f8faff, #eef3ff); border-bottom: 1px solid rgba(4,83,203,0.15); display: flex; align-items: center; gap: 0.6rem; }
.fn-filiere-dot { width: 8px; height: 8px; border-radius: 50%; background: #0453cb; flex-shrink: 0; transition: all 0.22s; }
.fn-filiere-card.has-selection .fn-filiere-dot { background: #059669; box-shadow: 0 0 0 3px rgba(5,150,105,0.2); }
.fn-filiere-name { font-size: 0.82rem; font-weight: 700; color: #1e293b; flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.fn-filiere-code { font-size: 0.67rem; font-weight: 700; color: #0453cb; background: rgba(4,83,203,0.08); border: 1px solid rgba(4,83,203,0.15); border-radius: 6px; padding: 0.15rem 0.45rem; flex-shrink: 0; }
.fn-filiere-sel-badge { font-size: 0.65rem; font-weight: 600; color: #059669; background: rgba(5,150,105,0.1); border-radius: 10px; padding: 0.15rem 0.4rem; display: none; flex-shrink: 0; }
.fn-filiere-card.has-selection .fn-filiere-sel-badge { display: inline; }
.fn-filiere-actions { padding: 0.5rem 1rem 0; display: flex; justify-content: flex-end; }
.fn-select-all-btn { font-size: 0.7rem; color: #64748b; cursor: pointer; background: none; border: none; padding: 0.15rem 0.4rem; border-radius: 6px; transition: all 0.22s; font-weight: 500; display: flex; align-items: center; gap: 0.3rem; }
.fn-select-all-btn:hover { color: #0453cb; background: rgba(4,83,203,0.08); }
.fn-select-all-btn.all-selected { color: #059669; }
.fn-niveaux-body { padding: 0.75rem 1rem 1rem; display: flex; flex-wrap: wrap; gap: 0.5rem; }
.fn-niveau-checkbox { position: absolute; opacity: 0; width: 0; height: 0; pointer-events: none; }
.fn-niveau-pill { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.35rem 0.75rem; border-radius: 22px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #64748b; font-size: 0.775rem; font-weight: 600; cursor: pointer; user-select: none; transition: all 0.22s; white-space: nowrap; }
.fn-niveau-pill .fn-pill-check { width: 14px; height: 14px; border-radius: 50%; border: 1.5px solid currentColor; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all 0.22s; font-size: 0.6rem; }
.fn-niveau-pill .fn-pill-check i { opacity: 0; transform: scale(0.4); transition: all 0.22s; }
.fn-niveau-pill .fn-pill-code { font-size: 0.64rem; opacity: 0.65; font-weight: 500; }
.fn-niveau-pill:hover { border-color: #0453cb; color: #0453cb; background: rgba(4,83,203,0.06); transform: translateY(-1px); box-shadow: 0 2px 8px rgba(4,83,203,0.12); }
.fn-niveau-pill.active { background: linear-gradient(135deg, #0453cb, #1a6ee8); border-color: #0453cb; color: #fff; box-shadow: 0 3px 12px rgba(4,83,203,0.32); transform: translateY(-1px); }
.fn-niveau-pill.active .fn-pill-check { border-color: rgba(255,255,255,0.7); background: rgba(255,255,255,0.25); }
.fn-niveau-pill.active .fn-pill-check i { opacity: 1; transform: scale(1); color: #fff; }
.fn-niveau-pill.active:hover { background: linear-gradient(135deg, #0342a8, #1058cc); box-shadow: 0 4px 16px rgba(4,83,203,0.4); color: #fff; }
.fn-empty-niveaux { width: 100%; text-align: center; padding: 1rem 0.5rem; color: #64748b; font-size: 0.78rem; }
@keyframes fn-fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
.fn-filiere-card:nth-child(1) { animation-delay: 0.04s; } .fn-filiere-card:nth-child(2) { animation-delay: 0.08s; } .fn-filiere-card:nth-child(3) { animation-delay: 0.12s; }
.fn-filiere-card:nth-child(4) { animation-delay: 0.16s; } .fn-filiere-card:nth-child(5) { animation-delay: 0.20s; } .fn-filiere-card:nth-child(6) { animation-delay: 0.24s; }
@media (max-width: 600px) { .fn-grid { grid-template-columns: 1fr; } }

/* --- Responsive --- */
@media (max-width: 768px) {
    .mc-hero { padding: 16px; margin: -16px -16px 0; }
    .mc-hero-name { font-size: 1.1rem; }
    .mc-hero-actions { width: 100%; margin-left: 0; }
    .mc-card-body { padding: 16px; }
}
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content" style="padding: 1.5rem; max-width: 100%; overflow-x: hidden;">

    <!-- Hero -->
    <div class="mc-hero">
        <div class="mc-hero-inner">
            <div class="mc-hero-icon"><i class="fas fa-plus-circle"></i></div>
            <div class="mc-hero-text">
                <div class="mc-hero-label"><i class="fas fa-book me-1"></i>Nouvelle matière</div>
                <div class="mc-hero-name">Ajouter une Matière</div>
                <div class="mc-hero-sub">Créez une nouvelle matière avec ses associations</div>
            </div>
            <div class="mc-hero-actions">
                <a href="{{ route('esbtp.matieres.index') }}" class="mc-hero-btn">
                    <i class="fas fa-list"></i> Liste des matières
                </a>
            </div>
        </div>
    </div>

    <!-- Errors -->
    @if(session('error'))
        <div class="mc-card" style="margin-top:16px;border-left:4px solid #dc2626;">
            <div class="mc-card-body" style="padding:14px 20px;">
                <div class="d-flex align-items-center gap-2" style="color:#991b1b;font-size:0.88rem;">
                    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                </div>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="mc-card" style="margin-top:16px;border-left:4px solid #dc2626;">
            <div class="mc-card-body" style="padding:14px 20px;">
                <h6 style="color:#991b1b;font-weight:700;font-size:0.85rem;margin-bottom:8px;">Erreurs de validation</h6>
                <ul style="margin:0;padding-left:18px;color:#991b1b;font-size:0.82rem;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @if(isset($preselectedFiliereId) || isset($preselectedNiveauId))
        <div class="mc-card" style="margin-top:16px;border-left:4px solid #10b981;">
            <div class="mc-card-body" style="padding:14px 20px;">
                <div class="d-flex align-items-center gap-2" style="color:#065f46;font-size:0.85rem;">
                    <i class="fas fa-info-circle"></i>
                    <span>Pré-sélection :
                        @if(isset($preselectedFiliereId))
                            @php $selectedFiliere = $filieres->firstWhere('id', $preselectedFiliereId); @endphp
                            <strong>{{ $selectedFiliere ? $selectedFiliere->name : "Filière ID $preselectedFiliereId" }}</strong>
                        @endif
                        @if(isset($preselectedFiliereId) && isset($preselectedNiveauId)) · @endif
                        @if(isset($preselectedNiveauId))
                            @php $selectedNiveau = $niveauxEtudes->firstWhere('id', $preselectedNiveauId); @endphp
                            <strong>{{ $selectedNiveau ? $selectedNiveau->name : "Niveau ID $preselectedNiveauId" }}</strong>
                        @endif
                    </span>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('esbtp.matieres.store') }}" method="POST">
        @csrf

        <div class="row" style="margin-top:4px;">
            <!-- Colonne gauche : Identité -->
            <div class="col-md-6">
                <div class="mc-card">
                    <div class="mc-card-header">
                        <div class="mc-card-header-icon"><i class="fas fa-info-circle"></i></div>
                        <div>
                            <div class="mc-card-header-title">Identité de la matière</div>
                            <div class="mc-card-header-sub">Code, nom et paramètres</div>
                        </div>
                    </div>
                    <div class="mc-card-body">
                        <div class="mb-3">
                            <label for="code" class="form-label">Code de la matière</label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code') }}" placeholder="Généré automatiquement si vide">
                            <div class="form-text">Laissez vide pour une génération automatique à partir du nom.</div>
                            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">Nom complet <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required placeholder="Ex: Mathématiques Appliquées">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="type_formation" class="form-label">Type de formation <span class="text-danger">*</span></label>
                            <select class="form-select @error('type_formation') is-invalid @enderror" id="type_formation" name="type_formation" required>
                                <option value="generale" {{ old('type_formation') == 'generale' ? 'selected' : '' }}>Formation générale</option>
                                <option value="technologique_professionnelle" {{ old('type_formation') == 'technologique_professionnelle' ? 'selected' : '' }}>Formation technologique et professionnelle</option>
                            </select>
                            @error('type_formation') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Couleur de la matière</label>
                            <div class="mc-color-wrap">
                                <input type="color" class="@error('couleur') is-invalid @enderror" id="couleur" name="couleur" value="{{ old('couleur', '#0453cb') }}">
                                <div>
                                    <div style="font-size:0.82rem;font-weight:600;color:#334155;">Couleur de l'emploi du temps</div>
                                    <div class="form-text" style="margin:0;">Utilisée pour représenter la matière dans le planning.</div>
                                </div>
                            </div>
                            @error('couleur') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" placeholder="Description optionnelle de la matière...">{{ old('description') }}</textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mc-switch-row">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                            <div>
                                <div class="mc-switch-text">Matière active</div>
                                <div class="mc-switch-hint">Une matière inactive ne sera pas disponible dans les emplois du temps ou les évaluations.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Colonne droite : Liaisons Filières × Niveaux -->
            <div class="col-md-6">
                <div class="mc-card">
                    <div class="mc-card-header">
                        <div class="mc-card-header-icon"><i class="fas fa-link"></i></div>
                        <div>
                            <div class="mc-card-header-title">Liaisons Filières & Niveaux</div>
                            <div class="mc-card-header-sub">Cliquez sur un niveau pour activer la liaison</div>
                        </div>
                    </div>
                    <div class="mc-card-body">
                        <div class="fn-section" @error('liaisons') style="border-color:#dc2626;" @enderror>
                            <div class="fn-section-header">
                                <div class="fn-section-icon"><i class="fas fa-graduation-cap"></i></div>
                                <div>
                                    <p class="fn-section-title">Filières & Niveaux</p>
                                    <p class="fn-section-subtitle">Cliquez sur un niveau pour activer la liaison</p>
                                </div>
                                <span class="fn-counter" id="mc-fn-global-counter">0 sélection</span>
                            </div>

                            <div class="fn-grid" id="mc-filieres-niveaux-grid">
                                @foreach($filieres as $filiere)
                                <div class="fn-filiere-card" data-filiere-id="{{ $filiere->id }}" id="mc-fn-card-{{ $filiere->id }}">
                                    <div class="fn-filiere-header">
                                        <span class="fn-filiere-dot"></span>
                                        <span class="fn-filiere-name" title="{{ $filiere->name }}">{{ $filiere->name }}</span>
                                        @if($filiere->code)
                                            <span class="fn-filiere-code">{{ $filiere->code }}</span>
                                        @endif
                                        <span class="fn-filiere-sel-badge" id="mc-fn-badge-{{ $filiere->id }}">&#10003;</span>
                                    </div>
                                    <div class="fn-filiere-actions">
                                        <button type="button" class="fn-select-all-btn" id="mc-fn-selectall-{{ $filiere->id }}"
                                                onclick="mcFnToggleAll({{ $filiere->id }}, this)">
                                            <i class="fas fa-check-double"></i><span>Tout sélectionner</span>
                                        </button>
                                    </div>
                                    <div class="fn-niveaux-body">
                                        @forelse($niveauxEtudes as $niveau)
                                        <span class="fn-niveau-pill" id="mc-fn-pill-{{ $filiere->id }}-{{ $niveau->id }}"
                                              onclick="mcFnToggle(this, {{ $filiere->id }}, {{ $niveau->id }})"
                                              title="{{ $niveau->name }}">
                                            <span class="fn-pill-check"><i class="fas fa-check"></i></span>
                                            {{ $niveau->name }}
                                            @if($niveau->code)
                                                <span class="fn-pill-code">{{ $niveau->code }}</span>
                                            @endif
                                        </span>
                                        @empty
                                        <div class="fn-empty-niveaux"><i class="fas fa-inbox me-1"></i>Aucun niveau</div>
                                        @endforelse
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @error('liaisons') <div style="color:#dc2626;font-size:0.82rem;margin-top:8px;">{{ $message }}</div> @enderror

                        <!-- Hidden inputs container (populated by JS) -->
                        <div id="mc-liaisons-hidden"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:20px;padding:16px 0;">
            <button type="reset" class="mc-btn-secondary">
                <i class="fas fa-undo"></i> Réinitialiser
            </button>
            <button type="submit" class="mc-btn-primary">
                <i class="fas fa-save"></i> Enregistrer la matière
            </button>
        </div>
    </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Auto-generate code from name
    $('#name').on('blur', function() {
        if ($('#code').val() === '') {
            let name = $(this).val().trim().toUpperCase();
            if (name) {
                let code = name.split(/\s+/).map(word => word.substring(0, 3)).join('');
                $('#code').val(code);
            }
        }
    });
});

// ═══ Filières × Niveaux pill-based liaisons (mc-fn-*) ═══

/** Toggle a single niveau pill */
function mcFnToggle(pillEl, filiereId, niveauId) {
    pillEl.classList.toggle('active');
    mcFnUpdateCard(filiereId);
    mcFnUpdateCounter();
    mcFnSyncHiddenInputs();
}

/** Toggle all niveaux for a filière */
function mcFnToggleAll(filiereId, btn) {
    const card = document.getElementById('mc-fn-card-' + filiereId);
    const pills = card.querySelectorAll('.fn-niveau-pill');
    const allActive = Array.from(pills).every(p => p.classList.contains('active'));
    pills.forEach(p => p.classList.toggle('active', !allActive));
    mcFnUpdateCard(filiereId);
    mcFnUpdateCounter();
    mcFnSyncHiddenInputs();
}

/** Update a filière card visual state */
function mcFnUpdateCard(filiereId) {
    const card = document.getElementById('mc-fn-card-' + filiereId);
    const pills = card.querySelectorAll('.fn-niveau-pill');
    const btn = document.getElementById('mc-fn-selectall-' + filiereId);
    const activeCount = card.querySelectorAll('.fn-niveau-pill.active').length;
    card.classList.toggle('has-selection', activeCount > 0);
    if (btn) {
        const allSelected = activeCount === pills.length && pills.length > 0;
        btn.classList.toggle('all-selected', allSelected);
        const icon = btn.querySelector('i');
        const span = btn.querySelector('span');
        if (allSelected) { icon.className = 'fas fa-times-circle'; span.textContent = 'Tout désélectionner'; }
        else { icon.className = 'fas fa-check-double'; span.textContent = 'Tout sélectionner'; }
    }
}

/** Update global counter */
function mcFnUpdateCounter() {
    const total = document.querySelectorAll('#mc-filieres-niveaux-grid .fn-niveau-pill.active').length;
    const counter = document.getElementById('mc-fn-global-counter');
    if (counter) counter.textContent = total + ' sélection' + (total > 1 ? 's' : '');
}

/** Sync hidden inputs for form submission: liaisons[0][filiere_id], liaisons[0][niveau_id] */
function mcFnSyncHiddenInputs() {
    const container = document.getElementById('mc-liaisons-hidden');
    container.innerHTML = '';
    let idx = 0;
    document.querySelectorAll('#mc-filieres-niveaux-grid .fn-niveau-pill.active').forEach(pill => {
        const card = pill.closest('.fn-filiere-card');
        const filiereId = card.dataset.filiereId;
        // Extract niveau ID from pill ID: mc-fn-pill-{filiereId}-{niveauId}
        const pillId = pill.id; // mc-fn-pill-X-Y
        const parts = pillId.split('-');
        const niveauId = parts[parts.length - 1];

        container.innerHTML += `<input type="hidden" name="liaisons[${idx}][filiere_id]" value="${filiereId}">`;
        container.innerHTML += `<input type="hidden" name="liaisons[${idx}][niveau_id]" value="${niveauId}">`;
        idx++;
    });
}
</script>
@endpush
