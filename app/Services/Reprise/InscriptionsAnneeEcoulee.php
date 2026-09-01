<?php

namespace App\Services\Reprise;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\User;
use App\Services\Inscriptions\NormalisationTypeInscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Remet en base les eleves d'une annee que l'ecole a vecue ailleurs.
 *
 * Une ecole qui rejoint KLASSCI en cours de route arrive avec une annee deja
 * faite : des eleves, des classes, et souvent des impayes. Rien de tout cela
 * n'existe ici, et l'application ne peut donc ni les retrouver, ni les
 * reinscrire, ni leur reclamer quoi que ce soit.
 *
 * Cette classe ne fait que la premiere marche : poser les ELEVES et leurs
 * INSCRIPTIONS sur l'annee ecoulee. Deliberement rien d'autre.
 *
 * Pas de frais, pas de versement, pas de reliquat. La dette est un chantier
 * separe : elle se posera plus tard sur ces inscriptions, via une souscription
 * portee par l'annee ecoulee et un `esbtp_reliquats_details` vers la
 * reinscription. Rien ici ne ferme cette porte — au contraire, c'est
 * precisement l'inscription source que ce mecanisme exigera, et c'est pourquoi
 * la correspondance matricule -> inscription est retournee.
 *
 * Le referentiel n'est PAS cree : filieres, niveaux et classes existent deja.
 * Les creer produirait des doublons. Une classe qui ne se resout pas fait sortir
 * la ligne du lot, elle n'en fabrique pas une nouvelle.
 *
 * Idempotent par matricule : relancer ne duplique rien.
 */
class InscriptionsAnneeEcoulee
{
    /**
     * @param  array<int, array<string, mixed>>  $lignes
     * @return array{annee: array, lus: int, retenus: int, ecartes: int, applique: bool,
     *               a_creer: array, correspondance: array, lignes: array, ecarts: array}
     */
    public function executer(array $lignes, int $anneeId, bool $appliquer = false): array
    {
        $annee = ESBTPAnneeUniversitaire::find($anneeId);
        if (! $annee) {
            throw new \RuntimeException("Annee universitaire {$anneeId} introuvable.");
        }

        $retenues = [];
        $ecarts = [];
        $apercu = [];

        foreach ($lignes as $ligne) {
            $resultat = $this->preparer($ligne, $anneeId);

            if (isset($resultat['ecart'])) {
                $ecarts[] = $resultat['ecart'];

                continue;
            }

            $retenues[] = $resultat['plan'];
            $apercu[] = $resultat['apercu'];
        }

        $aCreer = [
            'etudiants' => count(array_filter($apercu, fn ($l) => $l['etudiant_existe'] === false)),
            'inscriptions' => count(array_filter($apercu, fn ($l) => $l['inscription_existe'] === false)),
            'etudiants_deja_la' => count(array_filter($apercu, fn ($l) => $l['etudiant_existe'] === true)),
            'inscriptions_deja_la' => count(array_filter($apercu, fn ($l) => $l['inscription_existe'] === true)),
        ];

        $entete = [
            'annee' => ['id' => $annee->id, 'nom' => $annee->name],
            'lus' => count($lignes),
            'retenus' => count($retenues),
            'ecartes' => count($ecarts),
            'a_creer' => $aCreer,
        ];

        if (! $appliquer || $retenues === []) {
            return $entete + [
                'applique' => false,
                'correspondance' => [],
                'lignes' => $apercu,
                'ecarts' => $ecarts,
            ];
        }

        $correspondance = $this->ecrire($retenues, $annee);

        Log::warning('[reprise] eleves et inscriptions de l\'annee ecoulee poses', [
            'annee_id' => $anneeId,
            'lignes' => count($retenues),
            'etudiants_crees' => $correspondance['_compte']['etudiants'],
            'inscriptions_creees' => $correspondance['_compte']['inscriptions'],
            'ecartes' => count($ecarts),
        ]);

        $compte = $correspondance['_compte'];
        unset($correspondance['_compte']);

        return $entete + [
            'applique' => true,
            'ecrit' => $compte,
            'correspondance' => $correspondance,
            'lignes' => $apercu,
            'ecarts' => $ecarts,
        ];
    }

