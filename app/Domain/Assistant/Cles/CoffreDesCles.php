<?php

namespace App\Domain\Assistant\Cles;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Clés d'API des fournisseurs d'IA, posées par l'école (écran des réglages ou
 * klassci-cli) plutôt que dans le .env du serveur.
 *
 * Stockées CHIFFRÉES (APP_KEY) dans la table des réglages, sous
 * `assistant_cle_<fournisseur>`. Jamais relues en clair ailleurs qu'ici,
 * jamais renvoyées au navigateur ni au CLI : on n'expose que leur état et
 * leurs quatre derniers caractères. Une clé posée ici prime sur celle du .env.
 */
class CoffreDesCles
{
    private const PREFIXE = 'assistant_cle_';

    /** @return string[] fournisseurs déclarés dans config/assistant.php */
    public function fournisseurs(): array
    {
        return array_keys((array) config('assistant.fournisseurs', []));
    }

    public function lire(string $fournisseur): ?string
    {
        $chiffree = $this->valeurStockee($fournisseur);
        if ($chiffree === null) {
            return null;
        }

        try {
            $cle = Crypt::decryptString($chiffree);
        } catch (DecryptException $e) {
            // APP_KEY a changé depuis la pose : la clé est perdue, pas corrompue.
            Log::error('assistant.cle_illisible', ['fournisseur' => $fournisseur]);
            return null;
        }

        return $cle !== '' ? $cle : null;
    }

    public function definir(string $fournisseur, string $cle, ?int $auteurId = null): void
    {
        $this->verifierFournisseur($fournisseur);
        $cle = trim($cle);
        if ($cle === '' || mb_strlen($cle) > 500 || preg_match('/\s/', $cle)) {
            throw new InvalidArgumentException('La clé est vide, trop longue ou contient des espaces.');
        }

        Setting::updateOrCreate(
            ['key' => self::PREFIXE . $fournisseur],
            [
                'value' => Crypt::encryptString($cle),
                'type' => 'string',
                'group' => 'assistant',
                'description' => 'Clé API ' . $fournisseur . ' (chiffrée)',
                'is_active' => true,
                'updated_by' => $auteurId,
            ]
        );
        $this->oublierCache($fournisseur);

        Log::info('assistant.cle_posee', ['fournisseur' => $fournisseur, 'par' => $auteurId]);
    }

    public function retirer(string $fournisseur, ?int $auteurId = null): void
    {
        $this->verifierFournisseur($fournisseur);
        Setting::where('key', self::PREFIXE . $fournisseur)->delete();
        $this->oublierCache($fournisseur);

        Log::info('assistant.cle_retiree', ['fournisseur' => $fournisseur, 'par' => $auteurId]);
    }

    /**
     * État de chaque fournisseur, sans jamais la clé.
     *
     * @return array<string, array{source: string, fin: ?string}>
     *   source : « reglages » (posée par l'école), « env » (serveur) ou « aucune »
     */
    public function etat(): array
    {
        $etat = [];
        foreach ($this->fournisseurs() as $fournisseur) {
            $posee = $this->lire($fournisseur);
            $env = config('assistant.fournisseurs.' . $fournisseur . '.cle');
            $cle = $posee ?? (is_string($env) && $env !== '' ? $env : null);

            $etat[$fournisseur] = [
                'source' => $posee !== null ? 'reglages' : ($cle !== null ? 'env' : 'aucune'),
                'fin' => $cle !== null ? mb_substr($cle, -4) : null,
            ];
        }

        return $etat;
    }

    private function valeurStockee(string $fournisseur): ?string
    {
        try {
            $valeur = Setting::where('key', self::PREFIXE . $fournisseur)
                ->where('is_active', true)
                ->value('value');
        } catch (\Throwable $e) {
            // Table absente (installation, tests sans schéma) : pas de clé posée.
            return null;
        }

        return is_string($valeur) && $valeur !== '' ? $valeur : null;
    }

    private function verifierFournisseur(string $fournisseur): void
    {
        if (!in_array($fournisseur, $this->fournisseurs(), true)) {
            throw new InvalidArgumentException("Fournisseur inconnu : {$fournisseur}.");
        }
    }

    private function oublierCache(string $fournisseur): void
    {
        Cache::forget('setting_' . self::PREFIXE . $fournisseur);
    }
}
