<x-pdf-document
    title="Journal d'audit"
    subtitle="Traçabilité des actions système"
    orientation="landscape">

    <style>
        .audit-table { width:100%; border-collapse: collapse; font-size: 8.5pt; }
        .audit-table th { background: #0453cb; color:#fff; padding: 6px 4px; text-align:left; font-weight:700; }
        .audit-table td { padding: 5px 4px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .audit-table tr:nth-child(even) td { background:#f8fafc; }
        .chip { display:inline-block; padding: 2px 7px; border-radius: 9px; font-size: 7pt; font-weight:600; }
        .chip-created { background:#dcfce7; color:#15803d; }
        .chip-updated { background:#dbeafe; color:#1d4ed8; }
        .chip-deleted { background:#fee2e2; color:#991b1b; }
        .chip-restored { background:#fef3c7; color:#92400e; }
        .chip-retrieved { background:#f3f4f6; color:#4b5563; }
        .ip { font-family: monospace; font-size: 7.5pt; color:#64748b; }
        .meta-cell { color:#64748b; font-size:7.5pt; }
    </style>

    @php
        $eventLabels = [
            'created' => 'Création',
            'updated' => 'Modification',
            'deleted' => 'Suppression',
            'restored' => 'Restauration',
            'retrieved' => 'Consultation',
        ];
    @endphp

    <table class="audit-table">
        <thead>
            <tr>
                <th style="width: 14%;">Date / heure</th>
                <th style="width: 18%;">Utilisateur</th>
                <th style="width: 11%;">Action</th>
                <th style="width: 22%;">Entité</th>
                <th style="width: 13%;">IP</th>
                <th>Changements</th>
            </tr>
        </thead>
        <tbody>
            @forelse($audits as $audit)
                @php
                    $event = $audit->event;
                    $oldVals = $audit->old_values ?? [];
                    $newVals = $audit->new_values ?? [];
                    $fields = array_unique(array_merge(array_keys((array) $oldVals), array_keys((array) $newVals)));
                @endphp
                <tr>
                    <td>{{ $audit->created_at->format('d/m/Y H:i:s') }}</td>
                    <td>
                        {{ $audit->user?->name ?? 'Système' }}<br>
                        <span class="meta-cell">{{ $audit->user?->email ?? '' }}</span>
                    </td>
                    <td><span class="chip chip-{{ $event }}">{{ $eventLabels[$event] ?? mb_strtoupper($event, 'UTF-8') }}</span></td>
                    <td>
                        {{ class_basename($audit->auditable_type) }} #{{ $audit->auditable_id }}
                    </td>
                    <td class="ip">{{ $audit->ip_address ?? '—' }}</td>
                    <td>
                        @if(empty($fields))
                            <span class="meta-cell">—</span>
                        @else
                            {{ count($fields) }} champ(s) :
                            <span class="meta-cell">{{ \Illuminate\Support\Str::limit(implode(', ', $fields), 80) }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center; padding:20px; color:#64748b;">
                        Aucun événement sur la période sélectionnée.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</x-pdf-document>
