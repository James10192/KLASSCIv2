<?php

namespace App\Domain\Admissions;

use App\Services\Portail\ReferencePublique;
use Illuminate\Database\Eloquent\Builder;

/**
 * La recherche de la liste des dossiers : un nom (dans un ordre ou dans
 * l'autre), un telephone (six chiffres au moins, espaces et indicatif
 * ignores), une reference donnee a la famille, et le matricule d'un ancien.
 *
 * Une candidature porte elle-meme l'identite declaree ; une demande de
 * reinscription la lit sur la fiche de l'etudiant.
 */
final class RechercheDesDossiers
{
    public function __construct(private readonly ReferencePublique $references)
    {
    }

    public function candidatures(Builder $q, string $texte): void
    {
        [$like, $reference, $chiffres] = $this->termes($texte) ?? [null, null, null];
        if ($like === null) {
            return;
        }

        $q->where(fn ($w) => $w->where('nom', 'like', $like)->orWhere('prenoms', 'like', $like)
            ->orWhereRaw("CONCAT(nom, ' ', prenoms) LIKE ?", [$like])
            ->orWhereRaw("CONCAT(prenoms, ' ', nom) LIKE ?", [$like])
            ->orWhere('email', 'like', $like)
            ->when($reference !== '', fn ($w) => $w->orWhere('reference_publique', $reference))
            ->when(strlen($chiffres) >= 6, fn ($w) => $w->orWhere('telephone', 'like', '%'.$chiffres.'%')));
    }

    public function reinscriptions(Builder $q, string $texte): void
    {
        [$like, $reference, $chiffres] = $this->termes($texte) ?? [null, null, null];
        if ($like === null) {
            return;
        }

        $q->where(fn ($w) => $w
            ->whereHas('etudiant', fn ($e) => $e->where(fn ($e) => $e->where('nom', 'like', $like)->orWhere('prenoms', 'like', $like)
                ->orWhere('matricule', 'like', $like)
                ->orWhereRaw("CONCAT(nom, ' ', prenoms) LIKE ?", [$like])
                ->orWhereRaw("CONCAT(prenoms, ' ', nom) LIKE ?", [$like])
                ->when(strlen($chiffres) >= 6, fn ($e) => $e->orWhere('telephone', 'like', '%'.$chiffres.'%'))))
            ->when($reference !== '', fn ($w) => $w->orWhere('reference_publique', $reference)));
    }

    /** @return array{0: string, 1: string, 2: string}|null motif LIKE, reference normalisee, chiffres ; null si rien a chercher */
    private function termes(string $texte): ?array
    {
        $texte = trim($texte);
        if ($texte === '') {
            return null;
        }

        return [
            '%'.addcslashes($texte, '%_\\').'%',
            $this->references->normaliser($texte),
            (string) preg_replace('/\D/', '', $texte),
        ];
    }
}
