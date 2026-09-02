<?php

namespace App\Http\Controllers\Comptabilite;

use App\Http\Controllers\Controller;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPInscription;
use App\Services\Exports\PdfParLots;
use App\Services\Frais\EtatFinancierParFrais;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Qui a solde quel frais, et qui doit encore.
 *
 * Chez lui plutot que dans ESBTPPaiementController : ce document a sa route,
 * sa regle de perimetre et sa propre notion de filtre. Loge dans un controleur
 * de 2 900 lignes, il y ajoutait une methode de 110 lignes que plus personne
 * n'aurait retrouvee.
 *
 * Il repond a une question CUMULEE, pas a une question de periode. Les filtres
 * de date et de statut de versement de la liste ne s'y appliquent donc pas :
 * les appliquer transformerait « a solde » en « a solde pendant cette
 * semaine », ce qui ne veut rien dire. Le document le dit en toutes lettres.
 */
class EtatFinancierController extends Controller
{
    /**
     * Au-dela, le refus protege le temps de reponse, plus la memoire — le
     * rendu par lots a leve cette contrainte-la.
     */
    private const MAX_LIGNES = 10000;

    public function __invoke(Request $request, EtatFinancierParFrais $service): Response
    {
        $utilisateur = $request->user();
        $voitTout = (bool) $utilisateur?->can('paiements.view');

        // Le perimetre du document suit celui que l'utilisateur a deja le droit
        // de voir sur les paiements. Un caissier voit deja le solde de SES
        // etudiants au guichet quand il encaisse : lui refuser le document ne
        // protegeait rien et le privait de son propre suivi.
        abort_unless(
            $voitTout || $utilisateur?->can('paiements.view_own'),
            403,
            "L'état financier demande le droit de voir les paiements."
        );

        $valide = $request->validate([
            'frais_category_id' => ['nullable', 'integer', 'exists:esbtp_frais_categories,id'],
            'search' => ['nullable', 'string', 'max:120'],
            'solde' => ['nullable', 'in:soldes,partiels,impayes'],
        ]);

        $categorieId = isset($valide['frais_category_id']) ? (int) $valide['frais_category_id'] : null;
        $recherche = trim((string) ($valide['search'] ?? ''));
        $annee = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        $lignes = $this->filtrerParSolde(
            $service->construire(
                $this->inscriptions($annee, $recherche, $voitTout ? null : $utilisateur),
                $categorieId
            ),
            $valide['solde'] ?? null
        );

        if ($lignes->count() > self::MAX_LIGNES) {
            return redirect()->back()->with('error', sprintf(
                'Trop de lignes pour un PDF (%d, maximum %d). Restreignez à un frais, à un statut de solde, ou à une recherche.',
                $lignes->count(),
                self::MAX_LIGNES
            ));
        }

        return app(PdfParLots::class)->reponse(
            'esbtp.paiements.etat-financier-pdf',
            $lignes,
            [
                'totaux' => $this->totaux($lignes),
                'filtersRecap' => $this->recapFiltres($categorieId, $recherche, $valide['solde'] ?? null, $annee, $voitTout ? null : $utilisateur),
            ],
            'lignes',
            'etat-financier_'.now()->format('Y-m-d_His').'.pdf',
            inline: $request->boolean('inline'),
            orientation: 'landscape'
        );
    }

    /**
     * Les inscriptions du perimetre. `$restreintA` non nul limite aux etudiants
     * pour lesquels cet utilisateur a encaisse au moins un versement.
     */
    private function inscriptions(?ESBTPAnneeUniversitaire $annee, string $recherche, $restreintA): Collection
    {
        return ESBTPInscription::query()
            ->whereIn('status', ['active', 'en_attente'])
            ->when($annee, fn ($q) => $q->where('annee_universitaire_id', $annee->id))
            ->when($recherche !== '', function ($q) use ($recherche) {
                $comme = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $recherche).'%';
                $q->whereHas('etudiant', fn ($e) => $e->where('matricule', 'like', $comme)
                    ->orWhere('nom', 'like', $comme)
                    ->orWhere('prenoms', 'like', $comme)
                    ->orWhereRaw("CONCAT_WS(' ', nom, prenoms) LIKE ?", [$comme]));
            })
            ->when($restreintA, fn ($q) => $q->whereHas(
                'paiements',
                fn ($p) => $p->ownedBy($restreintA)
            ))
            ->with(['etudiant:id,nom,prenoms,matricule', 'classe:id,name'])
            ->get();
    }

    private function filtrerParSolde(Collection $lignes, ?string $solde): Collection
    {
        return match ($solde) {
            'soldes' => $lignes->where('statut', 'Soldé')->values(),
            'partiels' => $lignes->where('statut', 'Partiel')->values(),
            'impayes' => $lignes->where('statut', 'Aucun paiement')->values(),
            default => $lignes,
        };
    }

    /**
     * @return array<string, int|float>
     */
    private function totaux(Collection $lignes): array
    {
        return [
            'lignes' => $lignes->count(),
            'soldees' => $lignes->where('statut', 'Soldé')->count(),
            'partielles' => $lignes->where('statut', 'Partiel')->count(),
            'sans_paiement' => $lignes->where('statut', 'Aucun paiement')->count(),
            'du' => $lignes->sum('du'),
            'paye' => $lignes->sum('paye'),
            'reste' => $lignes->sum('reste'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function recapFiltres(?int $categorieId, string $recherche, ?string $solde, ?ESBTPAnneeUniversitaire $annee, $restreintA): array
    {
        return array_filter([
            'Frais' => $categorieId ? ESBTPFraisCategory::whereKey($categorieId)->value('name') : null,
            'Recherche' => $recherche !== '' ? $recherche : null,
            'Solde' => match ($solde) {
                'soldes' => 'Soldés uniquement',
                'partiels' => 'Paiements partiels',
                'impayes' => 'Aucun paiement',
                default => null,
            },
            'Année' => $annee->name ?? null,
            // Dire le perimetre sur le document lui-meme : un etat partiel qui
            // ne s'annonce pas se lit comme un etat complet.
            'Périmètre' => $restreintA
                ? 'Étudiants encaissés par '.($restreintA->name ?? 'cet utilisateur')
                : null,
        ]);
    }
}
