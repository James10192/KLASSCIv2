<?php

namespace App\Domain\Admissions;

use App\Domain\Notifications\PhoneFormatter;
use App\Models\ESBTPParent;
use Illuminate\Database\Eloquent\Builder;

/**
 * Retrouver un parent deja enregistre, pour le rattacher a un nouvel eleve
 * au lieu d'en creer un double.
 *
 * Un parent revient a chaque enfant inscrit, et sa fiche a souvent ete saisie
 * autrement la premiere fois : nom et prenoms inverses, telephone avec ou sans
 * indicatif, espaces, apostrophe. On cherche donc chaque mot dans le nom ou les
 * prenoms (la collation ignore accents et casse), et un telephone par ses huit
 * derniers chiffres, quelle que soit sa mise en forme.
 */
class RechercheParents
{
    public const MAX = 8;

    /** Huit chiffres distinguent un numero, avec ou sans indicatif ni zero initial. */
    private const CHIFFRES_COMPARES = 8;

    /** @return list<array<string, mixed>> */
    public function chercher(string $saisie, int $max = self::MAX): array
    {
        $saisie = trim(mb_substr($saisie, 0, 120));
        $chiffres = preg_replace('/\D/', '', $saisie);
        $lettres = trim((string) preg_replace('/[\d+]+/', ' ', $saisie));

        if (strlen($chiffres) < 6 && mb_strlen($lettres) < 2) {
            return [];
        }

        $requete = ESBTPParent::query();
        if (strlen($chiffres) >= 6) {
            $this->parTelephone($requete, $chiffres);
        }
        if (mb_strlen($lettres) >= 2) {
            $this->parNom($requete, $lettres);
        }

        return $this->presenter($requete, $max, $chiffres);
    }

    /**
     * Les parents qui ressemblent au tuteur declare sur la candidature : meme
     * telephone, ou meme nom complet. Proposes d'office dans la fenetre.
     *
     * @return list<array<string, mixed>>
     */
    public function proches(?string $nomDeclare, ?string $telephone, int $max = 4): array
    {
        $chiffres = preg_replace('/\D/', '', (string) $telephone);
        $nom = trim((string) $nomDeclare);
        $parTelephone = strlen($chiffres) >= 6;
        // Un seul mot designerait toute une famille : on ne propose par le nom
        // qu'un nom complet.
        $parNom = count($this->mots($nom)) >= 2;

        if (! $parTelephone && ! $parNom) {
            return [];
        }

        $requete = ESBTPParent::query()->where(function (Builder $w) use ($parTelephone, $parNom, $chiffres, $nom) {
            if ($parTelephone) {
                $w->orWhere(fn (Builder $t) => $this->parTelephone($t, $chiffres));
            }
            if ($parNom) {
                $w->orWhere(fn (Builder $n) => $this->parNom($n, $nom));
            }
        });

        return $this->presenter($requete, $max, $chiffres);
    }

    private function parTelephone(Builder $requete, string $chiffres): void
    {
        $fin = substr($chiffres, -self::CHIFFRES_COMPARES);
        $requete->whereRaw(
            "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(telephone, ''), ' ', ''), '-', ''), '.', ''), '+', ''), '/', '') LIKE ?",
            ['%'.$fin]
        );
    }

    private function parNom(Builder $requete, string $texte): void
    {
        foreach ($this->mots($texte) as $mot) {
            $like = '%'.addcslashes($mot, '%_\\').'%';
            $requete->where(fn (Builder $champ) => $champ
                ->where('nom', 'like', $like)
                ->orWhere('prenoms', 'like', $like)
                // « NGUESSAN » retrouve « N'GUESSAN ».
                ->orWhereRaw("REPLACE(REPLACE(REPLACE(CONCAT(COALESCE(nom, ''), COALESCE(prenoms, '')), '''', ''), '-', ''), ' ', '') LIKE ?", [$like]));
        }
    }

    /** @return list<string> */
    private function mots(string $texte): array
    {
        $texte = str_replace(["\u{2019}", "'"], '', $texte);

        return array_values(array_filter(
            preg_split('/[\s\-]+/u', $texte) ?: [],
            fn (string $m) => mb_strlen($m) >= 2
        ));
    }

    /** @return list<array<string, mixed>> */
    private function presenter(Builder $requete, int $max, string $chiffres): array
    {
        $fin = strlen($chiffres) >= 6 ? substr($chiffres, -self::CHIFFRES_COMPARES) : null;

        return $requete
            ->with(['etudiants' => fn ($e) => $e->select('esbtp_etudiants.id', 'nom', 'prenoms')])
            ->orderBy('nom')->orderBy('prenoms')
            ->limit($max)
            ->get(['id', 'nom', 'prenoms', 'telephone', 'profession'])
            ->map(fn (ESBTPParent $p) => [
                'id' => (int) $p->id,
                'nom' => (string) $p->nom,
                'prenoms' => (string) $p->prenoms,
                'telephone' => PhoneFormatter::toReadable($p->telephone) ?: (string) $p->telephone,
                'profession' => (string) $p->profession,
                // Les enfants deja rattaches aident a reconnaitre le bon parent
                // parmi des homonymes.
                'enfants' => $p->etudiants->map(fn ($e) => trim($e->nom.' '.$e->prenoms))->take(3)->values()->all(),
                'nb_enfants' => $p->etudiants->count(),
                'meme_telephone' => $fin !== null && str_ends_with(preg_replace('/\D/', '', (string) $p->telephone), $fin),
            ])->values()->all();
    }
}
