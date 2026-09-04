<?php

namespace App\Services\Dossiers;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPInscriptionPiece;
use App\Models\ESBTPPieceDossier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Repond a la question que l'ecole pose vraiment : « QUI doit encore fournir QUOI ».
 *
 * Le panneau par inscription dit ce qui manque a UN etudiant ; ce service dit ce
 * qui manque a travers une classe, une filiere ou une promotion, et combien
 * d'exemplaires de chaque piece restent a reunir avant de constituer les dossiers
 * pour les ministeres.
 *
 * Le coeur du calcul (resolution du catalogue, agregation) est volontairement pur :
 * il se teste sans base de donnees.
 */
class SuiviPiecesService
{
    /**
     * Garde-fou de volume : au-dela, on refuse plutot que de faire tomber le serveur
     * d'une ecole de 2000 inscrits. Configurable par instance.
     */
    public const CLE_MAX_INSCRIPTIONS = 'dossiers.suivi.max_inscriptions';
    public const DEFAUT_MAX_INSCRIPTIONS = 5000;

    /**
     * Resout, pour un scope (filiere, niveau), la liste des pieces reellement attendues.
     *
     * Regle : pour un meme code, la definition la plus specifique l'emporte
     * (filiere+niveau > filiere > niveau > defaut etablissement). Si la definition
     * gagnante est inactive, l'ecole a explicitement exempte ce scope : la piece
     * n'est pas attendue, meme si le defaut etablissement l'exige.
     *
     * Fonction pure : aucune requete, testable sans base.
     *
     * @param  iterable<ESBTPPieceDossier>  $catalogue
     * @return array<string, ESBTPPieceDossier>  indexe par code
     */
    public static function piecesAttendues(iterable $catalogue, ?int $filiereId, ?int $niveauId): array
    {
        $gagnantes = [];

        foreach ($catalogue as $piece) {
            if (! $piece->couvre($filiereId, $niveauId)) {
                continue;
            }

            $code = (string) $piece->code;
            $actuelle = $gagnantes[$code] ?? null;

            if ($actuelle === null || $piece->specificite() > $actuelle->specificite()) {
                $gagnantes[$code] = $piece;
            }
        }

        // L'exemption ne se voit qu'apres arbitrage : une ligne inactive doit pouvoir
        // battre le defaut avant d'etre retiree.
        $attendues = array_filter($gagnantes, fn (ESBTPPieceDossier $piece) => $piece->is_active);

        uasort($attendues, function (ESBTPPieceDossier $a, ESBTPPieceDossier $b) {
            return [$a->ordre, $a->libelle] <=> [$b->ordre, $b->libelle];
        });

        return $attendues;
    }

    /**
     * Agrege les lignes par piece : « il manque 34 extraits de naissance ».
     *
     * Fonction pure : aucune requete, testable sans base.
     *
     * @param  array<int, array>  $lignes  sortie de construireLignes()['lignes']
     * @return array<int, array>  trie par nombre d'etudiants concernes, decroissant
     */
    public static function agregerParPiece(array $lignes): array
    {
        $parCode = [];

        foreach ($lignes as $ligne) {
            foreach ($ligne['manquantes'] as $manquante) {
                $code = $manquante['code'];

                if (! isset($parCode[$code])) {
                    $parCode[$code] = [
                        'code' => $code,
                        'libelle' => $manquante['libelle'],
                        'obligatoire' => (bool) $manquante['obligatoire'],
                        'etudiants' => 0,
                        'exemplaires' => 0,
                    ];
                }

                $parCode[$code]['etudiants']++;
                $parCode[$code]['exemplaires'] += (int) $manquante['exemplaires_manquants'];

                // Une seule definition suffit a rendre la piece obligatoire dans le lot.
                if ($manquante['obligatoire']) {
                    $parCode[$code]['obligatoire'] = true;
                }
            }
        }

        $agrege = array_values($parCode);

        usort($agrege, function (array $a, array $b) {
            return [$b['obligatoire'], $b['etudiants']] <=> [$a['obligatoire'], $a['etudiants']];
        });

        return $agrege;
    }

