<?php

namespace App\Services;

use App\Services\Exporters\PDFExporter;
use App\Services\Exporters\ExcelExporter;
use App\Services\Exporters\CSVExporter;
use InvalidArgumentException;

class ExportFactory
{
    /**
     * Créer un exporter selon le format demandé
     */
    public function createExporter($format, $data, $options = [])
    {
        switch(strtolower($format)) {
            case 'pdf':
                return new PDFExporter($data, $options);

            case 'excel':
            case 'xlsx':
                return new ExcelExporter($data, $options);

            case 'csv':
                return new CSVExporter($data, $options);

            default:
                throw new InvalidArgumentException("Format d'export non supporté: {$format}");
        }
    }

    /**
     * Obtenir la liste des formats supportés
     */
    public static function getSupportedFormats()
    {
        return [
            'pdf' => [
                'name' => 'PDF',
                'description' => 'Document PDF avec mise en forme',
                'icon' => 'fas fa-file-pdf',
                'mime' => 'application/pdf',
                'extension' => 'pdf'
            ],
            'excel' => [
                'name' => 'Excel',
                'description' => 'Fichier Excel avec données structurées',
                'icon' => 'fas fa-file-excel',
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'extension' => 'xlsx'
            ],
            'csv' => [
                'name' => 'CSV',
                'description' => 'Données séparées par virgules',
                'icon' => 'fas fa-file-csv',
                'mime' => 'text/csv',
                'extension' => 'csv'
            ]
        ];
    }

    /**
     * Valider si le format est supporté
     */
    public static function isFormatSupported($format)
    {
        return array_key_exists(strtolower($format), self::getSupportedFormats());
    }

    /**
     * Obtenir les options par défaut pour un format
     */
    public static function getDefaultOptions($format)
    {
        $defaults = [
            'pdf' => [
                'orientation' => 'portrait',
                'paper' => 'a4',
                'margin' => ['top' => 20, 'right' => 15, 'bottom' => 20, 'left' => 15],
                'template' => 'default',
                'include_charts' => true,
                'watermark' => false
            ],
            'excel' => [
                'include_totals' => true,
                'format_numbers' => true,
                'auto_width' => true,
                'freeze_header' => true,
                'include_charts' => false
            ],
            'csv' => [
                'delimiter' => ',',
                'enclosure' => '"',
                'escape' => '\\',
                'encoding' => 'UTF-8',
                'include_header' => true
            ]
        ];

        return $defaults[strtolower($format)] ?? [];
    }
}
