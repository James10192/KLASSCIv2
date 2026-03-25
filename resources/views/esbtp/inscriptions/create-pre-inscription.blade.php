@extends('layouts.app')

@section('title', 'Pré-inscription | KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .pi-page { max-width: 800px; margin: 0 auto; padding: 20px; }
    .pi-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.06); border: 1px solid rgba(0,0,0,.08); overflow: hidden; }
    .pi-header { padding: 20px 24px; border-bottom: 1px solid rgba(0,0,0,.06); display: flex; align-items: center; gap: 12px; }
    .pi-header-icon { width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.1rem; }
    .pi-header h2 { margin: 0; font-size: 1.15rem; font-weight: 700; color: #1e293b; }
    .pi-header p { margin: 2px 0 0; font-size: .82rem; color: #64748b; }
    .pi-body { padding: 24px; }
    .pi-section { margin-bottom: 24px; }
    .pi-section-title { font-size: .82rem; font-weight: 700; color: #0453cb; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 12px; padding-bottom: 6px; border-bottom: 2px solid rgba(4,83,203,.1); }
    .pi-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 12px; }
    .pi-row.full { grid-template-columns: 1fr; }
    .pi-field label { display: block; font-size: .78rem; font-weight: 600; color: #475569; margin-bottom: 4px; }
    .pi-field label .required { color: #dc2626; }
    .pi-field input, .pi-field select { width: 100%; padding: 9px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: .88rem; color: #1e293b; transition: border-color .2s, box-shadow .2s; }
    .pi-field input:focus, .pi-field select:focus { outline: none; border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.12); }
    .pi-field .field-error { font-size: .75rem; color: #dc2626; margin-top: 2px; }
    .pi-submit { display: flex; justify-content: flex-end; gap: 10px; padding: 16px 24px; border-top: 1px solid rgba(0,0,0,.06); background: #f8fafc; }
    .pi-btn { padding: 10px 20px; border-radius: 8px; font-size: .88rem; font-weight: 600; cursor: pointer; border: none; transition: all .2s; }
    .pi-btn-primary { background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); color: #fff; box-shadow: 0 2px 8px rgba(4,83,203,.25); }
    .pi-btn-primary:hover { box-shadow: 0 4px 12px rgba(4,83,203,.35); transform: translateY(-1px); }
    .pi-btn-secondary { background: #fff; color: #64748b; border: 1px solid #d1d5db; }
    .pi-btn-secondary:hover { background: #f1f5f9; }
    .pi-info { padding: 12px 16px; background: rgba(4,83,203,.06); border: 1px solid rgba(4,83,203,.15); border-radius: 8px; font-size: .82rem; color: #1e40af; display: flex; align-items: flex-start; gap: 8px; margin-bottom: 20px; }
    .pi-info i { margin-top: 2px; }

    /* Step indicator — centered with continuous line */
    .pi-steps { display: flex; align-items: center; justify-content: center; padding: 20px 24px; border-bottom: 1px solid rgba(0,0,0,.06); position: relative; }
    .pi-step { display: flex; flex-direction: column; align-items: center; gap: 6px; z-index: 1; flex: 0 0 auto; min-width: 70px; }
    .pi-step-num { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .82rem; font-weight: 700; border: 2px solid #d1d5db; color: #94a3b8; background: #fff; transition: all .3s; position: relative; z-index: 2; }
    .pi-step.active .pi-step-num { border-color: #0453cb; background: linear-gradient(135deg, #0453cb, #5e91de); color: #fff; box-shadow: 0 2px 8px rgba(4,83,203,.3); }
    .pi-step.done .pi-step-num { border-color: #10b981; background: #10b981; color: #fff; }
    .pi-step-label { font-size: .74rem; font-weight: 600; color: #94a3b8; transition: color .3s; }
    .pi-step.active .pi-step-label { color: #0453cb; }
    .pi-step.done .pi-step-label { color: #10b981; }
    .pi-step-line { flex: 1; height: 2px; background: #e2e8f0; min-width: 40px; max-width: 160px; transition: background .3s; z-index: 0; align-self: flex-start; margin-top: 15px; }
    .pi-step-line.done { background: #10b981; }

    /* Progress bar */
    .pi-progress { height: 3px; background: #e2e8f0; }
    .pi-progress-bar { height: 100%; background: linear-gradient(90deg, #0453cb, #5e91de); transition: width .4s ease; }

    /* Frais cards */
    .pi-frais-list { display: flex; flex-direction: column; gap: 10px; }
    .pi-frais-item { padding: 14px 16px; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; transition: border-color .2s; }
    .pi-frais-item.mandatory { border-left: 3px solid #0453cb; }
    .pi-frais-item.optional { border-left: 3px solid #64748b; }
    .pi-frais-item.selected { border-color: #0453cb; background: rgba(4,83,203,.02); }
    .pi-frais-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
    .pi-frais-name { font-size: .88rem; font-weight: 600; color: #1e293b; }
    .pi-frais-amount { font-size: .92rem; font-weight: 700; color: #0453cb; white-space: nowrap; }
    .pi-frais-badge { display: inline-block; font-size: .68rem; font-weight: 600; padding: 2px 8px; border-radius: 20px; margin-left: 8px; }
    .pi-frais-badge.mandatory { background: rgba(4,83,203,.1); color: #0453cb; }
    .pi-frais-badge.optional { background: rgba(100,116,139,.1); color: #64748b; }
    .pi-frais-check { display: flex; align-items: center; gap: 8px; }
    .pi-frais-check input[type=checkbox] { width: 16px; height: 16px; accent-color: #0453cb; }
    .pi-frais-variants { margin-top: 10px; padding-top: 10px; border-top: 1px solid #f1f5f9; display: flex; flex-wrap: wrap; gap: 8px; }
    .pi-variant-label { display: flex; align-items: center; gap: 6px; padding: 6px 12px; border: 1px solid #e2e8f0; border-radius: 6px; cursor: pointer; font-size: .8rem; transition: all .2s; }
    .pi-variant-label:has(input:checked) { border-color: #0453cb; background: rgba(4,83,203,.06); }
    .pi-variant-label input[type=radio] { accent-color: #0453cb; }

    /* Total bar */
    .pi-total-bar { display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; background: linear-gradient(135deg, rgba(4,83,203,.06), rgba(94,145,222,.06)); border: 1px solid rgba(4,83,203,.15); border-radius: 10px; margin-top: 16px; }
    .pi-total-label { font-size: .85rem; font-weight: 600; color: #475569; }
    .pi-total-amount { font-size: 1.1rem; font-weight: 800; color: #0453cb; }

    /* Paiement slide */
    .pi-pay-item { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 8px; }
    .pi-pay-item label { display: flex; align-items: center; gap: 8px; font-size: .85rem; font-weight: 500; color: #1e293b; margin: 0; cursor: pointer; }
    .pi-pay-item input[type=checkbox] { width: 16px; height: 16px; accent-color: #10b981; }
    .pi-pay-amount { font-size: .88rem; font-weight: 700; color: #0453cb; }

    .pi-encaisser-total { display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; background: rgba(16,185,129,.06); border: 1px solid rgba(16,185,129,.2); border-radius: 10px; margin-top: 12px; margin-bottom: 16px; }
    .pi-encaisser-label { font-size: .88rem; font-weight: 600; color: #065f46; }
    .pi-encaisser-amount { font-size: 1.1rem; font-weight: 800; color: #059669; }

    /* Loading spinner */
    .pi-loading { text-align: center; padding: 30px 0; }
    .pi-loading .spinner { width: 28px; height: 28px; border: 3px solid #e2e8f0; border-top-color: #0453cb; border-radius: 50%; animation: pi-spin .7s linear infinite; display: inline-block; }
    @keyframes pi-spin { to { transform: rotate(360deg); } }
    .pi-loading p { margin: 10px 0 0; font-size: .82rem; color: #64748b; }

    /* Slide transitions */
    .pi-slide { display: none; }
    .pi-slide.active { display: block; animation: pi-fadeIn .3s ease; }
    @keyframes pi-fadeIn { from { opacity: 0; transform: translateX(10px); } to { opacity: 1; transform: translateX(0); } }
</style>
@endpush

@section('page_title', 'Pré-inscription')

@section('content')
<div class="pi-page" x-data="preInscription()">

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Erreurs de validation :</strong>
        <ul class="mb-0 mt-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="pi-card">
        <div class="pi-header">
            <div class="pi-header-icon"><i class="fas fa-user-plus"></i></div>
            <div>
                <h2>Pré-inscription rapide</h2>
                <p>Enregistrer un nouvel étudiant, souscrire aux frais et encaisser</p>
            </div>
        </div>

        {{-- Step indicator --}}
        <div class="pi-steps">
            <div class="pi-step" :class="{ active: step === 1, done: step > 1 }">
                <div class="pi-step-num">
                    <template x-if="step > 1"><i class="fas fa-check" style="font-size:.7rem"></i></template>
                    <template x-if="step <= 1"><span>1</span></template>
                </div>
                <span class="pi-step-label">Identité</span>
            </div>
            <div class="pi-step-line" :class="{ done: step > 1 }"></div>
            <div class="pi-step" :class="{ active: step === 2, done: step > 2 }">
                <div class="pi-step-num">
                    <template x-if="step > 2"><i class="fas fa-check" style="font-size:.7rem"></i></template>
                    <template x-if="step <= 2"><span>2</span></template>
                </div>
                <span class="pi-step-label">Frais</span>
            </div>
            <div class="pi-step-line" :class="{ done: step > 2 }"></div>
            <div class="pi-step" :class="{ active: step === 3 }">
                <div class="pi-step-num">3</div>
                <span class="pi-step-label">Paiement</span>
            </div>
        </div>

        {{-- Progress bar --}}
        <div class="pi-progress">
            <div class="pi-progress-bar" :style="'width:' + ((step) / 3 * 100) + '%'"></div>
        </div>

        <form action="{{ route('esbtp.inscriptions.store-pre-inscription') }}" method="POST" @submit="submitting = true">
            @csrf

            {{-- ===== SLIDE 1 — Identité ===== --}}
            <div class="pi-slide" :class="{ active: step === 1 }">
                <div class="pi-body">
                    <div class="pi-info">
                        <i class="fas fa-info-circle"></i>
                        <div>Recherchez un étudiant existant (réinscription) ou saisissez les informations pour un nouvel étudiant.</div>
                    </div>

                    {{-- Recherche étudiant existant --}}
                    <div class="pi-section">
                        <div class="pi-section-title"><i class="fas fa-search"></i> Étudiant existant (réinscription)</div>
                        <div class="pi-row full" x-show="!etudiantExistant">
                            <div class="pi-field" style="position:relative;">
                                <label>Rechercher par nom, prénom ou matricule</label>
                                <input type="text" x-model="searchQuery" @input.debounce.300ms="searchEtudiants()" placeholder="Tapez au moins 2 caractères...">
                                {{-- Résultats de recherche --}}
                                <div x-show="searchResults.length > 0" style="position:absolute; top:100%; left:0; right:0; z-index:10; background:#fff; border:1px solid #d1d5db; border-radius:0 0 8px 8px; box-shadow:0 4px 12px rgba(0,0,0,.1); max-height:200px; overflow-y:auto;">
                                    <template x-for="r in searchResults" :key="r.id">
                                        <div @click="selectEtudiant(r)" style="padding:10px 14px; cursor:pointer; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; gap:10px; transition:background .15s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#fff'">
                                            <div style="width:32px; height:32px; border-radius:50%; background:linear-gradient(135deg,#0453cb,#5e91de); color:#fff; display:flex; align-items:center; justify-content:center; font-size:.7rem; font-weight:700; flex-shrink:0;" x-text="(r.nom[0] || '') + (r.prenoms[0] || '')"></div>
                                            <div style="flex:1; min-width:0;">
                                                <div style="font-weight:600; font-size:.85rem; color:#1e293b;" x-text="r.nom + ' ' + r.prenoms"></div>
                                                <div style="font-size:.75rem; color:#64748b;" x-text="r.matricule + ' — ' + r.derniere_classe"></div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        {{-- Étudiant sélectionné --}}
                        <div x-show="etudiantExistant" style="display:none;">
                            <div style="display:flex; align-items:center; gap:14px; padding:14px 16px; background:rgba(16,185,129,.06); border:1px solid rgba(16,185,129,.2); border-radius:10px;">
                                <div style="width:40px; height:40px; border-radius:50%; background:#10b981; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:.85rem;" x-text="etudiantExistant ? (etudiantExistant.nom[0] + etudiantExistant.prenoms[0]) : ''"></div>
                                <div style="flex:1;">
                                    <div style="font-weight:700; color:#1e293b; font-size:.92rem;" x-text="etudiantExistant ? etudiantExistant.nom + ' ' + etudiantExistant.prenoms : ''"></div>
                                    <div style="font-size:.78rem; color:#64748b;" x-text="etudiantExistant ? 'Matricule: ' + etudiantExistant.matricule + ' — Tél: ' + (etudiantExistant.telephone || '—') : ''"></div>
                                </div>
                                <button type="button" @click="clearEtudiant()" style="padding:6px 12px; background:#fff; border:1px solid #d1d5db; border-radius:6px; font-size:.78rem; color:#64748b; cursor:pointer;">
                                    <i class="fas fa-times"></i> Changer
                                </button>
                            </div>
                            <input type="hidden" name="etudiant_existant_id" :value="etudiantExistant?.id">
                        </div>

                        {{-- Analyse académique (chargée en AJAX) --}}
                        <div x-show="analyseLoading" style="text-align:center; padding:20px 0; display:none;">
                            <div class="spinner" style="width:22px; height:22px; border-width:2px; display:inline-block;"></div>
                            <span style="font-size:.82rem; color:#64748b; margin-left:8px;">Analyse du dossier en cours...</span>
                        </div>

                        <div x-show="analyseData && analyseData.has_analysis" style="display:none; margin-top:16px;">
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                                {{-- Décision académique --}}
                                <div style="padding:18px 20px; border-radius:14px; position:relative; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.04);"
                                     :style="analyseData?.decision === 'passage' ? 'background:linear-gradient(145deg, #ecfdf5, #d1fae5); border:1.5px solid #6ee7b7;' :
                                             analyseData?.decision === 'redoublement' ? 'background:linear-gradient(145deg, #fef2f2, #fecaca); border:1.5px solid #fca5a5;' :
                                             'background:linear-gradient(145deg, #eff6ff, #dbeafe); border:1.5px solid #93c5fd;'">
                                    <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
                                        <div style="width:38px; height:38px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:.9rem; box-shadow:0 2px 6px rgba(0,0,0,.12);"
                                             :style="analyseData?.decision === 'passage' ? 'background:linear-gradient(135deg, #10b981, #059669); color:#fff;' :
                                                     analyseData?.decision === 'redoublement' ? 'background:linear-gradient(135deg, #ef4444, #dc2626); color:#fff;' : 'background:linear-gradient(135deg, #0453cb, #5e91de); color:#fff;'">
                                            <i class="fas" :class="analyseData?.decision === 'passage' ? 'fa-arrow-up' : analyseData?.decision === 'redoublement' ? 'fa-redo' : 'fa-sync-alt'"></i>
                                        </div>
                                        <div>
                                            <div style="font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:#64748b; margin-bottom:2px;">Decision</div>
                                            <div style="font-size:1.05rem; font-weight:800; line-height:1;"
                                                 :style="analyseData?.decision === 'passage' ? 'color:#047857;' : analyseData?.decision === 'redoublement' ? 'color:#b91c1c;' : 'color:#1d4ed8;'"
                                                 x-text="analyseData?.decision === 'passage' ? 'Passage' : analyseData?.decision === 'redoublement' ? 'Redoublement' : 'Rattrapage'"></div>
                                        </div>
                                    </div>
                                    <div style="display:flex; align-items:center; gap:8px; padding:8px 12px; background:rgba(255,255,255,.7); border-radius:8px; backdrop-filter:blur(4px);">
                                        <i class="fas fa-chart-line" style="font-size:.7rem; color:#64748b;"></i>
                                        <span style="font-size:.8rem; color:#475569;">Moyenne</span>
                                        <strong style="font-size:.88rem; color:#1e293b;" x-text="analyseData?.moyenne_generale ? parseFloat(analyseData.moyenne_generale).toFixed(2) + '/20' : 'N/A'"></strong>
                                    </div>
                                </div>

                                {{-- Situation financière --}}
                                <div style="padding:18px 20px; border-radius:14px; position:relative; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.04);"
                                     :style="analyseData?.solde_status === 'solde' ? 'background:linear-gradient(145deg, #ecfdf5, #d1fae5); border:1.5px solid #6ee7b7;' : 'background:linear-gradient(145deg, #fef2f2, #fecaca); border:1.5px solid #fca5a5;'">
                                    <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
                                        <div style="width:38px; height:38px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:.9rem; box-shadow:0 2px 6px rgba(0,0,0,.12);"
                                             :style="analyseData?.solde_status === 'solde' ? 'background:linear-gradient(135deg, #10b981, #059669); color:#fff;' : 'background:linear-gradient(135deg, #ef4444, #dc2626); color:#fff;'">
                                            <i class="fas" :class="analyseData?.solde_status === 'solde' ? 'fa-check-circle' : 'fa-exclamation-circle'"></i>
                                        </div>
                                        <div>
                                            <div style="font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:#64748b; margin-bottom:2px;">Finances</div>
                                            <div style="font-size:1.05rem; font-weight:800; line-height:1;"
                                                 :style="analyseData?.solde_status === 'solde' ? 'color:#047857;' : 'color:#b91c1c;'"
                                                 x-text="analyseData?.solde_status === 'solde' ? 'Solde' : 'Impaye'"></div>
                                        </div>
                                    </div>
                                    <div style="display:flex; flex-direction:column; gap:6px;">
                                        <div x-show="analyseData?.solde_status !== 'solde'" style="display:none;">
                                            <div style="display:flex; align-items:center; gap:8px; padding:8px 12px; background:rgba(220,38,38,.08); border-radius:8px;">
                                                <i class="fas fa-coins" style="font-size:.7rem; color:#dc2626;"></i>
                                                <span style="font-size:.82rem; font-weight:700; color:#b91c1c;" x-text="'Relicat : ' + formatFCFA(analyseData?.solde_restant || 0)"></span>
                                            </div>
                                        </div>
                                        <div style="display:flex; align-items:center; gap:8px; padding:8px 12px; background:rgba(255,255,255,.7); border-radius:8px; backdrop-filter:blur(4px);">
                                            <i class="fas fa-chalkboard" style="font-size:.7rem; color:#64748b;"></i>
                                            <span style="font-size:.8rem; color:#475569;">Classe</span>
                                            <strong style="font-size:.88rem; color:#1e293b;" x-text="analyseData?.classe_actuelle || '—'"></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Infos nouvel étudiant (masquées si existant sélectionné) --}}
                    <div class="pi-section" x-show="!etudiantExistant">
                        <div style="text-align:center; padding:8px 0; margin-bottom:12px; color:#94a3b8; font-size:.78rem; font-weight:600;">
                            — OU — Nouvel étudiant
                        </div>
                        <div class="pi-row">
                            <div class="pi-field">
                                <label>Nom <span class="required">*</span></label>
                                <input type="text" name="nom" x-model="nom" placeholder="Ex: KOUASSI">
                                <div class="field-error" x-show="errors.nom" x-text="errors.nom" style="display:none;"></div>
                            </div>
                            <div class="pi-field">
                                <label>Prénoms <span class="required">*</span></label>
                                <input type="text" name="prenoms" x-model="prenoms" placeholder="Ex: Jean-Marc">
                                <div class="field-error" x-show="errors.prenoms" x-text="errors.prenoms" style="display:none;"></div>
                            </div>
                        </div>
                        <div class="pi-row full">
                            <div class="pi-field">
                                <label>Téléphone</label>
                                <input type="text" name="telephone" x-model="telephone" placeholder="Ex: 0708091011">
                            </div>
                        </div>
                    </div>

                    {{-- Classe (toujours visible) --}}
                    <div class="pi-section">
                        <div class="pi-section-title"><i class="fas fa-graduation-cap"></i> Classe</div>
                        <div class="pi-row full">
                            <div class="pi-field">
                                <label>Classe <span class="required">*</span></label>
                                <select name="classe_id" x-model="classe_id" required>
                                    <option value="">-- Sélectionner une classe --</option>
                                    @foreach($classes as $classe)
                                        <option value="{{ $classe->id }}">
                                            {{ $classe->name }} ({{ $classe->filiere->name ?? '' }} - {{ $classe->niveau->name ?? '' }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="field-error" x-show="errors.classe_id" x-text="errors.classe_id" style="display:none;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pi-submit">
                    <a href="{{ route('esbtp.inscriptions.index') }}" class="pi-btn pi-btn-secondary">Annuler</a>
                    <button type="button" class="pi-btn pi-btn-primary" @click="goToStep2()" :disabled="loadingFrais">
                        <template x-if="loadingFrais">
                            <span><span class="spinner" style="width:14px;height:14px;border-width:2px;vertical-align:middle;margin-right:6px;"></span> Chargement...</span>
                        </template>
                        <template x-if="!loadingFrais">
                            <span>Suivant <i class="fas fa-arrow-right"></i></span>
                        </template>
                    </button>
                </div>
            </div>

            {{-- ===== SLIDE 2 — Frais & Souscriptions ===== --}}
            <div class="pi-slide" :class="{ active: step === 2 }">
                <div class="pi-body">
                    <div class="pi-section">
                        <div class="pi-section-title"><i class="fas fa-receipt"></i> Frais & Souscriptions</div>

                        {{-- Loading --}}
                        <div class="pi-loading" x-show="loadingFrais" style="display:none;">
                            <div class="spinner"></div>
                            <p>Chargement des frais pour cette classe...</p>
                        </div>

                        {{-- Frais list --}}
                        <div x-show="!loadingFrais && frais.length > 0" style="display:none;">

                            {{-- Mandatory --}}
                            <template x-if="fraisMandatory.length > 0">
                                <div>
                                    <p style="font-size:.78rem;font-weight:700;color:#0453cb;margin-bottom:8px;">
                                        <i class="fas fa-star"></i> Frais obligatoires
                                    </p>
                                    <div class="pi-frais-list">
                                        <template x-for="f in fraisMandatory" :key="f.category.id">
                                            <div class="pi-frais-item mandatory">
                                                <div class="pi-frais-top">
                                                    <div>
                                                        <span class="pi-frais-name" x-text="f.category.name"></span>
                                                        <span class="pi-frais-badge mandatory">Obligatoire</span>
                                                    </div>
                                                    <span class="pi-frais-amount" x-text="formatFCFA(getSelectedAmount(f))"></span>
                                                </div>

                                                {{-- Variants for mandatory --}}
                                                <template x-if="f._options && f._options.length > 0">
                                                    <div class="pi-frais-variants">
                                                        <label class="pi-variant-label">
                                                            <input type="radio"
                                                                   :name="'frais[' + f.category.id + '][variant_id]'"
                                                                   value="default"
                                                                   :data-amount="f.default_amount"
                                                                   checked
                                                                   @change="updateFraisSelection(f, 'default', f.default_amount)">
                                                            <span>Tarif configuré — <strong x-text="formatFCFA(f.default_amount)"></strong></span>
                                                        </label>
                                                        <template x-for="opt in f._options" :key="opt.id">
                                                            <label class="pi-variant-label">
                                                                <input type="radio"
                                                                       :name="'frais[' + f.category.id + '][variant_id]'"
                                                                       :value="opt.id"
                                                                       :data-amount="opt._totalAmount"
                                                                       @change="updateFraisSelection(f, opt.id, opt._totalAmount)">
                                                                <span x-text="opt.name + ' — '"></span><strong x-text="formatFCFA(opt._totalAmount)"></strong>
                                                            </label>
                                                        </template>
                                                    </div>
                                                </template>

                                                {{-- Hidden inputs for mandatory (always submitted) --}}
                                                <input type="hidden" :name="'frais[' + f.category.id + '][variant_id]'" :value="f._selectedVariant">
                                                <input type="hidden" :name="'frais[' + f.category.id + '][amount]'" :value="f._selectedAmount">
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            {{-- Optional --}}
                            <template x-if="fraisOptional.length > 0">
                                <div style="margin-top:20px;">
                                    <p style="font-size:.78rem;font-weight:700;color:#64748b;margin-bottom:8px;">
                                        <i class="fas fa-plus-circle"></i> Frais optionnels
                                    </p>
                                    <div class="pi-frais-list">
                                        <template x-for="f in fraisOptional" :key="f.category.id">
                                            <div class="pi-frais-item optional" :class="{ selected: f._subscribed }">
                                                <div class="pi-frais-top">
                                                    <div class="pi-frais-check">
                                                        <input type="checkbox"
                                                               :checked="f._subscribed"
                                                               @change="toggleOptionalFrais(f, $event.target.checked)">
                                                        <div>
                                                            <span class="pi-frais-name" x-text="f.category.name"></span>
                                                            <span class="pi-frais-badge optional">Optionnel</span>
                                                        </div>
                                                    </div>
                                                    <span class="pi-frais-amount" x-show="f._subscribed" x-text="formatFCFA(getSelectedAmount(f))"></span>
                                                </div>

                                                {{-- Options for optional with variants --}}
                                                <template x-if="f._subscribed && f._options && f._options.length > 0">
                                                    <div class="pi-frais-variants">
                                                        <template x-for="opt in f._options" :key="opt.id">
                                                            <label class="pi-variant-label">
                                                                <input type="radio"
                                                                       :name="'frais[' + f.category.id + '][variant_id]'"
                                                                       :value="opt.id"
                                                                       :checked="f._selectedVariant == opt.id"
                                                                       @change="updateFraisSelection(f, opt.id, opt._totalAmount)">
                                                                <span x-text="opt.name + ' — '"></span><strong x-text="formatFCFA(opt._totalAmount)"></strong>
                                                            </label>
                                                        </template>
                                                    </div>
                                                </template>

                                                {{-- Hidden inputs only when subscribed --}}
                                                <template x-if="f._subscribed">
                                                    <div>
                                                        <input type="hidden" :name="'frais[' + f.category.id + '][variant_id]'" :value="f._selectedVariant">
                                                        <input type="hidden" :name="'frais[' + f.category.id + '][amount]'" :value="f._selectedAmount">
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            {{-- No frais --}}
                            <div x-show="frais.length === 0 && !loadingFrais" style="display:none;">
                                <div class="pi-info">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <div>Aucun frais configuré pour cette classe.</div>
                                </div>
                            </div>

                            {{-- Total --}}
                            <div class="pi-total-bar">
                                <span class="pi-total-label"><i class="fas fa-calculator"></i> Total des frais sélectionnés</span>
                                <span class="pi-total-amount" x-text="formatFCFA(totalFrais)"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pi-submit">
                    <button type="button" class="pi-btn pi-btn-secondary" @click="step = 1">
                        <i class="fas fa-arrow-left"></i> Retour
                    </button>
                    <button type="button" class="pi-btn pi-btn-primary" @click="goToStep3()">
                        Suivant <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            {{-- ===== SLIDE 3 — Paiement ===== --}}
            <div class="pi-slide" :class="{ active: step === 3 }">
                <div class="pi-body">
                    <div class="pi-section">
                        <div class="pi-section-title"><i class="fas fa-money-bill-wave"></i> Encaissement</div>

                        <div class="pi-info">
                            <i class="fas fa-info-circle"></i>
                            <div>Cochez les catégories que l'étudiant règle maintenant. Les frais non cochés resteront en attente de paiement.</div>
                        </div>

                        {{-- Summary of selected fees with checkboxes + partial amount --}}
                        <template x-for="f in selectedFrais" :key="f.category.id">
                            <div class="pi-pay-item" style="flex-wrap:wrap;">
                                <div style="display:flex;align-items:center;justify-content:space-between;width:100%;">
                                    <label>
                                        <input type="checkbox"
                                               :value="f.category.id"
                                               name="paiement_categories[]"
                                               :checked="isPaymentChecked(f.category.id)"
                                               @change="togglePayment(f.category.id, $event.target.checked, f._selectedAmount)">
                                        <span x-text="f.category.name"></span>
                                        <span class="pi-frais-badge" :class="f.is_mandatory ? 'mandatory' : 'optional'"
                                              x-text="f.is_mandatory ? 'Obligatoire' : 'Optionnel'"></span>
                                    </label>
                                    <span class="pi-pay-amount" x-text="formatFCFA(f._selectedAmount)"></span>
                                </div>
                                {{-- Partial amount input when checked --}}
                                <div x-show="isPaymentChecked(f.category.id)" style="width:100%;margin-top:8px;padding-left:28px;display:none;" x-transition>
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <label style="font-size:.78rem;color:#475569;white-space:nowrap;margin:0;">Montant payé :</label>
                                        <input type="number"
                                               :name="'paiement_montants[' + f.category.id + ']'"
                                               :value="getPaymentAmount(f.category.id)"
                                               @input="updatePaymentAmount(f.category.id, $event.target.value)"
                                               :max="f._selectedAmount"
                                               min="0"
                                               step="500"
                                               style="width:160px;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:.85rem;font-weight:600;">
                                        <span style="font-size:.75rem;color:#64748b;">/ <span x-text="formatFCFA(f._selectedAmount)"></span></span>
                                    </div>
                                    <div x-show="getPaymentAmount(f.category.id) < f._selectedAmount" style="font-size:.72rem;color:#0453cb;margin-top:4px;display:none;">
                                        Paiement partiel — reste à payer : <strong x-text="formatFCFA(f._selectedAmount - getPaymentAmount(f.category.id))"></strong>
                                    </div>
                                </div>
                            </div>
                        </template>

                        {{-- Total a encaisser --}}
                        <div class="pi-encaisser-total">
                            <span class="pi-encaisser-label"><i class="fas fa-cash-register"></i> Total à encaisser</span>
                            <span class="pi-encaisser-amount" x-text="formatFCFA(totalEncaisser)"></span>
                        </div>

                        {{-- Payment details (only if something is checked) --}}
                        <div x-show="totalEncaisser > 0" style="display:none;">
                            <div class="pi-row">
                                <div class="pi-field">
                                    <label>Mode de paiement</label>
                                    <select name="mode_paiement" x-model="modePaiement">
                                        <option value="especes">Espèces</option>
                                        <option value="mobile_money">Mobile Money</option>
                                        <option value="cheque">Chèque</option>
                                        <option value="virement">Virement</option>
                                    </select>
                                </div>
                                <div class="pi-field">
                                    <label>Référence paiement</label>
                                    <input type="text" name="reference_paiement" x-model="referencePaiement" placeholder="N° reçu, référence transaction...">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pi-submit">
                    <button type="button" class="pi-btn pi-btn-secondary" @click="step = 2">
                        <i class="fas fa-arrow-left"></i> Retour
                    </button>
                    <button type="submit" class="pi-btn pi-btn-primary" :disabled="submitting">
                        <template x-if="submitting">
                            <span><span class="spinner" style="width:14px;height:14px;border-width:2px;vertical-align:middle;margin-right:6px;"></span> Enregistrement...</span>
                        </template>
                        <template x-if="!submitting">
                            <span><i class="fas fa-check-circle"></i> Enregistrer la pré-inscription</span>
                        </template>
                    </button>
                </div>
            </div>

        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function preInscription() {
    return {
        step: 1,
        nom: '{{ old("nom", "") }}',
        prenoms: '{{ old("prenoms", "") }}',
        telephone: '{{ old("telephone", "") }}',
        classe_id: '{{ old("classe_id", "") }}',
        searchQuery: '',
        searchResults: [],
        etudiantExistant: null,
        analyseData: null,
        analyseLoading: false,
        errors: {},
        loadingFrais: false,
        frais: [],
        paiementChecked: {},
        paiementAmounts: {},
        modePaiement: 'especes',
        referencePaiement: '',
        submitting: false,

        get fraisMandatory() {
            return this.frais.filter(f => f.is_mandatory);
        },
        get fraisOptional() {
            return this.frais.filter(f => !f.is_mandatory);
        },
        get selectedFrais() {
            return this.frais.filter(f => f.is_mandatory || f._subscribed);
        },
        get totalFrais() {
            return this.selectedFrais.reduce((sum, f) => sum + (parseFloat(f._selectedAmount) || 0), 0);
        },
        get totalEncaisser() {
            let total = 0;
            this.selectedFrais.forEach(f => {
                if (this.paiementChecked[f.category.id]) {
                    total += parseFloat(this.paiementAmounts[f.category.id]) || 0;
                }
            });
            return total;
        },

        formatFCFA(amount) {
            return (parseFloat(amount) || 0).toLocaleString('fr-FR') + ' FCFA';
        },

        getSelectedAmount(f) {
            return parseFloat(f._selectedAmount) || 0;
        },

        searchEtudiants() {
            if (this.searchQuery.length < 2) { this.searchResults = []; return; }
            fetch(`{{ route('esbtp.inscriptions.search-etudiants') }}?q=${encodeURIComponent(this.searchQuery)}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => { this.searchResults = data; })
            .catch(() => { this.searchResults = []; });
        },

        selectEtudiant(etudiant) {
            this.etudiantExistant = etudiant;
            this.nom = etudiant.nom;
            this.prenoms = etudiant.prenoms;
            this.telephone = etudiant.telephone || '';
            this.searchQuery = '';
            this.searchResults = [];
            this.loadAnalyse(etudiant.id);
        },

        clearEtudiant() {
            this.etudiantExistant = null;
            this.analyseData = null;
            this.nom = '';
            this.prenoms = '';
            this.telephone = '';
        },

        loadAnalyse(etudiantId) {
            this.analyseLoading = true;
            this.analyseData = null;
            fetch(`/esbtp/inscriptions/analyse-etudiant/${etudiantId}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.analyseData = data;
                    // Filtrer les classes proposées si disponibles
                    if (data.classes_proposees && data.classes_proposees.length > 0) {
                        this.classesProposees = data.classes_proposees;
                    }
                }
                this.analyseLoading = false;
            })
            .catch(() => { this.analyseLoading = false; });
        },

        validateStep1() {
            this.errors = {};
            if (!this.etudiantExistant) {
                if (!this.nom.trim()) this.errors.nom = 'Le nom est obligatoire';
                if (!this.prenoms.trim()) this.errors.prenoms = 'Le(s) prénom(s) est/sont obligatoire(s)';
            }
            if (!this.classe_id) this.errors.classe_id = 'Veuillez sélectionner une classe';
            return Object.keys(this.errors).length === 0;
        },

        goToStep2() {
            if (!this.validateStep1()) return;
            this.loadFrais();
        },

        loadFrais() {
            this.loadingFrais = true;
            this.step = 2;

            fetch(`/esbtp/inscriptions/frais-by-classe/${this.classe_id}?affectation_status=affecté`, {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => {
                if (!r.ok) throw new Error(r.status);
                return r.json();
            })
            .then(data => {
                if (data.success) {
                    this.frais = (data.frais || []).map(f => {
                        const baseAmount = parseFloat(f.default_amount) || parseFloat(f.configured_amount) || 0;
                        const options = (f.options || f.variants || []).map(opt => {
                            const addAmt = parseFloat(opt.additional_amount) || parseFloat(opt.amount) || 0;
                            return {
                                ...opt,
                                _totalAmount: baseAmount + addAmt
                            };
                        });

                        return {
                            ...f,
                            _options: options,
                            _selectedVariant: 'default',
                            _selectedAmount: baseAmount,
                            _subscribed: f.is_mandatory // mandatory = always subscribed
                        };
                    });
                } else {
                    this.frais = [];
                }
                this.loadingFrais = false;
            })
            .catch(err => {
                console.error('Erreur chargement frais:', err);
                this.frais = [];
                this.loadingFrais = false;
            });
        },

        updateFraisSelection(f, variantId, amount) {
            f._selectedVariant = variantId;
            f._selectedAmount = parseFloat(amount) || 0;
        },

        toggleOptionalFrais(f, checked) {
            f._subscribed = checked;
            if (checked) {
                // Auto-select first option if variants exist and nothing selected
                if (f._options && f._options.length > 0 && f._selectedVariant === 'default') {
                    f._selectedVariant = f._options[0].id;
                    f._selectedAmount = f._options[0]._totalAmount;
                } else if (!f._options || f._options.length === 0) {
                    f._selectedVariant = 'default';
                    f._selectedAmount = parseFloat(f.default_amount) || 0;
                }
            } else {
                // Uncheck payment too
                delete this.paiementChecked[f.category.id];
            }
        },

        goToStep3() {
            this.step = 3;
        },

        isPaymentChecked(categoryId) {
            return !!this.paiementChecked[categoryId];
        },

        togglePayment(categoryId, checked, fullAmount) {
            if (checked) {
                this.paiementChecked[categoryId] = true;
                // Default to full amount
                if (!this.paiementAmounts[categoryId]) {
                    this.paiementAmounts[categoryId] = fullAmount || 0;
                }
            } else {
                delete this.paiementChecked[categoryId];
                delete this.paiementAmounts[categoryId];
            }
        },

        getPaymentAmount(categoryId) {
            return this.paiementAmounts[categoryId] || 0;
        },

        updatePaymentAmount(categoryId, value) {
            const amount = parseFloat(value) || 0;
            // Find the max for this category
            const f = this.selectedFrais.find(fr => fr.category.id == categoryId);
            const max = f ? parseFloat(f._selectedAmount) || 0 : 0;
            this.paiementAmounts[categoryId] = Math.min(amount, max);
        }
    };
}
</script>
@endpush
