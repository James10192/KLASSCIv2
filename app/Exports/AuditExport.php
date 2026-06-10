<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * Export Excel pour les logs d'audit système.
 * Suit le pattern KLASSCI : headings, mapping, styles, formats.
 */
class AuditExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithColumnFormatting
{
    public function __construct(private $audits)
    {
    }

    public function collection()
    {
        return collect($this->audits);
    }

    public function headings(): array
    {
        return [
            'ID',
            'Date / Heure',
            'Événement',
            'Modèle',
            'ID Entité',
            'Utilisateur',
            'Adresse IP',
            'Navigateur',
            'URL',
            'Tags',
            'Niveau de risque',
        ];
    }

    public function map($audit): array
    {
        $userAgent = $audit->user_agent ?? '';
        $browser = 'Autre';
        if (str_contains($userAgent, 'Chrome')) {
            $browser = 'Chrome';
        } elseif (str_contains($userAgent, 'Firefox')) {
            $browser = 'Firefox';
        } elseif (str_contains($userAgent, 'Safari')) {
            $browser = 'Safari';
        } elseif (str_contains($userAgent, 'Edge')) {
            $browser = 'Edge';
        }

        $eventLabels = [
            'created' => 'Création',
            'updated' => 'Modification',
            'deleted' => 'Suppression',
            'restored' => 'Restauration',
            'retrieved' => 'Consultation',
        ];

        return [
            $audit->id,
            $audit->created_at?->format('d/m/Y H:i:s'),
            $eventLabels[$audit->event] ?? $audit->event,
            \App\Helpers\EntityLabelHelper::for($audit->auditable_type),
            $audit->auditable_id,
            $audit->user?->name ?? 'Système',
            $audit->ip_address,
            $browser,
            $audit->url,
            $audit->tags,
            $this->riskLabel($audit),
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_NUMBER,
            'E' => '@', // ID entité texte
            'G' => '@', // IP texte
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0453CB'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    private function riskLabel($audit): string
    {
        $factors = 0;
        if (in_array($audit->event, ['deleted', 'restored'], true)) {
            $factors += 3;
        }
        if (in_array($audit->auditable_type, [
            'App\\Models\\ESBTPPaiement',
            'App\\Models\\ESBTPDepense',
            'App\\Models\\ESBTPFacture',
        ], true)) {
            $factors += 2;
        }
        if ($audit->created_at) {
            $hour = $audit->created_at->hour;
            if ($hour < 8 || $hour > 18) {
                $factors += 1;
            }
        }

        return match (true) {
            $factors >= 4 => 'Critique',
            $factors >= 2 => 'Élevé',
            $factors >= 1 => 'Moyen',
            default => 'Faible',
        };
    }
}
