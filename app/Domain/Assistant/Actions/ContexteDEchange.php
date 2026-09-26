<?php

namespace App\Domain\Assistant\Actions;

use App\Models\ChatbotConversation;

/**
 * La conversation de l'échange en cours, pour les outils qui en ont besoin
 * (une proposition y est rattachée). Posé par Assistant::repondre.
 */
final class ContexteDEchange
{
    public ?ChatbotConversation $conversation = null;
}
