<?php

namespace App\Domain\Assistant\Outils;

use App\Domain\Assistant\Outils\Presentation\AfficherDiagramme;
use App\Domain\Assistant\Outils\Presentation\AfficherGraphique;
use App\Domain\Assistant\Outils\Presentation\AfficherTableau;
use App\Services\Chatbot\ChatbotSetupGuideService;
use App\Services\Chatbot\Tools\EvolutionEncaissementsTool;
use App\Services\Chatbot\Tools\RepartitionEffectifsTool;
use App\Services\Chatbot\Tools\ChatbotTool;
use App\Services\Chatbot\Tools\DiagnostiquerReinscriptionTool;
use App\Services\Chatbot\Tools\SuiviDesNotesTool;
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

class CatalogueOutils
{
    /** @var ChatbotTool[] */
    private array $outils;

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
            new DiagnostiquerReinscriptionTool(),
            new SuiviDesNotesTool(),
            new ChercherDansPiece(),
            new LireStructureAcademique(),
            new LireRendezVous(),
            app(RechercherRendezVous::class),
            new LireReglages(),
            new SearchBulletinsTool(),
            new SearchAbsencesSummaryTool(),
            new EvolutionEncaissementsTool(),
            new RepartitionEffectifsTool(),
            new GetSetupGuideTool($guide),
            new NavigateToPageTool(),
            new AfficherGraphique(),
            new AfficherTableau(),
            new AfficherDiagramme(),
            ...app(\App\Domain\Assistant\Actions\RegistreDesActions::class)->toutes(),
        ];
    }

    public function outil(string $nom): ?ChatbotTool
    {
        foreach ($this->outils as $outil) if ($outil->name() === $nom) return $outil;
        return null;
    }

    public function pour($user): array
    {
        return array_values(array_filter($this->outils, fn (ChatbotTool $o) => $o->isAvailableFor($user)));
    }

    public function schemas($user): array
    {
        return array_map(fn (ChatbotTool $o) => [
            'nom' => $o->name(), 'description' => $o->description(), 'parametres' => $o->parameters(),
        ], $this->pour($user));
    }

    public function libelle(string $nom): string
    {
        $outil = $this->outil($nom);
        return $outil ? $outil->libelle() : 'Consultation des données…';
    }

    public function executer(string $nom, array $arguments, $user): array
    {
        $outil = $this->outil($nom);
        if (!$outil) return ['error' => 'Outil indisponible.'];
        try {
            return $outil->executeAuthorized($arguments, $user);
        } catch (\Throwable $e) {
            Log::error('assistant.outil_en_echec', ['outil' => $nom, 'erreur' => $e->getMessage()]);
            return ['error' => "L'outil n'a pas pu répondre."];
        }
    }
}
