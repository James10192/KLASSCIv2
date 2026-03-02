<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Liste des Paiements</title>
    <style>
        /* ── Reset (skill §3) ── */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, Arial, sans-serif; /* §7: accents fr */
            font-size: {{ $settings['font_size'] ?? 11 }}px;
            color: {{ $settings['text_color'] ?? '#1f2937' }};
            line-height: 1.45;
            background: #ffffff;
        }

        @page {
            margin: {{ $settings['margin_top'] ?? 15 }}mm
                    {{ $settings['margin_right'] ?? 10 }}mm
                    {{ $settings['margin_bottom'] ?? 15 }}mm
                    {{ $settings['margin_left'] ?? 10 }}mm;
        }

        /* ── Utilitaires ── */
        .text-right  { text-align: right; }
        .text-center { text-align: center; }
        .bold        { font-weight: bold; }
        .text-muted  { color: #6b7280; font-size: 10px; }
        .mt-16       { margin-top: 16px; }
        .mb-16       { margin-bottom: 16px; }
        .no-break    { page-break-inside: avoid; }

        /* ── Table paiements ── */
        .paiements-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
            font-size: 10px;
        }

        /* §6: background sur thead td, PAS sur tr */
        .paiements-table thead td {
            background-color: {{ $settings['header_bg_color'] ?? '#0453cb' }};
            color: {{ $settings['header_text_color'] ?? '#ffffff' }};
            padding: 8px 10px;
            font-weight: bold;
            font-size: 10px;
            border-bottom: 2px solid #0343ab;
        }

        /* §6: background alternant sur tbody td, PAS tr */
        .paiements-table tbody .row-odd td  { background-color: #ffffff; }
        .paiements-table tbody .row-even td { background-color: #f3f4f6; }

        .paiements-table tbody td {
            padding: 7px 10px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }

        .paiements-table tfoot td {
            background-color: #f3f4f6;
            padding: 8px 10px;
            font-weight: bold;
            border-top: 2px solid #d1d5db;
            font-size: 10px;
        }

        /* ── Badges statut (§14) ── */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: bold;
            color: #ffffff;
        }
        .badge-valide    { background-color: #10b981; border: 1px solid #059669; }
        .badge-attente   { background-color: #f59e0b; border: 1px solid #d97706; color: #1f2937; }
        .badge-rejete    { background-color: #ef4444; border: 1px solid #dc2626; }
        .badge-default   { background-color: #6b7280; border: 1px solid #4b5563; }

        /* ── Footer ── */
        .doc-footer {
            margin-top: 20px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 9px;
            color: #9ca3af;
            page-break-inside: avoid;
        }
    </style>
</head>
<body>

    {{-- ── HEADER (skill §14 — document header block) ── --}}
    <table width="100%" border="0" cellspacing="0" cellpadding="0"
           style="border-bottom: 2.5px solid {{ $settings['primary_color'] ?? '#0453cb' }};
                  padding-bottom: 10px;
                  margin-bottom: 16px;">
        <tr>
            {{-- Logo (skill §8 — base64) --}}
            <td width="20%" style="vertical-align: middle;">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}"
                         style="max-height: 55px; max-width: 110px;"
                         alt="Logo">
                @endif
            </td>

            {{-- Titre central --}}
            <td width="60%" style="text-align: center; vertical-align: middle;">
                @if(!empty($schoolInfo['republic']))
                    <div style="font-size: 10px; color: #6b7280; margin-bottom: 2px;">
                        {{ $schoolInfo['republic'] ?? 'RÉPUBLIQUE DE CÔTE D\'IVOIRE' }}
                    </div>
                @endif
                <div style="font-size: 14px; font-weight: bold; color: {{ $settings['primary_color'] ?? '#0453cb' }};">
                    {{ $schoolInfo['name'] ?? config('app.name') }}
                </div>
                <div style="font-size: 17px; font-weight: bold; color: #1f2937; margin-top: 4px;">
                    LISTE DES PAIEMENTS
                </div>
                @if(!empty($periode))
                    <div style="font-size: 10px; color: #6b7280; margin-top: 2px;">
                        {{ $periode }}
                    </div>
                @endif
            </td>

            {{-- Date génération --}}
            <td width="20%" style="text-align: right; vertical-align: top; font-size: 10px; color: #6b7280;">
                Généré le<br>
                <strong>{{ now()->format('d/m/Y') }}</strong><br>
                <span style="font-size: 9px;">{{ now()->format('H:i') }}</span>
            </td>
        </tr>
    </table>

    {{-- ── STATS (skill §14 — stat card row) ── --}}
    @php
        $totalMontant  = $paiements->sum('montant') ?? 0;
        $totalCount    = $paiements->count();
        $countValides  = $paiements->where('status', 'validé')->count();
        $countAttente  = $paiements->where('status', 'en_attente')->count();
    @endphp
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 16px;">
        <tr>
            <td width="24%" style="padding-right: 6px;">
                <table width="100%" style="border: 1px solid #e5e7eb; border-radius: 6px;">
                    <tr><td style="background-color: #f9fafb; padding: 6px 10px; font-size: 9px; color: #6b7280; text-transform: uppercase; border-radius: 6px 6px 0 0;">Total paiements</td></tr>
                    <tr><td style="padding: 6px 10px 8px; font-size: 16px; font-weight: bold; color: {{ $settings['primary_color'] ?? '#0453cb' }};">{{ $totalCount }}</td></tr>
                </table>
            </td>
            <td width="2%"></td>
            <td width="24%" style="padding: 0 6px;">
                <table width="100%" style="border: 1px solid #e5e7eb; border-radius: 6px;">
                    <tr><td style="background-color: #f9fafb; padding: 6px 10px; font-size: 9px; color: #6b7280; text-transform: uppercase; border-radius: 6px 6px 0 0;">Montant total</td></tr>
                    <tr><td style="padding: 6px 10px 8px; font-size: 13px; font-weight: bold; color: #1f2937;">{{ number_format($totalMontant, 0, ',', ' ') }} FCFA</td></tr>
                </table>
            </td>
            <td width="2%"></td>
            <td width="24%" style="padding: 0 6px;">
                <table width="100%" style="border: 1px solid #d1fae5; border-radius: 6px;">
                    <tr><td style="background-color: #ecfdf5; padding: 6px 10px; font-size: 9px; color: #065f46; text-transform: uppercase; border-radius: 6px 6px 0 0;">Validés</td></tr>
                    <tr><td style="padding: 6px 10px 8px; font-size: 16px; font-weight: bold; color: #10b981;">{{ $countValides }}</td></tr>
                </table>
            </td>
            <td width="2%"></td>
            <td width="24%" style="padding-left: 6px;">
                <table width="100%" style="border: 1px solid #fef3c7; border-radius: 6px;">
                    <tr><td style="background-color: #fffbeb; padding: 6px 10px; font-size: 9px; color: #92400e; text-transform: uppercase; border-radius: 6px 6px 0 0;">En attente</td></tr>
                    <tr><td style="padding: 6px 10px 8px; font-size: 16px; font-weight: bold; color: #f59e0b;">{{ $countAttente }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ── TABLEAU PAIEMENTS ── --}}
    <div class="no-break">
        <table class="paiements-table">
            <thead>
                <tr>
                    <td width="4%"  class="text-center">N°</td>
                    <td width="26%">Étudiant</td>
                    <td width="14%" class="text-right">Montant</td>
                    <td width="12%" class="text-center">Mode</td>
                    <td width="10%" class="text-center">Date</td>
                    <td width="10%" class="text-center">Statut</td>
                    <td width="24%">Catégorie</td>
                </tr>
            </thead>
            <tbody>
                @forelse($paiements as $i => $paiement)
                    @php
                        $rowClass = $i % 2 === 0 ? 'row-even' : 'row-odd';

                        $nomEtudiant = trim(
                            ($paiement->etudiant->prenoms ?? '') . ' ' .
                            ($paiement->etudiant->nom ?? '')
                        ) ?: 'N/A';
                        $matricule    = $paiement->etudiant->matricule ?? null;
                        $montant      = number_format($paiement->montant ?? 0, 0, ',', ' ') . ' FCFA';
                        $mode         = $paiement->mode_paiement ?? 'N/A';
                        $date         = $paiement->date_paiement
                                        ? \Carbon\Carbon::parse($paiement->date_paiement)->format('d/m/Y')
                                        : 'N/A';
                        $categorie    = $paiement->fraisCategory->name ?? ($paiement->motif ?? 'N/A');
                        $status       = $paiement->status ?? $paiement->statut ?? '';

                        // Couleur badge (skill §14 — badge pattern)
                        $badgeClass = match(strtolower($status)) {
                            'validé', 'valide'                      => 'badge-valide',
                            'en_attente', 'en attente', 'pending'   => 'badge-attente',
                            'rejeté', 'rejete'                      => 'badge-rejete',
                            default                                 => 'badge-default',
                        };
                        $badgeLabel = match(strtolower($status)) {
                            'validé', 'valide'                      => 'Validé',
                            'en_attente', 'en attente', 'pending'   => 'En attente',
                            'rejeté', 'rejete'                      => 'Rejeté',
                            default                                 => ucfirst($status) ?: 'Inconnu',
                        };
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td class="text-center" style="color: #9ca3af;">{{ $i + 1 }}</td>
                        <td>
                            <span style="font-weight: bold;">{{ $nomEtudiant }}</span>
                            @if($matricule)
                                <br><span class="text-muted">{{ $matricule }}</span>
                            @endif
                        </td>
                        <td class="text-right bold" style="color: {{ $settings['primary_color'] ?? '#0453cb' }};">
                            {{ $montant }}
                        </td>
                        <td class="text-center">{{ $mode }}</td>
                        <td class="text-center">{{ $date }}</td>
                        <td class="text-center">
                            <span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                        </td>
                        <td>{{ $categorie }}</td>
                    </tr>
                @empty
                    <tr class="row-odd">
                        <td colspan="7" class="text-center"
                            style="padding: 20px; color: #6b7280; font-style: italic;">
                            Aucun paiement trouvé.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if($paiements->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="2" class="bold">Total ({{ $paiements->count() }} paiements)</td>
                    <td class="text-right bold" style="color: {{ $settings['primary_color'] ?? '#0453cb' }};">
                        {{ number_format($paiements->sum('montant'), 0, ',', ' ') }} FCFA
                    </td>
                    <td colspan="4"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>

    {{-- ── FOOTER (skill §14) ── --}}
    <div class="doc-footer">
        {{ $settings['footer_text'] ?? ($schoolInfo['name'] ?? config('app.name')) }}
        — Généré le {{ now()->format('d/m/Y à H:i') }}
        @if(!empty($settings['watermark']))
            — {{ $settings['watermark'] }}
        @endif
    </div>

</body>
</html>
