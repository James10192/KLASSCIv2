<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Notifications\PhoneNormalizer;
use App\Models\Setting;

/**
 * Les deux réglages qui disent comment lire un numéro saisi sans indicatif.
 *
 * Ils vont par paire, et c'est tout l'intérêt de les tenir ici plutôt que dans
 * la boucle générique de l'écran des réglages : celle-ci traite chaque champ
 * isolément, et ne peut donc pas voir qu'un indicatif changé sans ses préfixes
 * laisse l'instance dans un état qui accepte des numéros inexistants.
 *
 * Ils ne sont volontairement PAS dans `SettingsSeeder` : celui-ci écrit en
 * `updateOrCreate` sur tout son bloc, donc le relancer ramènerait une instance
 * béninoise à l'indicatif 225.
 */
final class TelephoneSettingsService
{
    /**
     * Crée les deux réglages s'ils manquent, sans jamais écraser ce qui est posé.
     *
     * Sans ce `firstOrCreate`, ces réglages n'auraient AUCUN chemin d'écriture
     * sur une instance vivante, et le correctif béninois serait resté
     * inactivable alors même qu'il était déployé.
     *
     * La ligne en base ne suffit PAS non plus à faire apparaître le champ :
     * l'écran des réglages n'énumère pas la table, il déclare ses champs un par
     * un en `name="setting_<clé>"`. Les deux sont donc posés dans l'onglet
     * Général, à côté du téléphone de l'école. Ce `firstOrCreate` reste
     * nécessaire pour autant : la boucle d'enregistrement ignore un `setting_*`
     * dont la ligne n'existe pas.
     */
    public function ensureDefaults(): void
    {
        $defauts = [
            PhoneNormalizer::CLE_INDICATIF => [
                'value' => PhoneNormalizer::INDICATIF_PAR_DEFAUT,
                'description' => 'Indicatif pays des numéros saisis sans indicatif (225 = Côte d’Ivoire, 229 = Bénin)',
                // Le premier chiffre ne peut pas être zéro : l'UIT-T E.164
                // répartit les indicatifs en neuf zones, de 1 à 9. Sans cette
                // borne, `0` passait la validation et produisait ensuite
                // `+00707123456` — une chaîne qui a la forme de l'E.164 sans
                // en être une. `PhoneNormalizer` le refuse aussi de son côté ;
                // ici on évite simplement de l'écrire en base.
                'validation_rules' => ['nullable', 'regex:/^\+?[1-9][0-9]{0,2}$/'],
                'sort_order' => 4,
            ],
            PhoneNormalizer::CLE_PREFIXES => [
                'value' => PhoneNormalizer::PREFIXES_PAR_DEFAUT,
                'description' => 'Préfixes qu’un numéro national peut porter ici, séparés par des virgules (Côte d’Ivoire : 01,02,03,05,06,07,08,09 — Bénin : 01)',
                'validation_rules' => ['nullable', 'regex:/^[0-9]{1,4}([ ,;|]+[0-9]{1,4})*$/'],
                'sort_order' => 5,
            ],
        ];

        foreach ($defauts as $cle => $attrs) {
            Setting::firstOrCreate(
                ['key' => $cle],
                [
                    'value' => $attrs['value'],
                    'type' => 'string',
                    'group' => 'general',
                    'category' => 'general',
                    'description' => $attrs['description'],
                    'is_required' => false,
                    'default_value' => $attrs['value'],
                    'validation_rules' => $attrs['validation_rules'],
                    'sort_order' => $attrs['sort_order'],
                ]
            );
        }
    }

