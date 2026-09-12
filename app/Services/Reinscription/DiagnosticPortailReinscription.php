<?php

namespace App\Services\Reinscription;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\ESBTPReinscriptionDemande;
use App\Services\TenantScolariteSettings;

/**
 * Pourquoi le portail public repond « aucun dossier ne correspond ».
 *
 * Le portail rend la MEME reponse a « ce matricule n'existe pas », « il existe
 * mais la date ne correspond pas » et « il existe mais n'a rien a reinscrire ».
 * C'est deliberé, et ca doit le rester : distinguer ces cas cote public
 * donnerait un oracle pour enumerer les matricules d'une ecole entiere.
 *
 * Le prix de cette discipline, c'est qu'une famille bloquee et la scolarite qui
 * la recoit au telephone n'ont rien a se dire. Le premier appel de la rentree
 * 2026 l'a montre : un matricule refuse, et personne — ni la famille, ni
 * l'ecole, ni nous — ne pouvait dire lequel des cinq verrous avait ferme.
 *
 * Ce service est le pendant INTERNE de cette reponse uniforme. Il repond a la
 * question que le portail se refuse a trancher, mais seulement pour qui est
 * deja entre : commande artisan sur le serveur, ou jeton `cli:admin`. Il ne
 * decide de rien, n'ecrit rien, et ne doit jamais etre branche sur une surface
 * publique — ce serait exactement l'oracle que le portail evite.
 *
 * Il s'appuie sur PortailReinscriptionService pour la fenetre et l'annee cible,
 * plutot que de les recalculer : un diagnostic qui repond autre chose que le
 * portail est pire qu'aucun diagnostic. Seule l'identification est redecoupee
 * en deux temps ici — matricule, puis date — parce que c'est precisement ce que
 * le portail fusionne et ce que l'ecole a besoin de separer. Ne pas « factoriser »
 * ces deux temps vers identifier() : la fusion EST la protection publique.
 */
class DiagnosticPortailReinscription
{
    /** Le portail aurait rendu trouve:false, et voici lequel des verrous a ferme. */
    public const CAUSE_ANNEE_CIBLE_ABSENTE = 'annee_cible_absente';

    public const CAUSE_ANNEE_CIBLE_SANS_DATE = 'annee_cible_sans_date_de_debut';

    public const CAUSE_MATRICULE_INCONNU = 'matricule_inconnu';

    public const CAUSE_ETUDIANT_SUPPRIME = 'etudiant_supprime';

    public const CAUSE_DATE_NAISSANCE_ABSENTE = 'date_de_naissance_absente_en_base';

    public const CAUSE_DATE_NAISSANCE_DIFFERENTE = 'date_de_naissance_differente';

    public const CAUSE_AUCUNE_INSCRIPTION_ANTERIEURE = 'aucune_inscription_anterieure';

    /** Combien de matricules voisins proposer quand celui-ci est inconnu. */
    private const VOISINS_MAX = 5;

    public function __construct(
        private readonly PortailReinscriptionService $portail,
        private readonly TenantScolariteSettings $reglages,
    ) {}

