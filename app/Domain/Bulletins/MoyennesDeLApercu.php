<?php

declare(strict_types=1);

namespace App\Domain\Bulletins;

use App\Domain\Academique\CoherenceSystemeAcademique;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNote;
use App\Models\ESBTPResultat;
use App\Services\AppreciationScaleService;
use App\Services\BulletinService;
use App\Services\ESBTP\BtsCurrentResultSnapshotService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Les lignes de l'apercu des moyennes : QUATRE sources, UNE preseance.
 *
 * POURQUOI CET OBJET EXISTE. Cet assemblage vivait dans
 * `ESBTPResultatController::previewMoyennes()`, sur 368 lignes, et sa preseance
 * n'etait ecrite nulle part : elle resultait de l'ORDRE de quatre boucles
 * separees par cent lignes de requetes. Deux revues successives ont compte
 * « trois chemins » puis « deux », et deux correctifs de fuite ECUE ont vise le
 * 2 puis le 3 — donc aucun des deux ne pouvait voir une matiere portant une
 * ligne deja enregistree, c'est-a-dire le cas le plus courant en production.
 *
 * LA PRESEANCE, une fois pour toutes :
 *
 *   1. les lignes deja enregistrees dans `esbtp_resultats` — elles GAGNENT ;
 *   2. les notes de l'eleve — elles COMBLENT ce qui manque ;
 *   3. la maquette de la classe — elle COMBLE encore, avec une moyenne nulle ;
 *   4. le snapshot du semestre — il RECOUVRE, et c'est le seul qui recouvre.
 *
 * Les chemins 2 et 3 n'ecrasent jamais : `poser()` le dit en une ligne, au lieu
 * de le laisser deduire de trois `if (! isset(...))` eparpilles. Le chemin 4
 * recouvre, et doit donc RECONDUIRE ce que les autres ont marque — c'est la
 * forme exacte du defaut que ce chantier a paye trois fois (une seconde
 * ecriture qui annule la premiere), et elle est ici a un seul endroit.
 *
 * UNE SEULE POLITIQUE DE COEFFICIENT. Les trois premiers chemins en avaient
 * trois differentes, dont deux se contredisaient A L'ECRAN : sur une matiere
 * BTS dont le coefficient n'est pas configure, l'eleve sans ligne enregistree
 * recevait un 302 « configurez les coefficients », le meme eleve avec une ligne
 * enregistree voyait l'ecran s'ouvrir avec 1. C'est `coefficient()` ci-dessous,
 * et rien d'autre.
 *
 * ELLE PREND LA PERIODE ET L'ELEVE, ET UNE PREMIERE VERSION NE LES PRENAIT PAS.
 * Le commentaire d'alors excusait l'ecart avec le chemin 4 : sa valeur venait
 * du snapshot, « plus riche, pas une divergence ». C'etait faux sur trois
 * points, et mesures :
 *
 * - sans periode, `getCoefficientForCombination()` normalise a `semestre1` —
 *   l'onglet du SECOND semestre affichait donc le coefficient du premier ;
 * - sans eleve, `resolveTroncCommunCoefficient()` n'est jamais atteint : un
 *   coefficient de tronc commun tombait sur le repli a 1, avec son
 *   `Log::warning` pour seule trace ;
 * - et cette valeur n'est pas qu'affichee. Le formulaire la reporte dans
 *   `name="resultats[…][coefficient]"`, et l'enregistrement l'ECRIT dans
 *   `esbtp_resultats.coefficient`. Un 1 de repli devenait donc un 1 enregistre.
 *
 * L'ECRIVAIN EST `ESBTPResultatController::updateMoyennes()`, et une version de
 * ce commentaire nommait `bulkUpdateMoyennes()`. C'etait faux et jamais
 * verifie : le formulaire de cet ecran poste vers `esbtp.bulletins.moyennes-update`
 * (`routes/web.php`), tandis que `bulkUpdateMoyennes()` sert `classe-edit` avec
 * une autre charge utile. Le mauvais nom avait de quoi couter cher — il
 * envoyait corriger un ecrivain qui n'est pas sur ce chemin, et c'est
 * exactement ce qui s'est passe : l'ecrivain reel est reste sur l'ancienne
 * signature une passe de plus. Il prend desormais la periode et l'eleve, lui
 * aussi.
 *
 * ET LE CHEMIN 4 NE RECOUVRE PLUS PAR UN `1` ARBITRAIRE. Sur un onglet de
 * semestre, le chemin 4 recouvre le coefficient des qu'il porte la matiere, et
 * le sien vient de `esbtp_resultats` : `BtsCurrentResultSnapshotService`
 * prefere la valeur stockee a la valeur configuree. Or
 * `RecomputeStudentResultatJob` y ecrivait `1` EN DUR des la premiere note —
 * un `1` que personne n'avait choisi. Le job lit maintenant la maquette pour
 * les lignes NEUVES, sans jamais reecrire un coefficient deja saisi.
 *
 * UN FILTRE DE COHERENCE A L'INGESTION, pas a l'affichage. Chaque chemin passe
 * par `CoherenceSystemeAcademique`, et les deux conduites different a dessein :
 * les chemins 2 et 3 ECARTENT une matiere etrangere au systeme de la classe ;
 * le chemin 1 la GARDE et la MARQUE `intruse`. L'ecarter du chemin 1
 * emporterait sa croix de suppression et la rendrait inextirpable depuis
 * l'ecran — exactement le defaut que le retrait de maquette corrige par
 * ailleurs. Voir `.claude/rules/lmd-ecue-leak-bts-picker.md`.
 *
 * @see .claude/rules/lmd-ecue-leak-bts-picker.md
 * @see .claude/rules/lmd-bts-bulletin-separation.md
 */
