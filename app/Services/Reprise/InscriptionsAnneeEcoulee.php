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

        $etatCivil = $this->etatCivil($ligne);

        return [
            'plan' => [
                'matricule' => $matricule,
                'nom' => $nom,
                'prenoms' => trim((string) ($ligne['prenoms'] ?? '')) ?: null,
                'telephone' => $this->telephone($ligne),
                'etat_civil' => $etatCivil,
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
                'sexe' => $etatCivil['sexe'],
                'date_naissance' => $etatCivil['date_naissance'],
                'lieu_naissance' => $etatCivil['lieu_naissance'],
                'nationalite' => $etatCivil['nationalite'],
                'etudiant_existe' => $etudiant !== null,
                'etudiant_id' => $etudiant?->id,
                'inscription_existe' => $inscription !== null,
                'inscription_id' => $inscription?->id,
            ],
        ];
    }

    /**
     * L'etat civil que la ligne apporte, normalise.
     *
     * Tout y est facultatif, et RIEN n'y est devine. Une date illisible — les
     * listes de classe en portent quelques-unes, « 27/ -07-/ 2005 » ou
     * « 30/05/20052 » — devient nulle plutot qu'inventee : une date de naissance
     * fabriquee finirait sur un releve de notes officiel.
     *
     * @return array{sexe: string|null, date_naissance: string|null, lieu_naissance: string|null, nationalite: string|null}
     */
    private function etatCivil(array $ligne): array
    {
        $sexe = mb_strtoupper(trim((string) ($ligne['sexe'] ?? '')));

        return [
            'sexe' => in_array($sexe, ['M', 'F'], true) ? $sexe : null,
            'date_naissance' => $this->date($ligne['date_naissance'] ?? null),
            'lieu_naissance' => $this->texte($ligne['lieu_naissance'] ?? null, 255),
            'nationalite' => $this->texte($ligne['nationalite'] ?? null, 100),
        ];
    }

    /**
     * Complete les champs d'etat civil VIDES d'un eleve deja en base.
     *
     * Jamais d'ecrasement : un guichet a pu corriger une date que la liste porte
     * de travers, et une reprise rejouee ne doit pas defaire cette correction.
     */
    private function completer(ESBTPEtudiant $etudiant, array $etatCivil, ?int $auteur): bool
    {
        $aPoser = [];

        foreach ($etatCivil as $champ => $valeur) {
            if ($valeur !== null && trim((string) $etudiant->{$champ}) === '') {
                $aPoser[$champ] = $valeur;
            }
        }

        if ($aPoser === []) {
            return false;
        }

        $etudiant->fill($aPoser + ['updated_by' => $auteur])->save();

        return true;
    }

    /**
     * Une date au format de la base, ou null si elle ne se lit pas.
     *
     * Le controle du retour a l'identique n'est pas une precaution de style :
     * `createFromFormat` accepte « 30/05/20052 » et le replie sur une date
     * absurde sans rien signaler. Une date qui ne se reecrit pas exactement
     * comme elle est venue n'a pas ete comprise.
     */
    private function date($brut): ?string
    {
        if ($brut instanceof \DateTimeInterface) {
            return Carbon::instance($brut)->toDateString();
        }

        $valeur = trim((string) $brut);

        if ($valeur === '') {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $valeur);
            } catch (\Throwable $e) {
                continue;
            }

            if ($date && $date->format($format) === $valeur) {
                return $date->toDateString();
            }
        }

        return null;
    }

    private function texte($brut, int $max): ?string
    {
        $valeur = trim((string) preg_replace('/\s+/u', ' ', (string) $brut));

        return $valeur === '' ? null : mb_substr($valeur, 0, $max);
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
            $compte = ['etudiants' => 0, 'inscriptions' => 0, 'completes' => 0];
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
                    ] + $plan['etat_civil']);
                    $etudiant->save();
                    $compte['etudiants']++;
                } elseif ($this->completer($etudiant, $plan['etat_civil'], $auteur)) {
                    // L'eleve etait deja la, mais sans etat civil : une reprise
                    // qui apporte le sexe et la date de naissance les POSE, sans
                    // jamais ecraser ce qui est deja renseigne. La liste de
                    // classe n'a pas autorite sur une fiche qu'un guichet a
                    // corrigee depuis.
                    $compte['completes']++;
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
