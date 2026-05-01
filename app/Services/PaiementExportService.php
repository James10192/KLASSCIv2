<?php

namespace App\Services;

use App\Exports\PaiementDetailleExport;
use App\Models\ESBTPPaiement;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * PaiementExportService — Lot 15.
 *
 * Génère les états financiers détaillés au format PDF ou Excel/CSV
 * avec des filtres avancés (étudiant, classe, filière, niveau, période, mode).
 *
 * Respecte la permission ownership : un utilisateur avec uniquement
 * paiements.view_own ne voit que les paiements qu'il a créés (created_by).
 */
class PaiementExportService
{
    /**
     * Nombre maximum de lignes pour un export PDF.
     * Au-delà, l'utilisateur doit choisir Excel.
     */
    public const PDF_MAX_ROWS = 500;

    /**
     * Plafond Excel (sécurité contre les exports massifs).
     */
    public const EXCEL_MAX_ROWS = 50000;

    /**
     * Construit la query Builder filtrée selon les critères et les permissions.
     *
     * @param array $filters Filtres normalisés (etudiant_id, classe_ids[], filiere_id, niveau_id, date_debut, date_fin, modes[])
     * @param User|null $user Utilisateur courant (pour le filter ownership)
     */
    public function buildQuery(array $filters, ?User $user = null): Builder
    {
        $query = ESBTPPaiement::query()
            ->with([
                'etudiant:id,matricule,nom,prenoms',
                'inscription:id,etudiant_id,classe_id,filiere_id,niveau_id',
                'inscription.classe:id,name,filiere_id,niveau_etude_id',
                'inscription.filiere:id,name',
                'inscription.niveau:id,name',
                'createdBy:id,name',
            ]);

        // Ownership : si l'user n'a PAS paiements.view mais a paiements.view_own
        // → restreindre aux paiements qu'il a créés (created_by)
        if ($user instanceof User
            && ! $user->can('paiements.view')
            && $user->can('paiements.view_own')) {
            $query->where('created_by', $user->id);
        }

        // Étudiant spécifique
        if (! empty($filters['etudiant_id'])) {
            $query->where('etudiant_id', $filters['etudiant_id']);
        }

        // Classes (multi-select via inscription)
        if (! empty($filters['classe_ids']) && is_array($filters['classe_ids'])) {
            $classeIds = array_values(array_filter($filters['classe_ids']));
            if (! empty($classeIds)) {
                $query->whereHas('inscription', function ($q) use ($classeIds) {
                    $q->whereIn('classe_id', $classeIds);
                });
            }
        }

        // Filière (via inscription)
        if (! empty($filters['filiere_id'])) {
            $query->whereHas('inscription', function ($q) use ($filters) {
                $q->where('filiere_id', $filters['filiere_id']);
            });
        }

        // Niveau d'études (via inscription)
        if (! empty($filters['niveau_id'])) {
            $query->whereHas('inscription', function ($q) use ($filters) {
                $q->where('niveau_id', $filters['niveau_id']);
            });
        }

        // Période — date début
        if (! empty($filters['date_debut'])) {
            $query->whereDate('date_paiement', '>=', $filters['date_debut']);
        }

        // Période — date fin
        if (! empty($filters['date_fin'])) {
            $query->whereDate('date_paiement', '<=', $filters['date_fin']);
        }

        // Modes de paiement (multi)
        if (! empty($filters['modes']) && is_array($filters['modes'])) {
            $modes = array_values(array_filter($filters['modes']));
            if (! empty($modes)) {
                $query->whereIn('mode_paiement', $modes);
            }
        }

        // Tri par défaut : du plus récent au plus ancien
        $query->orderByDesc('date_paiement')->orderByDesc('id');

        return $query;
    }