final class MoyennesDeLApercu
{
    public function __construct(
        private readonly BulletinService $bulletins,
        private readonly BtsCurrentResultSnapshotService $snapshots,
        private readonly AppreciationScaleService $appreciations,
    ) {}

    /**
     * Les lignes de l'apercu, et le detail des notes qui les justifie.
     *
     * @param  string  $periode  deja normalisee : `semestre1`, `semestre2` ou `annuel`
     * @return array{lignes: array<int, array<string, mixed>>, notes_par_matiere: array<int, array<string, mixed>>}
     */
    public function assembler(
        ESBTPEtudiant $etudiant,
        ESBTPClasse $classe,
        ESBTPAnneeUniversitaire $annee,
        string $periode,
    ): array {
        $notes = $this->notesDeLEleve($etudiant, $classe, $annee, $periode);

        // LES MOYENNES SE CALCULENT AVANT D'ETRE LUES, et ce n'etait pas le cas.
        //
        // La version d'origine posait les lignes du chemin 2 avec une moyenne
        // encore a zero, PUIS calculait ces moyennes cent lignes plus bas.
        //
        // COMBIEN CA COUTAIT, MESURE ET NON DEDUIT. Une premiere redaction de
        // ce commentaire annoncait « toute matiere notee s'affichait a 0,00 ».
        // C'etait faux, et le controle par retrait l'a dit : remettre l'ancien
        // ordre laissait le test VERT. La raison est `ESBTPNoteObserver`, qui
        // ecrit une ligne `esbtp_resultats` des qu'une note est enregistree —
        // donc le chemin 1 pose presque toujours la ligne avant le chemin 2,
        // et le chemin 2 est quasiment mort.
        //
        // Il reste un cas, et un seul : les notes rattachees a l'ANNEE
        // PRECEDENTE, que `byAnneeUniversitaireWithPrevious()` ramene mais dont
        // les lignes `esbtp_resultats` portent l'autre annee. C'est l'eleve
        // reinscrit. La, le chemin 2 pose bien, et l'ancien ordre affichait
        // 0,00. Le test `une note de l annee precedente porte sa vraie moyenne`
        // le tient, et il est rouge des qu'on remet l'ancien ordre.
        $parMatiere = $this->moyennesParMatiere($notes);

        $lignes = $this->depuisLesLignesEnregistrees($etudiant, $classe, $annee, $periode);
        $lignes = $this->comblerDepuisLesNotes($lignes, $parMatiere, $etudiant, $classe, $annee, $periode);
        $lignes = $this->comblerDepuisLaMaquette($lignes, $parMatiere, $etudiant, $classe, $annee, $periode);

        if (in_array($periode, ['semestre1', 'semestre2'], true)) {
            $matieresDuSnapshot = $this->snapshots->getSemesterSnapshot(
                (int) $etudiant->id,
                (int) $classe->id,
                (int) $annee->id,
                $periode,
            )['subjects'] ?? [];

            $parMatiere = $this->notesDetailleesDepuisLeSnapshot($matieresDuSnapshot, $notes);
            $lignes = $this->recouvrirParLeSnapshot($lignes, $parMatiere, $matieresDuSnapshot);
        }

        uasort($lignes, static fn ($a, $b) => strcasecmp($a['matiere']->name, $b['matiere']->name));

        return ['lignes' => $lignes, 'notes_par_matiere' => $parMatiere];
    }

