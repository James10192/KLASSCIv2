# Plan Chatbot KLASSCI - Version Améliorée
## Assistant IA Intégré pour Gestion d'Établissement

**Date**: 21 Octobre 2025
**Version**: 2.0
**Statut**: Plan détaillé avec pre-prompt, templates et deep linking

---

## 🎯 Vision

Un chatbot intelligent **100% intégré au design KLASSCI** qui permet aux utilisateurs de :
- Récupérer des données via langage naturel
- Créer/modifier des enregistrements par conversation
- Visualiser des données dans des **tableaux stylisés** directement dans le chat
- Accéder aux pages complètes via **deep links avec filtres appliqués**
- Bénéficier d'un assistant **personnalisable via pre-prompt**

---

## 🤖 Choix Technologique : Google Gemini API

### Pourquoi Gemini ?

| Critère | Google Gemini | OpenAI GPT-3.5 | Claude |
|---------|---------------|----------------|---------|
| **Gratuit** | ✅ Vraiment gratuit | ⚠️ $5 crédit (carte requise) | ⚠️ $5 crédit (carte requise) |
| **Limite gratuite** | 1500 req/jour | ~$5 de crédit | ~$5 de crédit |
| **Function Calling** | ✅ Natif | ✅ Natif | ✅ Natif |
| **Laravel Package** | ✅ `google-gemini-php/laravel` | ✅ Disponible | ✅ Disponible |
| **Multimodal** | ✅ (OCR futur) | ❌ (GPT-4 payant) | ✅ |
| **Coût après limite** | ~750 FCFA/mois | ~$0.50/1M tokens | Variable |

**Décision** : **Google Gemini API** - Idéal pour KLASSCI (gratuit, performant, function calling natif)

### Installation

```bash
composer require google-gemini-php/laravel
php artisan vendor:publish --provider="Gemini\Laravel\ServiceProvider"
```

**.env Configuration**
```env
GEMINI_API_KEY=your_api_key_here
GEMINI_MODEL=gemini-1.5-flash  # Ou gemini-1.5-pro pour plus de puissance
```

---

## 🗄️ Architecture Base de Données

### 1. Table `chatbot_conversations`

```php
Schema::create('chatbot_conversations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('session_id')->unique();
    $table->string('title')->nullable()->comment('Auto-généré par IA');
    $table->json('context')->nullable()->comment('Contexte accumulé');
    $table->timestamp('last_activity_at');
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();

    $table->index(['user_id', 'is_active']);
});
```

### 2. Table `chatbot_messages`

```php
Schema::create('chatbot_messages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('conversation_id')->constrained('chatbot_conversations')->cascadeOnDelete();
    $table->enum('role', ['user', 'assistant', 'system'])->default('user');
    $table->text('content');
    $table->json('metadata')->nullable()->comment('Fonctions appelées, templates utilisés, etc.');
    $table->string('display_type')->nullable()->comment('text|table|card|kpi');
    $table->json('display_data')->nullable()->comment('Données pour le template');
    $table->string('deep_link')->nullable()->comment('URL vers page complète');
    $table->timestamps();

    $table->index('conversation_id');
});
```

### 3. Table `chatbot_actions_log`

```php
Schema::create('chatbot_actions_log', function (Blueprint $table) {
    $table->id();
    $table->foreignId('conversation_id')->constrained('chatbot_conversations')->cascadeOnDelete();
    $table->foreignId('user_id')->constrained();
    $table->string('action_type')->comment('retrieve|create|update|delete');
    $table->string('model_type')->comment('ESBTPPaiement, ESBTPEtudiant, etc.');
    $table->unsignedBigInteger('model_id')->nullable();
    $table->json('action_data')->nullable()->comment('Paramètres de l\'action');
    $table->enum('status', ['success', 'failed', 'pending'])->default('pending');
    $table->text('error_message')->nullable();
    $table->timestamps();

    $table->index(['user_id', 'action_type']);
});
```

### 4. Table `chatbot_system_prompts` ⭐ NOUVEAU

```php
Schema::create('chatbot_system_prompts', function (Blueprint $table) {
    $table->id();
    $table->string('name')->unique()->comment('Nom du prompt (ex: default, enseignant, coordinateur)');
    $table->text('prompt')->comment('Texte du system prompt');
    $table->json('allowed_roles')->nullable()->comment('Roles autorisés à utiliser ce prompt');
    $table->boolean('is_active')->default(true);
    $table->boolean('is_default')->default(false);
    $table->integer('priority')->default(0)->comment('Ordre d\'application');
    $table->timestamps();
});
```

