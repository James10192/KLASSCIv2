<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPInscription;
use App\Services\Inscriptions\FiltresListeInscriptions;
use App\Services\Frais\SouscriptionsObligatoiresManquantes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * « Régénérer les frais » : remet les souscriptions d'accord avec le barème.
 *
 * Trois portées, une seule mécanique :
 *  - `scope=selection` : les inscriptions cochées, ou une fiche ;
 *  - `scope=filtre`    : exactement ce que la liste affiche (période, classe,
 *    filière, niveau, statut…), via les mêmes filtres que l'écran ;
 *  - `scope=annee`     : toutes les inscriptions actives d'une année, ce qu'il
 *    faut après avoir corrigé un tarif — sans quoi il faudrait cocher les
 *    étudiants page par page.
 *
 * Dans tous les cas l'aperçu précède l'écriture, et `lignes[]` permet de
 * n'appliquer qu'une partie des écarts : un montant déjà retouché à la main
 * porte une décision, et la décision de l'écraser appartient à l'école.
 */
class CompleterFraisManquantsController extends Controller
{
    /**
     * Au-delà, on ne renvoie plus le détail : l'aperçu doit rester lisible et la
     * réponse transportable. Les totaux, eux, restent exacts.
     */
    private const MAX_LIGNES_RENDUES = 300;

    public function __construct()
    {
        $this->middleware('auth');
        // `frais.regenerate` et non `inscriptions.edit` : reecrire les montants
        // dus change ce que l'ecole reclame aux familles, parfois sur une annee
        // entiere. Corriger un dossier et retarifer une promotion ne se
        // decident pas au meme endroit.
        $this->middleware('permission:frais.regenerate');
    }

    public function preview(Request $request, SouscriptionsObligatoiresManquantes $rattrapage): JsonResponse
    {
        [$ids, $anneeId, $portee] = $this->portee($request);
        $this->laisserLeTempsDeBalayer($anneeId, $portee);
        $resultat = $rattrapage->executer(false, $anneeId, $ids, true, $portee);

        return response()->json($this->charge($resultat));
    }

