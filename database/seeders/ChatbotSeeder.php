<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChatbotSystemPrompt;
use App\Models\ChatbotDisplayTemplate;
use Illuminate\Support\Facades\DB;

class ChatbotSeeder extends Seeder
{
    /**
     * Seed chatbot system prompts et display templates
     */
    public function run(): void
    {
        $this->seedSystemPrompts();
        $this->seedDisplayTemplates();
    }

    /**
     * Seed system prompts (pre-prompts par rôle)
     */
    protected function seedSystemPrompts(): void
    {
        $prompts = [
            [
                'name' => 'default',
                'prompt' => "Tu es l'assistant virtuel de KLASSCI, un système de gestion d'établissement scolaire.
Tu aides les utilisateurs à consulter et gérer les données de l'établissement : étudiants, paiements, notes, absences, etc.

**Règles importantes :**
1. Réponds TOUJOURS en français
2. Sois concis et professionnel
3. Pour les listes de données, utilise le template 'table'
4. Propose TOUJOURS un lien vers la page complète après avoir affiché un résumé
5. Ne divulgue JAMAIS de mots de passe ou données sensibles
6. Si tu ne peux pas répondre, oriente vers le support

**Ton style :** Efficace, pédagogue, respectueux de la hiérarchie académique.",
                'allowed_roles' => null,
                'is_active' => true,
                'is_default' => true,
                'priority' => 0,
            ],
            [
                'name' => 'enseignant',
                'prompt' => "Tu es l'assistant des enseignants de KLASSCI.
Priorise l'affichage des données liées aux classes, matières, notes et absences des étudiants.
Utilise un ton professionnel mais chaleureux.
Rappelle régulièrement les échéances importantes (évaluations, bulletins).

Lorsque l'enseignant demande des informations sur ses classes ou ses étudiants, filtre automatiquement pour ne montrer QUE les classes qu'il enseigne.",
                'allowed_roles' => json_encode(['enseignant', 'teacher']),
                'is_active' => true,
                'is_default' => false,
                'priority' => 10,
            ],
            [
                'name' => 'coordinateur',
                'prompt' => "Tu es l'assistant des coordinateurs de KLASSCI.
Priorise les KPI, statistiques globales et outils de pilotage.
Ton ton est analytique et orienté décision.
Mets en avant les alertes et anomalies (retards paiements, absences répétées, classes surchargées, etc.).

Propose régulièrement des insights et recommandations basées sur les données affichées.",
                'allowed_roles' => json_encode(['coordinateur', 'superAdmin']),
                'is_active' => true,
                'is_default' => false,
                'priority' => 10,
            ],
        ];

        foreach ($prompts as $prompt) {
            ChatbotSystemPrompt::updateOrCreate(
                ['name' => $prompt['name']],
                $prompt
            );
        }

        $this->command->info('✅ System prompts seeded');
    }

    /**
     * Seed display templates
     */
    protected function seedDisplayTemplates(): void
    {
        $templates = [
            [
                'name' => 'paiements_table',
                'type' => 'table',
                'description' => 'Template pour afficher liste de paiements',
                'html_template' => '<div class="chatbot-data-table">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Étudiant</th>
                <th>Montant</th>
                <th>Statut</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            {{#each rows}}
            <tr>
                <td>{{etudiant_nom}}</td>
                <td>{{montant_formatte}}</td>
                <td><span class="badge badge-{{statut_class}}">{{statut}}</span></td>
                <td>{{date_paiement}}</td>
            </tr>
            {{/each}}
        </tbody>
    </table>
    <div class="chatbot-table-footer">
        <p class="text-muted">{{total_count}} paiement(s) affiché(s) sur {{total_available}}</p>
        {{#if deep_link}}
        <a href="{{deep_link}}" class="btn-acasi secondary btn-sm">
            <i class="fas fa-external-link-alt"></i> Voir tous les paiements
        </a>
        {{/if}}
    </div>
</div>',
                'required_fields' => json_encode(['rows', 'total_count']),
                'optional_fields' => json_encode(['deep_link', 'total_available']),
                'is_active' => true,
            ],
            [
                'name' => 'etudiants_table',
                'type' => 'table',
                'description' => 'Template pour afficher liste étudiants',
                'html_template' => '<div class="chatbot-data-table">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Nom complet</th>
                <th>Matricule</th>
                <th>Classe</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            {{#each rows}}
            <tr>
                <td>{{nom_complet}}</td>
                <td>{{matricule}}</td>
                <td>{{classe}}</td>
                <td><span class="badge badge-{{statut_class}}">{{statut}}</span></td>
            </tr>
            {{/each}}
        </tbody>
    </table>
    <div class="chatbot-table-footer">
        <p class="text-muted">{{total_count}} étudiant(s) affiché(s) sur {{total_available}}</p>
        {{#if deep_link}}
        <a href="{{deep_link}}" class="btn-acasi secondary btn-sm">
            <i class="fas fa-external-link-alt"></i> Voir tous les étudiants
        </a>
        {{/if}}
    </div>
</div>',
                'required_fields' => json_encode(['rows', 'total_count']),
                'optional_fields' => json_encode(['deep_link', 'total_available']),
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            ChatbotDisplayTemplate::updateOrCreate(
                ['name' => $template['name']],
                $template
            );
        }

        $this->command->info('✅ Display templates seeded');
    }
}
