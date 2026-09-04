<?php

namespace App\Services\Dossiers;

use App\Helpers\SettingsHelper;
use App\Mail\PiecesManquantesRelanceMail;
use App\Models\ESBTPRelance;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Relance des etudiants dont le dossier reste incomplet.
 *
 * Reutilise le mecanisme de relance deja en place cote paiements : meme table
 * esbtp_relances, meme modele, meme vocabulaire (statut, canal, declenchee_par).
 * Seul le TYPE change, ce qui suffit a separer les deux historiques sans ecrire
 * un second mecanisme de relance.
 */
class RelancePiecesService
{
    /**
     * Discriminant dans esbtp_relances.type. La colonne est un VARCHAR(40) depuis
     * la migration de mai 2026, elle accepte donc une valeur hors de l'ancien enum.
     */
    public const TYPE = 'documents';

    /**
     * Plafond d'envoi par action. Une erreur de filtre ne doit pas arroser
     * 2000 etudiants d'un coup. Configurable par instance.
     */
    public const CLE_MAX_DESTINATAIRES = 'dossiers.relance.max_destinataires';
    public const DEFAUT_MAX_DESTINATAIRES = 200;

    /**
     * @param  array<int, array>  $lignes  lignes de suivi (sortie de SuiviPiecesService)
     * @return array{envoyees: int, sans_email: int, echecs: int, plafond: int}
     */
    public function relancer(array $lignes, ?int $declencheePar = null): array
    {
        $plafond = (int) SettingsHelper::get(self::CLE_MAX_DESTINATAIRES, self::DEFAUT_MAX_DESTINATAIRES);
        $lignes = array_slice($lignes, 0, max(1, $plafond));

        $ecole = SettingsHelper::getSchoolInfo();
        $envoyees = 0;
        $sansEmail = 0;
        $echecs = 0;

        foreach ($lignes as $ligne) {
            $email = trim((string) ($ligne['email'] ?? ''));

            // Pas d'adresse : on ne cree meme pas de relance, sinon l'historique
            // se remplit d'echecs qui n'apprennent rien au secretariat.
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $sansEmail++;
                continue;
            }

            $relance = ESBTPRelance::create([
                'etudiant_id' => $ligne['etudiant_id'],
                'inscription_id' => $ligne['inscription_id'],
                'type' => self::TYPE,
                'canal' => 'email',
                'niveau' => 1,
                'template_utilise' => 'pieces_manquantes',
                'contenu_message' => $this->resume($ligne),
                'statut' => ESBTPRelance::STATUT_PLANIFIEE,
                'declenchee_par' => $declencheePar,
            ]);

            try {
                $mailable = new PiecesManquantesRelanceMail(
                    etudiantNom: (string) $ligne['etudiant'],
                    manquantes: $ligne['manquantes'],
                    anneeNom: $ligne['annee'] ?? null,
                    ecoleNom: $ecole['name'] ?? null,
                );

                $envoi = Mail::to($email);

                if (config('queue.default') === 'sync') {
                    $envoi->send($mailable);
                } else {
                    $envoi->queue($mailable);
                }

                $relance->marquerCommeEnvoyee(['canal' => 'email', 'destinataire' => $email]);
                $envoyees++;
            } catch (\Throwable $e) {
                Log::error('Relance pieces manquantes en echec', [
                    'inscription_id' => $ligne['inscription_id'],
                    'error' => $e->getMessage(),
                ]);
                $relance->marquerCommeEchec(['error' => $e->getMessage()]);
                $echecs++;
            }
        }

        return [
            'envoyees' => $envoyees,
            'sans_email' => $sansEmail,
            'echecs' => $echecs,
            'plafond' => $plafond,
        ];
    }

    /**
     * Trace lisible de ce qui a ete reclame : c'est cette ligne que le secretariat
     * relit dans l'historique, pas le HTML du mail.
     */
    private function resume(array $ligne): string
    {
        $libelles = array_map(
            fn (array $m) => $m['libelle'] . ' (' . $m['exemplaires_manquants'] . ')',
            $ligne['manquantes']
        );

        return 'Pieces reclamees : ' . implode(', ', $libelles);
    }
}
