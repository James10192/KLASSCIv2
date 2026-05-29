<?php

namespace App\Console\Commands;

use App\Models\ESBTPEtudiant;
use App\Services\ESBTP\BulletinConsistencyService;
use Illuminate\Console\Command;

class ResultatsBulletinConsistencyDiagnoseCommand extends Command
{
    protected $signature = 'resultats:bulletin-consistency-diagnose
        {etudiant_id : ID de l\'étudiant}
        {classe_id : ID de la classe}
        {annee_universitaire_id : ID de l\'année universitaire}
        {periode : Période BTS (1, 2, semestre1, semestre2)}
        {--tenant=default : Identifiant logique du tenant pour le diagnostic}
        {--json : Sortie JSON uniquement}';

    protected $description = 'Diagnostic read-only complet entre bulletin officiel BTS et recalcul courant.';

    public function handle(BulletinConsistencyService $bulletinConsistencyService): int
    {
        $etudiantId = (int) $this->argument('etudiant_id');
        $classeId = (int) $this->argument('classe_id');
        $anneeId = (int) $this->argument('annee_universitaire_id');
        $periode = (string) $this->argument('periode');

        $etudiant = ESBTPEtudiant::find($etudiantId);
        if (! $etudiant) {
            $this->error('Étudiant introuvable.');

            return self::FAILURE;
        }

        $snapshot = $bulletinConsistencyService->getSnapshot($etudiantId, $classeId, $anneeId, $periode);
        $payload = [
            'tenant' => (string) $this->option('tenant'),
            'student' => [
                'id' => $etudiant->id,
                'matricule' => $etudiant->matricule,
                'nom_complet' => trim($etudiant->nom . ' ' . $etudiant->prenoms),
            ],
            'context' => [
                'classe_id' => $classeId,
                'annee_universitaire_id' => $anneeId,
                'periode' => $periode,
            ],
            'snapshot' => $snapshot,
        ];

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->info('Diagnostic cohérence bulletin');
        $this->line('Étudiant : ' . $payload['student']['nom_complet'] . ' [' . ($payload['student']['matricule'] ?? '?') . ']');
        $this->line('Contexte : classe=' . $classeId . ', année=' . $anneeId . ', période=' . $periode);
        $this->newLine();

        $this->table(
            ['Statut', 'Bulletin officiel', 'Total officiel', 'Total recalculé', 'Delta'],
            [[
                $snapshot['status'],
                $snapshot['official_bulletin_id'] ?? 'aucun',
                $snapshot['official_effective_total'] ?? 'n/a',
                $snapshot['current_recomputed_effective_total'] ?? 'n/a',
                $snapshot['difference_value'] ?? 'n/a',
            ]]
        );

        $this->line('Raisons : ' . (empty($snapshot['difference_reason_codes']) ? 'aucune' : implode(', ', $snapshot['difference_reason_codes'])));
        $this->line('Message : ' . $snapshot['user_message']);
        $this->newLine();
        $this->line(json_encode($payload['snapshot']['diagnostic'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
