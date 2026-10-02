<?php

namespace App\Domain\Assistant\Support;

/**
 * L'échange en cours entre la personne et Nanan, tel que l'écran le renvoie à
 * chaque tour. Le serveur ne garde rien entre deux tours : le fil fait foi, et
 * il est borné ici (nombre de messages, longueur de chacun) avant d'aller au
 * modèle ou dans la demande.
 *
 * Le contenu est celui de la personne : une donnée, jamais une instruction.
 */
final class FilDeSupport
{
    public const MESSAGES_MAX = 30;
    public const LONGUEUR_MAX = 1500;

    /** Ce que l'écran envoie quand la réponse de Nanan n'a pas suffi. */
    public const PAS_RESOLU = "Non, ça n'a pas résolu mon problème.";

    /** Les types d'un message de Nanan, tels que l'écran les a reçus (TourDeSupport::action). */
    private const TYPES = [TourDeSupport::QUESTION, TourDeSupport::REPONSE, TourDeSupport::RECAPITULATIF];

    /** @param list<array{role:string,texte:string,type?:string}> $messages */
    private function __construct(private readonly array $messages)
    {
    }

    /** Ce que le navigateur envoie, ramené à des messages propres. */
    public static function depuis(mixed $brut): self
    {
        $messages = [];
        foreach (is_array($brut) ? array_slice(array_values($brut), -self::MESSAGES_MAX) : [] as $m) {
            if (! is_array($m)) {
                continue;
            }
            $role = ($m['role'] ?? null) === 'nanan' ? 'nanan' : 'personne';
            $texte = is_string($m['texte'] ?? null) ? trim(self::sansControle($m['texte'])) : '';
            if ($texte === '') {
                continue;
            }
            $message = ['role' => $role, 'texte' => mb_substr($texte, 0, self::LONGUEUR_MAX)];
            if ($role === 'nanan' && in_array($m['type'] ?? null, self::TYPES, true)) {
                $message['type'] = $m['type'];
            }
            $messages[] = $message;
        }

        return new self($messages);
    }

    /** @return list<array{role:string,texte:string,type?:string}> */
    public function messages(): array
    {
        return $this->messages;
    }

    public function estVide(): bool
    {
        return $this->reponsesDeLaPersonne() === [];
    }

    /** Questions déjà posées par Nanan (ses tours, quels qu'ils soient). */
    public function toursDeNanan(): int
    {
        return count(array_filter($this->messages, fn ($m) => $m['role'] === 'nanan'));
    }

    /** @return list<string> */
    public function reponsesDeLaPersonne(): array
    {
        return array_values(array_map(
            fn ($m) => $m['texte'],
            array_filter($this->messages, fn ($m) => $m['role'] === 'personne')
        ));
    }

    /**
     * Le message de Nanan au rang `$i` est-il une réponse (une marche à suivre)
     * plutôt qu'une question ? L'écran le dit depuis octobre 2026 ; un brouillon
     * plus ancien ne le dit pas, et c'est alors la suite qui tranche : une
     * réponse est suivie de « ça n'a pas résolu », une question ne l'est jamais.
     */
    public function estUneReponseDeNanan(int $i): bool
    {
        $m = $this->messages[$i] ?? null;
        if ($m === null || $m['role'] !== 'nanan') {
            return false;
        }
        if (isset($m['type'])) {
            return $m['type'] === TourDeSupport::REPONSE;
        }

        return ($this->messages[$i + 1]['texte'] ?? null) === self::PAS_RESOLU;
    }

    /** La personne a dit que la réponse de Nanan ne l'avait pas aidée. */
    public function reponseInsuffisante(): bool
    {
        foreach ($this->messages as $i => $m) {
            if ($this->estUneReponseDeNanan($i) && ($this->messages[$i + 1]['texte'] ?? null) === self::PAS_RESOLU) {
                return true;
            }
        }

        return false;
    }

    /**
     * Le texte recopie-t-il une réponse de Nanan ? C'est le défaut que la
     * demande ne doit jamais avoir : le support y lirait la marche que Nanan a
     * proposée à la place de ce que la personne a demandé. Une réponse est
     * découpée en phrases ou en étapes ; deux d'entre elles retrouvées telles
     * quelles (une seule, si la réponse n'en a qu'une) suffisent.
     */
    public function recopieUneReponseDeNanan(string $texte): bool
    {
        $cible = self::normaliser($texte);
        foreach ($this->messages as $i => $m) {
            if (! $this->estUneReponseDeNanan($i)) {
                continue;
            }
            $morceaux = array_values(array_filter(
                array_map(fn ($p) => self::normaliser($p), preg_split('/(?<=[.!?:;])\s+|\R+/u', $m['texte']) ?: []),
                fn ($p) => mb_strlen($p) >= 25
            ));
            if ($morceaux === []) {
                continue;
            }
            $retrouves = count(array_filter($morceaux, fn ($p) => str_contains($cible, $p)));
            if ($retrouves >= min(2, count($morceaux))) {
                return true;
            }
        }

        return false;
    }

    /** Minuscules, puces et numéros d'étape retirés, espaces resserrés. */
    private static function normaliser(string $texte): string
    {
        $texte = mb_strtolower((string) preg_replace('/^\s*(?:\d+[.)]|[-•*])\s*/mu', '', $texte));

        return trim((string) preg_replace('/\s+/u', ' ', $texte));
    }

    public function premierMessage(): string
    {
        return $this->reponsesDeLaPersonne()[0] ?? '';
    }

    /**
     * Messages au format neutre des fournisseurs : l'échange commence par la
     * personne, et deux tours consécutifs d'un même rôle sont fusionnés.
     *
     * @return list<array{role:string,texte:string}>
     */
    public function pourLeModele(): array
    {
        $neutres = [];
        foreach ($this->messages as $m) {
            $role = $m['role'] === 'nanan' ? 'assistant' : 'user';
            if ($neutres === [] && $role !== 'user') {
                continue;
            }
            $dernier = array_key_last($neutres);
            if ($dernier !== null && $neutres[$dernier]['role'] === $role) {
                $neutres[$dernier]['texte'] .= "\n\n" . $m['texte'];
                continue;
            }
            $neutres[] = ['role' => $role, 'texte' => $m['texte']];
        }

        return $neutres;
    }

    /**
     * L'échange en clair, pour la demande transmise au support. Borné à
     * `$max` caractères : on garde le début (la question de départ) et la fin
     * (les dernières précisions), le milieu est marqué comme coupé.
     */
    public function transcription(int $max): string
    {
        if ($max <= 0 || $this->messages === []) {
            return '';
        }
        $lignes = array_map(
            fn ($m) => ($m['role'] === 'nanan' ? 'Nanan : ' : 'Personne : ') . $m['texte'],
            $this->messages
        );
        $texte = implode("\n", $lignes);
        if (mb_strlen($texte) <= $max) {
            return $texte;
        }

        $coupure = "\n[…]\n";
        $moitie = intdiv(max(0, $max - mb_strlen($coupure)), 2);

        return mb_substr($texte, 0, $moitie) . $coupure . mb_substr($texte, -$moitie);
    }

    /** Caractères de contrôle retirés, sauts de ligne gardés. */
    private static function sansControle(string $texte): string
    {
        return (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $texte);
    }
}
