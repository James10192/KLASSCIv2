<?php

declare(strict_types=1);

namespace App\Domain\Bulletins;

use App\Helpers\SettingsHelper;

/**
 * Dit si la moyenne générale et le taux de réussite de /esbtp/resultats sont
 * bons, à surveiller ou en alerte, pour que la couleur le montre d'un coup d'œil.
 *
 * Le seuil de réussite (10/20) est celui du calcul du taux : il ne se règle pas
 * ici, il se lit. Les seuils « satisfaisant » et « alerte », eux, sont des
 * repères d'établissement : une école s'inquiète sous 60 % de réussite, une
 * autre sous 40 %. Les clés sont semées par la migration
 * `add_seuils_des_resultats_settings` et exposées dans /esbtp/settings.
 */
final class EtatDesResultats
{
    /** Moyenne à partir de laquelle un élève est compté reçu dans le taux de réussite. */
    public const SEUIL_REUSSITE = 10.0;

    public const REGLAGE_MOYENNE_SATISFAISANTE = 'resultats.moyenne_satisfaisante';
    public const REGLAGE_REUSSITE_SATISFAISANTE = 'resultats.reussite_satisfaisante_pct';
    public const REGLAGE_REUSSITE_ALERTE = 'resultats.reussite_alerte_pct';

    public const MOYENNE_SATISFAISANTE_REPLI = 12.0;
    public const REUSSITE_SATISFAISANTE_REPLI = 70;
    public const REUSSITE_ALERTE_REPLI = 50;

    public const BON = 'bon';
    public const A_SURVEILLER = 'a_surveiller';
    public const ALERTE = 'alerte';

    /**
     * Ajoute aux indicateurs l'état de la moyenne et du taux de réussite.
     * Un indicateur sans valeur (aucune moyenne encore) n'a pas d'état : une
     * page vide ne doit ni rassurer ni alarmer.
     *
     * @param  array<string, mixed>  $kpis
     * @return array<string, mixed>
     */
    public function completer(array $kpis): array
    {
        $kpis['etats'] = [
            'moyenne_generale' => $this->etatDeLaMoyenne($kpis['moyenne_generale'] ?? null),
            'taux_reussite' => $this->etatDeLaReussite($kpis['taux_reussite'] ?? null),
        ];

        return $kpis;
    }

    public function etatDeLaMoyenne($moyenne): ?string
    {
        if (! is_numeric($moyenne)) {
            return null;
        }

        if ((float) $moyenne < self::SEUIL_REUSSITE) {
            return self::ALERTE;
        }

        return (float) $moyenne >= $this->moyenneSatisfaisante() ? self::BON : self::A_SURVEILLER;
    }

    public function etatDeLaReussite($taux): ?string
    {
        if (! is_numeric($taux)) {
            return null;
        }

        [$alerte, $satisfaisant] = $this->seuilsDeReussite();

        if ((float) $taux < $alerte) {
            return self::ALERTE;
        }

        return (float) $taux >= $satisfaisant ? self::BON : self::A_SURVEILLER;
    }

    public function moyenneSatisfaisante(): float
    {
        $brut = SettingsHelper::get(self::REGLAGE_MOYENNE_SATISFAISANTE, self::MOYENNE_SATISFAISANTE_REPLI);

        if (! is_numeric($brut) || (float) $brut < self::SEUIL_REUSSITE || (float) $brut > 20) {
            return self::MOYENNE_SATISFAISANTE_REPLI;
        }

        return (float) $brut;
    }

    /**
     * Les deux seuils se lisent ensemble : un « alerte » au-dessus du
     * « satisfaisant » rendrait tout taux soit rouge soit vert, sans orange.
     * Saisis dans le désordre, ils retombent tous deux sur leur repli.
     *
     * @return array{0: int, 1: int} [alerte, satisfaisant]
     */
    public function seuilsDeReussite(): array
    {
        $alerte = $this->pourcentage(self::REGLAGE_REUSSITE_ALERTE, self::REUSSITE_ALERTE_REPLI);
        $satisfaisant = $this->pourcentage(self::REGLAGE_REUSSITE_SATISFAISANTE, self::REUSSITE_SATISFAISANTE_REPLI);

        if ($alerte >= $satisfaisant) {
            return [self::REUSSITE_ALERTE_REPLI, self::REUSSITE_SATISFAISANTE_REPLI];
        }

        return [$alerte, $satisfaisant];
    }

    private function pourcentage(string $cle, int $repli): int
    {
        $brut = SettingsHelper::get($cle, $repli);

        if (! is_numeric($brut) || (int) $brut < 0 || (int) $brut > 100) {
            return $repli;
        }

        return (int) $brut;
    }
}