    /**
     * Construit la vue d'ensemble : une ligne par inscription a laquelle il manque
     * au moins une piece, plus l'agregat par piece et les indicateurs de tete.
     *
     * @param  array<string, mixed>  $filtres  annee_id, filiere_id, niveau_id,
     *                                        classe_id, piece_code, search,
     *                                        obligatoires_seulement
     * @return array{lignes: array, par_piece: array, kpis: array, tronque: bool}
     */
    public function construireLignes(array $filtres = []): array
    {
        $catalogue = $this->catalogue();

        // Catalogue vide = aucune piece attendue. Une ecole qui n'a rien configure
        // voit un ecran vide, pas un ecran faux.
        if ($catalogue->isEmpty()) {
            return [
                'lignes' => [],
                'par_piece' => [],
                'kpis' => $this->kpisVides(),
                'tronque' => false,
            ];
        }

        $max = (int) SettingsHelper::get(self::CLE_MAX_INSCRIPTIONS, self::DEFAUT_MAX_INSCRIPTIONS);
        $inscriptions = $this->inscriptions($filtres, $max + 1);

        $tronque = $inscriptions->count() > $max;
        if ($tronque) {
            $inscriptions = $inscriptions->take($max);
        }

        $etats = $this->etats($inscriptions->pluck('inscription_id')->all());

        $lignes = [];
        $totalAttendues = 0;
        $codeFiltre = (string) ($filtres['piece_code'] ?? '');
        $obligatoiresSeulement = ! empty($filtres['obligatoires_seulement']);

        foreach ($inscriptions as $inscription) {
            $attendues = self::piecesAttendues(
                $catalogue,
                $inscription->filiere_id !== null ? (int) $inscription->filiere_id : null,
                $inscription->niveau_id !== null ? (int) $inscription->niveau_id : null
            );

            $totalAttendues += count($attendues);
            $etatsInscription = $etats[$inscription->inscription_id] ?? [];
            $manquantes = [];

            foreach ($attendues as $code => $piece) {
                $etat = $etatsInscription[$piece->id] ?? null;

                if ($etat !== null && in_array($etat->statut, ESBTPInscriptionPiece::statutsSolde(), true)) {
                    continue;
                }

                if ($codeFiltre !== '' && $code !== $codeFiltre) {
                    continue;
                }

                if ($obligatoiresSeulement && ! $piece->est_obligatoire) {
                    continue;
                }

                $attendus = max(1, (int) $piece->nombre_exemplaires);
                $fournis = $etat !== null ? (int) $etat->exemplaires_fournis : 0;

                $manquantes[] = [
                    'code' => $code,
                    'libelle' => $piece->libelle,
                    'obligatoire' => (bool) $piece->est_obligatoire,
                    'exemplaires_attendus' => $attendus,
                    'exemplaires_fournis' => $fournis,
                    'exemplaires_manquants' => max(0, $attendus - $fournis),
                ];
            }

            if ($manquantes === []) {
                continue;
            }

            $lignes[] = [
                'inscription_id' => (int) $inscription->inscription_id,
                'etudiant_id' => (int) $inscription->etudiant_id,
                'matricule' => (string) ($inscription->matricule ?? ''),
                'etudiant' => trim(($inscription->nom ?? '') . ' ' . ($inscription->prenoms ?? '')),
                'email' => $inscription->email,
                'telephone' => $inscription->telephone,
                'classe' => $inscription->classe_nom,
                'filiere' => $inscription->filiere_nom,
                'niveau' => $inscription->niveau_nom,
                'annee' => $inscription->annee_nom,
                'manquantes' => $manquantes,
                'nb_manquantes' => count($manquantes),
                'nb_manquantes_obligatoires' => count(array_filter($manquantes, fn ($m) => $m['obligatoire'])),
                'nb_attendues' => count($attendues),
            ];
        }

        usort($lignes, function (array $a, array $b) {
            return [$b['nb_manquantes_obligatoires'], $b['nb_manquantes'], $a['etudiant']]
                <=> [$a['nb_manquantes_obligatoires'], $a['nb_manquantes'], $b['etudiant']];
        });

        $parPiece = self::agregerParPiece($lignes);

        return [
            'lignes' => $lignes,
            'par_piece' => $parPiece,
            'kpis' => [
                'inscriptions_examinees' => $inscriptions->count(),
                'inscriptions_incompletes' => count($lignes),
                'pieces_manquantes' => array_sum(array_column($lignes, 'nb_manquantes')),
                'exemplaires_manquants' => array_sum(array_column($parPiece, 'exemplaires')),
                'pieces_attendues' => $totalAttendues,
            ],
            'tronque' => $tronque,
        ];
    }

