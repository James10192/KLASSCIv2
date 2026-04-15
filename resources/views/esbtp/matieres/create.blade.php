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

/* --- FN section reuse from index (scoped copy) --- */
.mc-fn-section {
    background: #f1f5f9; border-radius: 14px;
    padding: 1.25rem; border: 1px solid #e2e8f0;
}
.mc-fn-header {
    display: flex; align-items: center; gap: 0.75rem;
    margin-bottom: 1rem; padding-bottom: 0.75rem;
    border-bottom: 1px solid #e2e8f0;
}
.mc-fn-icon {
    width: 34px; height: 34px; border-radius: 9px;
    background: linear-gradient(135deg, #0453cb, #1a6ee8);
    display: flex; align-items: center; justify-content: center;
    font-size: 0.8rem; color: #fff; flex-shrink: 0;
}
.mc-fn-title { font-size: 0.88rem; font-weight: 700; color: #1e293b; }
.mc-fn-sub { font-size: 0.72rem; color: #64748b; }
.mc-fn-counter {
    margin-left: auto;
    background: rgba(4,83,203,0.08); color: #0453cb;
    font-size: 0.72rem; font-weight: 700;
    padding: 0.2rem 0.65rem; border-radius: 20px;
    border: 1px solid rgba(4,83,203,0.15);
}

/* Checkbox list premium */
.mc-check-list {
    max-height: 220px; overflow-y: auto;
    border: 1px solid #e2e8f0; border-radius: 10px;
    background: #fff; padding: 4px;
}
.mc-check-item {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 12px; border-radius: 8px;
    transition: all 0.15s ease; cursor: pointer;
}
.mc-check-item:hover { background: rgba(4,83,203,0.04); }
.mc-check-item input[type="checkbox"] {
    width: 18px; height: 18px; border-radius: 4px;
    border: 2px solid #cbd5e1; cursor: pointer;
    accent-color: #0453cb;
}
.mc-check-item input[type="checkbox"]:checked { border-color: #0453cb; }
.mc-check-item-label { font-size: 0.85rem; font-weight: 600; color: #1e293b; cursor: pointer; }
.mc-check-item-code {
    font-size: 0.7rem; font-weight: 600; color: #0453cb;
    background: rgba(4,83,203,0.08); padding: 1px 6px;
    border-radius: 4px; margin-left: auto;
}
.mc-check-item .badge { font-size: 0.65rem; }

/* Combinations preview */
.mc-combos {
    padding: 14px; background: rgba(4,83,203,0.04);
    border: 1px solid rgba(4,83,203,0.1); border-radius: 12px;
}
.mc-combo-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 10px; border-radius: 8px;
    background: linear-gradient(135deg, #0453cb, #1a6ee8);
    color: #fff; font-size: 0.72rem; font-weight: 600;
    margin: 3px;
}

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

            <!-- Colonne droite : Associations -->
            <div class="col-md-6">
                <div class="mc-card">
                    <div class="mc-card-header">
                        <div class="mc-card-header-icon"><i class="fas fa-link"></i></div>
                        <div>
                            <div class="mc-card-header-title">Associations</div>
                            <div class="mc-card-header-sub">Filières et niveaux d'étude</div>
                        </div>
                    </div>
                    <div class="mc-card-body">
                        <!-- Filières -->
                        <div class="mb-4">
                            <div class="mc-fn-header" style="margin-bottom:10px;padding-bottom:8px;">
                                <div class="mc-fn-icon"><i class="fas fa-graduation-cap"></i></div>
                                <div>
                                    <div class="mc-fn-title">Filières</div>
                                    <div class="mc-fn-sub">Sélection multiple autorisée</div>
                                </div>
                                <span class="mc-fn-counter" id="mc-filiere-counter">0 sélectionnée(s)</span>
                            </div>
                            <div class="mc-check-list @error('filieres') is-invalid @enderror">
                                @foreach($filieres as $filiere)
                                <label class="mc-check-item" for="create_filiere_{{ $filiere->id }}">
                                    <input class="filiere-check" type="checkbox"
                                           value="{{ $filiere->id }}"
                                           id="create_filiere_{{ $filiere->id }}"
                                           name="filieres[]"
                                           {{ in_array($filiere->id, old('filieres', isset($preselectedFiliereId) ? [$preselectedFiliereId] : [])) ? 'checked' : '' }}>
                                    <span class="mc-check-item-label">{{ $filiere->name }}</span>
                                    @if($filiere->code)
                                        <span class="mc-check-item-code">{{ $filiere->code }}</span>
                                    @endif
                                    @if(isset($preselectedFiliereId) && $filiere->id == $preselectedFiliereId)
                                        <span class="badge bg-success ms-1" style="font-size:0.6rem;padding:2px 6px;">Pré-sélectionnée</span>
                                    @endif
                                </label>
                                @endforeach
                            </div>
                            @error('filieres') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <!-- Niveaux -->
                        <div class="mb-4">
                            <div class="mc-fn-header" style="margin-bottom:10px;padding-bottom:8px;">
                                <div class="mc-fn-icon"><i class="fas fa-layer-group"></i></div>
                                <div>
                                    <div class="mc-fn-title">Niveaux d'étude</div>
                                    <div class="mc-fn-sub">Sélection multiple autorisée</div>
                                </div>
                                <span class="mc-fn-counter" id="mc-niveau-counter">0 sélectionné(s)</span>
                            </div>
                            <div class="mc-check-list @error('niveaux') is-invalid @enderror">
                                @foreach($niveauxEtudes as $niveau)
                                <label class="mc-check-item" for="create_niveau_{{ $niveau->id }}">
                                    <input class="niveau-check" type="checkbox"
                                           value="{{ $niveau->id }}"
                                           id="create_niveau_{{ $niveau->id }}"
                                           name="niveaux[]"
                                           {{ in_array($niveau->id, old('niveaux', isset($preselectedNiveauId) ? [$preselectedNiveauId] : [])) ? 'checked' : '' }}>
                                    <span class="mc-check-item-label">{{ $niveau->name }}</span>
                                    @if($niveau->code)
                                        <span class="mc-check-item-code">{{ $niveau->code }}</span>
                                    @endif
                                    @if(isset($preselectedNiveauId) && $niveau->id == $preselectedNiveauId)
                                        <span class="badge bg-success ms-1" style="font-size:0.6rem;padding:2px 6px;">Pré-sélectionné</span>
                                    @endif
                                </label>
                                @endforeach
                            </div>
                            @error('niveaux') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <!-- Aperçu combinaisons -->
                        <div id="create-combinations-preview" class="mc-combos">
                            <div class="d-flex align-items-center gap-2" style="color:#64748b;font-size:0.82rem;">
                                <i class="fas fa-info-circle"></i>
                                Sélectionnez des filières et des niveaux pour voir les combinaisons.
                            </div>
                        </div>
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

@section('scripts')
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

    // Update counters
    function updateCounters() {
        const fCount = $('.filiere-check:checked').length;
        const nCount = $('.niveau-check:checked').length;
        $('#mc-filiere-counter').text(fCount + ' sélectionnée(s)');
        $('#mc-niveau-counter').text(nCount + ' sélectionné(s)');
    }

    // Update combinations preview
    function updateCreateCombinationsPreview() {
        const selectedFilieres = [];
        const selectedNiveaux = [];

        $('.filiere-check:checked').each(function() {
            selectedFilieres.push($(this).next('.mc-check-item-label').text().trim());
        });

        $('.niveau-check:checked').each(function() {
            selectedNiveaux.push($(this).next('.mc-check-item-label').text().trim());
        });

        const previewDiv = $('#create-combinations-preview');

        if (selectedFilieres.length === 0 || selectedNiveaux.length === 0) {
            previewDiv.html(`
                <div class="d-flex align-items-center gap-2" style="color:#64748b;font-size:0.82rem;">
                    <i class="fas fa-info-circle"></i>
                    Sélectionnez au moins une filière et un niveau pour voir les combinaisons.
                </div>
            `);
            return;
        }

        let html = `<div style="font-size:0.78rem;font-weight:700;color:#0453cb;margin-bottom:8px;">
            <i class="fas fa-check-circle me-1"></i>${selectedFilieres.length * selectedNiveaux.length} combinaison(s)
        </div><div style="display:flex;flex-wrap:wrap;">`;

        selectedFilieres.forEach(filiere => {
            selectedNiveaux.forEach(niveau => {
                html += `<span class="mc-combo-badge"><i class="fas fa-link" style="font-size:0.6rem;"></i>${filiere} ↔ ${niveau}</span>`;
            });
        });

        html += '</div>';
        previewDiv.html(html);

        updateCounters();
    }

    $(document).on('change', '.filiere-check, .niveau-check', function() {
        updateCreateCombinationsPreview();
        updateCounters();
    });

    updateCreateCombinationsPreview();
    updateCounters();
});
</script>
@endsection
