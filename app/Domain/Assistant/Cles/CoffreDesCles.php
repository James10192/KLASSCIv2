<?php

namespace App\Domain\Assistant\Cles;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Clés d'API des fournisseurs d'IA, posées par l'école (écran des réglages ou
 * klassci-cli) plutôt que dans le .env du serveur.
 *
 * Stockées CHIFFRÉES dans leur propre table (`assistant_cles`), hors des
 * réglages : les exports, sauvegardes, journaux et lectures génériques des
 * réglages n'y ont pas accès. Jamais renvoyées au navigateur ni au CLI : on
 * n'expose que leur état et leurs quatre derniers caractères. Une clé posée
 * ici prime sur celle du .env.
 */
class CoffreDesCles
{
    /** @var array<string, ?string> lecture mémorisée le temps d'une requête */
    private array $memo = [];

    /** @return string[] fournisseurs déclarés dans config/assistant.php */
    public function fournisseurs(): array
    {
        return array_keys((array) config('assistant.fournisseurs', []));
    }

    public function lire(string $fournisseur): ?string
    {
        if (array_key_exists($fournisseur, $this->memo)) {
            return $this->memo[$fournisseur];
        }

        try {
            $cle = CleFournisseur::where('fournisseur', $fournisseur)->first()?->cle;
        } catch (DecryptException $e) {
            // APP_KEY a changé depuis la pose : la clé est perdue, pas corrompue.
            Log::error('assistant.cle_illisible', ['fournisseur' => $fournisseur]);
            $cle = null;
        } catch (\Throwable $e) {
            // Table absente (déploiement pas encore migré, tests sans schéma).
            $cle = null;
        }

        return $this->memo[$fournisseur] = (is_string($cle) && $cle !== '') ? $cle : null;
    }

    public function definir(string $fournisseur, string $cle, ?int $auteurId = null): void
    {
        $this->verifierFournisseur($fournisseur);
        $cle = trim($cle);
        if ($cle === '' || mb_strlen($cle) > 500 || preg_match('/\s/', $cle)) {
            throw new InvalidArgumentException('La clé est vide, trop longue ou contient des espaces.');
        }

        CleFournisseur::updateOrCreate(['fournisseur' => $fournisseur], ['cle' => $cle, 'updated_by' => $auteurId]);
        unset($this->memo[$fournisseur]);

        Log::info('assistant.cle_posee', ['fournisseur' => $fournisseur, 'par' => $auteurId]);
    }

    public function retirer(string $fournisseur, ?int $auteurId = null): void
    {
        $this->verifierFournisseur($fournisseur);
        CleFournisseur::where('fournisseur', $fournisseur)->delete();
        unset($this->memo[$fournisseur]);

        Log::info('assistant.cle_retiree', ['fournisseur' => $fournisseur, 'par' => $auteurId]);
    }

    /**
     * État de chaque fournisseur, sans jamais la clé.
     *
     * @return array<string, array{libelle: string, source: string, fin: ?string}>
     *   source : « reglages » (posée par l'école), « env » (serveur) ou « aucune »
     */
    public function etat(): array
    {
        $etat = [];
        foreach ((array) config('assistant.fournisseurs', []) as $fournisseur => $conf) {
            $posee = $this->lire($fournisseur);
            $env = $conf['cle'] ?? null;
            $cle = $posee ?? (is_string($env) && $env !== '' ? $env : null);

            $etat[$fournisseur] = [
                'libelle' => (string) ($conf['libelle'] ?? $fournisseur),
                'source' => $posee !== null ? 'reglages' : ($cle !== null ? 'env' : 'aucune'),
                'fin' => $cle !== null ? mb_substr($cle, -4) : null,
            ];
        }

        return $etat;
    }

    private function verifierFournisseur(string $fournisseur): void
    {
        if (!in_array($fournisseur, $this->fournisseurs(), true)) {
            throw new InvalidArgumentException("Fournisseur inconnu : {$fournisseur}.");
        }
    }
}
