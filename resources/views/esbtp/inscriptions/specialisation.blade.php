@extends('layouts.app')

@section('title', 'Spécialisation — ' . $inscription->etudiant->nom_complet)

@push('styles')
<style>
[x-cloak] { display: none !important; }

/* ════════════════════ HERO ════════════════════ */
.spc-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 1.75rem 2.25rem 1.5rem;
    color: #fff;
    margin-bottom: 1.25rem;
    box-shadow: 0 8px 30px rgba(4,83,203,.18);
}
.spc-hero-top { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
.spc-hero-left { display:flex; align-items:flex-start; gap:1rem; flex:1; min-width:0; }
.spc-hero-icon { width:52px; height:52px; border-radius:14px; background:rgba(255,255,255,.15); backdrop-filter:blur(8px);
    border:1px solid rgba(255,255,255,.20); display:flex; align-items:center; justify-content:center; font-size:1.35rem; color:#fff; flex-shrink:0; }
.spc-hero h1 { font-size:1.45rem; font-weight:700; color:#fff; margin:0 0 .3rem; }
.spc-hero p { color:rgba(255,255,255,.80); font-size:.88rem; margin:0;
    display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; }
.spc-hero p code {
    background:rgba(255,255,255,.10); padding:.12rem .5rem; border-radius:5px;
    font-family:'SFMono-Regular',Consolas,monospace; font-size:.78rem;
    color:#fff; font-weight:600; border:1px solid rgba(255,255,255,.15);
}
.spc-hero-chip {
    display:inline-flex; align-items:center; gap:.35rem;
    padding:.25rem .55rem; border-radius:6px;
    background:rgba(255,255,255,.16); border:1px solid rgba(255,255,255,.22);
    color:#fff; font-size:.74rem; font-weight:600;
}

.spc-btn {
    padding:.55rem 1.05rem; border-radius:10px;
    font-size:.82rem; font-weight:600; border:1px solid;
    cursor:pointer; display:inline-flex; align-items:center; gap:.4rem;
    text-decoration:none; transition: all .15s ease;
}
.spc-btn--glass { background:rgba(255,255,255,.15); color:#fff; border-color:rgba(255,255,255,.25); }
.spc-btn--glass:hover { background:rgba(255,255,255,.25); color:#fff; transform:translateY(-1px); }
.spc-btn--primary { background:linear-gradient(135deg,#0453cb,#3b7ddb); color:#fff; border-color:transparent;
    box-shadow:0 2px 8px rgba(4,83,203,.22); }
.spc-btn--primary:hover { background:linear-gradient(135deg,#033a8e,#0453cb); transform:translateY(-1px);
    box-shadow:0 4px 12px rgba(4,83,203,.30); }
.spc-btn--secondary { background:#f1f5f9; color:#475569; border-color:#e2e8f0; }
.spc-btn--secondary:hover { background:#e2e8f0; color:#1e293b; }
.spc-btn--cta-big {
    width:100%; padding:.8rem 1.25rem; font-size:.95rem; justify-content:center;
}
.spc-btn:disabled { opacity:.55; cursor:wait; transform:none !important; }

/* ════════════════════ GRID ════════════════════ */
.spc-grid {
    display:grid; gap:1rem; grid-template-columns: 1fr 2fr;
}
@@media (max-width: 992px) { .spc-grid { grid-template-columns: 1fr; } }

.spc-card {
    background:#fff; border:1px solid #e2e8f0; border-radius:14px;
    box-shadow:0 1px 3px rgba(15,23,42,.04);
    /* pas d'overflow:hidden — voir rule css-stacking-pitfalls */
    position:relative; z-index:1;
}
.spc-card-header {
    padding:1rem 1.25rem .85rem; border-bottom:1px solid #f1f5f9;
    display:flex; align-items:center; gap:.55rem;
}
.spc-card-icon {
    width:36px; height:36px; border-radius:10px;
    background:linear-gradient(135deg,#0453cb,#3b7ddb);
    color:#fff; display:inline-flex; align-items:center; justify-content:center;
    font-size:.92rem; flex-shrink:0; box-shadow:0 2px 6px rgba(4,83,203,.22);
}
.spc-card-title { font-size:.95rem; font-weight:700; color:#0f172a; }
.spc-card-subtitle { font-size:.72rem; color:#64748b; font-weight:500; }
.spc-card-step {
    margin-left:auto; padding:.18rem .55rem; border-radius:6px;
    background:rgba(4,83,203,.10); color:#0453cb; font-size:.68rem; font-weight:700;
    text-transform:uppercase; letter-spacing:.04em;
}
.spc-card-body { padding:1.05rem 1.25rem 1.15rem; }

/* ════════════════════ RÉCAP ÉTUDIANT (sidebar) ════════════════════ */
.spc-student-avatar {
    width:56px; height:56px; border-radius:14px;
    background:linear-gradient(135deg,#0453cb,#3b7ddb); color:#fff;
    display:flex; align-items:center; justify-content:center;
    font-size:1.4rem; font-weight:700; margin:0 auto .65rem;
    box-shadow:0 4px 12px rgba(4,83,203,.20);
}
.spc-student-name {
    text-align:center; font-weight:700; color:#0f172a; font-size:1rem;
    margin-bottom:.15rem;
}
.spc-student-matricule {
    text-align:center; font-family:'SFMono-Regular',Consolas,monospace;
    font-size:.72rem; color:#0453cb; background:rgba(4,83,203,.08);
    padding:.15rem .55rem; border-radius:5px; display:inline-block;
    margin-bottom:1rem;
}
.spc-recap-list { display:flex; flex-direction:column; gap:.45rem; }
.spc-recap-item {
    display:flex; align-items:center; gap:.55rem;
    padding:.55rem .7rem; background:#f8fafc; border-radius:9px;
    font-size:.82rem;
}
.spc-recap-item i { color:#0453cb; font-size:.78rem; flex-shrink:0; }
.spc-recap-label { color:#64748b; font-weight:500; min-width:80px; }
.spc-recap-value { color:#0f172a; font-weight:600; flex:1; min-width:0;
    overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

.spc-paid-block {
    margin-top:1rem; padding-top:1rem; border-top:1px dashed #e2e8f0;
}
.spc-paid-amount {
    text-align:center; padding:.85rem;
    background:linear-gradient(135deg, rgba(16,185,129,.08), rgba(5,150,105,.08));
    border:1px solid rgba(16,185,129,.20); border-radius:10px;
}
.spc-paid-label { font-size:.7rem; color:#047857; text-transform:uppercase; letter-spacing:.04em; font-weight:600; }
.spc-paid-value { font-size:1.15rem; font-weight:800; color:#065f46; margin-top:.2rem; }

/* ════════════════════ CARDS SPÉCIALITÉ ════════════════════ */
.spc-spec-grid {
    display:grid; gap:.75rem;
    grid-template-columns:repeat(auto-fill,minmax(220px,1fr));
}
.spc-spec-card {
    background:#fff; border:2px solid #e2e8f0;
    border-radius:12px; padding:1rem;
    cursor:pointer; transition:all .15s ease;
    position:relative; overflow:hidden;
}
.spc-spec-card:hover {
    border-color:#0453cb; transform:translateY(-2px);
    box-shadow:0 6px 18px rgba(4,83,203,.12);
}
.spc-spec-card--selected {
    border-color:#0453cb;
    background:linear-gradient(135deg, rgba(4,83,203,.06), rgba(59,125,219,.04));
    box-shadow:0 0 0 3px rgba(4,83,203,.10);
}
.spc-spec-card--selected::after {
    content:'✓'; position:absolute; top:8px; right:10px;
    width:22px; height:22px; border-radius:50%;
    background:linear-gradient(135deg,#0453cb,#3b7ddb); color:#fff;
    display:flex; align-items:center; justify-content:center;
    font-size:.85rem; font-weight:700;
}
.spc-spec-icon {
    width:38px; height:38px; border-radius:10px;
    background:rgba(4,83,203,.08); color:#0453cb;
    display:inline-flex; align-items:center; justify-content:center;
    font-size:.95rem; margin-bottom:.55rem;
}
.spc-spec-name { font-weight:700; font-size:.95rem; color:#0f172a; margin-bottom:.2rem; }
.spc-spec-code {
    font-size:.7rem; font-family:'SFMono-Regular',Consolas,monospace;
    color:#0453cb; background:rgba(4,83,203,.06);
    padding:.1rem .4rem; border-radius:5px; display:inline-block;
}
.spc-spec-desc { font-size:.72rem; color:#64748b; margin-top:.45rem; line-height:1.4; }

/* ════════════════════ CARDS CLASSE ════════════════════ */
.spc-classe-list { display:flex; flex-direction:column; gap:.5rem; }
.spc-classe-card {
    background:#fff; border:2px solid #e2e8f0; border-radius:11px;
    padding:.7rem .9rem; cursor:pointer; transition:all .15s ease;
    display:flex; align-items:center; gap:.7rem;
}
.spc-classe-card:hover {
    border-color:#0453cb; background:#f8faff;
    transform:translateX(2px);
}
.spc-classe-card--selected {
    border-color:#0453cb;
    background:linear-gradient(90deg, rgba(4,83,203,.06), rgba(59,125,219,.02));
    box-shadow:0 0 0 3px rgba(4,83,203,.10);
}
.spc-classe-icon {
    width:36px; height:36px; border-radius:10px;
    background:linear-gradient(135deg,#0453cb,#3b7ddb);
    color:#fff; display:inline-flex; align-items:center; justify-content:center;
    font-size:.88rem; flex-shrink:0;
}
.spc-classe-body { flex:1; min-width:0; }
.spc-classe-name { font-weight:700; font-size:.9rem; color:#0f172a; }
.spc-classe-code { font-size:.72rem; color:#64748b; }
.spc-classe-places {
    font-size:.7rem; padding:.18rem .55rem; border-radius:6px; font-weight:700;
    flex-shrink:0;
}
.spc-classe-places--ok { background:#dcfce7; color:#15803d; }
.spc-classe-places--warning { background:#fef9c3; color:#854d0e; }
.spc-classe-places--danger { background:#fee2e2; color:#991b1b; }

.spc-loading {
    text-align:center; padding:2rem 1rem; color:#94a3b8;
}
.spc-loading i { color:#0453cb; font-size:1.4rem; }

.spc-empty {
    background:#fff; border:1px dashed #cbd5e1; border-radius:12px;
    padding:2rem 1rem; text-align:center; color:#64748b; font-size:.85rem;
}
.spc-empty i { font-size:2rem; color:#cbd5e1; display:block; margin-bottom:.65rem; }

.spc-info-banner {
    margin-top:.8rem; padding:.65rem .85rem;
    background:linear-gradient(135deg, rgba(4,83,203,.04), rgba(59,125,219,.06));
    border:1px solid rgba(4,83,203,.18);
    border-radius:9px;
    font-size:.78rem; color:#1e3a8a;
    display:flex; align-items:flex-start; gap:.5rem;
}
.spc-info-banner i { color:#0453cb; padding-top:.1rem; }

/* Step progress indicator */
.spc-stepper {
    display:flex; align-items:center; justify-content:center;
    gap:.45rem; margin-bottom:1rem;
}
.spc-step-dot {
    width:28px; height:28px; border-radius:50%;
    background:#e2e8f0; color:#94a3b8; font-weight:700; font-size:.78rem;
    display:flex; align-items:center; justify-content:center;
    transition:all .2s ease;
}
.spc-step-dot--active {
    background:linear-gradient(135deg,#0453cb,#3b7ddb); color:#fff;
    box-shadow:0 2px 6px rgba(4,83,203,.22);
    transform:scale(1.05);
}
.spc-step-dot--done {
    background:#10b981; color:#fff;
}
.spc-step-line { width:38px; height:2px; background:#e2e8f0; transition:background .2s; }
.spc-step-line--done { background:#10b981; }

.spc-toasts { position:fixed; bottom:1.5rem; right:1.5rem; z-index:1100;
    display:flex; flex-direction:column; gap:.5rem; max-width:400px; }
.spc-toast { display:flex; gap:.6rem; padding:.7rem 1rem; border-radius:10px;
    background:#fff; border:1px solid #e2e8f0;
    box-shadow:0 8px 24px rgba(15,23,42,.12); font-size:.85rem; }
.spc-toast--success { border-left:4px solid #10b981; color:#065f46; }
.spc-toast--error { border-left:4px solid #dc2626; color:#991b1b; }
</style>
@endpush

@section('content')
<div x-data="specialisation()" x-init="init()">

    {{-- HERO --}}
    <div class="spc-hero">
        <div class="spc-hero-top">
            <div class="spc-hero-left">
                <div class="spc-hero-icon"><i class="fas fa-graduation-cap"></i></div>
                <div>
                    <h1>Orientation vers spécialité</h1>
                    <p>
                        Étudiant <strong>{{ $inscription->etudiant->nom_complet }}</strong>
                        <code>{{ $inscription->etudiant->matricule }}</code>
                        <span class="spc-hero-chip"><i class="fas fa-route"></i> Tronc Commun</span>
                        <span class="spc-hero-chip"><i class="fas fa-arrow-right"></i> Spécialité</span>
                    </p>
                </div>
            </div>
            <a href="{{ route('esbtp.inscriptions.show', $inscription) }}" class="spc-btn spc-btn--glass">
                <i class="fas fa-arrow-left"></i> Retour à la fiche
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger" style="border-radius:12px; padding:.85rem 1rem;">
            <strong><i class="fas fa-circle-exclamation"></i> Validation échouée</strong>
            <ul style="margin:.35rem 0 0; padding-left:1.4rem;">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="spc-grid">

        {{-- SIDEBAR — Récap étudiant --}}
        <div class="spc-card">
            <div class="spc-card-header">
                <div class="spc-card-icon"><i class="fas fa-user-graduate"></i></div>
                <div>
                    <div class="spc-card-title">Inscription actuelle</div>
                    <div class="spc-card-subtitle">État avant orientation</div>
                </div>
            </div>
            <div class="spc-card-body">
                <div class="spc-student-avatar">
                    {{ mb_substr($inscription->etudiant->nom ?? '?', 0, 1, 'UTF-8') }}
                </div>
                <div class="spc-student-name">{{ $inscription->etudiant->nom_complet }}</div>
                <div style="text-align:center;">
                    <span class="spc-student-matricule">{{ $inscription->etudiant->matricule }}</span>
                </div>

                <div class="spc-recap-list">
                    <div class="spc-recap-item">
                        <i class="fas fa-stream"></i>
                        <span class="spc-recap-label">Filière TC</span>
                        <span class="spc-recap-value">{{ $inscription->filiere->name ?? '—' }}</span>
                    </div>
                    <div class="spc-recap-item">
                        <i class="fas fa-chalkboard"></i>
                        <span class="spc-recap-label">Classe</span>
                        <span class="spc-recap-value">{{ $inscription->classe->name ?? '—' }}</span>
                    </div>
                    <div class="spc-recap-item">
                        <i class="fas fa-layer-group"></i>
                        <span class="spc-recap-label">Niveau</span>
                        <span class="spc-recap-value">{{ $inscription->niveau->name ?? '—' }}</span>
                    </div>
                    <div class="spc-recap-item">
                        <i class="fas fa-calendar"></i>
                        <span class="spc-recap-label">Année</span>
                        <span class="spc-recap-value">{{ $inscription->anneeUniversitaire->name ?? '—' }}</span>
                    </div>
                </div>

                @if($totalPaye > 0)
                    <div class="spc-paid-block">
                        <div class="spc-paid-amount">
                            <div class="spc-paid-label">Total payé en TC</div>
                            <div class="spc-paid-value">{{ number_format($totalPaye, 0, ',', ' ') }} FCFA</div>
                        </div>
                        <div class="spc-info-banner" style="margin-top:.7rem;">
                            <i class="fas fa-info-circle"></i>
                            <div>
                                @if(\App\Helpers\SettingsHelper::get('tronc_commun_report_paiements', true))
                                    <strong>Report paiements activé</strong> — les paiements TC seront automatiquement reportés sur la nouvelle inscription en spécialité.
                                @else
                                    <strong>Report paiements désactivé</strong> — vérifier les souscriptions de frais après orientation.
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- WORKFLOW : Choix specialité + classe --}}
        <div>
            <form @submit.prevent="submitForm($el)" id="specialisationForm">
                @csrf

                {{-- Stepper visuel --}}
                <div class="spc-stepper">
                    <div class="spc-step-dot"
                         :class="filiereId ? 'spc-step-dot--done' : 'spc-step-dot--active'">1</div>
                    <div class="spc-step-line" :class="filiereId ? 'spc-step-line--done' : ''"></div>
                    <div class="spc-step-dot"
                         :class="classeId ? 'spc-step-dot--done' : (filiereId ? 'spc-step-dot--active' : '')">2</div>
                    <div class="spc-step-line" :class="classeId ? 'spc-step-line--done' : ''"></div>
                    <div class="spc-step-dot" :class="classeId ? 'spc-step-dot--active' : ''">3</div>
                </div>

                {{-- ÉTAPE 1 : Spécialité --}}
                <div class="spc-card" style="margin-bottom:1rem;">
                    <div class="spc-card-header">
                        <div class="spc-card-icon"><i class="fas fa-stream"></i></div>
                        <div>
                            <div class="spc-card-title">Choisir la spécialité</div>
                            <div class="spc-card-subtitle">Filière de destination</div>
                        </div>
                        <span class="spc-card-step">Étape 1</span>
                    </div>
                    <div class="spc-card-body">
                        @if($specialisations->isNotEmpty())
                            <div class="spc-spec-grid">
                                @foreach($specialisations as $spec)
                                    <div class="spc-spec-card"
                                         :class="filiereId === {{ $spec->id }} ? 'spc-spec-card--selected' : ''"
                                         @click="selectFiliere({{ $spec->id }})">
                                        <div class="spc-spec-icon"><i class="fas fa-graduation-cap"></i></div>
                                        <div class="spc-spec-name">{{ $spec->name }}</div>
                                        @if($spec->code)
                                            <span class="spc-spec-code">{{ $spec->code }}</span>
                                        @endif
                                        @if($spec->description)
                                            <div class="spc-spec-desc">{{ \Illuminate\Support\Str::limit($spec->description, 100) }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="spc-empty">
                                <i class="fas fa-circle-exclamation"></i>
                                <strong>Aucune spécialité configurée pour cette filière TC.</strong>
                                <p style="margin-top:.4rem;">
                                    Demandez à un admin de configurer les sorties depuis <em>Administration → Sorties BTS Tronc Commun</em>
                                    (ou via filières enfants si la hiérarchie est utilisée).
                                </p>
                            </div>
                        @endif
                        <input type="hidden" name="filiere_id" :value="filiereId">
                    </div>
                </div>

                {{-- ÉTAPE 2 : Classe (visible quand filière choisie) --}}
                <div class="spc-card" style="margin-bottom:1rem;" x-show="filiereId" x-cloak x-transition.opacity>
                    <div class="spc-card-header">
                        <div class="spc-card-icon"><i class="fas fa-chalkboard"></i></div>
                        <div>
                            <div class="spc-card-title">Choisir la classe</div>
                            <div class="spc-card-subtitle">Section de spécialisation</div>
                        </div>
                        <span class="spc-card-step">Étape 2</span>
                    </div>
                    <div class="spc-card-body">
                        <div class="spc-loading" x-show="loadingClasses" x-cloak>
                            <i class="fas fa-spinner fa-spin"></i>
                            <div style="margin-top:.4rem;">Chargement des classes…</div>
                        </div>
                        <div class="spc-classe-list" x-show="!loadingClasses && classes.length > 0" x-cloak>
                            <template x-for="cls in classes" :key="cls.id">
                                <div class="spc-classe-card"
                                     :class="classeId === cls.id ? 'spc-classe-card--selected' : ''"
                                     @click="selectClasse(cls.id)">
                                    <div class="spc-classe-icon"><i class="fas fa-chalkboard"></i></div>
                                    <div class="spc-classe-body">
                                        <div class="spc-classe-name" x-text="cls.name"></div>
                                        <div class="spc-classe-code" x-text="cls.code || ''"></div>
                                    </div>
                                    <span class="spc-classe-places" :class="placeClass(cls)"
                                          x-text="`${cls.places_disponibles}/${cls.places_totales} places`"></span>
                                </div>
                            </template>
                        </div>
                        <div class="spc-empty" x-show="!loadingClasses && classes.length === 0" x-cloak>
                            <i class="fas fa-circle-exclamation"></i>
                            <strong>Aucune classe cible n'est configurée pour cette spécialisation.</strong>
                            <p style="margin-top:.4rem;">
                                Demandez à un admin de configurer une <em>Sortie BTS Tronc Commun</em> vers une classe de cette filière.
                            </p>
                        </div>
                        <input type="hidden" name="classe_id" :value="classeId">
                    </div>
                </div>

                {{-- ÉTAPE 3 : Confirmation --}}
                <div class="spc-card" x-show="classeId" x-cloak x-transition.opacity>
                    <div class="spc-card-header">
                        <div class="spc-card-icon" style="background:linear-gradient(135deg,#10b981,#059669);">
                            <i class="fas fa-check-double"></i>
                        </div>
                        <div>
                            <div class="spc-card-title">Confirmer l'orientation</div>
                            <div class="spc-card-subtitle">Action irréversible — un audit sera créé</div>
                        </div>
                        <span class="spc-card-step">Étape 3</span>
                    </div>
                    <div class="spc-card-body">
                        <div class="spc-info-banner">
                            <i class="fas fa-info-circle"></i>
                            <div>
                                <strong>Workflow UEMOA :</strong> L'inscription actuelle sera conservée (frais payés préservés) et une <em>phase de spécialisation active</em> sera créée. L'historique du tronc commun reste consultable.
                            </div>
                        </div>
                        <button type="submit" class="spc-btn spc-btn--primary spc-btn--cta-big" :disabled="saving" style="margin-top:1rem;">
                            <i class="fas" :class="saving ? 'fa-spinner fa-spin' : 'fa-check-circle'"></i>
                            <span x-text="saving ? 'Orientation en cours…' : 'Confirmer la spécialisation'"></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="spc-toasts">
        <template x-for="t in toasts" :key="t.id">
            <div class="spc-toast" :class="'spc-toast--' + t.type">
                <i class="fas" :class="t.type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'"></i>
                <span x-text="t.message"></span>
            </div>
        </template>
    </div>
</div>

@push('scripts')
<script>
function specialisation() {
    return {
        filiereId: null,
        classeId: null,
        classes: [],
        loadingClasses: false,
        saving: false,
        toasts: [], toastId: 0,

        init() {},

        async selectFiliere(id) {
            this.filiereId = id;
            this.classeId = null;
            this.classes = [];
            await this.loadClasses(id);
        },

        selectClasse(id) {
            this.classeId = id;
        },

        async loadClasses(filiereId) {
            this.loadingClasses = true;
            try {
                const url = '{{ route("esbtp.inscriptions.specialisation.classes", $inscription) }}?filiere_id=' + filiereId;
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!res.ok) throw new Error('Erreur ' + res.status);
                const data = await res.json();
                this.classes = data.classes || [];
            } catch (e) {
                this.toast('error', 'Impossible de charger les classes : ' + e.message);
                this.classes = [];
            } finally {
                this.loadingClasses = false;
            }
        },

        placeClass(cls) {
            const total = cls.places_totales || 0;
            if (total === 0) return 'spc-classe-places--ok';
            const pct = Math.round((cls.nombre_etudiants / total) * 100);
            if (pct >= 90) return 'spc-classe-places--danger';
            if (pct >= 70) return 'spc-classe-places--warning';
            return 'spc-classe-places--ok';
        },

        async submitForm(formEl) {
            if (!this.filiereId || !this.classeId) {
                this.toast('error', 'Sélectionnez une spécialité ET une classe.');
                return;
            }
            this.saving = true;
            try {
                const fd = new FormData(formEl);
                const payload = {
                    filiere_id: this.filiereId,
                    classe_id: this.classeId,
                };
                const res = await fetch('{{ route("esbtp.inscriptions.specialisation.store", $inscription) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                });
                if (res.status === 422) {
                    const body = await res.json();
                    this.toast('error', body.message || 'Validation échouée');
                    this.saving = false;
                    return;
                }
                if (!res.ok) throw new Error('Erreur ' + res.status);
                const data = await res.json();
                this.toast('success', data.message || 'Orientation enregistrée');
                setTimeout(() => window.location = data.redirect_to || '{{ route("esbtp.inscriptions.show", $inscription) }}', 900);
            } catch (e) {
                this.toast('error', e.message);
                this.saving = false;
            }
        },

        toast(type, message) {
            const id = ++this.toastId;
            this.toasts.push({ id, type, message });
            setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 4000);
        },
    };
}
</script>
@endpush
@endsection
