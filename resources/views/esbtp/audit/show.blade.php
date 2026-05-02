@extends('layouts.app')

@section('title', "Détail audit #" . $audit->id)

@php
    // Parse user agent (simple detect)
    $ua = $audit->user_agent ?? '';
    $browser = 'Inconnu';
    if (str_contains($ua, 'Edg/')) $browser = 'Edge';
    elseif (str_contains($ua, 'Chrome')) $browser = 'Chrome';
    elseif (str_contains($ua, 'Firefox')) $browser = 'Firefox';
    elseif (str_contains($ua, 'Safari')) $browser = 'Safari';
    $os = 'Inconnu';
    if (str_contains($ua, 'Windows')) $os = 'Windows';
    elseif (str_contains($ua, 'Mac OS') || str_contains($ua, 'Macintosh')) $os = 'macOS';
    elseif (str_contains($ua, 'Android')) $os = 'Android';
    elseif (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) $os = 'iOS';
    elseif (str_contains($ua, 'Linux')) $os = 'Linux';

    $eventRaw = $audit->event;
    $eventLabels = [
        'created' => 'Création',
        'updated' => 'Modification',
        'deleted' => 'Suppression',
        'restored' => 'Restauration',
        'retrieved' => 'Consultation',
    ];
    $eventLabel = $eventLabels[$eventRaw] ?? mb_strtoupper($eventRaw, 'UTF-8');

    $modelLabels = [
        'App\Models\ESBTPPaiement' => 'Paiement',
        'App\Models\ESBTPDepense' => 'Dépense',
        'App\Models\ESBTPFacture' => 'Facture',
        'App\Models\ESBTPFactureDetail' => 'Détail facture',
        'App\Models\ESBTPFraisScolarite' => 'Frais scolarité',
        'App\Models\ESBTPSalaire' => 'Salaire',
        'App\Models\ESBTPBourse' => 'Bourse',
        'App\Models\ESBTPEtudiant' => 'Étudiant',
        'App\Models\ESBTPInscription' => 'Inscription',
        'App\Models\User' => 'Utilisateur',
    ];
    $modelLabel = $modelLabels[$audit->auditable_type] ?? class_basename($audit->auditable_type);

    $riskMap = [
        'Critique' => 'critique',
        'Élevé' => 'eleve',
        'Moyen' => 'moyen',
        'Faible' => 'faible',
    ];
    $riskClass = $riskMap[$riskLevel] ?? 'faible';
@endphp