    /**
     * CHEMIN 1 — ce qui est deja enregistre. Il gagne sur tout le reste.
     *
     * @return array<int, array<string, mixed>>
     */
    private function depuisLesLignesEnregistrees(
        ESBTPEtudiant $etudiant,
        ESBTPClasse $classe,
        ESBTPAnneeUniversitaire $annee,
        string $periode,
    ): array {
        $lignes = [];

        $resultats = ESBTPResultat::query()
            ->where('etudiant_id', $etudiant->id)
            ->where('classe_id', $classe->id)
            ->where('periode', $periode)
            ->where('annee_universitaire_id', $annee->id)
            // `withTrashed()` : `ESBTPMatiere` est en `SoftDeletes`. Sans lui,
            // une matiere effacee en douceur etait sautee — la ligne
            // disparaissait de l'ecran, donc SA CROIX DE SUPPRESSION avec elle,
            // et le chemin 4 la recreait ensuite sous « Matiere inconnue », sans
            // son marquage, donc modifiable et repostable.
            ->with(['matiere' => fn ($q) => $q->withTrashed()])
            ->get();

        foreach ($resultats as $resultat) {
            $matiere = $resultat->matiere
                ?: ESBTPMatiere::withTrashed()->find($resultat->matiere_id);

            if (! $matiere) {
                continue;
            }

            $lignes[(int) $resultat->matiere_id] = [
                'id' => $resultat->id,
                'matiere' => $matiere,
                'moyenne' => $resultat->moyenne,
                // ON NE LA CACHE PAS, ON LA MONTRE COMME INTRUSE — voir l'en-tete.
                'intruse' => ! CoherenceSystemeAcademique::matiereRetenue(
                    $matiere,
                    $classe,
                    'apercu moyennes/ligne enregistree',
                ),
                'coefficient' => $this->coefficient($matiere, $etudiant, $classe, $annee, $periode),
                'rang' => $resultat->rang,
                'appreciation' => $resultat->appreciation
                    ?: $this->appreciation($resultat->moyenne),
            ];
        }

        return $lignes;
    }

