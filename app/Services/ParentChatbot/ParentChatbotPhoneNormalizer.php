<?php

namespace App\Services\ParentChatbot;

use App\Domain\Notifications\PhoneNormalizer;

final class ParentChatbotPhoneNormalizer
{
    /**
     * `estMobileNational` et non `toE164` : ce numéro est la CLÉ de liaison
     * entre un parent et son enfant — c'est son empreinte que stocke la base.
     *
     * Depuis septembre 2026 l'analyseur croit un indicatif écrit explicitement,
     * donc `+33 6 12 34 56 78` lui est valide. C'est juste pour une relance, et
     * hors périmètre pour une clé : le chatbot garde donc sa portée étroite,
     * mais en la DISANT au lieu de l'hériter d'un refus qui n'existe plus.
     *
     * Sur une instance dont l'indicatif est réglé (229 pour `ucao-benin`), un
     * numéro béninois est national — donc accepté, sans rien changer ici.
     */
    public function normalize(string $phone): ?string
    {
        return PhoneNormalizer::estMobileNational($phone)
            ? PhoneNormalizer::toE164($phone)
            : null;
    }

    public function hash(string $normalizedPhone): string
    {
        return hash_hmac('sha256', $normalizedPhone, ParentChatbotSecurityConfig::phoneHashKey());
    }
}
