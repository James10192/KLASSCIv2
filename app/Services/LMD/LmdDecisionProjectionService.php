<?php

declare(strict_types=1);

namespace App\Services\LMD;

use App\Domain\OfficialDocuments\Models\OfficialDocument;
use App\Domain\OfficialDocuments\Services\BulletinArchiveService;
use App\Models\ESBTPLMDBulletin;
use App\Models\ESBTPLMDJury;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class LmdDecisionProjectionService
{
    public function publish(ESBTPLMDJury $jury, ?int $actorId): int
    {
        return DB::transaction(function () use ($jury, $actorId): int {
            $decisions = $jury->decisions()->lockForUpdate()->get();
            // Deux phases dans UNE transaction : appliquer toutes les décisions
            // avant de publier la première ligne. Sinon le verrou de cohorte
            // LMD bloque la deuxième décision dès que la première est publiée.
            $staged = [];

            foreach ($decisions as $decision) {
                if ($decision->bulletin_id === null) {
                    throw new RuntimeException("La décision de l'étudiant {$decision->etudiant_id} n'est liée à aucun bulletin.");
                }

                // Les PDF LMD émis manuellement sont immuables. La projection
                // des décisions d'un autre jury ne doit jamais modifier leurs
                // valeurs officielles en contournant le hook Eloquent par
                // update() SQL de masse.
                $archiveExistante = OfficialDocument::query()
                    ->forSource(ESBTPLMDBulletin::class, (int) $decision->bulletin_id)
                    ->where('document_type', BulletinArchiveService::TYPE_LMD)
                    ->exists();
                if ($archiveExistante) {
                    throw new RuntimeException(
                        "Le bulletin de l'étudiant {$decision->etudiant_id} dispose déjà d'une archive officielle : rectification versionnée obligatoire avant projection du jury."
                    );
                }

                // Une publication de jury est aussi une émission du bulletin.
                // Une mise à jour SQL directe publiait le parent sans conserver
                // les octets du PDF : le téléchargement ultérieur pouvait
                // afficher des notes ou réglages qui n'étaient pas ceux du jury.
                $bulletin = ESBTPLMDBulletin::query()
                    ->forJury($jury)
                    ->whereKey($decision->bulletin_id)
                    ->where('etudiant_id', $decision->etudiant_id)
                    ->lockForUpdate()
                    ->first();

                if (! $bulletin) {
                    throw new RuntimeException("Le bulletin de l'étudiant {$decision->etudiant_id} est introuvable ou incohérent.");
                }
                if ($bulletin->is_published) {
                    throw new RuntimeException("Le bulletin de l'étudiant {$decision->etudiant_id} est déjà publié : aucune projection sans rectification versionnée.");
                }

                $bulletin->forceFill([
                    'decision_deliberation' => $decision->decision,
                    'updated_by' => $actorId,
                ])->save();

                $staged[] = $bulletin;
            }

            // Les décisions sont toutes stabilisées : le verrou de cohorte
            // n'interdit plus la publication une par une. Les archives sont
            // émises à partir de leurs valeurs finales.
            $acteur = User::query()->findOrFail((int) $actorId);
            foreach ($staged as $bulletin) {
                app(BulletinArchiveService::class)->publier($bulletin, $acteur);
            }

            return count($staged);
        }, 3);
    }
}