    public function apply(Request $request, SouscriptionsObligatoiresManquantes $rattrapage): JsonResponse|RedirectResponse
    {
        [$ids, $anneeId, $portee] = $this->portee($request);
        $this->laisserLeTempsDeBalayer($anneeId, $portee);
        $resultat = $rattrapage->executer(true, $anneeId, $ids, true, $portee, $this->lignesRetenues($request));

        $message = $this->message($resultat);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['message' => $message] + $this->charge($resultat));
        }

        return back()->with('success', $message);
    }

    /**
     * Une année entière, c'est plusieurs milliers d'inscriptions à comparer au
     * barème. Le temps d'exécution par défaut d'une requête web les coupe en
     * plein milieu — et une régénération interrompue laisse la caisse à
     * moitié réalignée.
     */
    private function laisserLeTempsDeBalayer(?int $anneeId, $portee): void
    {
        if ($anneeId !== null || $portee !== null) {
            @set_time_limit(300);
        }
    }

    /**
     * Les lignes que l'utilisateur a laissées cochées dans l'aperçu.
     *
     * Absent = tout appliquer : c'est le cas des appelants qui n'offrent pas de
     * sélection. Un tableau VIDE, lui, veut dire « rien » et doit le rester —
     * d'où la distinction entre `null` et `[]`.
     *
     * @return array<int, string>|null
     */
    private function lignesRetenues(Request $request): ?array
    {
        // `selection_active` dit qu'un aperçu a montré des cases et que la
        // sélection fait foi. Sans lui, « aucune case cochée » arrivait comme
        // « pas de sélection » — donc en « tout appliquer », l'inverse exact de
        // ce que l'utilisateur venait de faire.
        if (! $request->boolean('selection_active') && ! $request->has('lignes')) {
            return null;
        }

        return array_values(array_filter(
            array_map('strval', (array) $request->input('lignes', [])),
            static fn (string $cle) => $cle !== '',
        ));
    }

    /**
     * Qui régénère-t-on : une sélection, ou toute une année.
     *
     * @return array{0: array<int, int>|null, 1: int|null}
     */
    private function portee(Request $request): array
    {
        $validated = $request->validate([
            'scope' => 'nullable|in:selection,annee,filtre',
            'annee_id' => 'nullable|integer|exists:esbtp_annee_universitaires,id',
            'inscription_ids' => 'required_if:scope,selection|required_without:scope|array|min:1',
            'inscription_ids.*' => 'integer|exists:esbtp_inscriptions,id',
            'selection_active' => 'nullable|boolean',
            'lignes' => 'nullable|array',
            'lignes.*' => 'string|max:64',
        ]);

        $scope = $validated['scope'] ?? 'selection';

        if ($scope === 'annee') {
            $anneeId = $validated['annee_id']
                ?? ESBTPAnneeUniversitaire::anneeCourante()?->id;

            // Sans année identifiable, la portée « toute l'année » viserait le
            // tenant entier, toutes promotions confondues. On refuse plutôt que
            // de deviner.
            abort_if(! $anneeId, 422, "Aucune année universitaire courante : précisez l'année à régénérer.");

            return [null, (int) $anneeId, null];
        }

        if ($scope === 'filtre') {
            // La recherche libre passe par un score de ressemblance plafonné :
            // elle retrouve une personne, elle ne définit pas un ensemble. En
            // faire une portée d'écriture donnerait un résultat qui dépend d'un
            // seuil, que personne ne peut vérifier avant de confirmer.
            abort_if(
                filled($request->input('search')),
                422,
                "Une recherche libre ne définit pas une portée : videz la recherche, ou cochez les lignes à régénérer.",
            );

            $filtres = app(FiltresListeInscriptions::class);

            return [null, null, $filtres->appliquer(ESBTPInscription::query(), $request)];
        }

        return [array_map('intval', $validated['inscription_ids']), null, null];
    }

    /**
     * @param  array<string, mixed>  $resultat
     * @return array<string, mixed>
     */
    private function charge(array $resultat): array
    {
        return [
            'success' => true,
            'total' => $resultat['total_ajouter'] + $resultat['total_retirer'] + $resultat['total_ajuster'],
            'total_ajouter' => $resultat['total_ajouter'],
            'total_retirer' => $resultat['total_retirer'],
            'total_ajuster' => $resultat['total_ajuster'],
            // Ce qui a ete TROUVE, distinct de ce qui sera ECRIT : un montant
            // negocie est detecte sans etre applicable tant que sa ligne n'a pas
            // ete cochee. C'est ce compte-la qui decide de l'etat « aucun ecart »,
            // sinon l'ecran repond « les frais sont a jour » a l'ecole qui a le
            // plus de corrections en attente.
            'total_ajuster_detecte' => $resultat['total_ajuster_detecte'] ?? $resultat['total_ajuster'],
            'inscriptions' => $resultat['inscriptions'],
            'lignes' => array_slice($resultat['lignes'], 0, self::MAX_LIGNES_RENDUES),
            'lignes_retrait' => array_slice($resultat['lignes_retrait'], 0, self::MAX_LIGNES_RENDUES),
            'lignes_ajustement' => array_slice($resultat['lignes_ajustement'], 0, self::MAX_LIGNES_RENDUES),
            'retouches' => count(array_filter(
                $resultat['lignes_ajustement'],
                static fn (array $ligne) => ! empty($ligne['montant_deja_retouche']),
            )),
            'tronque' => max(
                count($resultat['lignes']),
                count($resultat['lignes_retrait']),
                count($resultat['lignes_ajustement']),
            ) > self::MAX_LIGNES_RENDUES,
        ];
    }

    private function message(array $resultat): string
    {
        $ajoutes = (int) $resultat['total_ajouter'];
        $retires = (int) $resultat['total_retirer'];
        $ajustes = (int) $resultat['total_ajuster'];
        $detectes = (int) ($resultat['total_ajuster_detecte'] ?? $ajustes);
        $proteges = max(0, $detectes - $ajustes);

        if ($ajoutes === 0 && $retires === 0 && $detectes === 0) {
            return 'Aucun écart : les frais sont à jour.';
        }

        // Des ecarts existent, mais aucun n'est applicable en l'etat : le dire,
        // plutot que de repondre « a jour » a quelqu'un qui a des corrections
        // devant lui.
        if ($ajoutes === 0 && $retires === 0 && $ajustes === 0) {
            return sprintf(
                '%d montant(s) à mettre à jour, tous protégés car retouchés à la main. '
                . 'Cochez les lignes concernées pour les appliquer.',
                $proteges
            );
        }

        $parts = [];
        if ($ajoutes > 0) {
            $parts[] = sprintf('%d ajouté(s)', $ajoutes);
        }
        if ($retires > 0) {
            $parts[] = sprintf('%d retiré(s)', $retires);
        }
        if ($ajustes > 0) {
            $parts[] = sprintf('%d montant(s) mis à jour', $ajustes);
        }

        $phrase = sprintf('%s sur %d inscription(s).', implode(', ', $parts), $resultat['inscriptions']);

        if ($proteges > 0) {
            $phrase .= sprintf(
                ' %d montant(s) protégé(s) laissé(s) de côté : cochez-les pour les appliquer.',
                $proteges
            );
        }

        return $phrase;
    }
}