    /**
     * CHEMIN 2 — les notes de l'eleve. Elles comblent, elles n'ecrasent pas.
     *
     * @param  array<int, array<string, mixed>>  $lignes
     * @param  array<int, array<string, mixed>>  $parMatiere
     * @return array<int, array<string, mixed>>
     */
    private function comblerDepuisLesNotes(
        array $lignes,
        array $parMatiere,
        ESBTPEtudiant $etudiant,
        ESBTPClasse $classe,
        ESBTPAnneeUniversitaire $annee,
        string $periode,
    ): array {
        $manquantes = array_keys(array_diff_key($parMatiere, $lignes));

        if ($manquantes === []) {
            return $lignes;
        }

        // Un seul `whereIn` plutot qu'une requete par matiere.
        $modeles = ESBTPMatiere::query()
            ->with(['filieres', 'niveaux'])
            ->whereIn('id', $manquantes)
            ->get()
            ->keyBy('id');

        foreach ($manquantes as $matiereId) {
            $matiere = $modeles->get($matiereId);

            if (! $matiere) {
                Log::warning('Apercu des moyennes : matiere introuvable, ligne ignoree.', [
                    'matiere_id' => $matiereId,
                    'classe_id' => $classe->id,
                ]);

                continue;
            }

            if (! $this->classeEstQualifiee($classe, (int) $matiereId)) {
                continue;
            }

            // `matiereRetenue()` plutot qu'un filtre muet : la ligne est HERITEE
            // (quelqu'un a saisi ces notes), donc on l'ecarte en le disant. Un
            // rattrapage silencieux ne se cherche meme pas — piege #12 de
            // `klassci-debugging-discipline.md`.
            if (! CoherenceSystemeAcademique::matiereRetenue($matiere, $classe, 'apercu moyennes')) {
                continue;
            }

            if (! $this->matiereDeLaClasse($matiere, $classe)) {
                // CE REJET-LA SE JOURNALISE, ET IL AVAIT PERDU SA TRACE.
                //
                // On est sur le chemin 2 : cette matiere PORTE DES NOTES de cet
                // eleve, et elle disparait de l'ecran parce qu'elle n'est pas
                // au programme du couple filiere x niveau de la classe. Une
                // note saisie qui ne s'affiche nulle part est exactement le
                // rattrapage muet que le piege #12 de
                // `klassci-debugging-discipline.md` fait payer : on ne cherche
                // meme pas, faute de savoir qu'il y a quelque chose a chercher.
                //
                // Le controleur d'origine le disait en `Log::debug`, que la
                // production filtre (piege #4) — donc il ne le disait pas. En
                // `warning`, et borne par le nombre de matieres notees de
                // l'eleve, c'est-a-dire quelques dizaines au pire.
                //
                // Le rejet jumeau du chemin 3 (le filtre du catalogue) ne se
                // journalise PAS, et c'est delibere : la-bas, ecarter est la
                // selection normale — il rejetterait le catalogue entier a
                // chaque appel.
                Log::warning('Apercu des moyennes : matiere notee hors du programme de la classe, ecartee.', [
                    'matiere_id' => (int) $matiereId,
                    'matiere' => $matiere->name,
                    'classe_id' => (int) $classe->id,
                    'filiere_id' => $classe->filiere_id,
                    'niveau_etude_id' => $classe->niveau_etude_id,
                ]);

                continue;
            }

            $moyenne = $parMatiere[$matiereId]['moyenne'] ?? null;

            $lignes = $this->poser($lignes, (int) $matiereId, [
                'id' => null,
                'matiere' => $matiere,
                'moyenne' => $moyenne,
                'intruse' => false,
                'coefficient' => $this->coefficient($matiere, $etudiant, $classe, $annee, $periode),
                'rang' => null,
                'appreciation' => $this->appreciation($moyenne),
            ]);
        }

        return $lignes;
    }

    /**
     * CHEMIN 3 — la maquette de la classe. Elle comble les matieres sans note.
     *
     * `btsOnly()` N'EST PAS UNE CEINTURE DE PLUS, c'est la source de ce chemin.
     * Le croisement porte sur les deux pivots PLATS (`esbtp_matiere_filiere` +
     * `esbtp_matiere_niveau`), que `LiaisonsDeMatiere::retirer()` ne nettoie
     * volontairement pas : une ECUE retiree de la maquette garde ses lignes
     * plates et ressortait donc ici. Elle etait alors POSTEE en creation, et le
     * garde de `ESBTPResultat` levait au milieu de la boucle d'enregistrement —
     * l'ecran devenait insauvegardable.
     *
     * @param  array<int, array<string, mixed>>  $lignes
     * @param  array<int, array<string, mixed>>  $parMatiere
     * @return array<int, array<string, mixed>>
     */
    private function comblerDepuisLaMaquette(
        array $lignes,
        array $parMatiere,
        ESBTPEtudiant $etudiant,
        ESBTPClasse $classe,
        ESBTPAnneeUniversitaire $annee,
        string $periode,
    ): array {
        if (! $classe->filiere_id || ! $classe->niveau_etude_id) {
            return $lignes;
        }

        $matieres = ESBTPMatiere::query()
            ->with(['filieres:id,name,code', 'niveaux:id,name,code'])
            ->where('is_active', true)
            ->btsOnly()
            ->orderBy('name')
            ->get()
            ->filter(fn ($matiere) => $this->matiereDeLaClasse($matiere, $classe));

        foreach ($matieres as $matiere) {
            $moyenne = $parMatiere[$matiere->id]['moyenne'] ?? null;
            $source = $moyenne !== null ? 'calculee' : 'manuelle';

            if (isset($lignes[(int) $matiere->id])) {
                // La ligne existe deja (chemin 1 ou 2) : on ne pose que la
                // provenance, qui est la seule chose que ce chemin sait en plus.
                $lignes[(int) $matiere->id]['source'] = $source;

                continue;
            }

            $lignes = $this->poser($lignes, (int) $matiere->id, [
                'id' => null,
                'matiere' => $matiere,
                'moyenne' => $moyenne,
                'intruse' => false,
                'coefficient' => $this->coefficient($matiere, $etudiant, $classe, $annee, $periode),
                'rang' => null,
                'appreciation' => $moyenne === null ? null : $this->appreciation($moyenne),
                'source' => $source,
            ]);
        }

        return $lignes;
    }

