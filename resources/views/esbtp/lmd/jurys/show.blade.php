@extends('layouts.app')
@section('title', 'Jury — '.$jury->libelle)

@push('styles')
<style>
[x-cloak]{display:none !important;}
.juy-hero{background:linear-gradient(135deg,#0a3d8f,#0453cb,#3b7ddb);border-radius:18px;padding:1.75rem 2.25rem;color:#fff;margin-bottom:1.25rem;}
.juy-hero h1{margin:0;font-size:1.4rem;}
.juy-hero p{margin:.3rem 0 0;color:rgba(255,255,255,.75);font-size:.85rem;}
.juy-meta{display:flex;gap:1.25rem;flex-wrap:wrap;margin-top:1rem;}
.juy-meta-item{background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);border-radius:10px;padding:.5rem .85rem;}
.juy-meta-label{font-size:.6rem;color:rgba(255,255,255,.6);text-transform:uppercase;letter-spacing:.5px;}
.juy-meta-value{font-size:.9rem;font-weight:700;color:#fff;margin-top:.15rem;}
.juy-tabs{display:flex;gap:.4rem;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:.4rem;margin-bottom:1.25rem;flex-wrap:wrap;}
.juy-tab{padding:.55rem 1rem;border-radius:9px;font-size:.85rem;font-weight:600;background:transparent;border:none;cursor:pointer;color:#475569;transition:.15s;display:inline-flex;align-items:center;gap:.4rem;}
.juy-tab:hover{background:rgba(4,83,203,.06);color:#0453cb;}
.juy-tab--active{background:#0453cb;color:#fff;}
.juy-tab--active:hover{background:#033a8e;color:#fff;}
.juy-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:1.25rem;margin-bottom:1rem;}
.juy-card h2{margin:0 0 1rem;font-size:1rem;color:#1e293b;display:flex;align-items:center;gap:.5rem;}
.juy-card h2 i{color:#0453cb;}
.juy-quorum-ok{padding:.6rem 1rem;border-radius:10px;background:rgba(16,185,129,.10);color:#047857;font-size:.85rem;display:flex;align-items:center;gap:.5rem;font-weight:600;}
.juy-quorum-ko{padding:.6rem 1rem;border-radius:10px;background:rgba(245,158,11,.10);color:#b45309;font-size:.85rem;display:flex;flex-direction:column;gap:.25rem;font-weight:600;}
.juy-membre-row{display:flex;align-items:center;justify-content:space-between;padding:.65rem .85rem;background:#f8fafc;border-radius:8px;margin-bottom:.4rem;font-size:.82rem;}
.juy-membre-info{display:flex;align-items:center;gap:.55rem;}
.juy-role-chip{padding:.15rem .5rem;border-radius:5px;font-size:.65rem;text-transform:uppercase;font-weight:700;}
.juy-role-chip--president{background:rgba(4,83,203,.15);color:#0453cb;}
.juy-role-chip--assesseur{background:rgba(59,125,219,.10);color:#3b7ddb;}
.juy-role-chip--secretaire{background:rgba(16,185,129,.10);color:#047857;}
.juy-role-chip--consultatif{background:rgba(100,116,139,.10);color:#475569;}

.juy-decision-table{width:100%;border-collapse:separate;border-spacing:0;font-size:.82rem;}
.juy-decision-table th{background:#f8fafc;color:#475569;font-weight:600;font-size:.68rem;text-transform:uppercase;letter-spacing:.5px;padding:.6rem .75rem;text-align:left;border-bottom:1px solid #e2e8f0;}
.juy-decision-table td{padding:.7rem .75rem;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
.juy-decision-table tbody tr:hover{background:#f8fafc;cursor:pointer;}
.juy-dec-chip{display:inline-flex;padding:.15rem .55rem;border-radius:5px;font-size:.68rem;font-weight:700;text-transform:uppercase;}
.juy-dec-chip--admis{background:rgba(16,185,129,.10);color:#047857;}
.juy-dec-chip--admission_rattrapage{background:rgba(245,158,11,.10);color:#b45309;}
.juy-dec-chip--ajourne{background:rgba(220,38,38,.10);color:#b91c1c;}
.juy-dec-chip--exclu{background:rgba(220,38,38,.18);color:#7f1d1d;font-weight:800;}
.juy-dec-chip--admis_sous_condition{background:rgba(245,158,11,.18);color:#92400e;}
.juy-dec-chip--defere{background:rgba(100,116,139,.10);color:#475569;}
.juy-override-badge{display:inline-flex;padding:.1rem .35rem;border-radius:4px;font-size:.6rem;font-weight:700;background:rgba(245,158,11,.18);color:#92400e;margin-left:.3rem;}

.juy-stats-grid{display:grid;grid-template-columns:repeat(auto-fit, minmax(160px, 1fr));gap:.75rem;}
.juy-stat-card{padding:1rem;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;text-align:center;}
.juy-stat-value{font-size:1.8rem;font-weight:700;color:#0453cb;line-height:1;}
.juy-stat-label{font-size:.7rem;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-top:.35rem;}

.juy-btn{padding:.5rem 1rem;border-radius:9px;font-size:.82rem;font-weight:600;border:1px solid;cursor:pointer;display:inline-flex;align-items:center;gap:.4rem;text-decoration:none;}
.juy-btn--primary{background:#0453cb;color:#fff;border-color:#0453cb;}
.juy-btn--secondary{background:#f1f5f9;color:#475569;border-color:#e2e8f0;}
.juy-btn--warning{background:rgba(245,158,11,.10);color:#b45309;border-color:rgba(245,158,11,.25);}
.juy-btn--success{background:rgba(16,185,129,.10);color:#047857;border-color:rgba(16,185,129,.25);}
.juy-btn--danger{background:rgba(220,38,38,.08);color:#b91c1c;border-color:rgba(220,38,38,.2);}
.juy-btn:disabled{opacity:.5;cursor:not-allowed;}

.juy-modal{position:fixed;inset:0;background:rgba(15,23,42,.65);z-index:1050;display:flex;align-items:center;justify-content:center;padding:1rem;}
.juy-modal-body{background:#fff;border-radius:16px;padding:1.5rem;max-width:560px;width:100%;box-shadow:0 25px 60px rgba(0,0,0,.3);}
.juy-modal-body h2{margin:0 0 1rem;color:#0453cb;font-size:1.15rem;display:flex;align-items:center;gap:.5rem;}

.juy-actions-bar{display:flex;gap:.5rem;flex-wrap:wrap;padding:1rem 1.25rem;background:#fff;border:1px solid #e2e8f0;border-radius:14px;margin-bottom:1.25rem;align-items:center;}
.juy-actions-bar > .pv-numero{margin-left:auto;font-family:'Courier New',monospace;font-size:.85rem;color:#0453cb;font-weight:700;background:rgba(4,83,203,.08);padding:.35rem .65rem;border-radius:7px;}
</style>
@endpush

@section('content')
<div x-data="jurySalle()" x-init="init()">

<div class="juy-hero">
    <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;align-items:flex-start;">
        <div>
            <h1>{{ $jury->libelle }}</h1>
            <p>
                <span style="text-transform:uppercase;font-weight:700;font-size:.75rem;">{{ str_replace('_',' ', $jury->status) }}</span>
                @if($jury->parcours) · {{ $jury->parcours->name }} @endif
                @if($jury->classe) · {{ $jury->classe->name }} @endif
                @if($jury->semestre) · S{{ $jury->semestre }} @endif
                @if($jury->date_jury) · {{ $jury->date_jury->format('d/m/Y') }} @endif
            </p>
        </div>
        <a href="{{ route('esbtp.lmd.jurys.index', ['annee_universitaire_id' => $jury->annee_universitaire_id]) }}" style="padding:.5rem .9rem;border-radius:9px;background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.2);text-decoration:none;font-weight:600;font-size:.82rem;">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>

    <div class="juy-meta">
        <div class="juy-meta-item"><div class="juy-meta-label">Étudiants</div><div class="juy-meta-value" x-text="stats.total">{{ $stats['total'] }}</div></div>
        <div class="juy-meta-item"><div class="juy-meta-label">Admis</div><div class="juy-meta-value" x-text="stats.admis">{{ $stats['admis'] }}</div></div>
        <div class="juy-meta-item"><div class="juy-meta-label">Rattrapage</div><div class="juy-meta-value" x-text="stats.admission_rattrapage">{{ $stats['admission_rattrapage'] }}</div></div>
        <div class="juy-meta-item"><div class="juy-meta-label">Ajournés</div><div class="juy-meta-value" x-text="stats.ajourne">{{ $stats['ajourne'] }}</div></div>
        <div class="juy-meta-item"><div class="juy-meta-label">Overrides</div><div class="juy-meta-value" x-text="stats.overrides">{{ $stats['overrides'] }}</div></div>
    </div>
</div>

{{-- Actions bar --}}
<div class="juy-actions-bar">
    @can('lmd.jury.deliberate')
    @if(!$jury->isLocked())
    <button type="button" class="juy-btn juy-btn--primary" @click="appliquerAuto()" :disabled="busy">
        <i class="fas fa-bolt"></i> <span x-text="busy ? 'Calcul…' : 'Appliquer décisions auto'"></span>
    </button>
    @endif
    @endcan

    @can('lmd.jury.publish')
    @if(!$jury->pv_genere_at)
    <button type="button" class="juy-btn juy-btn--success" @click="genererPv()" :disabled="busy || !quorum.ok">
        <i class="fas fa-file-signature"></i> <span x-text="busy ? 'Génération…' : 'Générer PV'"></span>
    </button>
    @else
    <a href="{{ route('esbtp.lmd.jurys.pv-preview', $jury) }}" target="_blank" class="juy-btn juy-btn--secondary">
        <i class="fas fa-file-pdf"></i> Aperçu PV
    </a>
    <a href="{{ route('esbtp.lmd.jurys.pv-download', $jury) }}" class="juy-btn juy-btn--secondary">
        <i class="fas fa-download"></i> Télécharger PV
    </a>
    @if($jury->status !== 'publie')
    <button type="button" class="juy-btn juy-btn--success" @click="publier()" :disabled="busy">
        <i class="fas fa-flag-checkered"></i> <span x-text="busy ? 'Publication…' : 'Publier les décisions'"></span>
    </button>
    @endif
    @endif
    @endcan

    @if($jury->pv_numero)
    <span class="pv-numero"><i class="fas fa-stamp"></i> {{ $jury->pv_numero }}</span>
    @endif
</div>

{{-- Tabs --}}
<div class="juy-tabs" role="tablist">
    <button :class="tab==='composition' ? 'juy-tab juy-tab--active' : 'juy-tab'" @click="tab='composition'">
        <i class="fas fa-users"></i> Composition <span style="background:rgba(255,255,255,.2);border-radius:99px;padding:.05rem .4rem;font-size:.7rem;" x-text="membres.length">{{ $jury->membres->count() }}</span>
    </button>
    <button :class="tab==='deliberation' ? 'juy-tab juy-tab--active' : 'juy-tab'" @click="tab='deliberation'">
        <i class="fas fa-scale-balanced"></i> Délibération <span style="background:rgba(255,255,255,.2);border-radius:99px;padding:.05rem .4rem;font-size:.7rem;" x-text="decisions.length">{{ $jury->decisions->count() }}</span>
    </button>
    <button :class="tab==='statistiques' ? 'juy-tab juy-tab--active' : 'juy-tab'" @click="tab='statistiques'">
        <i class="fas fa-chart-pie"></i> Statistiques
    </button>
    <button :class="tab==='pv' ? 'juy-tab juy-tab--active' : 'juy-tab'" @click="tab='pv'">
        <i class="fas fa-file-signature"></i> PV
    </button>
</div>

{{-- Tab Composition --}}
<div x-show="tab==='composition'" x-cloak>
    <div class="juy-card">
        <h2><i class="fas fa-users"></i> Quorum &amp; membres</h2>
        <template x-if="quorum.ok">
            <div class="juy-quorum-ok">
                <i class="fas fa-check-circle"></i>
                Quorum atteint — <span x-text="quorum.present"></span> membres présents (min <span x-text="quorum.min"></span>)
                <template x-if="quorum.has_president"><span style="background:rgba(16,185,129,.15);padding:.1rem .4rem;border-radius:4px;font-size:.7rem;">Président présent</span></template>
            </div>
        </template>
        <template x-if="!quorum.ok">
            <div class="juy-quorum-ko">
                <div><i class="fas fa-triangle-exclamation"></i> Quorum non atteint</div>
                <template x-for="r in quorum.reasons" :key="r">
                    <div style="font-weight:400;font-size:.78rem;color:#92400e;">• <span x-text="r"></span></div>
                </template>
            </div>
        </template>

        <div style="margin-top:1rem;">
            <template x-for="m in membres" :key="m.id">
                <div class="juy-membre-row">
                    <div class="juy-membre-info">
                        <i :class="m.has_signed ? 'fas fa-check-circle' : 'far fa-circle'" :style="m.has_signed ? 'color:#10b981' : 'color:#94a3b8'"></i>
                        <div>
                            <div style="font-weight:600;color:#1e293b;" x-text="m.user_name"></div>
                            <div style="font-size:.7rem;color:#64748b;" x-text="m.present ? 'Présent' : 'Absent'"></div>
                        </div>
                        <span :class="'juy-role-chip juy-role-chip--'+m.role" x-text="m.role"></span>
                    </div>
                    @can('lmd.jury.preside')
                    @if(!$jury->isLocked())
                    <button type="button" @click="removeMembre(m)" style="background:none;border:none;color:#b91c1c;cursor:pointer;font-size:.85rem;padding:.3rem;">
                        <i class="fas fa-times"></i>
                    </button>
                    @endif
                    @endcan
                </div>
            </template>

            @can('lmd.jury.preside')
            @if(!$jury->isLocked())
            <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid #f1f5f9;display:grid;grid-template-columns:2fr 1fr auto;gap:.5rem;align-items:end;">
                <div>
                    <label style="font-size:.7rem;color:#475569;font-weight:600;text-transform:uppercase;">Utilisateur</label>
                    <select x-model="newMembreUserId" style="width:100%;padding:.4rem;border:1px solid #e2e8f0;border-radius:7px;font-size:.85rem;">
                        <option value="">— Sélectionner —</option>
                        @foreach($enseignants as $e)
                        <option value="{{ $e->id }}">{{ $e->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:.7rem;color:#475569;font-weight:600;text-transform:uppercase;">Rôle</label>
                    <select x-model="newMembreRole" style="width:100%;padding:.4rem;border:1px solid #e2e8f0;border-radius:7px;font-size:.85rem;">
                        <option value="president">Président</option>
                        <option value="assesseur">Assesseur</option>
                        <option value="secretaire">Secrétaire</option>
                        <option value="consultatif">Consultatif</option>
                    </select>
                </div>
                <button type="button" class="juy-btn juy-btn--primary" @click="addMembre()" :disabled="!newMembreUserId || busy">
                    <i class="fas fa-plus"></i> Ajouter
                </button>
            </div>
            @endif
            @endcan
        </div>
    </div>
</div>

{{-- Tab Délibération --}}
<div x-show="tab==='deliberation'" x-cloak>
    <div class="juy-card">
        <h2><i class="fas fa-scale-balanced"></i> Décisions ({{ $jury->decisions->count() }})</h2>

        <template x-if="decisions.length === 0">
            <div style="padding:2rem;text-align:center;color:#94a3b8;font-size:.88rem;">
                <i class="fas fa-bolt" style="font-size:2rem;color:#cbd5e1;display:block;margin-bottom:.5rem;"></i>
                Aucune décision. Utilisez « Appliquer décisions auto » pour générer les décisions automatiques basées sur les bulletins LMD.
            </div>
        </template>

        <template x-if="decisions.length > 0">
            <table class="juy-decision-table">
                <thead><tr><th>Étudiant</th><th>Moyenne</th><th>Crédits</th><th>Auto</th><th>Décision</th><th>Mention</th><th></th></tr></thead>
                <tbody>
                    <template x-for="d in decisions" :key="d.id">
                        <tr @click="openOverride(d)">
                            <td x-text="d.etudiant_name"></td>
                            <td x-text="d.moyenne_generale ? Number(d.moyenne_generale).toFixed(2) : '—'"></td>
                            <td><span x-text="d.credits_obtenus"></span> / <span x-text="d.credits_attendus"></span></td>
                            <td><span :class="'juy-dec-chip juy-dec-chip--'+d.decision_auto" x-text="d.decision_auto || '—'"></span></td>
                            <td>
                                <span :class="'juy-dec-chip juy-dec-chip--'+d.decision" x-text="d.decision"></span>
                                <template x-if="d.override_par_jury"><span class="juy-override-badge">Override</span></template>
                            </td>
                            <td x-text="d.mention || '—'"></td>
                            <td>
                                @can('lmd.jury.deliberate')
                                @if(!$jury->isLocked())
                                <button type="button" style="background:none;border:none;color:#0453cb;cursor:pointer;font-size:.85rem;">
                                    <i class="fas fa-pen-to-square"></i>
                                </button>
                                @endif
                                @endcan
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </template>
    </div>
</div>

{{-- Tab Statistiques --}}
<div x-show="tab==='statistiques'" x-cloak>
    <div class="juy-card">
        <h2><i class="fas fa-chart-pie"></i> Répartition des décisions</h2>
        <div class="juy-stats-grid">
            <div class="juy-stat-card"><div class="juy-stat-value" x-text="stats.admis">{{ $stats['admis'] }}</div><div class="juy-stat-label">Admis</div></div>
            <div class="juy-stat-card"><div class="juy-stat-value" x-text="stats.admission_rattrapage">{{ $stats['admission_rattrapage'] }}</div><div class="juy-stat-label">Rattrapage</div></div>
            <div class="juy-stat-card"><div class="juy-stat-value" x-text="stats.ajourne">{{ $stats['ajourne'] }}</div><div class="juy-stat-label">Ajournés</div></div>
            <div class="juy-stat-card"><div class="juy-stat-value" x-text="stats.exclu">{{ $stats['exclu'] }}</div><div class="juy-stat-label">Exclus</div></div>
            <div class="juy-stat-card"><div class="juy-stat-value" x-text="stats.admis_sous_condition">{{ $stats['admis_sous_condition'] }}</div><div class="juy-stat-label">Sous condition</div></div>
            <div class="juy-stat-card"><div class="juy-stat-value" x-text="stats.defere">{{ $stats['defere'] }}</div><div class="juy-stat-label">Déférés</div></div>
        </div>
    </div>

    <div class="juy-card">
        <h2><i class="fas fa-award"></i> Mentions</h2>
        <div class="juy-stats-grid">
            <div class="juy-stat-card"><div class="juy-stat-value">{{ $stats['mentions']['excellent'] }}</div><div class="juy-stat-label">Excellent</div></div>
            <div class="juy-stat-card"><div class="juy-stat-value">{{ $stats['mentions']['tres_bien'] }}</div><div class="juy-stat-label">Très Bien</div></div>
            <div class="juy-stat-card"><div class="juy-stat-value">{{ $stats['mentions']['bien'] }}</div><div class="juy-stat-label">Bien</div></div>
            <div class="juy-stat-card"><div class="juy-stat-value">{{ $stats['mentions']['assez_bien'] }}</div><div class="juy-stat-label">Assez Bien</div></div>
            <div class="juy-stat-card"><div class="juy-stat-value">{{ $stats['mentions']['passable'] }}</div><div class="juy-stat-label">Passable</div></div>
        </div>
        @if($stats['moyenne_promo'])
        <div style="margin-top:1rem;padding:.75rem;background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;color:#075985;font-size:.85rem;">
            <i class="fas fa-chart-line"></i> Moyenne promo : <strong>{{ number_format((float) $stats['moyenne_promo'], 2) }} / 20</strong>
        </div>
        @endif
    </div>
</div>

{{-- Tab PV --}}
<div x-show="tab==='pv'" x-cloak>
    <div class="juy-card">
        <h2><i class="fas fa-file-signature"></i> Procès-Verbal</h2>
        @if($jury->pv_genere_at)
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;font-size:.88rem;color:#1e293b;">
            <div><span style="color:#64748b;">Numéro :</span> <strong style="font-family:'Courier New';color:#0453cb;">{{ $jury->pv_numero }}</strong></div>
            <div><span style="color:#64748b;">Généré le :</span> <strong>{{ $jury->pv_genere_at->format('d/m/Y H:i') }}</strong></div>
            <div><span style="color:#64748b;">Path storage :</span> <code style="font-size:.78rem;background:#f1f5f9;padding:.15rem .35rem;border-radius:4px;">{{ $jury->pv_path }}</code></div>
            <div><span style="color:#64748b;">Statut :</span> <strong>{{ $jury->status }}</strong></div>
        </div>
        <div style="margin-top:1rem;display:flex;gap:.5rem;">
            <a href="{{ route('esbtp.lmd.jurys.pv-preview', $jury) }}" target="_blank" class="juy-btn juy-btn--secondary">
                <i class="fas fa-eye"></i> Aperçu inline
            </a>
            <a href="{{ route('esbtp.lmd.jurys.pv-download', $jury) }}" class="juy-btn juy-btn--primary">
                <i class="fas fa-download"></i> Télécharger PDF
            </a>
        </div>
        @else
        <div style="padding:2rem;text-align:center;color:#94a3b8;font-size:.88rem;">
            <i class="fas fa-hourglass" style="font-size:2rem;color:#cbd5e1;display:block;margin-bottom:.5rem;"></i>
            PV non encore généré. Validez d'abord la composition (quorum) et appliquez les décisions, puis cliquez sur « Générer PV » dans la barre d'actions.
        </div>
        @endif
    </div>
</div>

{{-- Modal Override --}}
<div class="juy-modal" x-show="overrideOpen" x-cloak @keydown.escape.window="closeOverride()">
    <div class="juy-modal-body" @click.outside="closeOverride()">
        <h2><i class="fas fa-pen-to-square"></i> Override décision</h2>
        <div style="background:#f8fafc;padding:.75rem;border-radius:8px;margin-bottom:1rem;">
            <div style="font-weight:600;color:#1e293b;" x-text="form.etudiant_name"></div>
            <div style="font-size:.78rem;color:#64748b;">
                Auto: <span :class="'juy-dec-chip juy-dec-chip--'+form.decision_auto" x-text="form.decision_auto"></span>
            </div>
        </div>
        <div>
            <label style="font-size:.72rem;color:#475569;font-weight:600;text-transform:uppercase;display:block;margin-bottom:.3rem;">Nouvelle décision *</label>
            <select x-model="form.decision" style="width:100%;padding:.5rem;border:1px solid #e2e8f0;border-radius:8px;font-size:.88rem;">
                <option value="admis">Admis</option>
                <option value="admission_rattrapage">Admission rattrapage</option>
                <option value="ajourne">Ajourné</option>
                <option value="exclu">Exclu</option>
                <option value="admis_sous_condition">Admis sous condition</option>
                <option value="defere">Déféré</option>
            </select>
        </div>
        <div style="margin-top:.75rem;">
            <label style="font-size:.72rem;color:#475569;font-weight:600;text-transform:uppercase;display:block;margin-bottom:.3rem;">Motif * (min 5 caractères)</label>
            <textarea x-model="form.motif" rows="3" required maxlength="1000" style="width:100%;padding:.5rem;border:1px solid #e2e8f0;border-radius:8px;font-size:.88rem;" placeholder="Ex: Cas exceptionnel — situation médicale documentée, vote majoritaire."></textarea>
        </div>
        <div style="margin-top:.75rem;">
            <label style="font-size:.72rem;color:#475569;font-weight:600;text-transform:uppercase;display:block;margin-bottom:.3rem;">Résultat vote</label>
            <select x-model="form.vote_resultat" style="width:100%;padding:.5rem;border:1px solid #e2e8f0;border-radius:8px;font-size:.88rem;">
                <option value="">— Aucun (consensus) —</option>
                <option value="unanime">Unanime</option>
                <option value="majorite">Majorité</option>
                <option value="partage_voix_president">Voix du président</option>
            </select>
        </div>
        <div style="margin-top:1.25rem;display:flex;gap:.5rem;justify-content:flex-end;">
            <button type="button" @click="closeOverride()" class="juy-btn juy-btn--secondary">Annuler</button>
            <button type="button" @click="saveOverride()" class="juy-btn juy-btn--primary" :disabled="busy || !form.motif || form.motif.length < 5">
                <i class="fas fa-check"></i> <span x-text="busy ? 'Enregistrement…' : 'Enregistrer'"></span>
            </button>
        </div>
    </div>
</div>

</div>

@php
    $decisionsData = $jury->decisions->map(function ($d) {
        return [
            'id' => $d->id,
            'etudiant_id' => $d->etudiant_id,
            'etudiant_name' => trim(($d->etudiant?->nom ?? '') . ' ' . ($d->etudiant?->prenom ?? '')) ?: ('Étudiant #' . $d->etudiant_id),
            'decision_auto' => $d->decision_auto,
            'decision' => $d->decision,
            'mention' => $d->mention,
            'override_par_jury' => (bool) $d->override_par_jury,
            'motif_override' => $d->motif_override,
            'vote_resultat' => $d->vote_resultat,
            'moyenne_generale' => $d->moyenne_generale,
            'credits_obtenus' => (int) $d->credits_obtenus,
            'credits_attendus' => (int) $d->credits_attendus,
        ];
    })->values();

    $membresData = $jury->membres->map(function ($m) {
        return [
            'id' => $m->id,
            'user_id' => $m->user_id,
            'user_name' => $m->user?->name ?: 'Utilisateur #' . $m->user_id,
            'role' => $m->role,
            'present' => (bool) $m->present,
            'has_signed' => $m->hasSigned(),
        ];
    })->values();
@endphp

@push('scripts')
<script>
function jurySalle() {
    return {
        tab: 'composition',
        membres: @json($membresData),
        decisions: @json($decisionsData),
        stats: @json($stats),
        quorum: @json($quorum),
        newMembreUserId: '',
        newMembreRole: 'assesseur',
        busy: false,
        overrideOpen: false,
        form: { etudiantId: null, etudiant_name: '', decision_auto: '', decision: '', motif: '', vote_resultat: '' },
        init(){
            window.addEventListener('jury:decision-updated', (ev) => {
                this.applyDecisionUpdate(ev.detail);
            });
        },
        async post(url, body = {}) {
            this.busy = true;
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, Accept: 'application/json' },
                    body: JSON.stringify(body)
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message || ('Erreur ' + res.status));
                return data;
            } finally { this.busy = false; }
        },
        async patch(url, body) {
            this.busy = true;
            try {
                const res = await fetch(url, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, Accept: 'application/json' },
                    body: JSON.stringify(body)
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message || ('Erreur ' + res.status));
                return data;
            } finally { this.busy = false; }
        },
        async delete(url) {
            this.busy = true;
            try {
                const res = await fetch(url, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, Accept: 'application/json' },
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message || ('Erreur ' + res.status));
                return data;
            } finally { this.busy = false; }
        },
        toast(type, message) {
            window.dispatchEvent(new CustomEvent('toast', { detail: { type, message } }));
        },
        async addMembre() {
            if (!this.newMembreUserId) return;
            try {
                const data = await this.post('{{ route('esbtp.lmd.jurys.membres.store', $jury) }}', {
                    user_id: parseInt(this.newMembreUserId), role: this.newMembreRole, present: true,
                });
                const existing = this.membres.findIndex(m => m.user_id === data.membre.user_id);
                if (existing >= 0) this.membres[existing] = data.membre;
                else this.membres.push(data.membre);
                this.quorum = data.quorum;
                this.newMembreUserId = '';
                this.toast('success', 'Membre ajouté.');
            } catch (e) { this.toast('error', e.message); }
        },
        async removeMembre(m) {
            if (!confirm(`Retirer ${m.user_name} ?`)) return;
            try {
                const data = await this.delete(`/esbtp/lmd/jurys/{{ $jury->id }}/membres/${m.id}`);
                this.membres = this.membres.filter(x => x.id !== m.id);
                this.quorum = data.quorum;
                this.toast('success', 'Membre retiré.');
            } catch (e) { this.toast('error', e.message); }
        },
        async appliquerAuto() {
            try {
                const data = await this.post('{{ route('esbtp.lmd.jurys.decisions.auto', $jury) }}');
                this.stats = data.stats;
                this.toast('success', `${data.created_count} décisions créées.`);
                setTimeout(() => window.location.reload(), 600);
            } catch (e) { this.toast('error', e.message); }
        },
        openOverride(d) {
            @if(!$jury->isLocked())
            this.form = {
                etudiantId: d.etudiant_id,
                etudiant_name: d.etudiant_name,
                decision_auto: d.decision_auto,
                decision: d.decision,
                motif: d.motif_override || '',
                vote_resultat: d.vote_resultat || '',
            };
            this.overrideOpen = true;
            @endif
        },
        closeOverride() { this.overrideOpen = false; },
        async saveOverride() {
            if (!this.form.motif || this.form.motif.length < 5) {
                this.toast('error', 'Motif obligatoire (min 5 caractères).');
                return;
            }
            try {
                const data = await this.patch(`/esbtp/lmd/jurys/{{ $jury->id }}/decisions/${this.form.etudiantId}`, {
                    decision: this.form.decision,
                    motif: this.form.motif,
                    vote_resultat: this.form.vote_resultat || null,
                });
                window.dispatchEvent(new CustomEvent('jury:decision-updated', { detail: data.decision }));
                this.stats = data.stats;
                this.overrideOpen = false;
                this.toast('success', 'Décision enregistrée.');
            } catch (e) { this.toast('error', e.message); }
        },
        applyDecisionUpdate(updated) {
            const idx = this.decisions.findIndex(d => d.etudiant_id === updated.etudiant_id);
            if (idx >= 0) {
                this.decisions[idx] = { ...this.decisions[idx], ...updated, etudiant_name: this.decisions[idx].etudiant_name };
            }
        },
        async genererPv() {
            if (!confirm('Générer le PV ? Les décisions seront verrouillées (anti-tampering).')) return;
            try {
                const data = await this.post('{{ route('esbtp.lmd.jurys.pv.generer', $jury) }}');
                this.toast('success', `PV ${data.pv.numero} généré.`);
                setTimeout(() => window.location.reload(), 800);
            } catch (e) { this.toast('error', e.message); }
        },
        async publier() {
            if (!confirm('Publier les décisions ? Action irréversible.')) return;
            try {
                await this.post('{{ route('esbtp.lmd.jurys.publier', $jury) }}');
                this.toast('success', 'Jury publié.');
                setTimeout(() => window.location.reload(), 800);
            } catch (e) { this.toast('error', e.message); }
        }
    };
}
</script>
@endpush
@endsection
