<?php

namespace App\Enums;

use App\Services\Inscription\PortailCandidaturePublication;
use App\Services\RendezVous\RendezVousReglages;
use App\Services\Reinscription\PortailReinscriptionService;
use App\Support\SeauDeDebit;
use Illuminate\Support\Facades\Log;

/**
 * Les canaux du portail public, et ce qui les distingue.
 *
 * Ils partagent la signature du site vitrine et la fenetre saisonniere de la
 * rentree. Trois choses les separent, et elles vivaient jusqu'ici en
 * comparaisons de chaines eparpillees dans le garde : l'interrupteur qui les
 * ouvre, la phrase qu'ils rendent fermes, et leurs seaux de debit.
 *
 * Les seaux sont SEPARES par canal, et c'est le point qui coute le plus cher a
 * se tromper : partages, une affluence sur les candidatures fermait la
 * reinscription, et reciproquement. A la rentree d'Abidjan, cent vingt jetons
 * par minute pour l'etablissement entier plafonnaient les deux canaux ensemble
 * a une soixantaine de visiteurs par minute.
 *
 * Un troisieme canal est deja annonce au public — le portail propose « College
 * et lycee ». Ici, il sera un cas d'enum ; disperse en `=== '...'`, il aurait
 * ete quatre sites a retrouver, chacun capable de fusionner deux seaux ou
 * d'ouvrir un canal ferme sans que rien ne le signale.
 */
enum CanalPortailPublic: string
{
    case Reinscriptions = 'reinscriptions';

    case Candidatures = 'candidatures';

    case Rendezvous = 'rendezvous';

    /** Bornes de volume, comptees sur des valeurs signees. */
    private const MAX_PAR_ADRESSE_PAR_MINUTE = 10;

    /**
     * Plafond de l'ETABLISSEMENT, tous visiteurs confondus.
     *
     * A retenir, parce que ce n'est pas une protection gratuite : douze adresses
     * a leur propre plafond suffisent a l'atteindre, et le canal de l'ecole se
     * ferme alors pour tout le monde pendant une minute. Le refus le dit —
     * `affluence`, et non « vous avez trop essaye » — mais il reste un refus.
     *
     * Le chiffre vient du canal de reinscription, ou les depots s'etalent sur
     * des semaines. La candidature se comporte autrement : elle suit les
     * resultats du BAC, et le portail est desormais lie depuis la navigation du
     * site. A surveiller sur la premiere rentree reelle — et a rendre reglable
     * par etablissement le jour ou une ecole le heurte, plutot qu'a relever en
     * aveugle pour les six.
     */
    private const MAX_GLOBAL_PAR_MINUTE = 120;

    /**
     * Le catalogue compte a part, et beaucoup plus large.
     *
     * /choix ne sert que des noms de filieres, de niveaux et de nationalites :
     * aucune donnee d'etudiant n'y entre ni n'en sort, il n'y a rien a
     * enumerer, et deux SELECT indexes en repondent. Le faire compter dans les
     * memes seaux que les depots le rendait pourtant aussi couteux qu'eux — or
     * OUVRIR le formulaire consomme un jeton avant meme qu'on ait tape quoi que
     * ce soit.
     */
    private const MAX_CATALOGUE_PAR_ADRESSE_PAR_MINUTE = 30;

    private const MAX_CATALOGUE_GLOBAL_PAR_MINUTE = 600;

    /**
     * Un canal inconnu retombe sur la reinscription — mais JAMAIS en silence.
     *
     * Le repli evite un 500 sur une surface publique, ce qui est bien. Il ne
     * rend pas la faute visible pour autant, et c'est la que le raisonnement
     * pechait : `portail.public:candidature` — un « s » de moins dans le
     * fichier de routes — ferait gouverner les candidatures par l'interrupteur
     * de la reinscription et leur rendrait ses seaux. Si l'ecole a ouvert les
     * deux canaux, rien ne se voit : les depots repondent 201. Ce qui a change,
     * c'est que les cent vingt jetons par minute redeviennent partages, donc
     * qu'une affluence sur un canal referme l'autre — precisement la regression
     * que la separation des seaux existe pour empecher.
     *
     * D'ou le journal. Contrairement a NaturePortailPublic, ou se tromper coute
     * un seau trop etroit — visible, et sans danger — se tromper ici est
     * invisible et dangereux.
     */
    public static function depuis(string $valeur): self
    {
        $canal = self::tryFrom($valeur);

        if ($canal === null) {
            Log::error('Canal de portail public inconnu, repli sur la reinscription', [
                'valeur' => $valeur,
                'connus' => array_column(self::cases(), 'value'),
            ]);
        }

        return $canal ?? self::Reinscriptions;
    }

    public function ouvert(): bool
    {
        return match ($this) {
            self::Candidatures => app(PortailCandidaturePublication::class)->canalOuvert(),
            self::Reinscriptions => app(PortailReinscriptionService::class)->canalOuvert(),
            self::Rendezvous => app(RendezVousReglages::class)->enabled(),
        };
    }

    public function messageFerme(): string
    {
        return match ($this) {
            self::Candidatures => 'Les inscriptions en ligne ne sont pas ouvertes actuellement.',
            self::Reinscriptions => 'Les réinscriptions en ligne ne sont pas ouvertes actuellement.',
            self::Rendezvous => 'La prise de rendez-vous n\'est pas ouverte actuellement.',
        };
    }

    /**
     * Les seaux a consulter pour un appel, du plus etroit au plus large.
     *
     * @param  string  $empreinteAdresse  l'adresse du visiteur, deja hachee
     * @return list<SeauDeDebit>
     */
    public function seaux(NaturePortailPublic $nature, string $empreinteAdresse): array
    {
        return match ($nature) {
            NaturePortailPublic::Catalogue => [
                SeauDeDebit::parAdresse(
                    'rp-cat-ip:'.$this->value.':'.$empreinteAdresse,
                    self::MAX_CATALOGUE_PAR_ADRESSE_PAR_MINUTE
                ),
                SeauDeDebit::global(
                    'rp-cat-global:'.$this->value,
                    self::MAX_CATALOGUE_GLOBAL_PAR_MINUTE
                ),
            ],
            NaturePortailPublic::Identite => [
                SeauDeDebit::parAdresse(
                    'rp-ip:'.$this->value.':'.$empreinteAdresse,
                    self::MAX_PAR_ADRESSE_PAR_MINUTE
                ),
                SeauDeDebit::global('rp-global:'.$this->value, self::MAX_GLOBAL_PAR_MINUTE),
            ],
        };
    }
}
