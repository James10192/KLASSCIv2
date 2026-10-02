<?php

namespace App\Domain\Inscriptions;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use App\Models\User;
use App\Services\Inscriptions\NormalisationTypeInscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Le statut « redoublant » d'une inscription : ce que le logiciel en déduit,
 * ce qu'une personne en a confirmé.
 *
 * Le logiciel déduit (même niveau d'étude que l'année d'avant, règle unique de
 * {@see ESBTPInscription::estUnRedoublement()}). Une personne habilitée confirme
 * la valeur, ou la corrige en disant pourquoi.
 *
 * La colonne `is_redoublant` est la SEULE valeur lue partout (écrans, listes,
 * bulletins, exports) : deux lectures différentes finiraient par se contredire
 * sur un même élève. Elle est donc tenue à jour : le modèle recalcule les
 * inscriptions non confirmées d'un étudiant dès qu'une de ses inscriptions
 * naît, disparaît, revient ou change de niveau ou d'année
 * ({@see rafraichirLEtudiant()}), et une inscription jamais recensée l'est à la
 * première lecture ({@see valeurEtablie()}). Une valeur établie par une
 * personne n'est plus réécrite, sauf si son inscription change de niveau ou
 * d'année : la confirmation portait sur l'ancien.
 *
 * Tant que personne n'a confirmé, le bulletin imprime la valeur déduite et la
 * génération des bulletins prévient (décision de l'établissement, octobre 2026).
 */
class StatutRedoublant
{
    public const PERMISSION = 'inscriptions.redoublant.confirm';

    public const SOURCE_DEDUIT = 'deduit';

    public const SOURCE_CONFIRME = 'confirme';

    public const SOURCE_CORRIGE = 'corrige';

    /** Une correction se justifie, comme une correction de note. */
    public const MOTIF_MINIMUM = 10;

    public const DECISIONS = ['passage', 'redoublement', 'rattrapage'];

    /** Ce que le logiciel conclut des inscriptions de l'étudiant. */
    public function deduire(ESBTPInscription $inscription): bool
    {
        $annee = $inscription->relationLoaded('anneeUniversitaire')
            && $inscription->anneeUniversitaire?->id === (int) $inscription->annee_universitaire_id
            ? $inscription->anneeUniversitaire
            : ESBTPAnneeUniversitaire::find($inscription->annee_universitaire_id);

        if ($annee === null) {
            return false;
        }

        return ESBTPInscription::estUnRedoublement(
            ESBTPInscription::precedantAnnee((int) $inscription->etudiant_id, $annee),
            $inscription->niveau_id !== null ? (int) $inscription->niveau_id : null,
        );
    }

    public function estEtabliParUnePersonne(ESBTPInscription $inscription): bool
    {
        return in_array($inscription->redoublant_source, [self::SOURCE_CONFIRME, self::SOURCE_CORRIGE], true);
    }

    /**
     * La valeur qui fait foi : la colonne. Une inscription jamais recensée
     * (créée avant ce statut, recensement du déploiement manqué) reçoit sa
     * déduction à cette lecture, pour que l'écran et le bulletin disent la même
     * chose.
     */
    public function valeurEtablie(ESBTPInscription $inscription): bool
    {
        if ($inscription->redoublant_source === null && $inscription->exists) {
            $this->ecrireLaDeduction($inscription, $this->deduire($inscription));
        }

        return (bool) $inscription->is_redoublant;
    }

    /**
     * Recalcule les inscriptions non confirmées d'un étudiant : une inscription
     * qui naît, disparaît ou change de niveau change la déduction des autres
     * (une année passée importée après coup, par exemple).
     */
    public function rafraichirLEtudiant(int $etudiantId): void
    {
        ESBTPInscription::query()->with('anneeUniversitaire')
            ->where('etudiant_id', $etudiantId)
            ->where(fn ($q) => $q->whereNull('redoublant_source')
                ->orWhereNotIn('redoublant_source', [self::SOURCE_CONFIRME, self::SOURCE_CORRIGE]))
            ->get()
            ->each(function (ESBTPInscription $inscription) {
                if ($inscription->anneeUniversitaire?->start_date === null) {
                    return;
                }
                $valeur = $this->deduire($inscription);
                if ($inscription->redoublant_source === null || (bool) $inscription->is_redoublant !== $valeur) {
                    $this->ecrireLaDeduction($inscription, $valeur);
                }
            });
    }

    /** Écrit la déduction sans réveiller les événements du modèle ni l'audit. */
    private function ecrireLaDeduction(ESBTPInscription $inscription, bool $valeur): void
    {
        DB::table('esbtp_inscriptions')->where('id', $inscription->id)
            ->where(fn ($q) => $q->whereNull('redoublant_source')
                ->orWhereNotIn('redoublant_source', [self::SOURCE_CONFIRME, self::SOURCE_CORRIGE]))
            ->update(['is_redoublant' => $valeur, 'redoublant_source' => self::SOURCE_DEDUIT]);

        $inscription->forceFill(['is_redoublant' => $valeur, 'redoublant_source' => self::SOURCE_DEDUIT])->syncOriginalAttributes(['is_redoublant', 'redoublant_source']);
    }

    /**
     * Une inscription attend une confirmation quand la question se pose
     * vraiment : l'étudiant était déjà là (réinscription), vient d'ailleurs
     * (transfert, il a pu redoubler chez l'autre), ou le logiciel le dit
     * redoublant. Un nouvel étudiant qui arrive de rien ne redouble pas ici, et
     * une inscription annulée ne demande plus rien.
     */
    public function aConfirmer(ESBTPInscription $inscription): bool
    {
        if ($this->estEtabliParUnePersonne($inscription)
            || in_array($inscription->status, ESBTPInscription::STATUTS_ANNULES, true)) {
            return false;
        }

        return $inscription->type_inscription === NormalisationTypeInscription::REINSCRIPTION
            || (bool) $inscription->est_transfert
            || (bool) $inscription->is_redoublant;
    }

    /** Le même tri que {@see aConfirmer()}, en requête. */
    public static function contraindreAConfirmer(Builder $requete): Builder
    {
        $table = $requete->getModel()->getTable();

        return $requete
            ->where(fn ($q) => $q->whereNull("{$table}.redoublant_source")
                ->orWhereNotIn("{$table}.redoublant_source", [self::SOURCE_CONFIRME, self::SOURCE_CORRIGE]))
            ->whereNotIn("{$table}.status", ESBTPInscription::STATUTS_ANNULES)
            ->where(fn ($q) => $q->where("{$table}.type_inscription", NormalisationTypeInscription::REINSCRIPTION)
                ->orWhere("{$table}.est_transfert", true)
                ->orWhere("{$table}.is_redoublant", true));
    }

    /** Le filtre des listes (écran et Nanan) : `oui`, `non`, `a_confirmer`. */
    public static function filtrer(Builder $requete, ?string $filtre): Builder
    {
        $table = $requete->getModel()->getTable();

        return match ($filtre) {
            'oui' => $requete->where("{$table}.is_redoublant", true),
            'non' => $requete->where(fn ($q) => $q->where("{$table}.is_redoublant", false)->orWhereNull("{$table}.is_redoublant")),
            'a_confirmer' => self::contraindreAConfirmer($requete),
            default => $requete,
        };
    }

    /**
     * Le niveau ou l'année a changé : la confirmation portait sur l'ancien. On
     * repart de la déduction, et l'inscription repasse « à confirmer ». Appelé
     * avant l'enregistrement par le modèle, pour tous les écrans qui déplacent
     * une inscription.
     */
    public function rouvrirSiLeNiveauChange(ESBTPInscription $inscription): void
    {
        if (! $inscription->exists || ! $inscription->isDirty(['niveau_id', 'annee_universitaire_id'])) {
            return;
        }

        $inscription->forceFill([
            'is_redoublant' => $this->deduire($inscription),
            'redoublant_source' => self::SOURCE_DEDUIT,
            'redoublant_confirme_par' => null,
            'redoublant_confirme_le' => null,
            'redoublant_motif' => null,
        ]);
    }

    /**
     * Une personne établit le statut. La valeur de la déduction est une
     * confirmation ; une autre est une correction. Changer la valeur qui fait
     * foi (voir {@see valeurEtablie()}) exige un motif.
     *
     * @throws ValidationException
     */
    public function etablir(ESBTPInscription $inscription, User $personne, bool $valeur, ?string $motif = null): void
    {
        $motif = trim((string) $motif);
        $deduction = $this->deduire($inscription);
        $reference = $this->estEtabliParUnePersonne($inscription) ? (bool) $inscription->is_redoublant : $deduction;
        $change = $reference !== $valeur;

        if ($change && mb_strlen($motif) < self::MOTIF_MINIMUM) {
            throw ValidationException::withMessages([
                'motif' => 'Dites en quelques mots pourquoi le statut change (au moins '.self::MOTIF_MINIMUM.' caractères).',
            ]);
        }

        $inscription->forceFill([
            'is_redoublant' => $valeur,
            'redoublant_source' => $valeur === $deduction ? self::SOURCE_CONFIRME : self::SOURCE_CORRIGE,
            'redoublant_confirme_par' => $personne->id,
            'redoublant_confirme_le' => now(),
            'redoublant_motif' => $motif !== '' ? $motif : ($change ? null : $inscription->redoublant_motif),
        ])->save();
    }

    /**
     * Confirme la valeur qui fait foi, sans rien changer. Tant que personne n'a
     * tranché, elle est d'abord remise à jour : on confirme la déduction du
     * moment, pas une colonne qui aurait vieilli.
     */
    public function confirmer(ESBTPInscription $inscription, User $personne): void
    {
        if (! $this->estEtabliParUnePersonne($inscription)) {
            $this->ecrireLaDeduction($inscription, $this->deduire($inscription));
        }

        $this->etablir($inscription, $personne, (bool) $inscription->is_redoublant);
    }

    /** Ce qu'une réinscription écrit à la création de l'inscription. */
    public static function colonnesALaReinscription(bool $deduit, ?string $decision): array
    {
        $decision = strtolower((string) $decision);

        return [
            'is_redoublant' => $deduit,
            'redoublant_source' => self::SOURCE_DEDUIT,
            'decision_reinscription' => in_array($decision, self::DECISIONS, true) ? $decision : null,
        ];
    }

    /**
     * La personne qui réinscrit a vu le statut proposé et l'a gardé ou changé :
     * c'est sa confirmation, si elle en a le droit. Sans ce droit (caisse, agent
     * d'inscription), la valeur reste déduite et la scolarité la confirmera.
     *
     * @throws ValidationException si elle change la valeur sans motif
     */
    public function apresReinscription(ESBTPInscription $inscription, ?bool $choix, ?string $motif): void
    {
        $personne = auth()->user();

        if ($choix === null || ! $personne || ! $personne->can(self::PERMISSION)) {
            return;
        }

        $this->etablir($inscription->loadMissing('anneeUniversitaire'), $personne, $choix, $motif);
    }

    /** Même niveau, même année : passer en spécialité ne change rien au statut. */
    public static function colonnesHeritees(ESBTPInscription $origine): array
    {
        return [
            'is_redoublant' => (bool) $origine->is_redoublant,
            'redoublant_source' => $origine->redoublant_source,
            'redoublant_confirme_par' => $origine->redoublant_confirme_par,
            'redoublant_confirme_le' => $origine->redoublant_confirme_le,
            'redoublant_motif' => $origine->redoublant_motif,
            'decision_reinscription' => $origine->decision_reinscription,
        ];
    }

    /**
     * Le constat du contrôle avant génération des bulletins : combien d'élèves
     * de la classe partiront avec une valeur déduite, et la liste qui les montre
     * (seulement à qui peut l'ouvrir).
     *
     * @return array{redoublants_a_confirmer:int, redoublants_url:?string}
     */
    public function pourLePreControle(ESBTPClasse $classe, int $anneeId, ?User $personne): array
    {
        $nombre = self::contraindreAConfirmer(ESBTPInscription::query())
            ->where('esbtp_inscriptions.classe_id', $classe->id)
            ->where('esbtp_inscriptions.annee_universitaire_id', $anneeId)
            ->count();

        $peutOuvrir = $personne && $personne->can('inscriptions.view') && $personne->can(self::PERMISSION);

        return [
            'redoublants_a_confirmer' => $nombre,
            'redoublants_url' => $nombre > 0 && $peutOuvrir
                ? route('esbtp.inscriptions.index', ['annee' => $anneeId, 'classe' => $classe->id, 'status' => 'all', 'redoublant' => 'a_confirmer'])
                : null,
        ];
    }

    /** « dont N redoublants » sur la page d'une classe (inscriptions actives de l'année). */
    public static function nombreDansLaClasse(ESBTPClasse $classe, ?int $anneeId): int
    {
        return $anneeId === null ? 0 : ESBTPInscription::where('classe_id', $classe->id)
            ->where('annee_universitaire_id', $anneeId)
            ->where('status', 'active')
            ->where('is_redoublant', true)
            ->count();
    }

    /**
     * Le niveau occupé l'année d'avant, pour chaque année où l'on peut
     * réinscrire : l'écran de réinscription en tire sa proposition, avec la
     * même règle que le serveur.
     *
     * @param  iterable<ESBTPAnneeUniversitaire>  $annees
     * @return array<string, ?int>
     */
    public static function niveauxDeLAnneePrecedente(int $etudiantId, iterable $annees): array
    {
        $niveaux = [];
        foreach ($annees as $annee) {
            $precedente = ESBTPInscription::precedantAnnee($etudiantId, $annee);
            $niveaux[(string) $annee->id] = $precedente?->niveau_id !== null ? (int) $precedente->niveau_id : null;
        }

        return $niveaux;
    }

    /**
     * Ce qui mérite un second regard : la décision de réinscription dit l'un,
     * la classe choisie dit l'autre. Le logiciel ne tranche pas, il le montre.
     */
    public function incoherence(ESBTPInscription $inscription, ?bool $valeur = null): ?string
    {
        if ($this->estEtabliParUnePersonne($inscription) || ! $inscription->decision_reinscription) {
            return null;
        }

        $decideRedoublement = $inscription->decision_reinscription === 'redoublement';

        if ($decideRedoublement === ($valeur ?? (bool) $inscription->is_redoublant)) {
            return null;
        }

        return $decideRedoublement
            ? 'La décision de réinscription est « redoublement », mais la classe choisie est d\'un autre niveau que l\'an dernier.'
            : 'La classe choisie est du même niveau que l\'an dernier, mais la décision de réinscription est « '.$inscription->decision_reinscription.' ».';
    }

    /** L'origine de la valeur, en un mot (exports). */
    public function libelleSource(ESBTPInscription $inscription): string
    {
        return match ($inscription->redoublant_source) {
            self::SOURCE_CONFIRME => 'Confirmé',
            self::SOURCE_CORRIGE => 'Corrigé',
            default => $this->aConfirmer($inscription) ? 'À confirmer' : 'Déduit',
        };
    }

    /**
     * Ce que l'écran montre : la valeur qui fait foi, d'où elle vient, et s'il
     * reste quelque chose à faire.
     *
     * @return array{valeur: bool, etat: string, libelle: string, detail: ?string, motif: ?string, incoherence: ?string, a_confirmer: bool}
     */
    public function pourAffichage(ESBTPInscription $inscription): array
    {
        $valeur = $this->valeurEtablie($inscription);
        $aConfirmer = $this->aConfirmer($inscription);
        $etat = $inscription->redoublant_source === self::SOURCE_CORRIGE ? 'corrige'
            : ($inscription->redoublant_source === self::SOURCE_CONFIRME ? 'confirme'
            : ($aConfirmer ? 'a_confirmer' : 'deduit'));

        $qui = $inscription->redoublantConfirmePar?->name;
        $quand = $inscription->redoublant_confirme_le?->format('d/m/Y');
        $detail = match ($etat) {
            'confirme' => trim('Confirmé'.($qui ? ' par '.$qui : '').($quand ? ' le '.$quand : '')),
            'corrige' => trim('Corrigé'.($qui ? ' par '.$qui : '').($quand ? ' le '.$quand : '')),
            'a_confirmer' => 'Déduit du niveau de l\'an dernier, à confirmer.',
            default => 'Première inscription dans l\'établissement.',
        };

        return [
            'valeur' => $valeur,
            'etat' => $etat,
            'libelle' => $valeur ? 'Redoublant' : 'Non redoublant',
            'detail' => $detail,
            'motif' => $inscription->redoublant_motif,
            'incoherence' => $this->incoherence($inscription, $valeur),
            'a_confirmer' => $aConfirmer,
        ];
    }

    /** La décision lue en tête du texte de réinscription (« Redoublement - … »). */
    public static function decisionDepuisObservations(?string $observations): ?string
    {
        if ($observations === null) {
            return null;
        }

        // Première ligne seulement : la réinscription groupée ajoute « [BATCH …] » dessous.
        $premiereLigne = strtok(trim($observations), "\n") ?: '';
        $tete = mb_strtolower(trim(explode(' - ', $premiereLigne, 2)[0]));

        return in_array($tete, self::DECISIONS, true) ? $tete : null;
    }
}