**Seed Data Example** :
```php
DB::table('chatbot_system_prompts')->insert([
    [
        'name' => 'default',
        'prompt' => "Tu es l'assistant virtuel de KLASSCI, un système de gestion d'établissement scolaire.
Tu aides les utilisateurs à consulter et gérer les données de l'établissement : étudiants, paiements, notes, absences, etc.

**Règles importantes :**
1. Réponds TOUJOURS en français
2. Sois concis et professionnel
3. Pour les listes de données, utilise TOUJOURS le template 'table'
4. Propose TOUJOURS un deep link vers la page complète après avoir affiché un résumé
5. Demande confirmation avant toute action de création/modification/suppression
6. Ne divulgue JAMAIS de mots de passe ou données sensibles
7. Si tu ne peux pas répondre, oriente vers le support

**Ton style :** Efficace, pédagogue, respectueux de la hiérarchie académique.",
        'is_active' => true,
        'is_default' => true,
        'priority' => 0,
    ],
    [
        'name' => 'enseignant',
        'prompt' => "Tu es l'assistant des enseignants.
Priorise l'affichage des données liées aux classes, matières, notes et absences des étudiants.
Utilise un ton professionnel mais chaleureux.
Rappelle régulièrement les échéances importantes (évaluations, bulletins).",
        'allowed_roles' => json_encode(['enseignant', 'teacher']),
        'is_active' => true,
        'is_default' => false,
        'priority' => 10,
    ],
    [
        'name' => 'coordinateur',
        'prompt' => "Tu es l'assistant des coordinateurs.
Priorise les KPI, statistiques globales et outils de pilotage.
Ton ton est analytique et orienté décision.
Mets en avant les alertes et anomalies (retards paiements, absences répétées, etc.).",
        'allowed_roles' => json_encode(['coordinateur', 'superAdmin']),
        'is_active' => true,
        'is_default' => false,
        'priority' => 10,
    ]
]);
```

### 5. Table `chatbot_display_templates` ⭐ NOUVEAU