    /**
     * @return array{plan?: array, apercu?: array, ecart?: array}
     */
    private function preparer(array $ligne, int $anneeId): array
    {
        $matricule = trim((string) ($ligne['matricule'] ?? ''));
        $refuser = fn (string $motif, array $detail = []) => ['ecart' => [
            'matricule' => $matricule ?: null,
            'classe' => $ligne['classe_pdf'] ?? null,
            'motif' => $motif,
        ] + $detail];

        if ($matricule === '') {
            return $refuser('ligne sans matricule : rien pour l\'identifier');
        }

        $nom = trim((string) ($ligne['nom'] ?? ''));
        if ($nom === '') {
            // `esbtp_etudiants.nom` est requis, et un nom ne se devine pas.
            return $refuser('ligne sans nom de famille');
        }

        $classe = ESBTPClasse::find($ligne['classe_id'] ?? null);
        if (! $classe) {
            return $refuser('classe introuvable — aucune classe n\'est creee ici', [
                'classe_id' => $ligne['classe_id'] ?? null,
            ]);
        }

        // La classe range son niveau sous `niveau_etude_id` ; l'inscription
        // l'attend sous `niveau_id`. Deux noms, une seule notion.
        if (! $classe->filiere_id || ! $classe->niveau_etude_id) {
            return $refuser('la classe ne porte pas de filiere ou de niveau exploitable', [
                'classe_id' => $classe->id,
                'classe_en_base' => $classe->name,
            ]);
        }

        $etudiant = ESBTPEtudiant::where('matricule', $matricule)->first();
        $inscription = $etudiant
            ? ESBTPInscription::where('etudiant_id', $etudiant->id)
                ->where('annee_universitaire_id', $anneeId)
                ->first()
            : null;

        // Une inscription deja posee sur une AUTRE classe est un desaccord de
        // fond : on le signale, on ne deplace pas un eleve sans qu'on le sache.
        if ($inscription && (int) $inscription->classe_id !== (int) $classe->id) {
            return $refuser('inscription deja presente sur une autre classe', [
                'inscription_id' => $inscription->id,
                'classe_actuelle_id' => $inscription->classe_id,
                'classe_attendue_id' => $classe->id,
            ]);
        }

        return [
            'plan' => [
                'matricule' => $matricule,
                'nom' => $nom,
                'prenoms' => trim((string) ($ligne['prenoms'] ?? '')) ?: null,
                'telephone' => $this->telephone($ligne),
                'classe' => $classe,
                'etudiant_id' => $etudiant?->id,
            ],
            'apercu' => [
                'matricule' => $matricule,
                'nom' => $nom,
                'prenoms' => trim((string) ($ligne['prenoms'] ?? '')) ?: null,
                'telephone' => $this->telephone($ligne),
                'telephone_ecarte' => $ligne['telephone_anomalie'] ?? null,
                'classe_pdf' => $ligne['classe_pdf'] ?? null,
                'classe_en_base' => $classe->name,
                'classe_id' => $classe->id,
                'filiere_id' => $classe->filiere_id,
                'niveau_id' => $classe->niveau_etude_id,
                'etudiant_existe' => $etudiant !== null,
                'etudiant_id' => $etudiant?->id,
                'inscription_existe' => $inscription !== null,
                'inscription_id' => $inscription?->id,
            ],
        ];
    }

    /**
     * Un numero ivoirien tient en dix chiffres. Le document porte parfois autre
     * chose dans cette colonne — un nom de tuteur, un format international, un
     * numero tronque. On laisse vide plutot que de reprendre une valeur fausse.
     */
    private function telephone(array $ligne): ?string
    {
        $brut = preg_replace('/\D/', '', (string) ($ligne['telephone'] ?? ''));

        return preg_match('/^0\d{9}$/', (string) $brut) ? $brut : null;
    }

    /**
     * @param  array<int, array>  $plans
     * @return array<string, mixed>  matricule -> inscription, plus `_compte`
     */
    private function ecrire(array $plans, ESBTPAnneeUniversitaire $annee): array
    {
        // `created_by` est NOT NULL sans defaut sur ces tables. Lancee en console
        // la commande n'a pas d'utilisateur authentifie, et l'insertion
        // echouerait sur un 1364.
        $auteur = auth()->id() ?? User::query()->min('id');
        $dateInscription = $annee->start_date
            ? Carbon::parse($annee->start_date)->toDateString()
            : now()->toDateString();

        return DB::transaction(function () use ($plans, $annee, $auteur, $dateInscription): array {
            $compte = ['etudiants' => 0, 'inscriptions' => 0];
            $correspondance = [];

            foreach ($plans as $plan) {
                $classe = $plan['classe'];

                $etudiant = ESBTPEtudiant::firstOrNew(['matricule' => $plan['matricule']]);
                if (! $etudiant->exists) {
                    $etudiant->fill([
                        'nom' => $plan['nom'],
                        'prenoms' => $plan['prenoms'],
                        'telephone' => $plan['telephone'],
                        // `actif` : ce sont precisement les eleves que l'ecole
                        // doit retrouver dans ses listes de reinscription.
                        'statut' => 'actif',
                        'created_by' => $auteur,
                    ]);
                    $etudiant->save();
                    $compte['etudiants']++;
                }

                $inscription = ESBTPInscription::firstOrNew([
                    'etudiant_id' => $etudiant->id,
                    'annee_universitaire_id' => $annee->id,
                ]);

                if (! $inscription->exists) {
                    $inscription->fill([
                        'filiere_id' => $classe->filiere_id,
                        'niveau_id' => $classe->niveau_etude_id,
                        'classe_id' => $classe->id,
                        'date_inscription' => $dateInscription,
                        'type_inscription' => NormalisationTypeInscription::PREMIERE,
                        'status' => 'active',
                        'workflow_step' => 'etudiant_cree',
                        // Aucun montant a cette etape : la dette viendra ensuite,
                        // portee par des souscriptions. Annoncer un chiffre ici
                        // reviendrait a reclamer une somme qu'on n'a pas encore
                        // etablie.
                        'montant_scolarite' => 0,
                        'frais_inscription' => 0,
                        'observations' => 'Reprise de l\'annee ecoulee : eleve et inscription seulement. Les frais restent a etablir.',
                        'created_by' => $auteur,
                    ]);
                    $inscription->save();
                    $compte['inscriptions']++;
                }

                $correspondance[$plan['matricule']] = [
                    'etudiant_id' => $etudiant->id,
                    'inscription_id' => $inscription->id,
                    'classe_id' => $classe->id,
                ];
            }

            return $correspondance + ['_compte' => $compte];
        });
    }
}
