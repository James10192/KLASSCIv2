<?php

namespace App\Domain\Assistant\Modeles;

use App\Domain\Assistant\Cles\CoffreDesCles;
use App\Helpers\SettingsHelper;
use Illuminate\Support\Facades\Log;

/**
 * Registre des modèles : lit config/assistant.php et les réglages d'instance.
 *
 * Aucun modèle n'est écrit dans le code. L'école choisit, par ses réglages
 * (`assistant.modele_defaut`, `assistant.modeles_autorises`) ou par son .env :
 *   - le modèle par défaut ;
 *   - la liste des modèles autorisés (vide = tous ceux du registre) ;
 *   - la chaîne de repli essayée quand un fournisseur tombe ou n'a pas de clé.
 */
class RegistreDesModeles
{
    /** @return array<string, ModeleIa> tous les modèles déclarés, configurés ou non */
    public function tous(): array
    {
        $fournisseurs = (array) config('assistant.fournisseurs', []);
        $coffre = app(CoffreDesCles::class);
        $clesPosees = [];
        $modeles = [];

        foreach ((array) config('assistant.modeles', []) as $cle => $def) {
            $fournisseur = (string) ($def['fournisseur'] ?? '');
            $conf = $fournisseurs[$fournisseur] ?? null;
            if (!$conf || empty($def['modele'])) {
                continue;
            }

            $modeles[$cle] = new ModeleIa(
                cle: (string) $cle,
                fournisseur: $fournisseur,
                adaptateur: (string) ($conf['adaptateur'] ?? $fournisseur),
                identifiant: (string) $def['modele'],
                libelle: (string) ($def['libelle'] ?? $cle),
                outils: (bool) ($def['outils'] ?? true),
                diffusion: (bool) ($def['diffusion'] ?? true),
                // Une clé posée par l'école (réglages, CLI) prime sur celle du .env.
                cleApi: ($clesPosees[$fournisseur] ??= ($coffre->lire($fournisseur) ?? '')) ?: ($conf['cle'] ?? null),
                url: rtrim((string) ($conf['url'] ?? ''), '/') . '/',
            );
        }

        return $modeles;
    }

    public function defaut(): string
    {
        return (string) ($this->reglage('assistant.modele_defaut') ?: config('assistant.modele_defaut', ''));
    }

    /** @return string[] clés autorisées ; vide = tout le registre */
    public function clesAutorisees(): array
    {
        $valeur = $this->reglage('assistant.modeles_autorises') ?: config('assistant.modeles_autorises', []);
        $liste = is_array($valeur) ? $valeur : explode(',', (string) $valeur);

        return array_values(array_filter(array_map('trim', $liste)));
    }

    /** @return array<string, ModeleIa> modèles autorisés ET munis d'une clé */
    public function disponibles(): array
    {
        $autorises = $this->clesAutorisees();

        return array_filter($this->tous(), function (ModeleIa $modele) use ($autorises) {
            return $modele->estConfigure() && ($autorises === [] || in_array($modele->cle, $autorises, true));
        });
    }

    /**
     * Ordre d'essai pour un échange : le modèle demandé (s'il est disponible),
     * puis le défaut, puis la chaîne de repli, sans doublon.
     *
     * @return ModeleIa[]
     */
    public function candidats(?string $demande = null): array
    {
        $disponibles = $this->disponibles();
        $ordre = array_merge(
            $demande ? [$demande] : [],
            [$this->defaut()],
            (array) config('assistant.repli', [])
        );

        $candidats = [];
        foreach ($ordre as $cle) {
            if (isset($disponibles[$cle]) && !isset($candidats[$cle])) {
                $candidats[$cle] = $disponibles[$cle];
            }
        }

        // Ni le défaut ni la chaîne n'ont de clé : le premier modèle réellement
        // configuré vaut mieux qu'un assistant muet.
        if ($candidats === [] && $disponibles !== []) {
            $candidats[] = reset($disponibles);
        }

        return array_values($candidats);
    }

    protected function reglage(string $cle): mixed
    {
        try {
            return SettingsHelper::get($cle);
        } catch (\Throwable $e) {
            Log::warning('assistant.reglage_illisible', ['cle' => $cle, 'erreur' => $e->getMessage()]);
            return null;
        }
    }
}
