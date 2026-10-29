<?php

namespace App\Domain\Lms\Synchronisation;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Les six flux de la synchronisation LMS : pour chacun, la requete et la forme
 * de ce qui part. Voir docs/api/LMS_SYNCHRONISATION.md.
 *
 * Requetes en query builder, pas en Eloquent, pour trois raisons :
 *  - les lignes supprimees en douceur doivent sortir aussi (marquees
 *    `supprime`), sinon le LMS garderait des eleves partis ;
 *  - aucun scope global ne doit restreindre ce que voit le serveur du LMS ;
 *  - les heures de seance doivent sortir telles qu'en base : l'accesseur de
 *    `ESBTPSeanceCours` les transforme en dates du jour (piege #14).
 *
 * Toutes les tables portent l'alias `t`, et chaque flux avance sur sa propre
 * date de modification. Un changement du COMPTE d'un eleve ou d'un enseignant
 * (desactivation, adresse, identifiant) fait avancer sa fiche :
 * `CompteLmsSuitLaFiche`. Lire la date du compte ici ferait repartir toute
 * personne connectee, dont la derniere visite est ecrite sur le compte.
 */
final class FluxDeSynchronisation
{
    /** Ordre de parcours, et types acceptes dans `types=`. */
    public const TYPES = ['classes', 'matieres', 'etudiants', 'enseignants', 'inscriptions', 'seances'];

    /** Nom d'un objet de chaque flux, tel qu'il part au LMS. */
    public const OBJET = [
        'classes' => 'classe',
        'matieres' => 'matiere',
        'etudiants' => 'etudiant',
        'enseignants' => 'enseignant',
        'inscriptions' => 'inscription',
        'seances' => 'seance',
    ];

    /** Flux bornes a une annee universitaire. Les classes, elles, sont universelles. */
    public const PAR_ANNEE = ['inscriptions', 'seances'];

    private const JAMAIS = "'1970-01-02 00:00:00'";

    /** @return array{0: Builder, 1: string} la requete et son expression de date de modification */
    public static function requete(string $type, ?int $anneeId): array
    {
        return match ($type) {
            'classes' => [DB::table('esbtp_classes as t')->select(
                't.id', 't.code', 't.name', 't.libelle', 't.systeme_academique', 't.filiere_id',
                't.niveau_etude_id', 't.parcours_id', 't.places_totales', 't.is_active', 't.deleted_at'
            ), self::date('t')],
            'matieres' => [DB::table('esbtp_matieres as t')->select(
                't.id', 't.code', 't.name', 't.unite_enseignement_id', 't.niveau_etude_id',
                't.coefficient', 't.credit_ecue', 't.is_active', 't.deleted_at'
            ), self::date('t')],
            'etudiants' => [DB::table('esbtp_etudiants as t')
                ->leftJoin('users as u', 'u.id', '=', 't.user_id')
                ->select('t.id', 't.user_id', 't.matricule', 't.nom', 't.prenoms', 't.sexe', 't.statut', 't.deleted_at',
                    'u.username', 'u.email', 'u.is_active as compte_actif', 'u.deleted_at as compte_supprime_le'),
                self::date('t')],
            'enseignants' => [DB::table('esbtp_teachers as t')
                ->leftJoin('users as u', 'u.id', '=', 't.user_id')
                ->select('t.id', 't.user_id', 't.matricule', 't.specialization', 't.is_active', 't.deleted_at',
                    't.email as email_fiche', 'u.name', 'u.username', 'u.email', 'u.is_active as compte_actif',
                    'u.deleted_at as compte_supprime_le'),
                self::date('t')],
            'inscriptions' => [DB::table('esbtp_inscriptions as t')
                ->select('t.id', 't.etudiant_id', 't.classe_id', 't.annee_universitaire_id', 't.status',
                    't.workflow_step', 't.date_inscription', 't.deleted_at')
                ->where('t.annee_universitaire_id', $anneeId),
                self::date('t')],
            'seances' => [DB::table('esbtp_seance_cours as t')
                ->select('t.id', 't.classe_id', 't.matiere_id', 't.teacher_id', 't.date_seance', 't.jour',
                    't.heure_debut', 't.heure_fin', 't.salle', 't.type_seance', 't.is_active', 't.deleted_at')
                // L'annee de la seance, ou a defaut celle de son emploi du temps.
                ->where(fn ($q) => $q->where('t.annee_universitaire_id', $anneeId)
                    ->orWhere(fn ($q) => $q->whereNull('t.annee_universitaire_id')->whereIn('t.emploi_temps_id',
                        DB::table('esbtp_emploi_temps')->select('id')->where('annee_universitaire_id', $anneeId)))),
                self::date('t')],
        };
    }

    /** Ce que le LMS recoit d'une ligne vivante. */
    public static function donnees(string $type, object $r): array
    {
        return match ($type) {
            'classes' => [
                'code' => $r->code, 'nom' => $r->name, 'libelle' => $r->libelle,
                'systeme_academique' => $r->systeme_academique, 'filiere_id' => $r->filiere_id,
                'niveau_etude_id' => $r->niveau_etude_id, 'parcours_id' => $r->parcours_id,
                'places_totales' => $r->places_totales, 'actif' => (bool) $r->is_active,
            ],
            'matieres' => [
                'code' => $r->code, 'nom' => $r->name,
                // Non nul : une ECUE du LMD. Nul : une matiere BTS.
                'unite_enseignement_id' => $r->unite_enseignement_id,
                'niveau_etude_id' => $r->niveau_etude_id, 'coefficient' => $r->coefficient,
                'credit_ecue' => $r->credit_ecue, 'actif' => (bool) $r->is_active,
            ],
            'etudiants' => [
                'user_id' => $r->user_id, 'matricule' => $r->matricule, 'nom' => $r->nom,
                'prenoms' => $r->prenoms, 'sexe' => $r->sexe, 'statut' => $r->statut,
                'identifiant' => $r->username, 'email' => $r->email,
                'actif' => self::compteActif($r),
            ],
            'enseignants' => [
                'user_id' => $r->user_id, 'matricule' => $r->matricule, 'nom' => $r->name,
                'specialite' => $r->specialization, 'identifiant' => $r->username,
                'email' => $r->email ?? $r->email_fiche,
                'actif' => (bool) $r->is_active && self::compteActif($r),
            ],
            'inscriptions' => [
                'etudiant_id' => $r->etudiant_id, 'classe_id' => $r->classe_id,
                'annee_universitaire_id' => $r->annee_universitaire_id, 'statut' => $r->status,
                'etape' => $r->workflow_step, 'date_inscription' => $r->date_inscription,
                // Une inscription « active » dont le dossier n'est pas termine
                // est encore en attente (rule inscriptions.md).
                'validee' => $r->status === 'active' && $r->workflow_step === 'etudiant_cree',
            ],
            'seances' => [
                'classe_id' => $r->classe_id, 'matiere_id' => $r->matiere_id, 'enseignant_id' => $r->teacher_id,
                'date' => $r->date_seance, 'jour' => $r->jour,
                'heure_debut' => self::heure($r->heure_debut), 'heure_fin' => self::heure($r->heure_fin),
                'salle' => $r->salle, 'type_seance' => $r->type_seance, 'actif' => (bool) $r->is_active,
            ],
        };
    }

    private static function date(string $alias): string
    {
        return "COALESCE({$alias}.updated_at, {$alias}.created_at, ".self::JAMAIS.')';
    }

    private static function compteActif(object $r): bool
    {
        return $r->user_id !== null && $r->compte_supprime_le === null && (bool) $r->compte_actif;
    }

    /** Colonne `time` brute (« 08:00:00 ») rendue en « 08:00 ». */
    private static function heure(?string $valeur): ?string
    {
        return $valeur === null ? null : substr($valeur, 0, 5);
    }
}
