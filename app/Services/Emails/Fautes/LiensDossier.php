<?php

namespace App\Services\Emails\Fautes;

use App\Enums\StatutReservationRdv;
use App\Services\Portail\ReferencePublique;
use Illuminate\Support\Facades\DB;

/**
 * Les autres lignes du MEME dossier qui portent la MEME adresse : corriger
 * l'une sans l'autre les ferait diverger (la candidature corrigee, mais la
 * convocation repartirait vers la reservation fautive).
 *
 * - candidature ↔ ses reservations actives ;
 * - reservation de reinscription ↔ les adresses de l'etudiant de la demande ;
 * - etudiant ↔ les reservations actives de ses demandes de reinscription,
 *   et son compte de connexion (`users`) ;
 * - parent ↔ son compte de connexion ;
 * - compte ↔ l'etudiant ou le parent qui le porte.
 *
 * Donne aussi la reference du dossier, pour que l'ecole le retrouve.
 */
class LiensDossier
{
    public function __construct(private readonly ReferencePublique $references) {}

    /** @return list<array{table: string, colonne: string, id: int, cle: string}> */
    public function lies(CibleCorrection $cible, string $email): array
    {
        $lignes = match ($cible->table) {
            'esbtp_candidatures' => $this->reservations('candidature_id', [$cible->id]),
            'esbtp_rdv_reservations' => $this->autourDeLaReservation($cible->id),
            'esbtp_etudiants' => [...$this->reservations('reinscription_demande_id', DB::table('esbtp_reinscription_demandes')
                ->where('etudiant_id', $cible->id)->pluck('id')->all()), ...$this->compteDe('esbtp_etudiants', $cible->id)],
            'esbtp_parents' => $this->compteDe('esbtp_parents', $cible->id),
            'users' => $this->porteursDuCompte($cible->id),
            default => [],
        };

        $lies = [];
        foreach ($lignes as [$table, $colonne, $id, $valeur]) {
            $lien = new CibleCorrection($table, $colonne, (int) $id);
            if ($lien->cle() !== $cible->cle() && self::memeAdresse($valeur, $email)) {
                $lies[] = ['table' => $table, 'colonne' => $colonne, 'id' => (int) $id, 'cle' => $lien->cle()];
            }
        }

        return $lies;
    }

    public function referenceMasquee(CibleCorrection $cible): ?string
    {
        $reference = match ($cible->table) {
            'esbtp_candidatures' => DB::table('esbtp_candidatures')->where('id', $cible->id)->value('reference_publique'),
            'esbtp_rdv_reservations' => $this->referenceDeLaReservation($cible->id),
            default => null,
        };
        $brut = $this->references->normaliser((string) $reference);

        return $brut === '' ? null : mb_substr($brut, 0, 2).'**-****-**'.mb_substr($brut, -2);
    }

    public static function memeAdresse(?string $a, ?string $b): bool
    {
        return $a !== null && $b !== null && mb_strtolower(trim($a)) === mb_strtolower(trim($b));
    }

    /** @return list<array{0: string, 1: string, 2: int, 3: ?string}> */
    private function compteDe(string $table, int $id): array
    {
        $compte = DB::table($table)->where('id', $id)->value('user_id');
        $email = $compte ? DB::table('users')->where('id', $compte)->value('email') : null;

        return $email === null ? [] : [['users', 'email', (int) $compte, $email]];
    }

    /** @return list<array{0: string, 1: string, 2: int, 3: ?string}> */
    private function porteursDuCompte(int $compte): array
    {
        $lignes = [];
        foreach (DB::table('esbtp_etudiants')->where('user_id', $compte)->get(['id', 'email', 'email_personnel']) as $e) {
            $lignes[] = ['esbtp_etudiants', 'email', (int) $e->id, $e->email];
            $lignes[] = ['esbtp_etudiants', 'email_personnel', (int) $e->id, $e->email_personnel];
        }
        foreach (DB::table('esbtp_parents')->where('user_id', $compte)->get(['id', 'email']) as $p) {
            $lignes[] = ['esbtp_parents', 'email', (int) $p->id, $p->email];
        }

        return $lignes;
    }

    /** @return list<array{0: string, 1: string, 2: int, 3: ?string}> */
    private function autourDeLaReservation(int $id): array
    {
        $r = DB::table('esbtp_rdv_reservations')->where('id', $id)->first(['candidature_id', 'reinscription_demande_id']);
        if ($r === null) {
            return [];
        }
        if ($r->candidature_id) {
            return [['esbtp_candidatures', 'email', (int) $r->candidature_id,
                DB::table('esbtp_candidatures')->where('id', $r->candidature_id)->value('email')]];
        }
        $etudiant = DB::table('esbtp_reinscription_demandes')->where('id', $r->reinscription_demande_id)->value('etudiant_id');
        $e = $etudiant ? DB::table('esbtp_etudiants')->where('id', $etudiant)->first(['id', 'email', 'email_personnel']) : null;

        return $e === null ? [] : [
            ['esbtp_etudiants', 'email', (int) $e->id, $e->email],
            ['esbtp_etudiants', 'email_personnel', (int) $e->id, $e->email_personnel],
        ];
    }

    /**
     * @param  list<int>  $porteurs
     * @return list<array{0: string, 1: string, 2: int, 3: ?string}>
     */
    private function reservations(string $colonnePorteur, array $porteurs): array
    {
        if ($porteurs === []) {
            return [];
        }

        return DB::table('esbtp_rdv_reservations')
            ->whereIn($colonnePorteur, $porteurs)
            ->whereIn('statut', StatutReservationRdv::valeursOccupantes())
            ->get(['id', 'email'])
            ->map(fn ($r) => ['esbtp_rdv_reservations', 'email', (int) $r->id, $r->email])
            ->all();
    }

    private function referenceDeLaReservation(int $id): ?string
    {
        $r = DB::table('esbtp_rdv_reservations')->where('id', $id)->first(['candidature_id', 'reinscription_demande_id']);

        return match (true) {
            $r === null => null,
            (bool) $r->candidature_id => DB::table('esbtp_candidatures')->where('id', $r->candidature_id)->value('reference_publique'),
            default => DB::table('esbtp_reinscription_demandes')->where('id', $r->reinscription_demande_id)->value('reference_publique'),
        };
    }
}
