<?php

namespace App\Services\ParentChatbot;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPParent;
use Illuminate\Support\Collection;

/**
 * Builds the three body variables of the UTILITY activation invitation:
 * parent name, school name, pupil name.
 *
 * Meta rejects a template parameter that is empty or carries a newline, a tab
 * or a long whitespace run, so every value is collapsed, bounded and falls back
 * to a neutral label rather than being sent blank.
 */
class ParentChatbotInvitationVariables
{
    private const MAX_PARAMETER_LENGTH = 120;

    private const FALLBACK_PARENT = 'cher tuteur';

    private const FALLBACK_SCHOOL = 'KLASSCI';

    private const FALLBACK_STUDENT = 'votre enfant';

    /** @return array{0: string, 1: string, 2: string} */
    public function forParent(ESBTPParent $parent): array
    {
        return [
            $this->parentName($parent),
            $this->schoolName(),
            $this->studentName($parent),
        ];
    }

    private function parentName(ESBTPParent $parent): string
    {
        $candidates = [
            (string) ($parent->prenoms ?? ''),
            (string) ($parent->nom_complet ?? ''),
            (string) ($parent->nom ?? ''),
        ];

        foreach ($candidates as $candidate) {
            $sanitized = $this->sanitize($candidate);
            if ($sanitized !== '') {
                return $sanitized;
            }
        }

        return self::FALLBACK_PARENT;
    }

    private function schoolName(): string
    {
        $sanitized = $this->sanitize((string) SettingsHelper::get('school_name', self::FALLBACK_SCHOOL));

        return $sanitized === '' ? self::FALLBACK_SCHOOL : $sanitized;
    }

    /**
     * A WhatsApp template holds a single pupil slot while a tutor may follow
     * several children. The oldest record wins so retries of the same issuance
     * stay stable, and the remaining children are summarised rather than
     * dropped silently.
     */
    private function studentName(ESBTPParent $parent): string
    {
        /** @var Collection<int, ESBTPEtudiant> $pupils */
        $pupils = $parent->pupilles()->orderBy('esbtp_etudiants.id')->get();
        $first = $pupils
            ->map(fn (ESBTPEtudiant $pupil): string => $this->sanitize((string) $pupil->nom_complet))
            ->first(fn (string $name): bool => $name !== '');

        if ($first === null) {
            return self::FALLBACK_STUDENT;
        }

        $others = max(0, $pupils->count() - 1);
        if ($others === 0) {
            return $first;
        }

        return $this->sanitize($first.($others === 1 ? ' et 1 autre enfant' : " et {$others} autres enfants"));
    }

    private function sanitize(string $value): string
    {
        // Braces are the template marker syntax on the transport side, which
        // refuses a rendered body still containing one. A tutor whose record
        // holds a stray brace would otherwise never receive the invitation.
        $withoutBraces = str_replace(['{', '}'], '', $value);
        $collapsed = trim((string) preg_replace('/\s+/u', ' ', $withoutBraces));

        return mb_strlen($collapsed) <= self::MAX_PARAMETER_LENGTH
            ? $collapsed
            : rtrim(mb_substr($collapsed, 0, self::MAX_PARAMETER_LENGTH - 1)).'…';
    }
}
