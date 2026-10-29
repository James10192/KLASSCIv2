<?php

namespace App\Domain\Assistant\Actions\Matieres;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Domain\BtsTroncCommun\ResolutionDeMatiere;
use App\Domain\BtsTroncCommun\RetraitDeMaquette;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Support\Facades\DB;

/**
 * Propose de retirer des matières de la maquette BTS d'un couple filière ×
 * niveau. Constat et écriture par RetraitDeMaquette, le chemin de l'écran de
 * classification et de la CLI ; le retrait lui-même passe par
 * LiaisonsDeMatiere::retirer(), qui accepte aussi une ECUE LMD posée par erreur
 * (lmd-ecue-leak-bts-picker.md). Droit de l'écran : matieres.edit.
 *
 * Plus strict que la CLI, à dessein : une matière absente de la maquette ou un
 * libellé ambigu est une question, pas une ligne ignorée. Une matière qui porte
 * des évaluations sur ce couple n'est retirée que si la personne le confirme
 * (confirme_malgre_les_notes) : la note resterait en base sans plus apparaître
 * sur le bulletin.
 */
class RetirerDeMaquetteBts extends ActionAgent
{
    private const MAX = 50;

    public function cle(): string
    {
        return 'retrait_maquette_bts';
    }

    public function description(): string
    {
        return "PROPOSE de retirer des matières de la maquette BTS d'un couple filière + niveau (codes ou identifiants) : `matieres` = codes, identifiants ou noms exacts. "
            . "Si une matière porte des évaluations, demande d'abord confirmation à la personne, puis repasse confirme_malgre_les_notes=true. Rien n'est écrit avant « Valider ».";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'filiere' => ['type' => 'string', 'description' => 'Code ou identifiant de la filière.'],
                'niveau' => ['type' => 'string', 'description' => 'Code ou identifiant du niveau.'],
                'matieres' => ['type' => 'array', 'items' => ['type' => 'string']],
                'confirme_malgre_les_notes' => ['type' => 'boolean', 'description' => 'La personne a confirmé le retrait de matières déjà évaluées.'],
            ],
            'required' => ['filiere', 'niveau', 'matieres'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Retirer de la maquette BTS';
        [$filiere, $niveau, $manques] = $this->couple($args);
        $matieres = array_values(array_filter(array_map(fn ($m) => trim((string) $m), (array) ($args['matieres'] ?? [])), fn ($m) => $m !== ''));
        if ($matieres === []) {
            $manques[] = 'Quelles matières retirer ?';
        } elseif (count($matieres) > self::MAX) {
            $manques[] = 'Plus de '.self::MAX.' matières : découpe la demande.';
        }
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: $manques);
        }

        $entrees = array_map(fn ($m) => $this->entree($m), $matieres);
        $plan = app(RetraitDeMaquette::class)->preparer($filiere, $niveau, $entrees);
        foreach ($plan['ambigus'] as $a) {
            $manques[] = "« {$a['libelle']} » désigne plusieurs matières : ".implode(', ', array_map(fn ($c) => ($c['code'] ?? '?').' ('.$c['name'].')', $a['candidats'])).'. Laquelle ?';
        }
        foreach ($plan['introuvables'] as $i) {
            $manques[] = "Matière « {$i['libelle']} » introuvable.";
        }
        foreach ($plan['lignes'] as $l) {
            if (! $l['dans_la_maquette']) {
                $manques[] = "{$l['matiere']} ({$l['code']}) n'est pas dans la maquette de {$filiere->name} · {$niveau->name}.";
            }
        }
        $confirme = filter_var($args['confirme_malgre_les_notes'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if ($plan['notees'] !== [] && ! $confirme) {
            $manques[] = 'Ces matières portent des évaluations sur ce niveau : '
                .implode(', ', array_map(fn ($n) => "{$n['matiere']} ({$n['evaluations']})", $plan['notees']))
                .'. Retirées, elles disparaîtront du bulletin (les notes restent en base). La personne confirme-t-elle ?';
        }
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: array_values(array_unique($manques)));
        }

        $avertissements = $plan['notees'] === [] ? [] : array_map(
            fn ($n) => "{$n['matiere']} porte {$n['evaluations']} évaluation(s) : elle disparaîtra du bulletin, les notes restent en base.",
            $plan['notees']
        );

        return new Proposition(
            titre: $titre.' · '.$filiere->name.' · '.$niveau->name,
            resume: count($plan['lignes']).' matière(s) retirée(s) de la maquette.',
            tableau: [
                'colonnes' => ['Matière', 'Code', 'Évaluations sur ce niveau', 'Maquette'],
                'lignes' => array_map(fn ($l) => [(string) $l['matiere'], (string) ($l['code'] ?? '—'), (string) $l['evaluations_sur_ce_couple'], 'présente → retirée'], $plan['lignes']),
            ],
            avertissements: $avertissements,
            donnees: ['filiere_id' => (int) $filiere->id, 'niveau_id' => (int) $niveau->id, 'entrees' => $entrees],
            etat: ['lignes' => $plan['lignes']],
            risque: $plan['notees'] === [] ? 'moyen' : 'eleve',
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        $d = $proposition->donnees;
        $filiere = ESBTPFiliere::findOrFail($d['filiere_id']);
        $niveau = ESBTPNiveauEtude::findOrFail($d['niveau_id']);
        $retrait = app(RetraitDeMaquette::class);

        $ecrit = DB::transaction(function () use ($retrait, $filiere, $niveau, $d, $proposition) {
            DB::table('esbtp_matiere_filiere_niveau')->where('filiere_id', $filiere->id)->where('niveau_etude_id', $niveau->id)->lockForUpdate()->get(['id']);
            $plan = $retrait->preparer($filiere, $niveau, $d['entrees']);
            if ($plan['lignes'] !== $proposition->etat['lignes']) {
                throw new PropositionPerimee('La maquette a changé depuis la proposition.');
            }

            return $retrait->appliquer($filiere, $niveau, $plan['lignes']);
        });

        return [
            'message' => "{$ecrit['retirees']} matière(s) retirée(s) de la maquette {$filiere->name} · {$niveau->name}.",
            'lien' => route('esbtp.matieres.classification', ['filiere_id' => $filiere->id, 'niveau_id' => $niveau->id], false),
            'model_type' => ESBTPFiliere::class,
            'model_id' => (int) $filiere->id,
            'details' => ['retirees' => $ecrit['retirees'], 'matieres' => array_column($ecrit['lignes'], 'matiere_id')],
        ];
    }

    /**
     * RetraitDeMaquette résout un identifiant ou un nom ; Nanan reçoit souvent
     * un CODE. Un code qui désigne une seule matière devient son identifiant,
     * tout le reste passe tel quel (et un nom ambigu reste une question).
     */
    private function entree(string $saisie): array|string
    {
        if (ctype_digit($saisie)) {
            return ['id' => (int) $saisie];
        }
        $parCode = ESBTPMatiere::whereRaw('UPPER(code) = ?', [mb_strtoupper($saisie)])->limit(2)->pluck('id');

        return $parCode->count() === 1 ? ['id' => (int) $parCode->first(), 'nom' => $saisie] : $saisie;
    }

    /** @return array{0: ?ESBTPFiliere, 1: ?ESBTPNiveauEtude, 2: string[]} */
    private function couple(array $args): array
    {
        $resolution = app(ResolutionDeMatiere::class);
        $manques = [];
        $f = trim((string) ($args['filiere'] ?? ''));
        $n = trim((string) ($args['niveau'] ?? ''));
        $filiere = $f === '' ? null : $resolution->filiere($f);
        $niveau = $n === '' ? null : $resolution->niveau($n);
        foreach ([['Quelle filière ?', 'Filière', $f, $filiere], ['Quel niveau ?', 'Niveau', $n, $niveau]] as [$question, $quoi, $saisi, $resolu]) {
            if ($resolu === null) {
                $manques[] = $question;
            } elseif ($resolu['statut'] === 'ambigu') {
                $manques[] = "{$quoi} « {$saisi} » : plusieurs correspondances, donne son code.";
            } elseif ($resolu['statut'] !== 'ok') {
                $manques[] = "{$quoi} « {$saisi} » introuvable.";
            }
        }

        return [($filiere['statut'] ?? null) === 'ok' ? $filiere['filiere'] : null, ($niveau['statut'] ?? null) === 'ok' ? $niveau['niveau'] : null, $manques];
    }
}