    /**
     * CHEMIN 4 — le snapshot du semestre. Le SEUL qui recouvre.
     *
     * Et parce qu'il recouvre, il doit RECONDUIRE : `intruse`, `rang` et
     * `appreciation` viennent des chemins precedents quand le snapshot ne les
     * porte pas. Sans ca, ce bloc effacerait le marquage pose par le chemin 1.
     * Inoffensif aujourd'hui — le snapshot filtre ses deux lectures, donc aucune
     * matiere etrangere n'y entre — mais c'est la forme exacte du defaut que ce
     * chantier a paye trois fois.
     *
     * @param  array<int, array<string, mixed>>  $lignes
     * @param  array<int, array<string, mixed>>  $parMatiere
     * @param  array<int, array<string, mixed>>  $matieresDuSnapshot
     * @return array<int, array<string, mixed>>
     */
    private function recouvrirParLeSnapshot(array $lignes, array $parMatiere, array $matieresDuSnapshot): array
    {
        foreach ($matieresDuSnapshot as $matiereDuSnapshot) {
            $matiereId = (int) ($matiereDuSnapshot['matiere_id'] ?? 0);

            if ($matiereId === 0) {
                continue;
            }

            $precedente = $lignes[$matiereId] ?? [];
            $moyenne = $matiereDuSnapshot['moyenne'] ?? null;

            $lignes[$matiereId] = [
                'id' => $precedente['id']
                    ?? ($matiereDuSnapshot['manual_resultat']['resultat_id'] ?? null),
                'matiere' => $precedente['matiere']
                    ?? $parMatiere[$matiereId]['matiere']
                    ?? (object) ['id' => $matiereId, 'name' => $matiereDuSnapshot['matiere'] ?? 'Matière inconnue'],
                'intruse' => $precedente['intruse'] ?? false,
                'moyenne' => $moyenne,
                // Le snapshot peut rendre un coefficient nul (son propre repli
                // est muet). Le gabarit le blanchissait alors en 1 sans une
                // ligne de journal. On reprend donc la valeur des chemins 1 a 3,
                // qui vient de `coefficient()` et qui est, elle, journalisee.
                'coefficient' => $matiereDuSnapshot['coefficient']
                    ?? ($precedente['coefficient'] ?? null),
                'rang' => $precedente['rang'] ?? null,
                'appreciation' => $precedente['appreciation']
                    ?? ($matiereDuSnapshot['manual_resultat']['appreciation'] ?? null)
                    ?? $this->appreciation($moyenne),
                'source' => $matiereDuSnapshot['source'] ?? 'calculee',
            ];
        }

        return $lignes;
    }