    /**
     * Refuse un changement de pays qui laisse derrière lui les préfixes livrés.
     *
     * Le cas visé est celui d'`ucao-benin` : poser l'indicatif à 229 en laissant
     * `01,02,03,05,06,07,08,09` produit une instance qui accepte `0707123456` —
     * un préfixe que l'ARCEP Bénin n'attribue à personne depuis que la
     * renumérotation du 30 novembre 2024 a préfixé `01` à tous les numéros du
     * pays. Le numéro `+2290707123456` partirait, et n'arriverait nulle part,
     * sans erreur ni ligne au journal.
     *
     * Le contrôle ne se déclenche QUE si l'indicatif change dans cette
     * soumission. Ce n'est pas un invariant permanent, et c'est délibéré :
     * affirmer « ces préfixes sont faux pour ce pays » demanderait la table
     * « pays → préfixes » que `PhoneNormalizer` refuse justement de porter,
     * parce qu'elle serait fausse le jour de son écriture. Ici on n'affirme
     * rien sur le pays : on signale un oubli au seul moment où il se commet.
     * Une école qui veut réellement cette combinaison pose les deux champs
     * ensemble, puis reste libre de modifier les préfixes seuls plus tard.
     *
     * Portée assumée, et volontairement étroite : le contrôle ne reconnaît QUE
     * la liste livrée, parce qu'elle est la seule dont on sache à quel indicatif
     * elle appartient — 225. Un 229 → 233 qui oublierait ses préfixes ne serait
     * pas vu : le dire demanderait la table « pays → préfixes » qu'on refuse de
     * porter. C'est sans conséquence pratique aujourd'hui : les huit instances
     * partent du défaut livré, donc tout premier départ du plan ivoirien passe
     * bien par ce contrôle.
     *
     * @return string|null Le message à afficher, ou null si rien à signaler.
     */
    public function incoherenceDuChangementDePays(?string $indicatifSoumis, ?string $prefixesSoumis): ?string
    {
        $indicatifSoumis = $this->normaliser($indicatifSoumis);

        if ($indicatifSoumis === null) {
            return null; // Le champ n'est pas dans la soumission : rien ne change.
        }

        $indicatifActuel = $this->normaliser($this->valeurStockee(PhoneNormalizer::CLE_INDICATIF))
            ?? PhoneNormalizer::INDICATIF_PAR_DEFAUT;

        if ($indicatifSoumis === $indicatifActuel) {
            return null; // Pas de changement de pays : ce contrôle ne regarde pas.
        }

        if ($indicatifSoumis === PhoneNormalizer::INDICATIF_PAR_DEFAUT) {
            // On REVIENT à l'indicatif auquel la liste livrée appartient : la
            // combinaison est juste par construction. Sans cette sortie, une
            // école qui a posé 229 par erreur ne pourrait plus faire marche
            // arrière — le garde-fou lui interdirait le seul état correct.
            return null;
        }

        // Les préfixes tels qu'ils seront APRÈS enregistrement.
        $prefixesResultants = $this->normaliser($prefixesSoumis)
            ?? $this->normaliser($this->valeurStockee(PhoneNormalizer::CLE_PREFIXES))
            ?? PhoneNormalizer::PREFIXES_PAR_DEFAUT;

        if ($this->memeListe($prefixesResultants, PhoneNormalizer::PREFIXES_PAR_DEFAUT)) {
            return 'Changer l’indicatif sans toucher aux préfixes laisse l’instance sur le plan '
                .'ivoirien livré par défaut ('.PhoneNormalizer::PREFIXES_PAR_DEFAUT.'). Elle accepterait '
                .'alors des numéros que l’indicatif '.$indicatifSoumis.' ne peut pas porter, et les '
                .'messages partiraient sans arriver. Renseignez les deux champs ensemble '
                .'(Bénin : indicatif 229, préfixes 01).';
        }

        return null;
    }

    private function valeurStockee(string $cle): ?string
    {
        $valeur = Setting::where('key', $cle)->value('value');

        return is_string($valeur) ? $valeur : null;
    }

    private function normaliser(?string $valeur): ?string
    {
        if ($valeur === null) {
            return null;
        }

        $valeur = trim($valeur);

        return $valeur === '' ? null : $valeur;
    }

    /**
     * Compare deux listes de préfixes sans se laisser distraire par le séparateur
     * ni par l'ordre — le champ accepte espaces, virgules, points-virgules et
     * barres verticales, exactement comme `PhoneNormalizer::prefixesNationaux()`.
     */
    private function memeListe(string $a, string $b): bool
    {
        return $this->decouper($a) === $this->decouper($b);
    }

    /** @return list<string> */
    private function decouper(string $liste): array
    {
        $parts = preg_split('/[\s,;|]+/', trim($liste), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        sort($parts);

        return array_values(array_unique($parts));
    }
}
