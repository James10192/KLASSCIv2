<?php

namespace App\Domain\Assistant\Reglages;

use App\Domain\Assistant\Cles\CoffreDesCles;
use App\Domain\Assistant\Consommation\BudgetAssistant;
use App\Domain\Assistant\Routage\Routeur;
use App\Domain\Assistant\Diagnostic\DiagnosticAssistant;
use App\Domain\Assistant\Modeles\ModeleIa;
use App\Domain\Assistant\Modeles\RegistreDesModeles;
use App\Helpers\SettingsHelper;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

/**
 * Ce que l'école règle elle-même pour son assistant : les clés des
 * fournisseurs et le modèle par défaut. Servi à l'identique par l'écran des
 * réglages et par klassci-cli, pour que les deux ne divergent jamais.
 */
class ReglagesAssistant
{
    public const CLE_MODELE_DEFAUT = 'assistant.modele_defaut';
    public const CLE_BUDGET = 'assistant.budget_mensuel_fcfa';

    public function __construct(
        private CoffreDesCles $coffre,
        private RegistreDesModeles $registre,
        private DiagnosticAssistant $diagnostic,
    ) {
    }

    /** @return array<string, mixed> état complet, sans aucune clé en clair */
    public function etat(): array
    {
        $candidats = $this->registre->candidats();

        return [
            'fournisseurs' => $this->coffre->etat(),
            'modeles' => array_values(array_map(fn (ModeleIa $m) => [
                'cle' => $m->cle,
                'libelle' => $m->libelle,
                'fournisseur' => $m->fournisseur,
                'identifiant' => $m->identifiant,
                'configure' => $m->estConfigure(),
            ], $this->registre->tous())),
            'modele_defaut' => $this->registre->defaut(),
            'modele_effectif' => $candidats[0]->cle ?? null,
            // Routage automatique : chaque palier avec ses modèles réellement joignables.
            'paliers' => array_map(
                fn (array $cles) => array_values(array_filter($cles, fn ($c) => isset($this->registre->disponibles()[$c]))),
                app(Routeur::class)->paliers()
            ),
            'budget' => [
                'mensuel_fcfa' => app(BudgetAssistant::class)->budgetMensuelFcfa(),
                'depense_du_mois_fcfa' => round(app(BudgetAssistant::class)->depenseDuMois(), 2),
                'etat' => app(BudgetAssistant::class)->etat(),
            ],
        ];
    }

    public function poserCle(string $fournisseur, string $cle, ?int $auteurId): void
    {
        $this->coffre->definir($fournisseur, $cle, $auteurId);
    }

    public function retirerCle(string $fournisseur, ?int $auteurId): void
    {
        $this->coffre->retirer($fournisseur, $auteurId);
    }

    public function choisirModele(string $cle): void
    {
        if (!array_key_exists($cle, $this->registre->tous())) {
            throw new InvalidArgumentException("Modèle inconnu : {$cle}.");
        }

        SettingsHelper::setOrCreate(self::CLE_MODELE_DEFAUT, $cle, 'assistant', 'string');
        Cache::forget('setting_' . self::CLE_MODELE_DEFAUT);
    }

    /** Budget mensuel en FCFA ; 0 retire la limite. */
    public function definirBudget(float $fcfa): void
    {
        if ($fcfa < 0) {
            throw new InvalidArgumentException('Le budget ne peut pas être négatif.');
        }

        SettingsHelper::setOrCreate(self::CLE_BUDGET, (string) $fcfa, 'assistant', 'string');
        Cache::forget('setting_' . self::CLE_BUDGET);
        app(BudgetAssistant::class)->oublier();
    }

    /** @return array<string, mixed> */
    public function tester(?string $modele = null): array
    {
        if ($modele !== null && !array_key_exists($modele, $this->registre->tous())) {
            throw new InvalidArgumentException("Modèle inconnu : {$modele}.");
        }

        return $this->diagnostic->tester($modele);
    }
}