    /**
     * Le detail des notes, remis en forme par ce que dit le snapshot.
     *
     * PUBLIQUE PARCE QUE PARTAGEE : la fiche de resultats d'un eleve en a deux
     * autres appels, et cette methode etait une privee du controleur. Une seule
     * implementation, un seul endroit.
     *
     * @param  array<int, array<string, mixed>>  $matieresDuSnapshot
     * @param  Collection<int, ESBTPNote>  $notes
     * @return array<int, array<string, mixed>>
     */
    public function notesDetailleesDepuisLeSnapshot(array $matieresDuSnapshot, Collection $notes): array
    {
        $detail = [];

        foreach ($matieresDuSnapshot as $matiereDuSnapshot) {
            $matiereId = $matiereDuSnapshot['matiere_id'] ?? null;

            if (! $matiereId) {
                continue;
            }

            $notesDeLaMatiere = $notes->filter(function ($note) use ($matiereId, $matiereDuSnapshot) {
                $idDeLaNote = $note->matiere_id ?: $note->evaluation?->matiere?->id;

                if ($idDeLaNote !== $matiereId) {
                    return false;
                }

                $evaluations = collect($matiereDuSnapshot['evaluations'] ?? [])
                    ->pluck('evaluation_id')
                    ->filter()
                    ->all();

                return $evaluations === [] || in_array($note->evaluation_id, $evaluations, true);
            })->values();

            $matiere = $notesDeLaMatiere->first()?->matiere
                ?: $notesDeLaMatiere->first()?->evaluation?->matiere
                ?: (object) [
                    'id' => $matiereId,
                    'name' => $matiereDuSnapshot['matiere'] ?? 'Matière inconnue',
                    'code' => null,
                ];

            $detail[$matiereId] = [
                'matiere' => $matiere,
                'notes' => $notesDeLaMatiere->all(),
                'calculations' => [],
                'total_points' => 0,
                'total_coefficients' => (float) ($matiereDuSnapshot['coefficient'] ?? 0),
                'moyenne' => (float) ($matiereDuSnapshot['moyenne'] ?? 0),
                'origin' => 'notes',
                'source' => ($matiereDuSnapshot['source'] ?? 'calculee') === 'manuelle' ? 'manuelle' : 'calculee',
            ];
        }

        return $detail;
    }

    /**
     * Les notes de l'eleve pour ce couple classe x periode.
     *
     * Le repli sur l'annee precedente n'est pas decoratif : un eleve reinscrit
     * garde des notes rattachees a l'annee d'avant, et cet ecran est le seul
     * endroit ou on peut les reprendre.
     *
     * @return Collection<int, ESBTPNote>
     */
    private function notesDeLEleve(
        ESBTPEtudiant $etudiant,
        ESBTPClasse $classe,
        ESBTPAnneeUniversitaire $annee,
        string $periode,
    ): Collection {
        $notes = ESBTPNote::query()
            ->where('etudiant_id', $etudiant->id)
            ->with(['evaluation.matiere', 'matiere'])
            ->where(fn ($q) => $q
                ->where('semestre', $periode)
                ->orWhereHas('evaluation', fn ($e) => $e->where('periode', $periode)))
            ->byClasse($classe->id)
            ->byAnneeUniversitaireWithPrevious($annee->id)
            ->get();

        if ($notes->isNotEmpty()) {
            return $notes;
        }

        $anneePrecedente = (int) $annee->id - 1;

        return ESBTPNote::query()
            ->where('etudiant_id', $etudiant->id)
            ->withValidEvaluation()
            ->whereHas('evaluation', function ($query) use ($periode, $classe, $anneePrecedente) {
                $query->where('classe_id', $classe->id);

                if ($periode !== 'annuel') {
                    $query->where('periode', $periode);
                }

                $query->where('annee_universitaire_id', $anneePrecedente);
            })
            ->get();
    }

