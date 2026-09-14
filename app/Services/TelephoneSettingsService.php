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
                'validation_rules' => ['nullable', 'regex:/^\+?[0-9]{1,3}$/'],
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

    /**
     * Ce que l'écran doit montrer : la valeur ÉCRITE, et ce qui s'applique.
     *
     * Les deux champs affichaient jusqu'ici la valeur effective, celle que rend
     * le résolveur. C'est le repli, pas le réglage — et sur une valeur illisible
     * les deux divergent sans que rien ne le dise. Une école qui aurait saisi
     * « Bénin » dans l'indicatif voyait `225` s'afficher : l'écran lui confirmait
     * un réglage qu'elle n'avait pas, pendant que ses relances partaient en
     * Côte d'Ivoire. C'est le repli silencieux que `rien-en-dur.md` interdit.
     *
     * On rend donc les deux, et l'écran signale l'écart quand il y en a un.
     * Sur une instance saine ils coïncident, et rien ne change à l'affichage.
     *
     * @return array<string, array{stockee: string, appliquee: string, ignoree: bool}>
     */
    public function etatAffichable(): array
    {
        $etat = [];

        foreach ([
            PhoneNormalizer::CLE_INDICATIF => PhoneNormalizer::indicatifNationalParDefaut(),
            PhoneNormalizer::CLE_PREFIXES => implode(',', PhoneNormalizer::prefixesNationaux()),
        ] as $cle => $appliquee) {
            $stockee = $this->normaliser($this->valeurStockee($cle));

            $etat[$cle] = [
                'stockee' => $stockee ?? '',
                'appliquee' => $appliquee,
                'ignoree' => $this->estIgnoree($stockee, $appliquee),
            ];
        }

        return $etat;
    }

    /**
     * La valeur écrite a-t-elle été écartée par le résolveur ?
     *
     * Une valeur vide n'est pas « ignorée » : c'est une absence, et le défaut
     * livré est alors la réponse attendue — l'annoncer comme une anomalie
     * ferait crier le garde-fou sur les huit instances.
     *
     * N'est ignorée que la valeur écrite qui ne se retrouve PAS dans ce que le
     * résolveur rend. C'est la seule preuve fiable qu'il l'a écartée, et elle
     * ne duplique aucune des règles de `PhoneNormalizer` : refaire ici son test
     * de longueur les ferait diverger au premier changement là-bas.
     *
     * La comparaison porte sur les listes de chiffres, parce que `+229` et
     * `229`, ou `01 ; 02` et `01,02`, sont la même chose : une normalisation
     * n'est pas un rejet, et la signaler serait un faux positif.
     */
    private function estIgnoree(?string $stockee, string $appliquee): bool
    {
        if ($stockee === null) {
            return false;
        }

        return $this->decouper($this->chiffresSeuls($stockee))
            !== $this->decouper($this->chiffresSeuls($appliquee));
    }

    /**
     * Réduit une saisie à ses groupes de chiffres, séparateurs conservés.
     *
     * `+229` devient `229`, `01 ; 02` devient `01 02`, `Bénin` devient la chaîne
     * vide — donc une valeur qui ne porte aucun chiffre ne peut jamais coïncider
     * avec ce que le résolveur rend, et ressort bien comme ignorée.
     */
    private function chiffresSeuls(string $valeur): string
    {
        return trim((string) preg_replace('/[^0-9]+/', ' ', $valeur));
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
