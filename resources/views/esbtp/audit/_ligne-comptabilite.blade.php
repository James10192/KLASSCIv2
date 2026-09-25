{{-- Une operation comptable auditee, et sa ligne de liens repliee : rendue par la page
     et par la suite chargee au defilement. Attend $a, $financialModelsLabels, $entityLinksMap. --}}
@php
    $eventLabels = ['created' => 'Création', 'updated' => 'Modification', 'deleted' => 'Suppression', 'restored' => 'Restauration', 'retrieved' => 'Consultation'];
    $oldValues = is_array($a->old_values) ? $a->old_values : (json_decode($a->old_values, true) ?? []);
    $newValues = is_array($a->new_values) ? $a->new_values : (json_decode($a->new_values, true) ?? []);
    $oldMontant = $oldValues['montant'] ?? null;
    $newMontant = $newValues['montant'] ?? null;
    $modelLabel = $financialModelsLabels[$a->auditable_type] ?? \App\Helpers\EntityLabelHelper::for($a->auditable_type);
    $rowLinks = $entityLinksMap[$a->id] ?? [];
    $rowLinksCount = count($rowLinks);
@endphp
<tr data-li-cle="{{ $a->id }}" class="au-row {{ $rowLinksCount === 0 ? 'au-row--inert' : '' }}"
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
    <tr data-li-cle="{{ $a->id }}-liens" class="au-row-expand" x-show="openIds.includes({{ $a->id }})" x-cloak x-transition.opacity>
        <td></td>
        <td colspan="8" class="au-row-expand-cell">
            <x-audit-links :links="$rowLinks" :compact="true" />
        </td>
    </tr>
@endif
