<?php

namespace App\Services\Emails\Fautes;

use App\Enums\StatutConvocationRdv;
use App\Enums\StatutReservationRdv;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OwenIt\Auditing\Models\Audit;
use RuntimeException;

/**
 * Corrige les adresses que l'ecole a validees, et seulement elles.
 *
 * Pour chaque cle : l'adresse doit etre encore fautive, et le domaine propose
 * doit etre la suggestion canonique du domaine ACTUEL (DomaineCanonique),
 * jamais un domaine arbitraire. Seul le domaine change, la partie locale est
 * gardee telle quelle.
 *
 * Avant toute ecriture, une sauvegarde (table, id, colonne, ancienne et
 * nouvelle valeur) est ecrite et relue : sans elle, rien n'est modifie. Puis,
 * ligne par ligne, sous verrou : ecriture conditionnelle (valeur inchangee
 * depuis la lecture) et trace d'audit dans la meme transaction.
 *
 * Rien n'est envoye : une reservation corrigee dont la convocation etait en
 * echec ou sans e-mail est seulement comptee (`convocations_a_renvoyer`).
 */
class CorrectionFautes
{
    public const MODIFIEE = 'modifiee_entre_temps';

    public const NON_CANONIQUE = 'domaine_non_canonique';

    public const CLE_INVALIDE = 'cle_invalide';

    public const CONFLIT = 'adresse_deja_utilisee';

    public const ECHEC = 'echec_ecriture';

    public function __construct(private readonly DomaineCanonique $canonique) {}

    /**
     * @param  list<array{cle: string, domaine_propose: string, domaine_actuel?: ?string}>  $corrections
     * @return array{corrigees: int, ignorees: list<array{cle: string, motif: string}>, sauvegarde: ?string, convocations_a_renvoyer: int}
     */
    public function executer(array $corrections, bool $inclureComptes, ?Model $auteur, bool $avecMx = true): array
    {
        [$prevues, $ignorees] = $this->preparer($corrections, $inclureComptes, $avecMx);
        $rapport = ['corrigees' => 0, 'ignorees' => $ignorees, 'sauvegarde' => null, 'convocations_a_renvoyer' => 0];
        if ($prevues === []) {
            return $rapport;
        }

        $rapport['sauvegarde'] = $this->sauvegarder($prevues);
        foreach ($prevues as $prevue) {
            $motif = $this->ecrire($prevue, $auteur);
            if ($motif !== null) {
                $rapport['ignorees'][] = ['cle' => $prevue['cible']->cle(), 'motif' => $motif];

                continue;
            }
            $rapport['corrigees']++;
            $rapport['convocations_a_renvoyer'] += (int) $this->convocationARenvoyer($prevue['cible']);
        }

        return $rapport;
    }

    /** @return array{0: list<array{cible: CibleCorrection, ancienne: string, nouvelle: string}>, 1: list<array{cle: string, motif: string}>} */
    private function preparer(array $corrections, bool $inclureComptes, bool $avecMx): array
    {
        $prevues = [];
        $ignorees = [];
        foreach ($corrections as $correction) {
            $cible = CibleCorrection::depuisCle($correction['cle'], $inclureComptes);
            if ($cible === null) {
                $ignorees[] = ['cle' => $correction['cle'], 'motif' => self::CLE_INVALIDE];

                continue;
            }
            $actuelle = DB::table($cible->table)->where('id', $cible->id)->value($cible->colonne);
            $domaine = DomaineCanonique::domaineDe($actuelle);
            $canonique = $this->canonique->pour($domaine, $avecMx);
            $motif = match (true) {
                $canonique === null => self::MODIFIEE,
                isset($correction['domaine_actuel']) && mb_strtolower(trim($correction['domaine_actuel'])) !== $domaine => self::MODIFIEE,
                mb_strtolower(trim($correction['domaine_propose'])) !== $canonique => self::NON_CANONIQUE,
                default => null,
            };
            if ($motif !== null) {
                $ignorees[] = ['cle' => $cible->cle(), 'motif' => $motif];

                continue;
            }
            $prevues[] = ['cible' => $cible, 'ancienne' => (string) $actuelle, 'nouvelle' => DomaineCanonique::corriger((string) $actuelle, $canonique)];
        }

        return [$prevues, $ignorees];
    }

    /** @return string|null le motif si la ligne n'a pas ete corrigee */
    private function ecrire(array $prevue, ?Model $auteur): ?string
    {
        /** @var CibleCorrection $cible */
        $cible = $prevue['cible'];
        try {
            return DB::transaction(function () use ($cible, $prevue, $auteur) {
                $actuelle = DB::table($cible->table)->where('id', $cible->id)->lockForUpdate()->value($cible->colonne);
                if ($actuelle !== $prevue['ancienne']) {
                    return self::MODIFIEE;
                }
                DB::table($cible->table)->where('id', $cible->id)->where($cible->colonne, $prevue['ancienne'])
                    ->update([$cible->colonne => $prevue['nouvelle'], 'updated_at' => now()]);
                $this->auditer($cible, $prevue, $auteur);

                return null;
            });
        } catch (QueryException $e) {
            // Contrainte d'unicite (esbtp_etudiants.email, users.email) : l'adresse corrigee existe deja.
            return ($e->errorInfo[1] ?? null) === 1062 ? self::CONFLIT : $this->echec($cible, $e);
        } catch (\Throwable $e) {
            return $this->echec($cible, $e);
        }
    }

    private function echec(CibleCorrection $cible, \Throwable $e): string
    {
        Log::error('Correction d\'adresse annulee', ['cle' => $cible->cle(), 'erreur' => $e->getMessage()]);

        return self::ECHEC;
    }

    private function auditer(CibleCorrection $cible, array $prevue, ?Model $auteur): void
    {
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

    private function convocationARenvoyer(CibleCorrection $cible): bool
    {
        return $cible->table === 'esbtp_rdv_reservations' && DB::table('esbtp_rdv_reservations')
            ->where('id', $cible->id)
            ->whereIn('statut', StatutReservationRdv::valeursOccupantes())
            ->whereIn('convocation_statut', [StatutConvocationRdv::Echec->value, StatutConvocationRdv::SansEmail->value])
            ->exists();
    }

    /** @param  list<array{cible: CibleCorrection, ancienne: string, nouvelle: string}>  $prevues */
    private function sauvegarder(array $prevues): string
    {
        $lignes = array_map(fn ($p) => [
            'table' => $p['cible']->table, 'id' => $p['cible']->id, 'colonne' => $p['cible']->colonne,
            'ancienne_valeur' => $p['ancienne'], 'nouvelle_valeur' => $p['nouvelle'],
        ], $prevues);
        $chemin = 'backups/emails-fautes-'.now()->format('Ymd_His').'.json';
        $contenu = (string) json_encode($lignes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        Storage::disk('local')->put($chemin, $contenu);

        if (Storage::disk('local')->get($chemin) !== $contenu) {
            throw new RuntimeException('Sauvegarde illisible : aucune adresse modifiee.');
        }

        return basename($chemin);
    }
}
