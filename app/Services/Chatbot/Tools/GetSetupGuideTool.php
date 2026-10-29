<?php

namespace App\Services\Chatbot\Tools;

use App\Services\Chatbot\ChatbotSetupGuideService;

class GetSetupGuideTool extends ChatbotTool
{
    protected ChatbotSetupGuideService $setupGuide;

    public function __construct(ChatbotSetupGuideService $setupGuide)
    {
        $this->setupGuide = $setupGuide;
    }

    public function name(): string
    {
        return 'get_setup_guide';
    }

    public function description(): string
    {
        return 'Obtenir le guide de mise en route / checklist de configuration de KLASSCI. Montre les étapes complétées et celles restantes. Utiliser quand l\'utilisateur demande un guide, une checklist, les étapes de démarrage, ou "comment configurer".';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'scope' => [
                    'type' => 'string',
                    'description' => 'Portée du guide: "global" (tout), "academique" (classes, filières, planning), "financier" (frais, inscriptions, paiements), "pedagogie" (évaluations, notes, bulletins)',
                ],
                'full_guide' => [
                    'type' => 'boolean',
                    'description' => 'Si true, retourne toutes les étapes. Si false, retourne un aperçu des 3 prochaines étapes. Défaut: false.',
                ],
            ],
        ];
    }

    public function execute(array $args, $user): array
    {
        $scope = $args['scope'] ?? 'global';
        $fullGuide = $args['full_guide'] ?? false;

        if ($fullGuide) {
            $guide = $this->setupGuide->buildGuide($user, $scope);
        } else {
            $guide = $this->setupGuide->buildGuidePreview($user, $scope, 3);
        }

        return [
            'guide' => $guide,
            'display_type' => 'checklist',
            'scope' => $scope,
            'is_full' => $fullGuide,
        ];
    }
}
