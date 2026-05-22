@extends('layouts.app')

@section('title', "Audit comptable")

@section('content')
<div class="container-fluid au-page">

    {{-- ═══════════════════════════════ HERO ═══════════════════════════════ --}}
    <div class="au-hero">
        <div class="au-hero-top">
            <div class="au-hero-left">
                <div class="au-hero-icon"><i class="fas fa-coins"></i></div>
                <div class="au-hero-info">
                    <h1>Audit comptable</h1>
                    <p>Surveillance ciblée des opérations financières (paiements, factures, dépenses)</p>
                </div>
            </div>
            <div class="au-hero-actions">
                <a href="{{ route('esbtp.audit.index') }}" class="au-btn au-btn--glass">
                    <i class="fas fa-arrow-left"></i> Retour au journal
                </a>
            </div>
        </div>

        <div class="au-kpis">
            <div class="au-kpi">
                <div class="au-kpi-icon"><i class="fas fa-money-bill-wave"></i></div>
                <div>
                    <div class="au-kpi-value">{{ number_format($kpis['paiements_modifies']) }}</div>
                    <div class="au-kpi-label">Paiements modifiés (30j)</div>
                </div>
            </div>
            <div class="au-kpi">
                <div class="au-kpi-icon"><i class="fas fa-file-invoice"></i></div>
                <div>
                    <div class="au-kpi-value">{{ number_format($kpis['factures_modifiees']) }}</div>
                    <div class="au-kpi-label">Factures modifiées (30j)</div>
                </div>
            </div>
            <div class="au-kpi au-kpi--alert">
                <div class="au-kpi-icon"><i class="fas fa-times-circle"></i></div>
                <div>
                    <div class="au-kpi-value">{{ number_format($kpis['annulations_semaine']) }}</div>
                    <div class="au-kpi-label">Annulations cette semaine</div>
                </div>
            </div>
            <div class="au-kpi">
                <div class="au-kpi-icon"><i class="fas fa-check-circle"></i></div>
                <div>
                    <div class="au-kpi-value">{{ number_format($kpis['validations_semaine']) }}</div>
                    <div class="au-kpi-label">Validations cette semaine</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════ FILTRES ═══════════════════════════════ --}}
    <form method="GET" action="{{ route('esbtp.audit.comptabilite') }}" class="au-filters">
        <div class="au-filters-row">
            <x-au-select
                class="au-filter-grow"
                name="model_type"
                :value="request('model_type')"
                icon="fa-filter"
                placeholder="Tous les types financiers"
                :options="$financialModelsLabels" />
            <x-au-select
                name="event"
                :value="request('event')"
                placeholder="Tous événements"
                :options="[
                    'created' => 'Création',
                    'updated' => 'Modification',
                    'deleted' => 'Suppression',
                ]" />
            <div class="au-filter-field">
                <input type="number" name="montant_min" value="{{ request('montant_min') }}" placeholder="Montant min (FCFA)" min="0">
            </div>
            <div class="au-filter-field">
                <input type="date" name="date_from" value="{{ request('date_from') }}" title="Date début">
            </div>
            <div class="au-filter-field">
                <input type="date" name="date_to" value="{{ request('date_to') }}" title="Date fin">
            </div>
            <button type="submit" class="au-btn au-btn--primary">
                <i class="fas fa-search"></i> Filtrer
            </button>
            <a href="{{ route('esbtp.audit.comptabilite') }}" class="au-filter-reset" title="Réinitialiser">
                <i class="fas fa-undo"></i>
            </a>
        </div>
    </form>

    {{-- ═══════════════════════════════ TABLEAU ═══════════════════════════════ --}}
    <div class="au-card">
        <div class="au-card-header">
            <div class="au-card-title">
                <i class="fas fa-list-ul"></i> Opérations financières auditées
                <span class="au-badge-count">{{ $audits->total() }} résultats</span>
            </div>
        </div>

        <div class="au-table-wrap">
            @if($audits->isEmpty())
                <div class="au-empty">
                    <i class="fas fa-search"></i>
                    <h3>Aucune opération trouvée</h3>
                    <p>Essayez de modifier vos critères de recherche.</p>
                </div>
            @else
                <table class="au-table au-table--expandable" x-data="{ openIds: [] }">
                    <thead>
                        <tr>
                            <th style="width:42px"></th>
                            <th>Date / Heure</th>
                            <th>Utilisateur</th>
                            <th>Action</th>
                            <th>Type</th>
                            <th>ID</th>
                            <th>Montant (avant → après)</th>
                            <th>Liens</th>
                            <th class="au-th-actions">Détails</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($audits as $a)
                            @php
                                $eventLabels = ['created' => 'Création', 'updated' => 'Modification', 'deleted' => 'Suppression', 'restored' => 'Restauration', 'retrieved' => 'Consultation'];
                                $oldValues = is_array($a->old_values) ? $a->old_values : (json_decode($a->old_values, true) ?? []);
                                $newValues = is_array($a->new_values) ? $a->new_values : (json_decode($a->new_values, true) ?? []);
                                $oldMontant = $oldValues['montant'] ?? null;
                                $newMontant = $newValues['montant'] ?? null;
                                $modelLabel = $financialModelsLabels[$a->auditable_type] ?? class_basename($a->auditable_type);
                                $rowLinks = $entityLinksMap[$a->id] ?? [];
                                $rowLinksCount = count($rowLinks);
                            @endphp
                            <tr class="au-row {{ $rowLinksCount === 0 ? 'au-row--inert' : '' }}"
                                :class="openIds.includes({{ $a->id }}) ? 'au-row--open' : ''"
                                @if($rowLinksCount > 0)
                                @click="openIds.includes({{ $a->id }}) ? openIds = openIds.filter(i => i !== {{ $a->id }}) : openIds.push({{ $a->id }})"
                                @endif>
                                <td class="au-cell-toggle">
                                    <button type="button" class="au-toggle"
                                            :class="openIds.includes({{ $a->id }}) ? 'au-toggle--open' : ''"
                                            @click.stop="openIds.includes({{ $a->id }}) ? openIds = openIds.filter(i => i !== {{ $a->id }}) : openIds.push({{ $a->id }})"
                                            :title="openIds.includes({{ $a->id }}) ? 'Replier' : 'Voir les liens'"
                                            @disabled($rowLinksCount === 0)>
                                        <i class="fas fa-chevron-right au-toggle-caret"></i>
                                    </button>
                                </td>
                                <td>
                                    <div class="au-cell-date">
                                        <i class="far fa-clock"></i>
                                        {{ $a->created_at->format('d/m/Y H:i:s') }}
                                    </div>
                                </td>
                                <td>
                                    <div class="au-cell-user">
                                        <span class="au-avatar">{{ mb_substr($a->user?->name ?? 'S', 0, 1, 'UTF-8') }}</span>
                                        <span>{{ $a->user?->name ?? 'Système' }}</span>
                                    </div>
                                </td>
                                <td><span class="au-chip au-chip--{{ $a->event }}">{{ $eventLabels[$a->event] ?? $a->event }}</span></td>
                                <td><span class="au-chip au-chip--neutral">{{ $modelLabel }}</span></td>
                                <td><code class="au-code">#{{ $a->auditable_id }}</code></td>
                                <td>
                                    @if($oldMontant !== null || $newMontant !== null)
                                        <div class="au-amount-diff">
                                            @if($oldMontant !== null)
                                                <span class="au-amount-old">{{ number_format($oldMontant, 0, ',', ' ') }} F</span>
                                            @endif
                                            @if($oldMontant !== null && $newMontant !== null)
                                                <i class="fas fa-arrow-right au-amount-arrow"></i>
                                            @endif
                                            @if($newMontant !== null)
                                                <span class="au-amount-new">{{ number_format($newMontant, 0, ',', ' ') }} F</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="au-meta-empty">—</span>
                                    @endif
                                </td>
                                <td @click.stop>
                                    @if($rowLinksCount > 0)
                                        <button type="button" class="au-links-pill"
                                                @click="openIds.includes({{ $a->id }}) ? openIds = openIds.filter(i => i !== {{ $a->id }}) : openIds.push({{ $a->id }})"
                                                title="Voir les {{ $rowLinksCount }} entité{{ $rowLinksCount > 1 ? 's' : '' }} liée{{ $rowLinksCount > 1 ? 's' : '' }}">
                                            <i class="fas fa-project-diagram"></i>
                                            {{ $rowLinksCount }} lien{{ $rowLinksCount > 1 ? 's' : '' }}
                                        </button>
                                    @else
                                        <span class="au-meta-empty">—</span>
                                    @endif
                                </td>
                                <td class="au-td-actions" @click.stop>
                                    <a href="{{ route('esbtp.audit.show', $a->id) }}" class="au-icon-btn au-icon-btn--primary" title="Voir détail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @if($rowLinksCount > 0)
                                <tr class="au-row-expand" x-show="openIds.includes({{ $a->id }})" x-cloak x-transition.opacity>
                                    <td></td>
                                    <td colspan="8" class="au-row-expand-cell">
                                        <x-audit-links :links="$rowLinks" :compact="true" />
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        @if($audits->hasPages())
            <div class="au-pagination-laravel">
                {{ $audits->links() }}
            </div>
        @endif
    </div>

</div>
@endsection

@push('styles')
<style>
@include('esbtp.audit._styles')
</style>
@endpush
