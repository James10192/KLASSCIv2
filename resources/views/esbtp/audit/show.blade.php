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

    // Libellé d'entité sans préfixe « ESBTP » (source unique : EntityLabelHelper)
    $modelLabel = \App\Helpers\EntityLabelHelper::for($audit->auditable_type);

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
                @elseif(!$entityExists)
                    <div class="au-warning mt-3">
                        <i class="fas fa-exclamation-triangle"></i>
                        Cette entité a été <strong>supprimée définitivement</strong> et n'est plus consultable.
                    </div>
                @else
                    <div class="au-info mt-3">
                        <i class="fas fa-info-circle"></i>
                        Pas de page de détail dédiée pour ce type. Voir le panneau <strong>« Liens vers les entités liées »</strong> ci-dessous pour le contexte.
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════ LIENS ENTITÉS LIÉES ═══════════════════════════════ --}}
    @if(!empty($entityLinks) && count($entityLinks) > 0)
        <div class="au-card">
            <div class="au-card-header">
                <div class="au-card-title">
                    <i class="fas fa-project-diagram"></i> Liens vers les entités liées
                    <span class="au-meta-sub">Tracer le contexte métier de cet événement</span>
                </div>
            </div>
            <div class="au-card-body">
                <x-audit-links :links="$entityLinks" title="Entités liées à cet audit" />
            </div>
        </div>
    @endif

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
@include('esbtp.audit._styles')

/* Page-specific styles for show */
.au-show-badges { display: flex; gap: .5rem; margin-top: 1.5rem; flex-wrap: wrap; }
.au-show-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; }
.au-card { margin-bottom: 1rem; }
.au-chip { margin-right: .25rem; }
@media (max-width: 992px) {
    .au-show-grid { grid-template-columns: 1fr; }
}
</style>
@endpush