    /**
     * Compte le nombre de lignes correspondant aux filtres.
     */
    public function count(array $filters, ?User $user = null): int
    {
        // Pour le count on n'a pas besoin du eager loading
        $countQuery = ESBTPPaiement::query();

        if ($user instanceof User
            && ! $user->can('paiements.view')
            && $user->can('paiements.view_own')) {
            $countQuery->where('created_by', $user->id);
        }

        if (! empty($filters['etudiant_id'])) {
            $countQuery->where('etudiant_id', $filters['etudiant_id']);
        }

        if (! empty($filters['classe_ids']) && is_array($filters['classe_ids'])) {
            $classeIds = array_values(array_filter($filters['classe_ids']));
            if (! empty($classeIds)) {
                $countQuery->whereHas('inscription', function ($q) use ($classeIds) {
                    $q->whereIn('classe_id', $classeIds);
                });
            }
        }

        if (! empty($filters['filiere_id'])) {
            $countQuery->whereHas('inscription', function ($q) use ($filters) {
                $q->where('filiere_id', $filters['filiere_id']);
            });
        }

        if (! empty($filters['niveau_id'])) {
            $countQuery->whereHas('inscription', function ($q) use ($filters) {
                $q->where('niveau_id', $filters['niveau_id']);
            });
        }

        if (! empty($filters['date_debut'])) {
            $countQuery->whereDate('date_paiement', '>=', $filters['date_debut']);
        }

        if (! empty($filters['date_fin'])) {
            $countQuery->whereDate('date_paiement', '<=', $filters['date_fin']);
        }

        if (! empty($filters['modes']) && is_array($filters['modes'])) {
            $modes = array_values(array_filter($filters['modes']));
            if (! empty($modes)) {
                $countQuery->whereIn('mode_paiement', $modes);
            }
        }

        return $countQuery->count();
    }

    /**
     * Total des montants validés correspondant aux filtres.
     * Utilisé dans le footer du document.
     */
    public function totalMontant(array $filters, ?User $user = null): float
    {
        $query = $this->buildQuery($filters, $user);
        $query->getQuery()->orders = null;
        // On clone pour ne pas affecter l'instance
        return (float) (clone $query)->sum('montant');
    }

