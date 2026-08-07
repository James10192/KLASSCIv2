<?php

namespace App\Services\ParentChatbot;

use App\Models\ESBTPBulletin;
use App\Models\ParentChatbotLink;
use Illuminate\Support\Facades\URL;

/**
 * Issues and revalidates the time limited report card links handed to parents.
 *
 * The signature only proves the URL was minted by KLASSCI. Every guarantee that
 * matters (publication, link still active, child still attached) is rechecked
 * at click time, because any of them can change after the message was sent.
 */
final class ParentChatbotReportCardAccess
{
    private const DEFAULT_TTL_HOURS = 48;

    public function __construct(private ParentChatbotPublicationPolicy $publicationPolicy) {}

    public function signedUrlFor(ParentChatbotLink $link, ESBTPBulletin $bulletin): string
    {
        return URL::temporarySignedRoute(
            'parent-chatbot.report-card',
            now()->addHours($this->ttlHours()),
            ['bulletin' => $bulletin->getKey(), 'link' => $link->getKey()],
        );
    }

    public function ttlHours(): int
    {
        $hours = (int) config('services.mailpulse.parent_chatbot_report_card_ttl_hours', self::DEFAULT_TTL_HOURS);

        return $hours > 0 ? $hours : self::DEFAULT_TTL_HOURS;
    }

    /**
     * Whether the link may still disclose this report card right now.
     */
    public function grants(?ParentChatbotLink $link, ?ESBTPBulletin $bulletin): bool
    {
        if ($link === null || $bulletin === null || ! $link->isActive()) {
            return false;
        }

        $studentId = (int) $bulletin->etudiant_id;
        if ($studentId <= 0) {
            return false;
        }

        $parent = $link->parent;
        if ($parent === null || ! $parent->pupilles()->whereKey($studentId)->exists()) {
            return false;
        }

        return $this->publicationPolicy->reportCardIsPublished($bulletin);
    }
}
