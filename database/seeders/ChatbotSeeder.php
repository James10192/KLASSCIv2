<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChatbotSystemPrompt;
use App\Models\ChatbotDisplayTemplate;
use App\Models\ChatbotKnowledgeBase;
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
        $this->seedKnowledgeBase();
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
                'name' => 'table_generic',
                'type' => 'table',
                'description' => 'Template générique pour affichage tabulaire',
                'html_template' => '<div class="chatbot-data-table">
    <table class="table table-hover">
        <thead>
            <tr>
                {{#each columns}}
                <th>{{label}}</th>
                {{/each}}
            </tr>
        </thead>
        <tbody>
            {{#each rows}}
            <tr>
                {{#each cells}}
                <td>
                    {{#if badge}}
                        <span class="badge badge-{{badge}}">{{value}}</span>
                    {{else}}
                        {{value}}
                    {{/if}}
                </td>
                {{/each}}
            </tr>
            {{#if actions}}
            <tr class="chatbot-row-actions">
                <td colspan="{{column_count}}">
                    {{#each actions}}
                    <a href="{{url}}" class="btn-acasi secondary btn-xs" target="_blank" rel="noopener noreferrer">
                        {{#if icon}}<i class="{{icon}}"></i> {{/if}}{{label}}
                    </a>
                    {{/each}}
                </td>
            </tr>
            {{/if}}
            {{/each}}
        </tbody>
    </table>
    <div class="chatbot-table-footer">
        <p class="text-muted">{{total_count}} résultat(s) affiché(s) sur {{total_available}}</p>
        {{#if deep_link}}
        <a href="{{deep_link}}" class="btn-acasi secondary btn-sm" target="_blank" rel="noopener noreferrer">
            <i class="fas fa-external-link-alt"></i> Ouvrir la page
        </a>
        {{/if}}
    </div>
</div>',
                'required_fields' => json_encode(['rows', 'columns', 'total_count']),
                'optional_fields' => json_encode(['deep_link', 'total_available']),
                'is_active' => true,
            ],
            [
                'name' => 'cards_generic',
                'type' => 'cards',
                'description' => 'Template générique pour affichage sous forme de cartes',
                'html_template' => '<div class="chatbot-card-grid">
    {{#each cards}}
    <div class="chatbot-card">
        <div class="chatbot-card-header">
            <div>
                <h5>{{title}}</h5>
                <p class="chatbot-card-subtitle">{{subtitle}}</p>
            </div>
            {{#if badges}}
            <div class="chatbot-card-badges">
                {{#each badges}}
                <span class="badge badge-{{style}}">{{label}}</span>
                {{/each}}
            </div>
            {{/if}}
        </div>
        {{#if meta}}
        <div class="chatbot-card-body">
            {{#each meta}}
            <div class="chatbot-card-row">
                <span class="chatbot-card-label">{{label}}</span>
                <span class="chatbot-card-value">{{value}}</span>
            </div>
            {{/each}}
        </div>
        {{/if}}
        {{#if actions}}
        <div class="chatbot-card-actions">
            {{#each actions}}
            <a href="{{url}}" class="btn-acasi secondary btn-xs" target="_blank" rel="noopener noreferrer">
                {{#if icon}}<i class="{{icon}}"></i> {{/if}}{{label}}
            </a>
            {{/each}}
        </div>
        {{/if}}
    </div>
    {{/each}}
    <div class="chatbot-table-footer">
        <p class="text-muted">{{total_count}} élément(s) affiché(s) sur {{total_available}}</p>
        {{#if deep_link}}
        <a href="{{deep_link}}" class="btn-acasi secondary btn-sm" target="_blank" rel="noopener noreferrer">
            <i class="fas fa-external-link-alt"></i> Ouvrir la page
        </a>
        {{/if}}
    </div>
</div>',
                'required_fields' => json_encode(['cards', 'total_count']),
                'optional_fields' => json_encode(['deep_link', 'total_available']),
                'is_active' => true,
            ],
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
                'name' => 'inscriptions_table',
                'type' => 'table',
                'description' => 'Template pour afficher liste inscriptions',
                'html_template' => '<div class="chatbot-data-table">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Étudiant</th>
                <th>Classe</th>
                <th>Type</th>
                <th>Statut</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            {{#each rows}}
            <tr>
                <td>{{etudiant_nom}}</td>
                <td>{{classe}}</td>
                <td>{{type_inscription}}</td>
                <td><span class="badge badge-{{statut_class}}">{{statut}}</span></td>
                <td>{{date_inscription}}</td>
            </tr>
            {{/each}}
        </tbody>
    </table>
    <div class="chatbot-table-footer">
        <p class="text-muted">{{total_count}} inscription(s) affichée(s) sur {{total_available}}</p>
        {{#if deep_link}}
        <a href="{{deep_link}}" class="btn-acasi secondary btn-sm">
            <i class="fas fa-external-link-alt"></i> Voir toutes les inscriptions
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

    /**
     * Seed knowledge base avec deep link patterns corrects
     */
    protected function seedKnowledgeBase(): void
    {
        $knowledgeEntries = [
            [
                'intent' => 'get_inscriptions',
                'deep_link_pattern' => 'http://localhost:8000/esbtp/inscriptions?annee=&filiere={filiere}&niveau={niveau}&search=&status={status}',
            ],
            [
                'intent' => 'get_paiements',
                'deep_link_pattern' => 'http://localhost:8000/esbtp/paiements?status={status}&date_debut={date_debut}&date_fin={date_fin}&etudiant_id={etudiant_id}&filiere_id={filiere_id}&niveau_id={niveau_id}&category_id={category_id}&mode_paiement={mode_paiement}',
            ],
            [
                'intent' => 'get_frais',
                'deep_link_pattern' => 'http://localhost:8000/esbtp/frais',
            ],
        ];

        foreach ($knowledgeEntries as $entry) {
            // Mise à jour uniquement du deep_link_pattern si l'entrée existe déjà
            $knowledge = ChatbotKnowledgeBase::where('intent', $entry['intent'])->first();

            if ($knowledge) {
                // Mettre à jour seulement le pattern (préserver les autres données explorées)
                $knowledge->update(['deep_link_pattern' => $entry['deep_link_pattern']]);
                $this->command->info("✅ Updated deep_link_pattern for intent: {$entry['intent']}");
            } else {
                // Créer une nouvelle entrée minimale (sera complétée par l'exploration)
                ChatbotKnowledgeBase::create($entry);
                $this->command->info("✅ Created knowledge base entry for intent: {$entry['intent']}");
            }
        }

        $this->command->info('✅ Knowledge base patterns seeded');
    }
}
