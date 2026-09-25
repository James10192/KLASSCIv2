<?php

namespace App\Domain\Assistant\Outils;

use App\Domain\Assistant\Outils\Presentation\AfficherDiagramme;
use App\Domain\Assistant\Outils\Presentation\AfficherGraphique;
use App\Domain\Assistant\Outils\Presentation\AfficherTableau;
use App\Services\Chatbot\ChatbotSetupGuideService;
use App\Services\Chatbot\Tools\EvolutionEncaissementsTool;
use App\Services\Chatbot\Tools\RepartitionEffectifsTool;
use App\Services\Chatbot\Tools\ChatbotTool;
use App\Services\Chatbot\Tools\GetDashboardKpisTool;
use App\Services\Chatbot\Tools\GetFinancialSummaryTool;
use App\Services\Chatbot\Tools\GetSetupGuideTool;
use App\Services\Chatbot\Tools\NavigateToPageTool;
use App\Services\Chatbot\Tools\SearchAbsencesSummaryTool;
use App\Services\Chatbot\Tools\SearchAttendancesTool;
use App\Services\Chatbot\Tools\SearchBulletinsTool;
use App\Services\Chatbot\Tools\SearchClassesTool;
use App\Services\Chatbot\Tools\SearchDebtorsTool;
use App\Services\Chatbot\Tools\SearchEvaluationsTool;
use App\Services\Chatbot\Tools\SearchFeesTool;
use App\Services\Chatbot\Tools\SearchInscriptionsTool;
use App\Services\Chatbot\Tools\SearchNotesTool;
use App\Services\Chatbot\Tools\SearchPaymentsTool;
use App\Services\Chatbot\Tools\SearchResultsTool;
use App\Services\Chatbot\Tools\SearchStudentsTool;
use App\Services\Chatbot\Tools\SearchSubjectsTool;
use App\Services\Chatbot\Tools\SearchTeachersTool;
use App\Services\Chatbot\Tools\SearchTimetableTool;
use Illuminate\Support\Facades\Log;

/**
 * Catalogue des outils de l'assistant, commun à tous les fournisseurs.
 *
 * Deux contrôles, inchangés par rapport à l'ancien service Claude :
 *  - pour()     : seuls les outils que l'utilisateur peut utiliser sont DÉCLARÉS au modèle ;
 *  - executer() : chaque appel repasse par ChatbotTool::executeAuthorized, qui revérifie
 *                 les permissions — même pour un outil que le modèle n'aurait pas dû connaître.
 */
class CatalogueOutils
{
    /** @var ChatbotTool[] */
    private array $outils;

    /**
     * @param ChatbotTool[]|null $outils liste imposée (tests) ; sinon les outils de l'application
     */
    public function __construct(ChatbotSetupGuideService $guide, ?array $outils = null)
    {
        $this->outils = $outils ?? [
            new SearchStudentsTool(),
            new SearchPaymentsTool(),
            new SearchInscriptionsTool(),
            new SearchFeesTool(),
            new SearchClassesTool(),
            new SearchEvaluationsTool(),
            new SearchNotesTool(),
            new SearchAttendancesTool(),
            new SearchTeachersTool(),
            new GetDashboardKpisTool(),
            new SearchResultsTool(),
            new SearchTimetableTool(),
            new SearchSubjectsTool(),
            new GetFinancialSummaryTool(),
            new SearchDebtorsTool(),
            new SearchBulletinsTool(),
            new SearchAbsencesSummaryTool(),
            new EvolutionEncaissementsTool(),
            new RepartitionEffectifsTool(),
            new GetSetupGuideTool($guide),
            new NavigateToPageTool(),
            new AfficherGraphique(),
            new AfficherTableau(),
            new AfficherDiagramme(),
        ];
    }

    public function outil(string $nom): ?ChatbotTool
    {
        foreach ($this->outils as $outil) {
            if ($outil->name() === $nom) {
                return $outil;
            }
        }

        return null;
    }

    /** @return ChatbotTool[] outils que cet utilisateur peut réellement utiliser */
    public function pour($user): array
    {
        return array_values(array_filter($this->outils, fn (ChatbotTool $o) => $o->isAvailableFor($user)));
    }

    /** Schémas neutres, déclarés une fois ; chaque adaptateur les traduit. */
    public function schemas($user): array
    {
        return array_map(fn (ChatbotTool $o) => [
            'nom' => $o->name(),
            'description' => $o->description(),
            'parametres' => $o->parameters(),
        ], $this->pour($user));
    }

    public function libelle(string $nom): string
    {
        $outil = $this->outil($nom);

        return $outil ? $outil->libelle() : 'Consultation des données…';
    }

    /**
     * Exécute un appel demandé par le modèle. Le détail d'une exception ne part
     * ni au modèle ni au navigateur : il est journalisé.
     */
    public function executer(string $nom, array $arguments, $user): array
    {
        $outil = $this->outil($nom);
        if (!$outil) {
            return ['error' => 'Outil indisponible.'];
        }

        try {
            return $outil->executeAuthorized($arguments, $user);
        } catch (\Throwable $e) {
            Log::error('assistant.outil_en_echec', ['outil' => $nom, 'erreur' => $e->getMessage()]);
            return ['error' => "L'outil n'a pas pu répondre."];
        }
    }
}