```php
Schema::create('chatbot_display_templates', function (Blueprint $table) {
    $table->id();
    $table->string('name')->unique()->comment('Nom du template (ex: paiements_table, kpi_card)');
    $table->string('type')->comment('table|card|kpi|chart');
    $table->text('description')->nullable();
    $table->text('html_template')->comment('Template Blade/HTML avec placeholders');
    $table->json('required_fields')->comment('Champs requis dans display_data');
    $table->json('optional_fields')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

**Seed Data Example** :
```php
DB::table('chatbot_display_templates')->insert([
    [
        'name' => 'paiements_table',
        'type' => 'table',
        'description' => 'Affiche une liste de paiements avec actions',
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
        'name' => 'kpi_card',
        'type' => 'kpi',
        'description' => 'Affiche un KPI avec icône et tendance',
        'html_template' => '<div class="chatbot-kpi-card">
    <div class="kpi-icon {{icon_class}}">
        <i class="{{icon}}"></i>
    </div>
    <div class="kpi-content">
        <h4>{{title}}</h4>
        <p class="kpi-value">{{value}}</p>
        {{#if trend}}
        <span class="kpi-trend trend-{{trend_direction}}">
            <i class="fas fa-arrow-{{trend_icon}}"></i> {{trend_text}}
        </span>
        {{/if}}
    </div>
</div>',
        'required_fields' => json_encode(['title', 'value', 'icon']),
        'optional_fields' => json_encode(['trend', 'trend_direction', 'trend_icon', 'trend_text', 'icon_class']),
        'is_active' => true,
    ]
]);
```

---

## 🎨 Design System - Cohérence KLASSCI

### CSS du Chatbot Widget (Intégré à dashboard-moderne.css)

```css
/* ========================================
   CHATBOT WIDGET - KLASSCI DESIGN
   ======================================== */

.chatbot-widget {
    position: fixed;
    bottom: var(--space-lg, 20px);
    right: var(--space-lg, 20px);
    z-index: 9999;
    font-family: var(--font-family-base, 'Inter', sans-serif);
}

.chatbot-trigger {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); /* Gradient KLASSCI */
    border: none;
    box-shadow: 0 4px 12px rgba(4, 83, 203, 0.3);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.chatbot-trigger:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 16px rgba(4, 83, 203, 0.4);
}

.chatbot-trigger i {
    color: white;
    font-size: 24px;
}

.chatbot-window {
    position: fixed;
    bottom: 90px;
    right: 20px;
    width: 420px;
    max-width: calc(100vw - 40px);
    height: 600px;
    max-height: calc(100vh - 120px);
    background: white;
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    opacity: 0;
    transform: translateY(20px);
    pointer-events: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.chatbot-window.active {
    opacity: 1;
    transform: translateY(0);
    pointer-events: all;
}

/* Header - Style KLASSCI */
.chatbot-header {
    background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
    color: white;
    padding: var(--space-md, 16px);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chatbot-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.chatbot-header button {
    background: transparent;
    border: none;
    color: white;
    cursor: pointer;
    padding: 4px;
}

/* Messages Container */
.chatbot-messages {
    flex: 1;
    overflow-y: auto;
    padding: var(--space-md, 16px);
    background: #f8f9fa;
}

.chatbot-message {
    margin-bottom: var(--space-md, 16px);
    display: flex;
    gap: 8px;
}

.chatbot-message.user {
    flex-direction: row-reverse;
}

.chatbot-message-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 14px;
    flex-shrink: 0;
}

.chatbot-message.user .chatbot-message-avatar {
    background: #6c757d;
}

.chatbot-message-content {
    max-width: 75%;
    background: white;
    padding: 12px 16px;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.06);
}

.chatbot-message.user .chatbot-message-content {
    background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
    color: white;
}

/* Display Templates Styling */
.chatbot-data-table {
    margin-top: 8px;
    background: white;
    border-radius: 8px;
    overflow: hidden;
}

.chatbot-data-table table {
    width: 100%;
    margin: 0;
    font-size: 13px;
}

.chatbot-data-table thead {
    background: #f1f3f5;
}

.chatbot-data-table th {
    padding: 8px 12px;
    font-weight: 600;
    color: var(--text-primary, #212529);
    border-bottom: 1px solid #dee2e6;
}

.chatbot-data-table td {
    padding: 8px 12px;
    border-bottom: 1px solid #f1f3f5;
}

.chatbot-data-table .badge {
    font-size: 11px;
    padding: 4px 8px;
}

.chatbot-table-footer {
    padding: 12px;
    background: #f8f9fa;
    border-top: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chatbot-table-footer p {
    margin: 0;
    font-size: 12px;
}

/* KPI Card dans le chat */
.chatbot-kpi-card {
    display: flex;
    gap: 12px;
    padding: 12px;
    background: white;
    border-radius: 8px;
    margin-top: 8px;
    border-left: 4px solid #0453cb;
}

.chatbot-kpi-card .kpi-icon {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
}

.chatbot-kpi-card .kpi-content h4 {
    margin: 0 0 4px 0;
    font-size: 13px;
    color: #6c757d;
}

.chatbot-kpi-card .kpi-value {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-primary, #212529);
    margin: 0;
}

.chatbot-kpi-card .kpi-trend {
    font-size: 12px;
    margin-top: 4px;
    display: inline-block;
}

.chatbot-kpi-card .kpi-trend.trend-up {
    color: #28a745;
}

.chatbot-kpi-card .kpi-trend.trend-down {
    color: #dc3545;
}

/* Boutons dans le chat - Style KLASSCI */
.chatbot-message-content .btn-acasi {
    margin-top: 8px;
    font-size: 13px;
}

/* Input Container */
.chatbot-input-container {
    padding: var(--space-md, 16px);
    background: white;
    border-top: 1px solid #dee2e6;
    display: flex;
    gap: 8px;
}

.chatbot-input-container input {
    flex: 1;
    padding: 10px 16px;
    border: 1px solid #dee2e6;
    border-radius: 24px;
    outline: none;
    font-size: 14px;
}

.chatbot-input-container input:focus {
    border-color: #0453cb;
}

.chatbot-input-container button {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
    border: none;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.chatbot-input-container button:hover {
    transform: scale(1.05);
}

.chatbot-input-container button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Loading Animation */
.chatbot-typing {
    display: flex;
    gap: 4px;
    padding: 8px 0;
}

.chatbot-typing span {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #0453cb;
    animation: typing 1.4s infinite;
}

.chatbot-typing span:nth-child(2) {
    animation-delay: 0.2s;
}

.chatbot-typing span:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes typing {
    0%, 60%, 100% {
        transform: translateY(0);
        opacity: 0.6;
    }
    30% {
        transform: translateY(-10px);
        opacity: 1;
    }
}

/* Responsive */
@media (max-width: 768px) {
    .chatbot-window {
        width: calc(100vw - 40px);
        height: calc(100vh - 120px);
        bottom: 80px;
        right: 20px;
    }

    .chatbot-data-table {
        font-size: 12px;
    }

    .chatbot-message-content {
        max-width: 85%;
    }
}
```

---

## 🔧 Service Layer : ChatbotService

### Fichier : `app/Services/ChatbotService.php`

```php
<?php

namespace App\Services;

use Gemini\Laravel\Facades\Gemini;
use App\Models\ChatbotConversation;
use App\Models\ChatbotMessage;
use App\Models\ChatbotActionLog;
use App\Models\ChatbotSystemPrompt;
use App\Models\ChatbotDisplayTemplate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ChatbotService
{
    /**
     * Envoyer un message et obtenir une réponse
     */
    public function sendMessage(string $message, ?string $sessionId = null): array
    {
        $user = Auth::user();

        // Récupérer ou créer la conversation
        $conversation = $this->getOrCreateConversation($user->id, $sessionId);

        // Enregistrer le message utilisateur
        $userMessage = ChatbotMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $message,
            'display_type' => 'text',
        ]);

        // Construire le contexte (historique + system prompt)
        $messages = $this->buildMessageContext($conversation, $user);

        // Ajouter le message actuel
        $messages[] = [
            'role' => 'user',
            'parts' => [['text' => $message]]
        ];

        // Appeler Gemini avec function calling
        try {
            $response = Gemini::geminiPro()
                ->generateContent($messages);

            $responseText = $response->text();

            // Parser la réponse pour détecter les function calls
            $functionCalls = $this->parseFunctionCalls($response);

            if (!empty($functionCalls)) {
                $executionResults = $this->executeFunctions($functionCalls, $conversation, $user);

                // Formater la réponse avec les données
                $formattedResponse = $this->formatResponseWithData($responseText, $executionResults);

                // Enregistrer le message assistant avec metadata
                $assistantMessage = ChatbotMessage::create([
                    'conversation_id' => $conversation->id,
                    'role' => 'assistant',
                    'content' => $formattedResponse['text'],
                    'display_type' => $formattedResponse['display_type'] ?? 'text',
                    'display_data' => $formattedResponse['display_data'] ?? null,
                    'deep_link' => $formattedResponse['deep_link'] ?? null,
                    'metadata' => [
                        'functions_called' => $functionCalls,
                        'execution_results' => $executionResults,
                    ],
                ]);
            } else {
                // Réponse texte simple
                $assistantMessage = ChatbotMessage::create([
                    'conversation_id' => $conversation->id,
                    'role' => 'assistant',
                    'content' => $responseText,
                    'display_type' => 'text',
                ]);
            }

            // Mettre à jour la dernière activité
            $conversation->update(['last_activity_at' => now()]);

            return [
                'success' => true,
                'message' => $assistantMessage->content,
                'display_type' => $assistantMessage->display_type,
                'display_data' => $assistantMessage->display_data,
                'deep_link' => $assistantMessage->deep_link,
                'conversation_id' => $conversation->id,
            ];

        } catch (\Exception $e) {
            Log::error('Chatbot Error', [
                'user_id' => $user->id,
                'message' => $message,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => "Désolé, une erreur s'est produite. Veuillez réessayer.",
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Construire le contexte avec system prompt et historique
     */
    protected function buildMessageContext(ChatbotConversation $conversation, $user): array
    {
        $messages = [];

        // 1. Récupérer le system prompt approprié
        $systemPrompt = $this->getSystemPrompt($user);

        $messages[] = [
            'role' => 'user',
            'parts' => [['text' => $systemPrompt]]
        ];

        // 2. Ajouter l'historique (derniers 10 messages)
        $history = ChatbotMessage::where('conversation_id', $conversation->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->reverse();

        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg->role === 'user' ? 'user' : 'model',
                'parts' => [['text' => $msg->content]]
            ];
        }

        return $messages;
    }

    /**
     * Récupérer le system prompt basé sur le rôle utilisateur
     */
    protected function getSystemPrompt($user): string
    {
        $userRoles = $user->roles->pluck('name')->toArray();

        // Chercher un prompt spécifique au rôle avec priorité la plus haute
        $specificPrompt = ChatbotSystemPrompt::where('is_active', true)
            ->whereNotNull('allowed_roles')
            ->get()
            ->filter(function ($prompt) use ($userRoles) {
                $allowedRoles = json_decode($prompt->allowed_roles, true);
                return !empty(array_intersect($userRoles, $allowedRoles));
            })
            ->sortByDesc('priority')
            ->first();

        if ($specificPrompt) {
            return $specificPrompt->prompt;
        }

        // Sinon, utiliser le prompt par défaut
        $defaultPrompt = ChatbotSystemPrompt::where('is_default', true)
            ->where('is_active', true)
            ->first();

        return $defaultPrompt ? $defaultPrompt->prompt : $this->getFallbackPrompt();
    }

    /**
     * Fallback prompt si aucun en BDD
     */
    protected function getFallbackPrompt(): string
    {
        return "Tu es l'assistant KLASSCI. Aide les utilisateurs à gérer leur établissement scolaire. Réponds en français, sois concis et professionnel.";
    }

    /**
     * Parser les function calls depuis la réponse Gemini
     */
    protected function parseFunctionCalls($response): array
    {
        // Gemini retourne les function calls dans candidates[0].content.parts
        $candidates = $response->candidates ?? [];

        if (empty($candidates)) {
            return [];
        }

        $parts = $candidates[0]->content->parts ?? [];
        $functionCalls = [];

        foreach ($parts as $part) {
            if (isset($part->functionCall)) {
                $functionCalls[] = [
                    'name' => $part->functionCall->name,
                    'args' => (array) $part->functionCall->args,
                ];
            }
        }

        return $functionCalls;
    }

    /**
     * Exécuter les fonctions appelées par l'IA
     */
    protected function executeFunctions(array $functionCalls, ChatbotConversation $conversation, $user): array
    {
        $results = [];

        foreach ($functionCalls as $call) {
            $functionName = $call['name'];
            $args = $call['args'];

            // Log de l'action
            $actionLog = ChatbotActionLog::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'action_type' => $this->detectActionType($functionName),
                'model_type' => $args['model'] ?? null,
                'action_data' => $args,
                'status' => 'pending',
            ]);

            try {
                // Appeler la fonction correspondante
                $result = $this->callFunction($functionName, $args, $user);

                $actionLog->update([
                    'status' => 'success',
                    'model_id' => $result['model_id'] ?? null,
                ]);

                $results[$functionName] = $result;

            } catch (\Exception $e) {
                $actionLog->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);

                $results[$functionName] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Appeler une fonction spécifique
     */
    protected function callFunction(string $functionName, array $args, $user)
    {
        // Mapping des fonctions disponibles
        $functionsMap = [
            'get_paiements' => 'getPaiements',
            'get_etudiants' => 'getEtudiants',
            'get_absences' => 'getAbsences',
            'get_notes' => 'getNotes',
            'create_paiement' => 'createPaiement',
            'update_paiement_status' => 'updatePaiementStatus',
            // ... autres fonctions
        ];

        if (!isset($functionsMap[$functionName])) {
            throw new \Exception("Fonction inconnue : {$functionName}");
        }

        $method = $functionsMap[$functionName];

        if (!method_exists($this, $method)) {
            throw new \Exception("Méthode non implémentée : {$method}");
        }

        return $this->$method($args, $user);
    }

    /**
     * Fonction : Récupérer les paiements
     */
    protected function getPaiements(array $args, $user): array
    {
        $query = \App\Models\ESBTPPaiement::query();

        // Filtres basés sur les args
        if (isset($args['status'])) {
            $query->where('statut', $args['status']);
        }

        if (isset($args['month'])) {
            $query->whereMonth('date_paiement', $args['month']);
        }

        if (isset($args['limit'])) {
            $query->limit($args['limit']);
        } else {
            $query->limit(5); // Par défaut, max 5 résultats dans le chat
        }

        $paiements = $query->with(['etudiant', 'inscription'])
            ->orderBy('date_paiement', 'desc')
            ->get();

        // Générer deep link
        $deepLinkParams = [];
        if (isset($args['status'])) {
            $deepLinkParams['status'] = $args['status'];
        }
        if (isset($args['month'])) {
            $deepLinkParams['month'] = $args['month'];
        }

        $deepLink = route('esbtp.paiements.index', $deepLinkParams);

        // Formater les données pour le template
        $rows = $paiements->map(function ($paiement) {
            return [
                'etudiant_nom' => $paiement->etudiant->nom . ' ' . $paiement->etudiant->prenom,
                'montant_formatte' => number_format($paiement->montant, 0, ',', ' ') . ' FCFA',
                'statut' => ucfirst($paiement->statut),
                'statut_class' => $this->getStatutBadgeClass($paiement->statut),
                'date_paiement' => $paiement->date_paiement->format('d/m/Y'),
            ];
        })->toArray();

        return [
            'success' => true,
            'data' => $rows,
            'total_count' => count($rows),
            'total_available' => \App\Models\ESBTPPaiement::query()
                ->when(isset($args['status']), fn($q) => $q->where('statut', $args['status']))
                ->when(isset($args['month']), fn($q) => $q->whereMonth('date_paiement', $args['month']))
                ->count(),
            'deep_link' => $deepLink,
            'template' => 'paiements_table',
        ];
    }

    /**
     * Helper : Badge class pour statut
     */
    protected function getStatutBadgeClass(string $statut): string
    {
        return match($statut) {
            'validé' => 'success',
            'en_attente' => 'warning',
            'rejeté' => 'danger',
            'annulé' => 'secondary',
            default => 'info',
        };
    }

    /**
     * Formater la réponse avec les données d'exécution
     */
    protected function formatResponseWithData(string $responseText, array $executionResults): array
    {
        // Si une fonction a retourné des données pour un template
        foreach ($executionResults as $functionName => $result) {
            if (isset($result['template'])) {
                $template = ChatbotDisplayTemplate::where('name', $result['template'])
                    ->where('is_active', true)
                    ->first();

                if ($template) {
                    return [
                        'text' => $responseText,
                        'display_type' => $template->type,
                        'display_data' => [
                            'template_name' => $template->name,
                            'template_html' => $template->html_template,
                            'data' => $result['data'] ?? [],
                            'total_count' => $result['total_count'] ?? 0,
                            'total_available' => $result['total_available'] ?? 0,
                        ],
                        'deep_link' => $result['deep_link'] ?? null,
                    ];
                }
            }
        }

        // Sinon, réponse texte simple
        return [
            'text' => $responseText,
            'display_type' => 'text',
        ];
    }

    /**
     * Récupérer ou créer une conversation
     */
    protected function getOrCreateConversation(int $userId, ?string $sessionId): ChatbotConversation
    {
        if ($sessionId) {
            $conversation = ChatbotConversation::where('session_id', $sessionId)
                ->where('user_id', $userId)
                ->where('is_active', true)
                ->first();

            if ($conversation) {
                return $conversation;
            }
        }

        // Créer nouvelle conversation
        return ChatbotConversation::create([
            'user_id' => $userId,
            'session_id' => \Str::uuid(),
            'last_activity_at' => now(),
            'is_active' => true,
        ]);
    }

    /**
     * Détecter le type d'action depuis le nom de fonction
     */
    protected function detectActionType(string $functionName): string
    {
        if (str_starts_with($functionName, 'get_')) {
            return 'retrieve';
        }
        if (str_starts_with($functionName, 'create_')) {
            return 'create';
        }
        if (str_starts_with($functionName, 'update_')) {
            return 'update';
        }
        if (str_starts_with($functionName, 'delete_')) {
            return 'delete';
        }
        return 'unknown';
    }
}
```

---

## 🌐 API Controller : ChatbotController

### Fichier : `app/Http/Controllers/ChatbotController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Services\ChatbotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ChatbotConversation;

class ChatbotController extends Controller
{
    protected $chatbotService;

    public function __construct(ChatbotService $chatbotService)
    {
        $this->middleware('auth');
        $this->chatbotService = $chatbotService;
    }

    /**
     * Envoyer un message au chatbot
     */
    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'conversation_id' => 'nullable|string',
        ]);

        $response = $this->chatbotService->sendMessage(
            $validated['message'],
            $validated['conversation_id'] ?? null
        );

        return response()->json($response);
    }

    /**
     * Récupérer l'historique d'une conversation
     */
    public function getHistory(Request $request, string $conversationId)
    {
        $conversation = ChatbotConversation::where('session_id', $conversationId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $messages = $conversation->messages()
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'role' => $message->role,
                    'content' => $message->content,
                    'display_type' => $message->display_type,
                    'display_data' => $message->display_data,
                    'deep_link' => $message->deep_link,
                    'created_at' => $message->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'messages' => $messages,
        ]);
    }

    /**
     * Lister les conversations actives de l'utilisateur
     */
    public function listConversations(Request $request)
    {
        $conversations = ChatbotConversation::where('user_id', Auth::id())
            ->where('is_active', true)
            ->orderBy('last_activity_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($conv) {
                $lastMessage = $conv->messages()->latest()->first();

                return [
                    'id' => $conv->session_id,
                    'title' => $conv->title ?? 'Conversation sans titre',
                    'last_activity' => $conv->last_activity_at->diffForHumans(),
                    'last_message' => $lastMessage ? substr($lastMessage->content, 0, 50) . '...' : null,
                ];
            });

        return response()->json([
            'success' => true,
            'conversations' => $conversations,
        ]);
    }

    /**
     * Supprimer une conversation
     */
    public function deleteConversation(string $conversationId)
    {
        $conversation = ChatbotConversation::where('session_id', $conversationId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $conversation->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Conversation supprimée',
        ]);
    }
}
```

---

## 🎨 Frontend Widget

### Fichier : `resources/views/components/chatbot-widget.blade.php`

```blade
<!-- Chatbot Widget Component -->
<div class="chatbot-widget" id="chatbot-widget">
    <!-- Trigger Button -->
    <button class="chatbot-trigger" id="chatbot-trigger" aria-label="Ouvrir le chatbot">
        <i class="fas fa-comments"></i>
    </button>

    <!-- Chat Window -->
    <div class="chatbot-window" id="chatbot-window">
        <!-- Header -->
        <div class="chatbot-header">
            <div>
                <h3>Assistant KLASSCI</h3>
                <small>Posez vos questions</small>
            </div>
            <button id="chatbot-close" aria-label="Fermer">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Messages Container -->
        <div class="chatbot-messages" id="chatbot-messages">
            <!-- Message de bienvenue -->
            <div class="chatbot-message">
                <div class="chatbot-message-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="chatbot-message-content">
                    <p>Bonjour {{ Auth::user()->prenom }} ! 👋</p>
                    <p>Je suis votre assistant KLASSCI. Comment puis-je vous aider aujourd'hui ?</p>

                    <div class="quick-actions mt-2">
                        <button class="btn-acasi secondary btn-sm" onclick="sendQuickMessage('Montre-moi les paiements en attente')">
                            💰 Paiements en attente
                        </button>
                        <button class="btn-acasi secondary btn-sm" onclick="sendQuickMessage('Quels sont les étudiants absents aujourd\'hui ?')">
                            📅 Absences du jour
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Input Container -->
        <div class="chatbot-input-container">
            <input
                type="text"
                id="chatbot-input"
                placeholder="Tapez votre message..."
                autocomplete="off"
            />
            <button id="chatbot-send" aria-label="Envoyer">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Chatbot Widget JavaScript