    /**
     * Génère un export PDF (DomPDF) — paysage si > 8 colonnes (avec colonne Encaissé par).
     */
    public function exportPdf(array $filters, ?User $user = null): Response
    {
        $pdf = $this->buildPdf($filters, $user);
        $filename = 'etat-paiements-detaille_' . now()->format('Y-m-d_His') . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Aperçu PDF inline (Content-Disposition: inline) — ouvre dans un nouvel
     * onglet du navigateur sans télécharger.
     */
    public function previewPdf(array $filters, ?User $user = null): Response
    {
        $pdf = $this->buildPdf($filters, $user);
        $filename = 'apercu-paiements-detaille_' . now()->format('Y-m-d_His') . '.pdf';
        return new Response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    private function buildPdf(array $filters, ?User $user)
    {
        $paiements = $this->buildQuery($filters, $user)->get();
        $count = $paiements->count();
        $totalMontant = (float) $paiements->sum('montant');
        $showCreator = $this->shouldShowCreatorColumn($user);
        $context = $this->buildContext($filters, $user, $showCreator);
        $orientation = $showCreator ? 'landscape' : 'portrait';

        Log::info('PDF détaillé paiements rendu', [
            'user_id' => $user?->id,
            'count' => $count,
            'orientation' => $orientation,
        ]);

        return Pdf::loadView('esbtp.paiements.exports.pdf-detaille', [
            'paiements' => $paiements,
            'count' => $count,
            'totalMontant' => $totalMontant,
            'showCreator' => $showCreator,
            'context' => $context,
            'filtersSummary' => $this->buildFiltersSummary($filters, $user),
            'dateGeneration' => now(),
        ])->setPaper('a4', $orientation);
    }

    /**
     * Génère un export Excel — utilise maatwebsite/excel pour produire un .xlsx natif.
     *
     * Lot 17e : maatwebsite/excel est désormais utilisé en chemin nominal.
     * En cas d'erreur (paquet manquant, exception PhpSpreadsheet…), on retombe
     * automatiquement sur un CSV UTF-8 BOM compatible Excel.
     */
    public function exportExcel(array $filters, ?User $user = null): Response
    {
        $paiements = $this->buildQuery($filters, $user)->get();
        $count = $paiements->count();
        $showCreator = $this->shouldShowCreatorColumn($user);
        $totalMontant = (float) $paiements->sum('montant');
        $filtersSummary = $this->buildFiltersSummary($filters, $user);
        $context = $this->buildContext($filters, $user, $showCreator);

        Log::info('Export Excel détaillé paiements demandé', [
            'user_id' => $user?->id,
            'count' => $count,
        ]);

        // Chemin nominal : .xlsx via maatwebsite/excel
        try {
            $filename = 'etat-paiements-detaille_' . now()->format('Y-m-d_His') . '.xlsx';
            $export = new PaiementDetailleExport(
                $paiements,
                $showCreator,
                $count,
                $totalMontant,
                $filtersSummary,
                $context
            );

            return Excel::download($export, $filename);
        } catch (\Throwable $e) {
            Log::warning('Excel xlsx indisponible — fallback CSV', [
                'error' => $e->getMessage(),
            ]);

            return $this->exportCsvFallback(
                $paiements,
                $showCreator,
                $count,
                $totalMontant,
                $filtersSummary
            );
        }
    }

    /**
     * Fallback CSV UTF-8 BOM (ouvrable Excel) — utilisé si maatwebsite/excel échoue.
     */
    protected function exportCsvFallback(
        $paiements,
        bool $showCreator,
        int $count,
        float $totalMontant,
        array $filtersSummary
    ): Response {
        $filename = 'etat-paiements-detaille_' . now()->format('Y-m-d_His') . '.csv';

        return new StreamedResponse(function () use ($paiements, $showCreator, $count, $totalMontant, $filtersSummary) {
            $out = fopen('php://output', 'w');
            // BOM UTF-8 pour Excel
            fwrite($out, "\xEF\xBB\xBF");

            // Titre + sous-titres
            fputcsv($out, ['Tableau détaillé des paiements'], ';');
            fputcsv($out, ['Généré le', now()->format('d/m/Y H:i')], ';');
            fputcsv($out, ['Nombre de paiements', $count], ';');
            fputcsv($out, ['Total montant (FCFA)', number_format($totalMontant, 0, ',', ' ')], ';');

            foreach ($filtersSummary as $row) {
                fputcsv($out, [$row['label'], $row['value']], ';');
            }
            fputcsv($out, [], ';'); // ligne vide

            // En-têtes
            $headers = ['Date', 'N° Reçu', 'Matricule', 'Nom étudiant', 'Classe', 'Mode', 'Montant (FCFA)', 'Statut'];
            if ($showCreator) {
                $headers[] = 'Encaissé par';
            }
            fputcsv($out, $headers, ';');

            foreach ($paiements as $p) {
                $row = [
                    $p->date_paiement ? $p->date_paiement->format('d/m/Y') : '',
                    $p->numero_recu ?? '',
                    optional($p->etudiant)->matricule ?? '',
                    trim((optional($p->etudiant)->nom ?? '') . ' ' . (optional($p->etudiant)->prenoms ?? '')),
                    optional(optional($p->inscription)->classe)->name ?? '',
                    $p->mode_paiement ?? '',
                    number_format((float) $p->montant, 0, ',', ' '),
                    $this->statusLabel($p->status ?? $p->statut ?? ''),
                ];
                if ($showCreator) {
                    $row[] = optional($p->createdBy)->name ?? '';
                }
                fputcsv($out, $row, ';');
            }

            // Footer
            fputcsv($out, [], ';');
            $totalRow = ['TOTAL', '', '', '', '', '', number_format($totalMontant, 0, ',', ' '), ''];
            if ($showCreator) {
                $totalRow[] = '';
            }
            fputcsv($out, $totalRow, ';');
            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * Vrai si l'user voit TOUS les paiements (paiements.view).
     * Si user voit uniquement view_own → masquer la colonne (c'est lui-même).
     */
    public function shouldShowCreatorColumn(?User $user): bool
    {
        if (! $user instanceof User) {
            return true;
        }
        return $user->can('paiements.view');
    }

    /**
     * Construit le contexte pour le rendu du document
     * (titre, sous-titre "Encaissé par : X" si applicable, etc.).
     */
    public function buildContext(array $filters, ?User $user, bool $showCreator): array
    {
        $context = [
            'title' => 'Tableau détaillé des paiements',
            'subtitle_creator' => null,
        ];

        // Si l'utilisateur n'a que view_own, on ajoute son nom comme sous-titre
        if (! $showCreator && $user instanceof User) {
            $context['subtitle_creator'] = 'Encaissé par : ' . $user->name;
        }

        return $context;
    }

    /**
     * Construit la liste des filtres appliqués pour affichage (PDF + CSV).
     *
     * @return array<int, array{label: string, value: string}>
     */
    public function buildFiltersSummary(array $filters, ?User $user = null): array
    {
        $summary = [];

        if (! empty($filters['date_debut']) || ! empty($filters['date_fin'])) {
            $debut = ! empty($filters['date_debut'])
                ? Carbon::parse($filters['date_debut'])->format('d/m/Y')
                : '—';
            $fin = ! empty($filters['date_fin'])
                ? Carbon::parse($filters['date_fin'])->format('d/m/Y')
                : '—';
            $summary[] = ['label' => 'Période', 'value' => $debut . ' → ' . $fin];
        }

        if (! empty($filters['etudiant_id'])) {
            $etudiant = \App\Models\ESBTPEtudiant::find($filters['etudiant_id']);
            if ($etudiant) {
                $summary[] = [
                    'label' => 'Étudiant',
                    'value' => $etudiant->matricule . ' - ' . $etudiant->nom . ' ' . $etudiant->prenoms,
                ];
            }
        }

        if (! empty($filters['classe_ids']) && is_array($filters['classe_ids'])) {
            $names = \App\Models\ESBTPClasse::whereIn('id', $filters['classe_ids'])
                ->pluck('name')->toArray();
            if (! empty($names)) {
                $summary[] = ['label' => 'Classe(s)', 'value' => implode(', ', $names)];
            }
        }

        if (! empty($filters['filiere_id'])) {
            $name = \App\Models\ESBTPFiliere::where('id', $filters['filiere_id'])->value('name');
            if ($name) {
                $summary[] = ['label' => 'Filière', 'value' => $name];
            }
        }

        if (! empty($filters['niveau_id'])) {
            $name = \App\Models\ESBTPNiveauEtude::where('id', $filters['niveau_id'])->value('name');
            if ($name) {
                $summary[] = ['label' => 'Niveau', 'value' => $name];
            }
        }

        if (! empty($filters['modes']) && is_array($filters['modes'])) {
            $modes = array_values(array_filter($filters['modes']));
            if (! empty($modes)) {
                $summary[] = ['label' => 'Mode(s) de paiement', 'value' => implode(', ', $modes)];
            }
        }

        return $summary;
    }

    /**
     * Label humain pour un statut.
     */
    public function statusLabel(string $status): string
    {
        $normalized = Str::lower(trim($status));
        return match ($normalized) {
            'validé', 'valide' => 'Validé',
            'en_attente', 'en attente' => 'En attente',
            'rejeté', 'rejete' => 'Rejeté',
            'annulé', 'annule' => 'Annulé',
            '' => '—',
            default => ucfirst($status),
        };
    }
}