    /**
     * @param  string|null  $dateNaissance  Format Y-m-d, tel que le portail l'exige.
     *                                      Absente, le diagnostic s'arrete avant
     *                                      la comparaison et dit ce qu'il a lu.
     * @return array<string, mixed>
     */
    public function pour(string $matricule, ?string $dateNaissance = null): array
    {
        $matricule = trim($matricule);

        $rapport = [
            'matricule_demande' => $matricule,
            'date_naissance_fournie' => $dateNaissance,
            'canal' => $this->canal(),
            'trouve' => false,
            'cause' => null,
            'explication' => null,
            'action' => null,
        ];

        // Le canal ferme ne produit PAS « aucun dossier » : le garde repond 503
        // en amont, avec un autre message. On le rapporte quand meme, parce que
        // c'est la premiere chose que l'ecole verifie et que la distinction est
        // exactement ce que le telephone ne permet pas d'etablir.
        $anneeCible = $this->portail->anneeCible();

        if ($anneeCible === null) {
            return array_merge($rapport, [
                'cause' => self::CAUSE_ANNEE_CIBLE_ABSENTE,
                'explication' => "Aucune annee cible : le reglage « inscriptions.annee_cible » est vide "
                    ."et aucune annee universitaire n'est marquee comme courante. TOUS les etudiants "
                    .'sont refuses, pas seulement celui-ci.',
                'action' => 'Designer l\'annee de la rentree dans les parametres de scolarite, '
                    .'ou marquer une annee universitaire comme courante.',
            ]);
        }

        $rapport['annee_cible'] = $this->anneeCible($anneeCible);

        // Sans date de debut sur l'annee visee, precedantAnnee() ne peut comparer
        // aucune anteriorite et rend null pour tout le monde, en silence.
        if ($anneeCible->start_date === null) {
            return array_merge($rapport, [
                'cause' => self::CAUSE_ANNEE_CIBLE_SANS_DATE,
                'explication' => "L'annee cible « {$anneeCible->display_name} » n'a pas de date de debut. "
                    ."L'anteriorite des inscriptions est alors indeterminable et TOUS les etudiants "
                    .'sont refuses, pas seulement celui-ci.',
                'action' => "Renseigner la date de debut de l'annee « {$anneeCible->display_name} ».",
            ]);
        }

        $etudiant = ESBTPEtudiant::where('matricule', $matricule)->first();

        if ($etudiant === null) {
            return array_merge($rapport, $this->matriculeIntrouvable($matricule));
        }

        $rapport['etudiant'] = $this->etudiant($etudiant);

        if ($etudiant->date_naissance === null) {
            return array_merge($rapport, [
                'cause' => self::CAUSE_DATE_NAISSANCE_ABSENTE,
                'explication' => "Le dossier existe mais sa date de naissance est vide en base. "
                    ."Le portail identifie sur le couple matricule + date : sans date, aucune saisie "
                    .'ne peut correspondre.',
                'action' => 'Renseigner la date de naissance sur la fiche de l\'etudiant.',
            ]);
        }

        $dateEnBase = $etudiant->date_naissance->toDateString();

        if ($dateNaissance !== null && ! hash_equals($dateEnBase, trim($dateNaissance))) {
            return array_merge($rapport, [
                'cause' => self::CAUSE_DATE_NAISSANCE_DIFFERENTE,
                'explication' => "Le matricule existe, mais sa date de naissance en base est {$dateEnBase} "
                    .'et non celle qui a ete saisie.',
                'action' => "Faire saisir {$dateEnBase} a la famille, ou corriger la fiche si c'est la "
                    .'base qui se trompe.',
            ]);
        }

        $rapport['inscriptions'] = $this->inscriptions($etudiant);

        $inscriptionPrecedente = ESBTPInscription::precedantAnnee($etudiant->id, $anneeCible);

        if ($inscriptionPrecedente === null) {
            return array_merge($rapport, [
                'cause' => self::CAUSE_AUCUNE_INSCRIPTION_ANTERIEURE,
                'explication' => "Aucune inscription anterieure a « {$anneeCible->display_name} ». "
                    ."Le portail ne sert que les etudiants deja passes par l'ecole : sans annee "
                    .'precedente, il n\'y a rien a reinscrire. Une annee universitaire sans date de '
                    .'debut ne compte pas non plus (colonne « anteriorite » ci-dessous).',
                'action' => "Verifier la liste des inscriptions ci-dessous : si l'annee precedente y "
                    ."figure sans date de debut, renseigner cette date suffit. Si l'etudiant est "
                    .'nouveau cette annee, sa reinscription se fait au guichet.',
            ]);
        }

        $dejaReinscrit = ESBTPInscription::aUneInscriptionVivantePour($etudiant->id, $anneeCible->id);

        return array_merge($rapport, [
            'trouve' => true,
            'inscription_precedente' => [
                'id' => $inscriptionPrecedente->id,
                'annee' => $inscriptionPrecedente->anneeUniversitaire?->display_name,
                'classe' => $inscriptionPrecedente->classe?->name,
            ],
            'eligible' => ! $dejaReinscrit,
            'deja_reinscrit' => $dejaReinscrit,
            'demande_existante' => ESBTPReinscriptionDemande::where('etudiant_id', $etudiant->id)
                ->where('annee_universitaire_id', $anneeCible->id)
                ->where('statut', ESBTPReinscriptionDemande::STATUT_EN_ATTENTE)
                ->exists(),
            'explication' => $dejaReinscrit
                ? 'Le portail trouve ce dossier mais refuse le depot : une inscription vivante existe '
                    ."deja pour « {$anneeCible->display_name} »."
                : 'Le portail trouve ce dossier et accepte le depot. Si la famille voit malgre tout un '
                    .'refus, la cause est ailleurs : saisie differente de ce qui a ete teste ici, '
                    .'plafond de tentatives atteint, ou canal ferme (voir « canal » ci-dessus).',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function canal(): array
    {
        $interrupteur = $this->reglages->reinscriptionEnLigneEnabled();
        $fenetre = $this->portail->fenetreOuverte();

        return [
            'interrupteur' => $interrupteur,
            'fenetre_ouverte' => $fenetre,
            'ouvert' => $interrupteur && $fenetre,
            // Un canal ferme donne un 503 « hors saison », jamais « aucun dossier » :
            // c'est ce qui permet de dire au telephone lequel des deux on regarde.
            'note' => $interrupteur && $fenetre
                ? null
                : 'Canal ferme : le portail repond « indisponible », pas « aucun dossier ». '
                    .'Un visiteur qui lit « aucun dossier » ne bute donc pas la-dessus.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function anneeCible(ESBTPAnneeUniversitaire $annee): array
    {
        $choisie = $this->portail->anneeChoisie();

        return [
            'id' => $annee->id,
            'libelle' => $annee->display_name,
            'start_date' => $annee->start_date?->toDateString(),
            'is_current' => (bool) $annee->is_current,
            // D'ou vient l'annee visee : le reglage explicite, ou le repli sur
            // l'annee courante. Une ecole qui croit ouvrir 2026-2027 et ouvre
            // 2025-2026 ne s'en apercoit autrement qu'au volume de refus.
            'source' => $choisie === null ? 'repli sur l\'annee courante' : 'reglage inscriptions.annee_cible',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function etudiant(ESBTPEtudiant $etudiant): array
    {
        return [
            'id' => $etudiant->id,
            'matricule' => $etudiant->matricule,
            'nom' => $etudiant->nom,
            'prenoms' => $etudiant->prenoms,
            'date_naissance' => $etudiant->date_naissance?->toDateString(),
        ];
    }

    /**
     * Toutes les inscriptions de l'etudiant, avec ce qui decide de l'anteriorite.
     *
     * La colonne qui compte n'est pas le statut mais la date de debut de l'ANNEE :
     * c'est elle que precedantAnnee() compare, et une annee sans date est ecartee
     * en silence. La montrer ici evite de chercher du cote du statut, qui n'entre
     * pas dans la decision.
     *
     * @return array<int, array<string, mixed>>
     */
    private function inscriptions(ESBTPEtudiant $etudiant): array
    {
        return ESBTPInscription::where('etudiant_id', $etudiant->id)
            ->with(['anneeUniversitaire', 'classe'])
            ->get()
            ->map(fn (ESBTPInscription $inscription) => [
                'id' => $inscription->id,
                'annee' => $inscription->anneeUniversitaire?->display_name,
                'annee_start_date' => $inscription->anneeUniversitaire?->start_date?->toDateString(),
                'anteriorite_utilisable' => $inscription->anneeUniversitaire?->start_date !== null,
                'statut' => $inscription->status,
                'classe' => $inscription->classe?->name,
            ])
            ->all();
    }

    /**
     * Le matricule n'existe pas tel quel. Deux pistes valent d'etre servies
     * ensemble, parce qu'elles se confondent au telephone : le dossier a ete
     * supprime, ou il est ecrit autrement.
     *
     * @return array<string, mixed>
     */
    private function matriculeIntrouvable(string $matricule): array
    {
        $supprime = ESBTPEtudiant::onlyTrashed()->where('matricule', $matricule)->first();

        if ($supprime !== null) {
            return [
                'cause' => self::CAUSE_ETUDIANT_SUPPRIME,
                'etudiant' => $this->etudiant($supprime) + ['supprime_le' => $supprime->deleted_at?->toDateTimeString()],
                'explication' => 'Ce matricule existe mais son dossier a ete supprime. Le portail ne sert '
                    .'que les dossiers vivants.',
                'action' => 'Restaurer le dossier si la suppression etait une erreur.',
            ];
        }

        $voisins = $this->voisins($matricule);

        return [
            'cause' => self::CAUSE_MATRICULE_INCONNU,
            'matricules_proches' => $voisins,
            'explication' => $voisins === []
                ? "Aucun etudiant ne porte ce matricule, et aucun ne s'en approche."
                : 'Aucun etudiant ne porte exactement ce matricule. Ceux-ci s\'en approchent : '
                    .implode(', ', $voisins).'.',
            'action' => $voisins === []
                ? "Verifier que la famille s'adresse au bon etablissement."
                : 'Faire saisir le matricule exactement comme il figure ci-contre.',
        ];
    }

    /**
     * Matricules qui ne different que par la ponctuation, la casse ou les zeros
     * de tete du dernier segment.
     *
     * Ces trois-la couvrent ce que les familles ecrivent reellement :
     * « mesbtp25-0070 », « MESBTP25 0070 », « MESBTP25-70 ». La comparaison se
     * fait en PHP et non en SQL parce qu'aucun index ne sert une normalisation
     * pareille ; sur une ecole de quelques milliers de dossiers, et pour un outil
     * qu'on lance a la main, c'est sans consequence.
     *
     * @return array<int, string>
     */
    private function voisins(string $matricule): array
    {
        $cherche = $this->normaliser($matricule);

        if ($cherche === '') {
            return [];
        }

        $voisins = [];

        ESBTPEtudiant::select('matricule')
            ->whereNotNull('matricule')
            ->orderBy('id')
            ->chunk(1000, function ($lot) use ($cherche, &$voisins) {
                foreach ($lot as $candidat) {
                    if ($this->normaliser((string) $candidat->matricule) === $cherche) {
                        $voisins[] = (string) $candidat->matricule;
                    }
                }

                return count($voisins) < self::VOISINS_MAX;
            });

        return array_slice($voisins, 0, self::VOISINS_MAX);
    }

    /**
     * Largeur du numero d'ordre dans un matricule KLASSCI (« MESBTP25-0070 »).
     *
     * Elle sert a ecrire les deux graphies d'un meme dossier de la meme facon,
     * pas a decrire un format : une ecole qui numeroterait sur cinq chiffres
     * n'obtiendrait simplement aucune suggestion de voisin, ce qui est
     * exactement le sens de l'echec qu'on veut ici.
     */
    private const LARGEUR_NUMERO_ORDRE = 4;

    /**
     * Reduit un matricule a ce qui l'identifie vraiment.
     *
     * Majuscules, ponctuation retiree, et numero d'ordre ramene a une largeur
     * fixe pour que « MESBTP25-0070 », « MESBTP25 70 » et « MESBTP250070 »
     * s'ecrivent pareil.
     *
     * Le rembourrage remplace un rabotage des zeros, qui confondait des eleves
     * reels. En retirant d'abord la ponctuation, on perdait le separateur —
     * seule chose qui distingue la promotion du numero d'ordre — puis on otait
     * TOUS les zeros suivis d'un chiffre, y compris celui de l'annee :
     * « MESBTP20-0107 » (promotion 2020, dossier 107) et « MESBTP21-07 »
     * (promotion 2021, dossier 7) se reduisaient tous deux a « MESBTP217 ».
     * Le diagnostic designait donc un autre eleve, et la scolarite dictait a
     * une famille le matricule de quelqu'un d'autre — qui echouait ensuite sur
     * la date de naissance, sans que personne ne comprenne pourquoi.
     *
     * Rembourrer plutot que raboter garde la tolerance a la graphie sans tiret,
     * qui etait le but d'origine : « MESBTP25-70 » devient « MESBTP250070 »,
     * soit exactement ce qu'on obtient d'un matricule tape d'un seul tenant.
     */
    private function normaliser(string $matricule): string
    {
        $segments = preg_split('/[^A-Za-z0-9]+/', mb_strtoupper($matricule), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($segments === []) {
            return '';
        }

        // Un seul segment : aucun separateur n'a ete tape, donc rien ne dit ou
        // commence le numero d'ordre. On ne devine pas — c'est deja la forme
        // canonique.
        if (count($segments) > 1) {
            $dernier = array_pop($segments);

            $segments[] = ctype_digit($dernier)
                ? str_pad($dernier, self::LARGEUR_NUMERO_ORDRE, '0', STR_PAD_LEFT)
                : $dernier;
        }

        return implode('', $segments);
    }
}
