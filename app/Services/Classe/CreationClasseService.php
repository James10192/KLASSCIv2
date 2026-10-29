<?php

namespace App\Services\Classe;

use App\Models\ESBTPClasse;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPLMDMention;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPNiveauEtude;
use App\Services\LMD\FiliereMiroirLmd;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Ecriture commune à l'écran des classes et aux propositions de Nanan.
 * L'ancrage LMD, la classe et ses matières sont atomiques.
 * Le formulaire transmet un ID de mention dans filiere_id en mode LMD ;
 * l'enregistrement persiste toujours un véritable ID de filière.
 */
class CreationClasseService
{
    public function creer(array $donnees, int $userId): ESBTPClasse
    {
        return DB::transaction(function () use ($donnees, $userId) {
            $niveau = ESBTPNiveauEtude::findOrFail($donnees['niveau_etude_id']);

            if ($niveau->estUnCycleLmd()) {
                $miroirs = app(FiliereMiroirLmd::class);
                if (! empty($donnees['parcours_id'])) {
                    $parcours = ESBTPLMDParcours::findOrFail($donnees['parcours_id']);
                    $donnees['filiere_id'] = $miroirs->pourParcours($parcours)->id;
                } elseif (! empty($donnees['filiere_id'])) {
                    $mention = ESBTPLMDMention::find($donnees['filiere_id']);
                    if ($mention) {
                        $donnees['filiere_id'] = $miroirs->pourMention($mention)->id;
                    }
                } else {
                    throw new InvalidArgumentException('Une mention ou un parcours LMD est requis.');
                }
            }

            $donnees['created_by'] = $userId;
            $donnees['updated_by'] = $userId;
            $classe = ESBTPClasse::create($donnees);

            // Même attachement historique que le formulaire des classes.
            // Les UE LMD restent lues depuis leur maquette de parcours.
            $matieres = ESBTPMatiere::whereHas('niveaux', function ($q) use ($niveau) {
                $q->where('esbtp_niveau_etudes.id', $niveau->id);
            })->get();
            foreach ($matieres as $matiere) {
                $classe->matieres()->attach($matiere->id, [
                    'coefficient' => $matiere->coefficient_default,
                    'total_heures' => $matiere->total_heures_default,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $classe;
        });
    }
}