    /**
     * Catalogue complet (actif ET inactif) : les lignes inactives servent d'exemption
     * de scope, elles doivent participer a l'arbitrage.
     *
     * @return Collection<int, ESBTPPieceDossier>
     */
    public function catalogue(): Collection
    {
        return ESBTPPieceDossier::query()
            ->orderBy('ordre')
            ->orderBy('libelle')
            ->get();
    }

    /**
     * Codes distincts du catalogue actif, pour alimenter le filtre « piece ».
     *
     * @return array<string, string>  code => libelle
     */
    public function codesDisponibles(): array
    {
        return ESBTPPieceDossier::query()
            ->actif()
            ->orderBy('ordre')
            ->orderBy('libelle')
            ->get(['code', 'libelle'])
            ->mapWithKeys(fn ($piece) => [$piece->code => $piece->libelle])
            ->all();
    }

    /**
     * Inscriptions dans le perimetre, a plat : une seule requete, pas de N+1.
     */
    private function inscriptions(array $filtres, int $limite): Collection
    {
        $query = DB::table('esbtp_inscriptions as i')
            ->join('esbtp_etudiants as e', 'e.id', '=', 'i.etudiant_id')
            ->leftJoin('esbtp_classes as c', 'c.id', '=', 'i.classe_id')
            ->leftJoin('esbtp_filieres as f', 'f.id', '=', 'i.filiere_id')
            ->leftJoin('esbtp_niveau_etudes as n', 'n.id', '=', 'i.niveau_id')
            ->leftJoin('esbtp_annee_universitaires as a', 'a.id', '=', 'i.annee_universitaire_id')
            ->whereNull('i.deleted_at')
            ->select([
                'i.id as inscription_id',
                'i.etudiant_id',
                'i.filiere_id',
                'i.niveau_id',
                'e.matricule',
                'e.nom',
                'e.prenoms',
                'e.email',
                'e.telephone',
                'c.name as classe_nom',
                'f.name as filiere_nom',
                'n.name as niveau_nom',
                'a.name as annee_nom',
            ]);

        if (! empty($filtres['annee_id'])) {
            $query->where('i.annee_universitaire_id', (int) $filtres['annee_id']);
        }

        if (! empty($filtres['filiere_id'])) {
            $query->where('i.filiere_id', (int) $filtres['filiere_id']);
        }

        if (! empty($filtres['niveau_id'])) {
            $query->where('i.niveau_id', (int) $filtres['niveau_id']);
        }

        if (! empty($filtres['classe_id'])) {
            $query->where('i.classe_id', (int) $filtres['classe_id']);
        }

        if (! empty($filtres['statut_inscription'])) {
            $query->where('i.status', (string) $filtres['statut_inscription']);
        }

        $recherche = trim((string) ($filtres['search'] ?? ''));
        if ($recherche !== '') {
            $query->where(function ($q) use ($recherche) {
                $q->where('e.nom', 'like', "%{$recherche}%")
                    ->orWhere('e.prenoms', 'like', "%{$recherche}%")
                    ->orWhere('e.matricule', 'like', "%{$recherche}%");
            });
        }

        return $query->orderBy('e.nom')->orderBy('e.prenoms')->limit($limite)->get();
    }

    /**
     * Etats connus, indexes [inscription_id][piece_id].
     *
     * @param  array<int, int>  $inscriptionIds
     * @return array<int, array<int, ESBTPInscriptionPiece>>
     */
    private function etats(array $inscriptionIds): array
    {
        if ($inscriptionIds === []) {
            return [];
        }

        $index = [];

        ESBTPInscriptionPiece::query()
            ->whereIn('inscription_id', $inscriptionIds)
            ->select(['id', 'inscription_id', 'piece_id', 'statut', 'exemplaires_fournis'])
            ->chunk(2000, function ($lot) use (&$index) {
                foreach ($lot as $etat) {
                    $index[(int) $etat->inscription_id][(int) $etat->piece_id] = $etat;
                }
            });

        return $index;
    }

    private function kpisVides(): array
    {
        return [
            'inscriptions_examinees' => 0,
            'inscriptions_incompletes' => 0,
            'pieces_manquantes' => 0,
            'exemplaires_manquants' => 0,
            'pieces_attendues' => 0,
        ];
    }
}
