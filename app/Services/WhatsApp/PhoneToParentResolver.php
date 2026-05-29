<?php

namespace App\Services\WhatsApp;

use App\Domain\Notifications\PhoneNormalizer;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPParent;
use Illuminate\Support\Collection;

/**
 * Resolver phone → parent → étudiant pour le chat 2-way (Phase 7 + Phase 11 routing).
 *
 * Cherche le parent ESBTPParent par téléphone normalisé E.164, puis retourne :
 *  - Le parent
 *  - La liste de ses étudiants rattachés (relation belongsToMany esbtp_etudiant_parent)
 *  - L'étudiant principal (tuteur principal) si désigné via pivot.is_tuteur
 *
 * Si aucun parent trouvé OU plusieurs étudiants ambigus, retourne null/array
 * → message orphelin assigné manuellement à secrétaire (Phase 7 UI inbox).
 */
class PhoneToParentResolver
{
    /**
     * @return array{
     *     parent: ESBTPParent|null,
     *     etudiants: Collection<int, ESBTPEtudiant>,
     *     primary_etudiant: ESBTPEtudiant|null,
     *     ambiguous: bool
     * }
     */
    public function resolve(string $rawPhone): array
    {
        $normalized = PhoneNormalizer::normalize($rawPhone) ?? $rawPhone;

        // Lookup parent par téléphone (essai E.164 puis raw)
        $parent = ESBTPParent::where('telephone', $normalized)
            ->orWhere('telephone', $rawPhone)
            ->orWhere('telephone_secondaire', $normalized)
            ->orWhere('telephone_secondaire', $rawPhone)
            ->first();

        if (! $parent) {
            return [
                'parent' => null,
                'etudiants' => collect(),
                'primary_etudiant' => null,
                'ambiguous' => false,
            ];
        }

        $etudiants = $parent->etudiants()
            ->whereHas('inscriptions', fn ($q) => $q->where('status', 'active'))
            ->get();

        // Étudiant principal = tuteur principal via pivot, sinon premier si unique
        $primary = $etudiants->first(fn ($e) => $e->pivot->is_tuteur ?? false)
            ?? ($etudiants->count() === 1 ? $etudiants->first() : null);

        return [
            'parent' => $parent,
            'etudiants' => $etudiants,
            'primary_etudiant' => $primary,
            'ambiguous' => $etudiants->count() > 1 && ! $primary,
        ];
    }
}
