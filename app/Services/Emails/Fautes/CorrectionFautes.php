<?php

namespace App\Services\Emails\Fautes;

use App\Enums\StatutConvocationRdv;
use App\Enums\StatutReservationRdv;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OwenIt\Auditing\Models\Audit;

/**
 * Corrige les adresses validees par l'ecole, groupe par groupe.
 *
 * PreparationCorrections decide quoi ecrire (refus par defaut). Ici :
 * sauvegarde ecrite et relue d'abord (sans elle, rien) ; puis chaque groupe
 * dans UNE transaction : toutes ses lignes verrouillees et relues, chacune
 * ecrite seulement si elle n'a pas change, avec sa trace d'audit. Une ligne
 * changee, une adresse deja utilisee ou une erreur annulent le groupe entier.
 * Un groupe en echec n'arrete pas les suivants.
 *
 * Rien n'est envoye : une reservation corrigee dont la convocation etait en
 * echec ou sans e-mail est seulement comptee (`convocations_a_renvoyer`).
 */
class CorrectionFautes
{
    public function __construct(
        private readonly PreparationCorrections $preparation,
        private readonly SauvegardeCorrections $sauvegarde,
    ) {}

    /**
     * @param  list<array{cle: string, domaine_propose: string, domaine_actuel?: ?string}>  $corrections
     * @return array{corrigees: int, ignorees: list<array{cle: string, motif: string}>, sauvegarde: ?string, convocations_a_renvoyer: int}
     *
     * @throws EchecSauvegarde
     */
    public function executer(array $corrections, bool $inclureComptes, bool $inclureProbables, ?Model $auteur): array
    {
        ['groupes' => $groupes, 'ignorees' => $ignorees] = $this->preparation->preparer($corrections, $inclureComptes, $inclureProbables);
        $rapport = ['corrigees' => 0, 'ignorees' => $ignorees, 'sauvegarde' => null, 'convocations_a_renvoyer' => 0];
        if ($groupes === []) {
            return $rapport;
        }

        $rapport['sauvegarde'] = $this->sauvegarde->ecrire(array_map(fn ($p) => [
            'table' => $p['cible']->table, 'id' => $p['cible']->id, 'colonne' => $p['cible']->colonne,
            'ancienne_valeur' => $p['ancienne'], 'nouvelle_valeur' => $p['nouvelle'],
        ], array_merge(...$groupes)));

        foreach ($groupes as $groupe) {
            $refus = $this->ecrireGroupe($groupe, $auteur);
            if ($refus !== []) {
                array_push($rapport['ignorees'], ...$refus);

                continue;
            }
            $rapport['corrigees'] += count($groupe);
            foreach ($groupe as $prevue) {
                $rapport['convocations_a_renvoyer'] += (int) $this->convocationARenvoyer($prevue['cible']);
            }
        }

        return $rapport;
    }

    /**
     * @param  list<array{cible: CibleCorrection, ancienne: string, nouvelle: string}>  $groupe
     * @return list<array{cle: string, motif: string}> vide si le groupe est ecrit
     */
    private function ecrireGroupe(array $groupe, ?Model $auteur): array
    {
        $tous = fn (string $motif, ?string $sauf = null, ?string $motifSauf = null) => array_map(
            fn ($p) => ['cle' => $p['cible']->cle(), 'motif' => $p['cible']->cle() === $sauf ? (string) $motifSauf : $motif],
            $groupe,
        );

        try {
            DB::transaction(function () use ($groupe, $auteur) {
                foreach ($groupe as $prevue) {
                    $this->verrouiller($prevue);
                }
                foreach ($groupe as $prevue) {
                    $cible = $prevue['cible'];
                    DB::table($cible->table)->where('id', $cible->id)->where($cible->colonne, $prevue['ancienne'])
                        ->update([$cible->colonne => $prevue['nouvelle'], 'updated_at' => now()]);
                    $this->auditer($prevue, $auteur);
                }
            });

            return [];
        } catch (ValeurModifiee $e) {
            return $tous(MotifsCorrection::GROUPE_REFUSE, $e->cle, MotifsCorrection::MODIFIEE);
        } catch (QueryException $e) {
            // Contrainte d'unicite (esbtp_etudiants.email, users.email) : l'adresse corrigee existe deja.
            if (($e->errorInfo[1] ?? null) === 1062) {
                return $tous(MotifsCorrection::CONFLIT);
            }
            $this->journaliser($groupe, $e);

            return $tous(MotifsCorrection::ECHEC);
        } catch (\Throwable $e) {
            $this->journaliser($groupe, $e);

            return $tous(MotifsCorrection::ECHEC);
        }
    }

    private function verrouiller(array $prevue): void
    {
        $cible = $prevue['cible'];
        $actuelle = DB::table($cible->table)->where('id', $cible->id)->lockForUpdate()->value($cible->colonne);
        if ($actuelle !== $prevue['ancienne']) {
            throw new ValeurModifiee($cible->cle());
        }
    }

    private function auditer(array $prevue, ?Model $auteur): void
    {
        $cible = $prevue['cible'];
        $modele = $cible->modele();
        Audit::query()->create([
            'user_type' => $auteur?->getMorphClass(),
            'user_id' => $auteur?->getKey(),
            'event' => 'correction_faute_email',
            'auditable_type' => (new $modele)->getMorphClass(),
            'auditable_id' => $cible->id,
            'old_values' => [$cible->colonne => $prevue['ancienne']],
            'new_values' => [$cible->colonne => $prevue['nouvelle'], 'source' => 'correction_fautes_cli'],
            'url' => request()?->fullUrl(),
            'ip_address' => request()?->ip(),
            'user_agent' => mb_substr((string) request()?->userAgent(), 0, 1023),
            'tags' => 'cli,correction_fautes',
        ]);
    }

    private function journaliser(array $groupe, \Throwable $e): void
    {
        Log::error('Correction d\'adresses : groupe annule', [
            'cles' => array_map(fn ($p) => $p['cible']->cle(), $groupe),
            'erreur' => $e->getMessage(),
        ]);
    }

    /** Ne leve jamais : l'ecriture est faite, seul le compte serait faux. */
    private function convocationARenvoyer(CibleCorrection $cible): bool
    {
        if ($cible->table !== 'esbtp_rdv_reservations') {
            return false;
        }
        try {
            return DB::table('esbtp_rdv_reservations')
                ->where('id', $cible->id)
                ->whereIn('statut', StatutReservationRdv::valeursOccupantes())
                ->whereIn('convocation_statut', [StatutConvocationRdv::Echec->value, StatutConvocationRdv::SansEmail->value])
                ->exists();
        } catch (\Throwable $e) {
            Log::warning('Correction d\'adresses : convocation non evaluee', ['cle' => $cible->cle(), 'erreur' => $e->getMessage()]);

            return false;
        }
    }
}