    /**
     * Les notes regroupees par matiere, avec leur moyenne ponderee.
     *
     * @param  Collection<int, ESBTPNote>  $notes
     * @return array<int, array<string, mixed>>
     */
    private function moyennesParMatiere(Collection $notes): array
    {
        $parMatiere = [];

        foreach ($notes as $note) {
            $matiere = $note->evaluation?->matiere;

            if (! $matiere) {
                continue;
            }

            $matiereId = (int) $matiere->id;

            if (! isset($parMatiere[$matiereId])) {
                $parMatiere[$matiereId] = [
                    'matiere' => $matiere,
                    'notes' => [],
                    'total_points' => 0,
                    'total_coefficients' => 0,
                    'moyenne' => 0,
                ];
            }

            $parMatiere[$matiereId]['notes'][] = $note;
        }

        foreach ($parMatiere as $matiereId => $donnees) {
            $points = 0.0;
            $coefficients = 0.0;

            foreach ($donnees['notes'] as $note) {
                $bareme = (float) ($note->evaluation->bareme ?? 0);

                if ($bareme <= 0) {
                    continue;
                }

                $valeur = is_numeric($note->note)
                    ? (float) $note->note
                    : (is_numeric($note->valeur) ? (float) $note->valeur : 0.0);
                $coefficient = $note->evaluation->coefficient ? (float) $note->evaluation->coefficient : 1.0;

                $points += ($valeur / $bareme) * 20 * $coefficient;
                $coefficients += $coefficient;
            }

            $parMatiere[$matiereId]['total_points'] = $points;
            $parMatiere[$matiereId]['total_coefficients'] = $coefficients;
            $parMatiere[$matiereId]['moyenne'] = $coefficients > 0 ? $points / $coefficients : 0;
        }

        return $parMatiere;
    }

    /**
     * La regle des chemins 2 et 3 : on comble, on n'ecrase jamais.
     *
     * @param  array<int, array<string, mixed>>  $lignes
     * @param  array<string, mixed>  $ligne
     * @return array<int, array<string, mixed>>
     */
    private function poser(array $lignes, int $matiereId, array $ligne): array
    {
        if (isset($lignes[$matiereId])) {
            return $lignes;
        }

        $lignes[$matiereId] = $ligne;

        return $lignes;
    }

    /**
     * LA politique de coefficient — il n'y en a pas d'autre.
     *
     * `coefficientOrDefault()` replie sur 1 EN LE DISANT, et laisse passer le
     * « Classe invalide » que le `catch` du controleur doit continuer de
     * recevoir. La version d'origine avait ici trois politiques, dont un
     * `try/catch` qui repliait en silence.
     *
     * Une matiere etrangere au systeme de la classe n'a pas de coefficient sur
     * ce couple — le sien vit dans sa propre maquette. L'appel nu levait, et le
     * `catch (\RuntimeException)` renvoyait l'utilisateur vers « configurez les
     * coefficients » pour une matiere dont configurer le coefficient ne
     * reglerait rien : l'ecran ne s'ouvrait donc PAS.
     *
     * LA PERIODE ET L'ELEVE NE SONT PAS DECORATIFS — voir l'en-tete de la
     * classe. Sans eux, cet ecran lisait le coefficient du premier semestre sur
     * l'onglet du second, et n'atteignait jamais le repli Tronc Commun. Ne les
     * retirez pas pour « simplifier la signature » : la valeur rendue ici est
     * ECRITE en base par `ESBTPResultatController::updateMoyennes()`, vers qui
     * poste le formulaire de cet ecran.
     */
    private function coefficient(
        ESBTPMatiere $matiere,
        ESBTPEtudiant $etudiant,
        ESBTPClasse $classe,
        ESBTPAnneeUniversitaire $annee,
        string $periode,
    ): float {
        return $this->bulletins->coefficientOrDefault(
            (int) $matiere->id,
            (int) $classe->id,
            (int) $annee->id,
            $periode,
            (int) $etudiant->id,
        );
    }

    private function appreciation(mixed $moyenne): string
    {
        return $this->appreciations->labelFor(
            $moyenne === null ? null : (float) $moyenne,
            'bts',
            '',
        );
    }

    private function classeEstQualifiee(ESBTPClasse $classe, int $matiereId): bool
    {
        if ($classe->filiere_id && $classe->niveau_etude_id) {
            return true;
        }

        Log::warning('Apercu des moyennes : classe sans filiere ou sans niveau, matiere ignoree.', [
            'classe_id' => $classe->id,
            'matiere_id' => $matiereId,
        ]);

        return false;
    }

    private function matiereDeLaClasse(ESBTPMatiere $matiere, ESBTPClasse $classe): bool
    {
        return $matiere->filieres->pluck('id')->contains($classe->filiere_id)
            && $matiere->niveaux->pluck('id')->contains($classe->niveau_etude_id);
    }
}
