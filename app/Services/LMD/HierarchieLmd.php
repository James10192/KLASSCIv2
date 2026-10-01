<?php

namespace App\Services\LMD;

use App\Models\ESBTPFiliere;
use App\Models\ESBTPLMDDomaine;
use App\Models\ESBTPLMDMention;
use App\Models\ESBTPLMDParcours;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Pose la hierarchie LMD d'un parcours : Domaine → Mention → Parcours (et sa
 * filiere, si on la donne). Reutilise ce qui existe sous le meme code.
 *
 * Partage par la CLI (`POST /api/cli/lmd/setup`) et par Nanan
 * (`proposer_hierarchie_lmd`). Deux gardes que `lmd/setup` n'avait pas
 * (l'import de maquette, lui, refusait deja le deplacement) :
 *
 *  - un code est unique dans l'ecole. Une mention dont le code existe deja
 *    sous un AUTRE domaine (ou un parcours sous une autre mention) n'est pas
 *    deplacee : c'est un conflit. `lmd/setup` cherchait par (code, parent),
 *    ne trouvait rien, et l'insertion butait sur l'index unique (500) ;
 *  - sans filiere dans la demande, le parcours garde la sienne, et ses
 *    credits s'ils ne sont pas donnes. L'ancien upsert ecrivait null et les
 *    totaux par defaut.
 *
 * Un code absent se deduit du nom (codeDeduit) en retrouvant d'abord une
 * fiche existante sous l'une ou l'autre des deux formes historiques : celle de
 * `lmd/setup` (« GENIECIVIL ») et celle de `lmd/import` (« genie-civil »).
 * Nanan, elle, exige toujours le code.
 */
class HierarchieLmd
{
    public function __construct(private RefusDeDeplacement $deplacement)
    {
    }

    /**
     * Ce que l'installation ferait, sans rien ecrire.
     *
     * @return array{lignes: list<array{niveau: string, code: string, avant: ?string, apres: string}>, conflits: list<array{type: string, code: string, detail: string}>}
     */
    public function apercu(array $spec): array
    {
        $codes = $this->codes($spec);
        $lignes = [];
        $conflits = [];

        $domaine = ESBTPLMDDomaine::where('code', $codes['domaine'])->first();
        $lignes[] = $this->ligne('Domaine', $codes['domaine'], $domaine?->name, $spec['domaine']['name']);

        $mention = ESBTPLMDMention::with('domaine')->where('code', $codes['mention'])->first();
        if ($domaine && ($c = $this->conflitMention($mention, (int) $domaine->id, $codes['mention'], $spec['domaine']['name']))) {
            $conflits[] = $c;
        } elseif (! $domaine && $mention) {
            $conflits[] = ['type' => 'MENTION', 'code' => $codes['mention'], 'detail' => "Le code « {$codes['mention']} » désigne déjà la mention « {$mention->name} » d'un autre domaine : donnez à cette mention un code propre."];
        }
        $lignes[] = $this->ligne('Mention', $codes['mention'], $mention?->name, $spec['mention']['name']);

        if ($codes['filiere'] !== null) {
            $filiere = ESBTPFiliere::where('code', $codes['filiere'])->first();
            $lignes[] = $this->ligne('Filière', $codes['filiere'], $filiere?->name, $spec['filiere']['name']);
        }

        $parcours = ESBTPLMDParcours::with('mention')->where('code', $codes['parcours'])->first();
        if ($parcours && (! $mention || (int) $parcours->mention_id !== (int) $mention->id)) {
            $conflits[] = $this->conflitParcours($parcours, $codes['parcours'], $spec['mention']['name']);
        }
        $lignes[] = $this->ligne('Parcours', $codes['parcours'], $parcours?->name, $spec['parcours']['name']);

        return ['lignes' => $lignes, 'conflits' => $conflits];
    }

    /**
     * Ecrit dans une transaction. Leve ConflitDeMaquette sans rien ecrire si
     * un code designe deja une fiche rattachee ailleurs.
     *
     * @return array{domaine: ESBTPLMDDomaine, mention: ESBTPLMDMention, filiere: ?ESBTPFiliere, parcours: ESBTPLMDParcours}
     */
    public function installer(array $spec, ?int $userId): array
    {
        return DB::transaction(function () use ($spec, $userId) {
            $codes = $this->codes($spec);
            $domaine = $this->upsertDomaine($spec['domaine'], $codes['domaine'], $userId);

            $existante = ESBTPLMDMention::with('domaine')->where('code', $codes['mention'])->first();
            if ($c = $this->conflitMention($existante, (int) $domaine->id, $codes['mention'], $domaine->name)) {
                throw new ConflitDeMaquette([$c]);
            }
            $mention = ESBTPLMDMention::updateOrCreate(
                ['code' => $codes['mention']],
                ['name' => $spec['mention']['name'], 'domaine_id' => $domaine->id, 'is_active' => true, 'created_by' => $existante?->created_by ?? $userId, 'updated_by' => $userId]
            );

            $filiere = $codes['filiere'] !== null
                ? ESBTPFiliere::updateOrCreate(
                    ['code' => $codes['filiere']],
                    ['name' => $spec['filiere']['name'], 'is_active' => true, 'updated_by' => $userId]
                        + (ESBTPFiliere::where('code', $codes['filiere'])->exists() ? [] : ['created_by' => $userId])
                )
                : null;

            $existant = ESBTPLMDParcours::with('mention')->where('code', $codes['parcours'])->first();
            if ($existant && (int) $existant->mention_id !== (int) $mention->id) {
                throw new ConflitDeMaquette([$this->conflitParcours($existant, $codes['parcours'], $mention->name)]);
            }
            $rules = app(LmdAcademicRuleProfile::class);
            $valeurs = [
                'name' => $spec['parcours']['name'],
                'mention_id' => $mention->id,
                // Totaux par defaut lus dans les reglages de l'ecole, pas ecrits en dur.
                'credits_licence' => $spec['parcours']['credits_licence'] ?? $existant?->credits_licence ?? $rules->diplomaCreditTotal('licence'),
                'credits_master' => $spec['parcours']['credits_master'] ?? $existant?->credits_master ?? $rules->diplomaCreditTotal('master'),
                'is_active' => true,
                'created_by' => $existant?->created_by ?? $userId,
                'updated_by' => $userId,
            ];
            if ($filiere !== null || ! $existant) {
                $valeurs['filiere_id'] = $filiere?->id;
            }
            $parcours = ESBTPLMDParcours::updateOrCreate(['code' => $codes['parcours']], $valeurs);

            return compact('domaine', 'mention', 'filiere', 'parcours');
        });
    }

    public const FORME_SETUP = 'setup';

    public const FORME_IMPORT = 'import';

    /** @return array{domaine: string, mention: string, parcours: string, filiere: ?string} */
    public function codes(array $spec): array
    {
        return [
            'domaine' => self::codeDeduit(ESBTPLMDDomaine::class, $spec['domaine'], self::FORME_SETUP),
            'mention' => self::codeDeduit(ESBTPLMDMention::class, $spec['mention'], self::FORME_SETUP),
            'parcours' => self::codeDeduit(ESBTPLMDParcours::class, $spec['parcours'], self::FORME_SETUP),
            'filiere' => isset($spec['filiere']['name']) && $spec['filiere']['name'] !== ''
                ? self::codeDeduit(ESBTPFiliere::class, $spec['filiere'], self::FORME_SETUP)
                : null,
        ];
    }

    /**
     * Le code donne, sinon celui d'une fiche existante sous l'une des deux formes
     * deduites du nom, sinon la forme propre a l'appelant. Les codes deja en base
     * restent stables : aucun des deux chemins ne cree le double de l'autre.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modele
     */
    public static function codeDeduit(string $modele, array $niveau, string $forme): string
    {
        $donne = trim((string) ($niveau['code'] ?? ''));
        if ($donne !== '') {
            return $donne;
        }
        $formes = [
            self::FORME_SETUP => Str::upper(Str::slug((string) $niveau['name'], '')),
            self::FORME_IMPORT => Str::slug((string) $niveau['name']),
        ];
        foreach ($formes as $candidat) {
            if ($modele::where('code', $candidat)->exists()) {
                return $candidat;
            }
        }

        return $formes[$forme];
    }

    private function upsertDomaine(array $data, string $code, ?int $userId): ESBTPLMDDomaine
    {
        $existant = ESBTPLMDDomaine::where('code', $code)->first();
        $valeurs = $this->deplacement->preserver([
            'name' => $data['name'],
            'is_active' => true,
            'created_by' => $existant?->created_by ?? $userId,
            'updated_by' => $userId,
        ], $data, 'nature');

        // La description et la nature ne sont ecrites que si l'appel les donne :
        // une omission n'efface pas ce que l'ecran a pose.
        return ESBTPLMDDomaine::updateOrCreate(['code' => $code], $this->deplacement->preserver($valeurs, $data, 'description'));
    }

    private function conflitMention(?ESBTPLMDMention $existante, int $domaineId, string $code, string $domaineNom): ?array
    {
        return $this->deplacement->siAutreParent($existante, 'domaine_id', $domaineId, 'MENTION', $code, fn ($e) => sprintf(
            "Le code « %s » désigne déjà la mention « %s » du domaine « %s ». La poser sous « %s » l'y déplacerait avec ses parcours : donnez à cette mention un code propre.",
            $code, $e->name, $e->domaine?->name ?? ('#'.$e->domaine_id), $domaineNom
        ));
    }

    private function conflitParcours(ESBTPLMDParcours $existant, string $code, string $mentionNom): array
    {
        return [
            'type' => 'PARCOURS',
            'code' => $code,
            'detail' => sprintf(
                "Le code « %s » désigne déjà le parcours « %s » de la mention « %s ». Le poser sous « %s » l'y déplacerait avec sa maquette : donnez à ce parcours un code propre.",
                $code, $existant->name, $existant->mention?->name ?? ('#'.$existant->mention_id), $mentionNom
            ),
        ];
    }

    private function ligne(string $niveau, string $code, ?string $avant, string $apres): array
    {
        return ['niveau' => $niveau, 'code' => $code, 'avant' => $avant, 'apres' => $apres];
    }
}