@section('content')
<div class="container-fluid au-page">

    {{-- ═══════════════════════════════ HERO SHOW ═══════════════════════════════ --}}
    <div class="au-hero au-show-hero">
        <div class="au-hero-top">
            <div class="au-hero-left">
                <div class="au-hero-icon"><i class="fas fa-info-circle"></i></div>
                <div class="au-hero-info">
                    <h1>{{ $eventLabel }} de {{ $modelLabel }} #{{ $audit->auditable_id }}</h1>
                    <p>
                        Effectuée par <strong>{{ $audit->user?->name ?? 'Système' }}</strong>
                        le {{ $audit->created_at->format('d/m/Y à H:i:s') }}
                    </p>
                </div>
            </div>
            <div class="au-hero-actions">
                <a href="{{ route('esbtp.audit.index') }}" class="au-btn au-btn--glass">
                    <i class="fas fa-arrow-left"></i> Retour au journal
                </a>
            </div>
        </div>
        <div class="au-show-badges">
            <span class="au-chip au-chip--{{ $eventRaw }} au-chip--lg">{{ $eventLabel }}</span>
            <span class="au-chip au-chip--risk-{{ $riskClass }} au-chip--lg">
                <i class="fas fa-shield-alt"></i> Risque : {{ $riskLevel }}
            </span>
            <span class="au-chip au-chip--neutral au-chip--lg">
                <i class="far fa-clock"></i> ID #{{ $audit->id }}
            </span>
        </div>
    </div>

    <div class="au-show-grid">
        {{-- ═══════════════════════════════ MÉTADONNÉES ═══════════════════════════════ --}}
        <div class="au-card">
            <div class="au-card-header">
                <div class="au-card-title"><i class="fas fa-tags"></i> Métadonnées</div>
            </div>
            <div class="au-card-body">
                <dl class="au-meta-list">
                    <div>
                        <dt>Utilisateur</dt>
                        <dd>
                            @if($audit->user)
                                <span class="au-cell-user">
                                    <span class="au-avatar">{{ mb_substr($audit->user->name, 0, 1, 'UTF-8') }}</span>
                                    <div>
                                        <div class="au-meta-strong">{{ $audit->user->name }}</div>
                                        <div class="au-meta-sub">{{ $audit->user->email }}</div>
                                    </div>
                                </span>
                            @else
                                <em>Système (action automatique)</em>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt>Adresse IP</dt>
                        <dd><code class="au-code">{{ $audit->ip_address ?? 'N/A' }}</code></dd>
                    </div>
                    <div>
                        <dt>Navigateur</dt>
                        <dd>
                            <i class="fab fa-{{ strtolower($browser) }} me-1"></i>
                            {{ $browser }} <span class="au-meta-sub">/ {{ $os }}</span>
                        </dd>
                    </div>
                    <div>
                        <dt>URL accédée</dt>
                        <dd><code class="au-code au-code--block">{{ $audit->url ?? 'N/A' }}</code></dd>
                    </div>
                    <div>
                        <dt>Tags</dt>
                        <dd>
                            @if($audit->tags)
                                @foreach(explode(',', $audit->tags) as $tag)
                                    <span class="au-chip au-chip--neutral">{{ trim($tag) }}</span>
                                @endforeach
                            @else
                                <em class="au-meta-empty">Aucun tag</em>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt>Date / Heure</dt>
                        <dd>{{ $audit->created_at->format('d/m/Y H:i:s') }} <span class="au-meta-sub">({{ $audit->created_at->diffForHumans() }})</span></dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- ═══════════════════════════════ ENTITÉ CONCERNÉE ═══════════════════════════════ --}}
        <div class="au-card">
            <div class="au-card-header">
                <div class="au-card-title"><i class="fas fa-link"></i> Entité concernée</div>
            </div>
            <div class="au-card-body">
                <div class="au-entity-info">
                    <div class="au-entity-row">
                        <span class="au-meta-label">Type :</span>
                        <span class="au-chip au-chip--neutral">{{ $modelLabel }}</span>
                    </div>
                    <div class="au-entity-row">
                        <span class="au-meta-label">Identifiant :</span>
                        <code class="au-code">#{{ $audit->auditable_id }}</code>
                    </div>
                    <div class="au-entity-row">
                        <span class="au-meta-label">Classe :</span>
                        <code class="au-code au-code--block">{{ $audit->auditable_type }}</code>
                    </div>
                </div>

                @if($entityUrl)
                    <a href="{{ $entityUrl }}" class="au-btn au-btn--primary mt-3">
                        <i class="fas fa-external-link-alt"></i> Ouvrir l'entité
                    </a>
                @else
                    <div class="au-warning mt-3">
                        <i class="fas fa-exclamation-triangle"></i>
                        Cette entité n'est plus accessible (supprimée ou modèle non navigable).
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════ DIFFÉRENCES ═══════════════════════════════ --}}
    <div class="au-card">
        <div class="au-card-header">
            <div class="au-card-title">
                <i class="fas fa-exchange-alt"></i> Différences
                @if(count($changes) > 0)
                    <span class="au-badge-count">{{ count($changes) }} champ{{ count($changes) > 1 ? 's' : '' }}</span>
                @endif
            </div>
        </div>
        <div class="au-card-body au-card-body--flush">
            @if(count($changes) > 0)
                <table class="au-diff-table au-diff-table--full">
                    <thead>
                        <tr>
                            <th style="width: 22%">Champ</th>
                            <th style="width: 39%"><i class="fas fa-arrow-left text-danger me-1"></i> Ancienne valeur</th>
                            <th style="width: 39%"><i class="fas fa-arrow-right text-success me-1"></i> Nouvelle valeur</th>
                        </tr>
                    </thead>
                    <tbody x-data="{ expanded: {} }">
                        @foreach($changes as $i => $c)
                            @php
                                $oldStr = is_string($c['old']) ? $c['old'] : json_encode($c['old'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                                $newStr = is_string($c['new']) ? $c['new'] : json_encode($c['new'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                                $longContent = (mb_strlen((string) $oldStr) > 200 || mb_strlen((string) $newStr) > 200);
                            @endphp
                            <tr>
                                <td><strong>{{ $c['field'] }}</strong></td>
                                <td>
                                    @if($longContent)
                                        <div x-show="expanded[{{ $i }}]" x-cloak><span class="au-diff-old au-diff-old--block">{{ $oldStr }}</span></div>
                                        <div x-show="!expanded[{{ $i }}]"><span class="au-diff-old">{{ mb_substr((string) $oldStr, 0, 150, 'UTF-8') }}…</span></div>
                                    @else
                                        <span class="au-diff-old">{{ $oldStr ?: '—' }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($longContent)
                                        <div x-show="expanded[{{ $i }}]" x-cloak><span class="au-diff-new au-diff-new--block">{{ $newStr }}</span></div>
                                        <div x-show="!expanded[{{ $i }}]"><span class="au-diff-new">{{ mb_substr((string) $newStr, 0, 150, 'UTF-8') }}…</span></div>
                                    @else
                                        <span class="au-diff-new">{{ $newStr ?: '—' }}</span>
                                    @endif
                                    @if($longContent)
                                        <button type="button" class="au-link-btn" @click="expanded[{{ $i }}] = !expanded[{{ $i }}]">
                                            <span x-show="!expanded[{{ $i }}]">Voir plus</span>
                                            <span x-show="expanded[{{ $i }}]" x-cloak>Voir moins</span>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="au-empty au-empty--small">
                    <i class="fas fa-info-circle"></i>
                    <p>Aucun changement de valeur enregistré pour cet audit.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- ═══════════════════════════════ AUDITS LIÉS ═══════════════════════════════ --}}
    <div class="au-card">
        <div class="au-card-header">
            <div class="au-card-title">
                <i class="fas fa-history"></i> Audits liés
                <span class="au-meta-sub">5 derniers événements sur cette entité</span>
            </div>
        </div>
        <div class="au-card-body au-card-body--flush">
            @if($relatedAudits->isEmpty())
                <div class="au-empty au-empty--small">
                    <i class="fas fa-history"></i>
                    <p>Aucun autre audit enregistré pour cette entité.</p>
                </div>
            @else
                <ul class="au-timeline">
                    @foreach($relatedAudits as $r)
                        @php
                            $rEventLabel = $eventLabels[$r->event] ?? $r->event;
                        @endphp
                        <li class="au-timeline-item au-timeline-item--{{ $r->event }}">
                            <span class="au-timeline-icon">
                                @switch($r->event)
                                    @case('created') <i class="fas fa-plus"></i> @break
                                    @case('updated') <i class="fas fa-pen"></i> @break
                                    @case('deleted') <i class="fas fa-trash"></i> @break
                                    @case('restored') <i class="fas fa-undo"></i> @break
                                    @default <i class="fas fa-eye"></i>
                                @endswitch
                            </span>
                            <div class="au-timeline-content">
                                <div class="au-timeline-meta">
                                    <span class="au-chip au-chip--{{ $r->event }}">{{ $rEventLabel }}</span>
                                    par <strong>{{ $r->user?->name ?? 'Système' }}</strong>
                                    <span class="au-meta-sub">• {{ $r->created_at->diffForHumans() }}</span>
                                </div>
                                <div class="au-timeline-actions">
                                    <a href="{{ route('esbtp.audit.show', $r->id) }}" class="au-link-btn">
                                        Voir détail <i class="fas fa-chevron-right"></i>
                                    </a>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
[x-cloak] { display: none !important; }

.au-page { padding: 1rem 0; }

/* ───── HERO (réutilise .au-hero) ───── */
.au-hero {
    position: relative;
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px; padding: 2rem 2.5rem 1.5rem; color: #fff; margin-bottom: 1.25rem;
    box-shadow: 0 8px 30px rgba(4,83,203,.15); animation: au-fadeDown .5s ease-out;
}
@keyframes au-fadeDown { from { opacity:0; transform:translateY(-15px); } to { opacity:1; transform:translateY(0); } }
.au-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
.au-hero-left { display: flex; align-items: center; gap: 1rem; }
.au-hero-icon {
    width: 52px; height: 52px; border-radius: 14px;
    background: rgba(255,255,255,.12); backdrop-filter: blur(8px);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; border: 1px solid rgba(255,255,255,.15); flex-shrink: 0; color: #fff;
}
.au-hero-info h1 { font-size: 1.45rem; font-weight: 700; margin: 0 0 .2rem; color: #fff; letter-spacing: -.02em; }
.au-hero-info p { margin: 0; opacity: .8; font-size: .88rem; color: rgba(255,255,255,.7); }
.au-hero-info p strong { color: #fff; }
.au-hero-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
.au-btn { display: inline-flex; align-items: center; gap: .4rem; padding: .5rem 1rem; border-radius: 10px; font-size: .82rem; font-weight: 600; text-decoration: none; transition: all .2s; border: 1px solid transparent; cursor: pointer; }
.au-btn--glass { background: rgba(255,255,255,.15); color: #fff; border-color: rgba(255,255,255,.2); }
.au-btn--glass:hover { background: rgba(255,255,255,.22); color: #fff; }
.au-btn--primary { background: #0453cb; color: #fff; }
.au-btn--primary:hover { background: #033a8e; color: #fff; }

.au-show-badges { display: flex; gap: .5rem; margin-top: 1.5rem; flex-wrap: wrap; }

/* ───── GRID 2 COLONNES SHOW ───── */
.au-show-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; }

/* ───── CARD ───── */
.au-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; margin-bottom: 1rem;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
}
.au-card-header {
    padding: 1rem 1.25rem; display: flex; align-items: center; justify-content: space-between;
    border-bottom: 1px solid #f1f5f9; background: #fafbfc;
}
.au-card-title { display: flex; align-items: center; gap: .6rem; font-size: 1rem; font-weight: 700; color: #0f172a; flex-wrap: wrap; }
.au-card-title i { color: #0453cb; }
.au-card-body { padding: 1.25rem; }
.au-card-body--flush { padding: 0; }

.au-badge-count { background: #eff6ff; color: #0453cb; padding: .2rem .55rem; border-radius: 8px; font-size: .72rem; font-weight: 600; border: 1px solid #dbeafe; }

/* ───── META LIST ───── */
.au-meta-list { margin: 0; }
.au-meta-list > div { display: grid; grid-template-columns: 130px 1fr; gap: 1rem; padding: .65rem 0; border-bottom: 1px solid #f1f5f9; }
.au-meta-list > div:last-child { border-bottom: none; }
.au-meta-list dt { font-size: .72rem; color: #64748b; text-transform: uppercase; letter-spacing: .04em; font-weight: 700; padding-top: .15rem; }
.au-meta-list dd { margin: 0; font-size: .88rem; color: #1e293b; }
.au-meta-strong { font-weight: 600; color: #0f172a; }
.au-meta-sub { font-size: .78rem; color: #64748b; }
.au-meta-label { font-size: .8rem; color: #64748b; font-weight: 500; }
.au-meta-empty { color: #94a3b8; font-style: italic; font-size: .82rem; }

/* ───── CHIP ───── */
.au-chip { display: inline-flex; align-items: center; gap: .3rem; padding: .25rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 600; line-height: 1.2; border: 1px solid transparent; white-space: nowrap; margin-right: .25rem; }
.au-chip--lg { padding: .35rem .8rem; font-size: .8rem; }
.au-chip--created { background: #d1fae5; color: #065f46; border-color: #a7f3d0; }
.au-chip--updated { background: #dbeafe; color: #1e3a8a; border-color: #bfdbfe; }
.au-chip--deleted { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
.au-chip--restored { background: #fef3c7; color: #92400e; border-color: #fde68a; }
.au-chip--retrieved { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }
.au-chip--neutral { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }
.au-chip--risk-critique { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
.au-chip--risk-eleve { background: #fef3c7; color: #92400e; border-color: #fde68a; }
.au-chip--risk-moyen { background: #dbeafe; color: #1e3a8a; border-color: #bfdbfe; }
.au-chip--risk-faible { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }

/* ───── CODE ───── */
.au-code {
    font-family: ui-monospace, "SF Mono", Menlo, monospace; font-size: .78rem;
    background: #f1f5f9; padding: .15rem .45rem; border-radius: 6px; color: #475569;
    word-break: break-all;
}
.au-code--block { display: inline-block; padding: .35rem .55rem; }

/* ───── USER AVATAR ───── */
.au-cell-user { display: flex; align-items: center; gap: .65rem; }
.au-avatar {
    width: 32px; height: 32px; border-radius: 50%;
    background: linear-gradient(135deg, #0453cb, #3b7ddb); color: #fff;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: .8rem; font-weight: 700; flex-shrink: 0;
}

/* ───── ENTITY ───── */
.au-entity-info { display: flex; flex-direction: column; gap: .55rem; }
.au-entity-row { display: flex; align-items: center; gap: .55rem; flex-wrap: wrap; }

.au-warning {
    background: #fef3c7; border: 1px solid #fde68a; border-radius: 10px; padding: .75rem .9rem;
    color: #92400e; font-size: .85rem; display: flex; align-items: center; gap: .55rem;
}
.au-warning i { color: #b45309; }

/* ───── DIFF TABLE ───── */
.au-diff-table { width: 100%; border-collapse: collapse; }
.au-diff-table--full { border-radius: 0; }
.au-diff-table thead th {
    background: #f8fafc; font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;
    color: #475569; padding: .85rem 1rem; text-align: left; border-bottom: 1px solid #e2e8f0;
}
.au-diff-table tbody td { padding: .85rem 1rem; font-size: .85rem; vertical-align: top; border-bottom: 1px solid #f1f5f9; }
.au-diff-table tbody tr:last-child td { border-bottom: none; }
.au-diff-table tbody tr:hover { background: #fafbfc; }
.au-diff-old { display: inline-block; color: #991b1b; background: #fee2e2; padding: .15rem .5rem; border-radius: 6px; font-family: ui-monospace, "SF Mono", monospace; font-size: .78rem; word-break: break-all; }
.au-diff-new { display: inline-block; color: #065f46; background: #d1fae5; padding: .15rem .5rem; border-radius: 6px; font-family: ui-monospace, "SF Mono", monospace; font-size: .78rem; word-break: break-all; }
.au-diff-old--block, .au-diff-new--block { display: block; padding: .65rem; white-space: pre-wrap; max-height: 300px; overflow-y: auto; }
.au-link-btn { background: none; border: none; color: #0453cb; font-size: .78rem; font-weight: 600; cursor: pointer; padding: .3rem 0; margin-top: .35rem; display: inline-flex; align-items: center; gap: .25rem; text-decoration: none; }
.au-link-btn:hover { color: #033a8e; text-decoration: underline; }

/* ───── EMPTY ───── */
.au-empty { padding: 3rem 1rem; text-align: center; color: #64748b; display: flex; flex-direction: column; align-items: center; gap: .65rem; }
.au-empty i { font-size: 2.5rem; color: #cbd5e1; }
.au-empty p { margin: 0; font-size: .85rem; }
.au-empty--small { padding: 1.75rem 1rem; }
.au-empty--small i { font-size: 1.75rem; }

/* ───── TIMELINE ───── */
.au-timeline { list-style: none; padding: 0; margin: 0; }
.au-timeline-item { display: flex; gap: 1rem; padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9; align-items: flex-start; }
.au-timeline-item:last-child { border-bottom: none; }
.au-timeline-icon {
    width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; font-size: .85rem; color: #fff;
}
.au-timeline-item--created .au-timeline-icon { background: #10b981; }
.au-timeline-item--updated .au-timeline-icon { background: #3b7ddb; }
.au-timeline-item--deleted .au-timeline-icon { background: #dc2626; }
.au-timeline-item--restored .au-timeline-icon { background: #f59e0b; }
.au-timeline-item--retrieved .au-timeline-icon { background: #94a3b8; }
.au-timeline-content { flex: 1; min-width: 0; }
.au-timeline-meta { font-size: .85rem; color: #475569; display: flex; align-items: center; gap: .35rem; flex-wrap: wrap; }
.au-timeline-meta strong { color: #0f172a; }
.au-timeline-actions { margin-top: .25rem; }

/* ───── RESPONSIVE ───── */
@media (max-width: 992px) {
    .au-hero { padding: 1.5rem 1.5rem 1rem; }
    .au-hero-info h1 { font-size: 1.15rem; }
    .au-show-grid { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .au-meta-list > div { grid-template-columns: 1fr; gap: .25rem; }
    .au-diff-table thead { display: none; }
    .au-diff-table tbody, .au-diff-table tr, .au-diff-table td { display: block; width: 100%; }
    .au-diff-table tbody tr { padding: .85rem; border-bottom: 1px solid #f1f5f9; }
    .au-diff-table tbody td { padding: .25rem 0; border: none; }
}
@media (max-width: 576px) {
    .au-hero-actions { width: 100%; }
    .au-hero-actions .au-btn { flex: 1; justify-content: center; }
}
</style>
@endpush