(function() {
    const widget = {
        conversationId: null,
        isOpen: false,

        init() {
            this.bindEvents();
            this.loadConversation();
        },

        bindEvents() {
            document.getElementById('chatbot-trigger').addEventListener('click', () => {
                this.toggle();
            });

            document.getElementById('chatbot-close').addEventListener('click', () => {
                this.toggle();
            });

            document.getElementById('chatbot-send').addEventListener('click', () => {
                this.sendMessage();
            });

            document.getElementById('chatbot-input').addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.sendMessage();
                }
            });
        },

        toggle() {
            this.isOpen = !this.isOpen;
            const window = document.getElementById('chatbot-window');
            window.classList.toggle('active', this.isOpen);
        },

        async sendMessage() {
            const input = document.getElementById('chatbot-input');
            const message = input.value.trim();

            if (!message) return;

            // Afficher le message utilisateur
            this.addMessage('user', message);
            input.value = '';

            // Afficher le typing indicator
            this.showTyping();

            // Envoyer à l'API
            try {
                const response = await fetch('/chatbot/message', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        message: message,
                        conversation_id: this.conversationId,
                    }),
                });

                const data = await response.json();

                // Masquer le typing
                this.hideTyping();

                if (data.success) {
                    // Enregistrer l'ID de conversation
                    if (data.conversation_id) {
                        this.conversationId = data.conversation_id;
                    }

                    // Afficher la réponse
                    this.addMessage('assistant', data.message, {
                        displayType: data.display_type,
                        displayData: data.display_data,
                        deepLink: data.deep_link,
                    });
                } else {
                    this.addMessage('assistant', data.message || 'Une erreur est survenue');
                }

            } catch (error) {
                console.error('Chatbot Error:', error);
                this.hideTyping();
                this.addMessage('assistant', 'Désolé, une erreur de connexion est survenue.');
            }
        },

        addMessage(role, content, options = {}) {
            const messagesContainer = document.getElementById('chatbot-messages');

            const messageDiv = document.createElement('div');
            messageDiv.className = `chatbot-message ${role}`;

            const avatar = document.createElement('div');
            avatar.className = 'chatbot-message-avatar';
            avatar.innerHTML = role === 'user'
                ? '<i class="fas fa-user"></i>'
                : '<i class="fas fa-robot"></i>';

            const contentDiv = document.createElement('div');
            contentDiv.className = 'chatbot-message-content';

            // Si display_type = table, utiliser le template
            if (options.displayType === 'table' && options.displayData) {
                contentDiv.innerHTML = `<p>${content}</p>`;
                contentDiv.innerHTML += this.renderTemplate(options.displayData);
            }
            // Si display_type = kpi
            else if (options.displayType === 'kpi' && options.displayData) {
                contentDiv.innerHTML = `<p>${content}</p>`;
                contentDiv.innerHTML += this.renderTemplate(options.displayData);
            }
            // Texte simple
            else {
                contentDiv.innerHTML = `<p>${this.escapeHtml(content)}</p>`;
            }

            messageDiv.appendChild(avatar);
            messageDiv.appendChild(contentDiv);
            messagesContainer.appendChild(messageDiv);

            // Scroll to bottom
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        },

        renderTemplate(displayData) {
            const templateHtml = displayData.template_html;
            const data = displayData.data || [];
            const totalCount = displayData.total_count || 0;
            const totalAvailable = displayData.total_available || 0;

            // Simple Handlebars-like replacement
            let rendered = templateHtml;

            // Replace {{#each rows}}
            if (rendered.includes('{{#each rows}}')) {
                const rowsHtml = data.map(row => {
                    let rowTemplate = rendered.match(/{{#each rows}}([\s\S]*?){{\/each}}/)[1];

                    // Replace {{field}} with actual values
                    Object.keys(row).forEach(key => {
                        rowTemplate = rowTemplate.replace(new RegExp(`{{${key}}}`, 'g'), row[key]);
                    });

                    return rowTemplate;
                }).join('');

                rendered = rendered.replace(/{{#each rows}}[\s\S]*?{{\/each}}/, rowsHtml);
            }

            // Replace {{total_count}}, {{total_available}}
            rendered = rendered.replace(/{{total_count}}/g, totalCount);
            rendered = rendered.replace(/{{total_available}}/g, totalAvailable);

            // Replace {{#if deep_link}}
            if (displayData.deep_link) {
                rendered = rendered.replace(/{{deep_link}}/g, displayData.deep_link);
                rendered = rendered.replace(/{{#if deep_link}}[\s\S]*?{{\/if}}/g, (match) => {
                    return match.replace('{{#if deep_link}}', '').replace('{{/if}}', '');
                });
            } else {
                rendered = rendered.replace(/{{#if deep_link}}[\s\S]*?{{\/if}}/g, '');
            }

            return rendered;
        },

        showTyping() {
            const messagesContainer = document.getElementById('chatbot-messages');

            const typingDiv = document.createElement('div');
            typingDiv.className = 'chatbot-message';
            typingDiv.id = 'chatbot-typing';

            typingDiv.innerHTML = `
                <div class="chatbot-message-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="chatbot-message-content">
                    <div class="chatbot-typing">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            `;

            messagesContainer.appendChild(typingDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        },

        hideTyping() {
            const typing = document.getElementById('chatbot-typing');
            if (typing) {
                typing.remove();
            }
        },

        loadConversation() {
            // Charger la dernière conversation depuis localStorage ou API
            const savedConvId = localStorage.getItem('chatbot_conversation_id');
            if (savedConvId) {
                this.conversationId = savedConvId;
            }
        },

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },
    };

    // Global function pour quick actions
    window.sendQuickMessage = function(message) {
        document.getElementById('chatbot-input').value = message;
        widget.sendMessage();
    };

    // Init on DOM ready
    document.addEventListener('DOMContentLoaded', () => {
        widget.init();
    });
})();
</script>
@endpush
```

---

## 📋 Cas d'Usage Détaillés

### 1. Consultation Paiements avec Filtres

**Dialogue** :
```
User: Montre-moi les paiements en attente ce mois-ci