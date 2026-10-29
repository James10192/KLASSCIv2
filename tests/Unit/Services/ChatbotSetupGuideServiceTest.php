<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\Chatbot\ChatbotSetupGuideService;
use Tests\TestCase;

class ChatbotSetupGuideServiceTest extends TestCase
{
    public function test_build_missing_steps_preview_returns_only_missing_steps(): void
    {
        $guide = [
            'title' => 'Guide de mise en route KLASSCI',
            'summary' => '0/2 etapes completees',
            'sections' => [
                [
                    'title' => 'Phase 2 - Frais & inscriptions',
                    'description' => 'Objectif : tester les prerequis.',
                    'progress' => '0/2 completees',
                    'steps' => [
                        [
                            'id' => 'frais_categories',
                            'title' => 'Creer les categories de frais',
                            'description' => 'Configurer les categories.',
                            'done' => false,
                            'status' => 'todo',
                            'deep_link' => '/frais',
                            'action_label' => 'Ouvrir',
                            'requires' => [],
                        ],
                        [
                            'id' => 'inscriptions',
                            'title' => 'Creer les inscriptions',
                            'description' => 'Inscrire les etudiants.',
                            'done' => false,
                            'status' => 'todo',
                            'deep_link' => '/inscriptions',
                            'action_label' => 'Ouvrir',
                            'requires' => ['frais_categories'],
                        ],
                    ],
                ],
            ],
        ];

        $service = new class($guide) extends ChatbotSetupGuideService {
            public function __construct(private array $guide)
            {
            }

            public function buildGuide(User $user, ?string $scope = null): array
            {
                return $this->guide;
            }
        };

        $preview = $service->buildMissingStepsPreview(
            new User(),
            'financier',
            ['frais_categories', 'inscriptions'],
            1
        );

        $this->assertNotNull($preview);
        $this->assertSame('Étapes à faire avant', $preview['title']);
        $this->assertSame('2 étape(s) à compléter', $preview['summary']);
        $this->assertCount(1, $preview['sections']);
        $this->assertCount(1, $preview['sections'][0]['steps']);
        $this->assertSame('frais_categories', $preview['sections'][0]['steps'][0]['id']);
    }
}
