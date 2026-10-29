<?php

namespace App\Domain\Assistant\Actions\Classes;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPLMDMention;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPNiveauEtude;
use App\Services\Classe\CreationClasseService;
use Illuminate\Support\Facades\DB;

/**
 * Crée des classes LMD sous une mention ou un parcours existant, sans
 * inventer de structure académique. Proposition puis validation obligatoire.
 */
class AjouterClassesLmd extends ActionAgent
{
    public function cle(): string
    {
        return 'creation_classes_lmd';
    }

    public function libelle(): string
    {
        return 'Préparation des classes de Licence, Master ou Doctorat…';
    }

    public function description(): string
    {
        return "PROPOSE de créer des classes LMD (Licence 1/2/3, Master 1/2, etc.), jamais BTS. "
            . "cible : code du parcours, de la mention ou de sa filière associée (ex. GGT). "
            . "niveaux : liste explicite (ex. L1, L2, L3). places : capacité par classe. "
            . "Si une cible identifie plusieurs parcours/mentions, demander une précision. "
            . "Ne rien créer avant la validation humaine.";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'cible' => ['type' => 'string', 'description' => 'Code du parcours, de la mention ou de la filière associée, ex. GGT.'],
                'type_cible' => ['type' => 'string', 'enum' => ['parcours', 'mention'], 'description' => 'Préciser si le code est ambigu.'],
                'niveaux' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Niveaux LMD demandés, ex. ["L1","L2","L3"].'],
                'places' => ['type' => 'integer', 'description' => 'Nombre de places par nouvelle classe.'],
            ],
            'required' => ['cible', 'niveaux', 'places'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Créer des classes LMD';
        $manques = [];
        $cible = mb_strtoupper(trim((string) ($args['cible'] ?? '')));
        $places = (int) ($args['places'] ?? 0);
        $codesNiveaux = (array) ($args['niveaux'] ?? []);
        $typeDemande = (string) ($args['type_cible'] ?? '');

        if ($cible === '') {
            $manques[] = 'Quel est le code de la mention ou du parcours LMD ?';
        }
        if ($places < 1) {
            $manques[] = 'Combien de places faut-il dans chaque classe ?';
        }
        if ($codesNiveaux === []) {
            $manques[] = 'Quels niveaux LMD faut-il ouvrir (ex. L1, L2, L3) ?';
        }
        $annee = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        if (! $annee) {
            $manques[] = "Aucune année universitaire courante : définis-en une d'abord.";
        }
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: $manques);
        }

        // Le libellé « filière GGT » peut désigner le code d'un parcours, de
        // sa filière d'ancrage, ou d'une mention. On ne choisit pas au hasard.
        $parcours = ESBTPLMDParcours::query()->where('is_active', true)
            ->where(function ($q) use ($cible) {
                $q->where('code', $cible)
                    ->orWhereHas('filiere', fn ($f) => $f->where('code', $cible));
            })->get();
        $mentions = ESBTPLMDMention::query()->where('is_active', true)->where('code', $cible)->get();

        $candidats = collect();
        if ($typeDemande !== 'mention') {
            foreach ($parcours as $p) {
                $candidats->push(['type' => 'parcours', 'id' => (int) $p->id, 'code' => $p->code, 'nom' => $p->name]);
            }
        }
        if ($typeDemande !== 'parcours') {
            foreach ($mentions as $m) {
                $candidats->push(['type' => 'mention', 'id' => (int) $m->id, 'code' => $m->code, 'nom' => $m->name]);
            }
        }
        if ($candidats->isEmpty()) {
            return new Proposition(titre: $titre, resume: '', manques: [
                "Aucun parcours ou mention LMD actif n'est relié au code {$cible}. Vérifie la structure académique ; Nanan ne doit pas créer un parcours implicite.",
            ]);
        }
        if ($candidats->count() > 1) {
            return new Proposition(titre: $titre, resume: '', manques: [
                'Code ambigu : '.implode(' ; ', $candidats->map(fn ($c) => $c['type'].' '.$c['code'].' ('.$c['nom'].')')->all())
                . '. Précise le parcours ou la mention exacte avant de créer.',
            ]);
        }
        $choix = $candidats->first();
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->get()
            ->filter(fn ($n) => $n->estUnCycleLmd() && $n->anneeCoherenteAvecSonCycle() === true);

        $retenus = collect();
        foreach ($codesNiveaux as $code) {
            $cherche = mb_strtoupper(preg_replace('/[\s_-]+/u', '', trim((string) $code)));
            $niveau = $niveaux->first(function ($n) use ($cherche) {
                $codeNiveau = mb_strtoupper(preg_replace('/[\s_-]+/u', '', (string) $n->code));
                $nom = mb_strtoupper(preg_replace('/[\s_-]+/u', '', (string) $n->name));
                $cycle = match ($n->type) {
                    'Licence' => 'L', 'Master' => 'M', 'Doctorat' => 'D', default => '',
                };
                $rang = $n->type === 'Master' ? (int) $n->year - 3 : ($n->type === 'Doctorat' ? (int) $n->year - 5 : (int) $n->year);
                return $cherche === $codeNiveau || $cherche === $nom
                    || $cherche === $cycle.$rang || $cherche === mb_strtoupper($n->type).$rang;
            });
            if (! $niveau) {
                $manques[] = "Niveau LMD inconnu, inactif ou incohérent : {$code}.";
            } elseif (! $retenus->contains('id', $niveau->id)) {
                $retenus->push($niveau);
            }
        }
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: $manques);
        }
        if ($retenus->count() > 30) {
            return new Proposition(titre: $titre, resume: '', manques: ['Trop de classes demandées en une opération.']);
        }

        $classes = [];
        $avertissements = [];
        $codesPris = ESBTPClasse::withTrashed()->pluck('code')->map(fn ($c) => mb_strtoupper((string) $c))->flip();
        $lignesEtat = [];
        foreach ($retenus as $niveau) {
            $existantes = ESBTPClasse::query()
                ->where('systeme_academique', 'LMD')
                ->where('niveau_etude_id', $niveau->id)
                ->when($choix['type'] === 'parcours',
                    fn ($q) => $q->where('parcours_id', $choix['id']),
                    fn ($q) => $q->whereNull('parcours_id')->whereHas('filiere', fn ($f) => $f->where('lmd_mention_id', $choix['id'])))
                ->orderBy('id')->get(['id', 'name', 'code']);
            $lignesEtat[(string) $niveau->id] = $existantes->map(fn ($c) => [$c->id, $c->name, $c->code])->all();

            $cycle = match ($niveau->type) {
                'Licence' => 'L', 'Master' => 'M', 'Doctorat' => 'D',
            };
            $rang = $niveau->type === 'Master' ? (int) $niveau->year - 3
                : ($niveau->type === 'Doctorat' ? (int) $niveau->year - 5 : (int) $niveau->year);
            // Conserver le code donné par l'école (GGT), même si le parcours
            // interne lié possède un autre code (ex. PAR-GGT).
            $prefixe = $cible.' '.$cycle.$rang;
            $max = 0;
            foreach ($existantes as $s) {
                if (preg_match('/^'.preg_quote($prefixe, '/').'([A-Z])?$/ui', trim($s->name), $m)) {
                    $max = max($max, empty($m[1]) ? 1 : ord(mb_strtoupper($m[1])) - 64);
                }
            }
            $codeBase = mb_strtoupper(preg_replace('/[^A-Z0-9]+/i', '_', $cible)).'_'.$cycle.$rang;
            $nouvelle = null;
            for ($i = $max + 1; $i <= 26; $i++) {
                $lettre = chr(64 + $i);
                $code = $codeBase.$lettre;
                if (! $codesPris->has(mb_strtoupper($code))) {
                    $nouvelle = [
                        'name' => $prefixe.$lettre, 'code' => $code,
                        'niveau_etude_id' => (int) $niveau->id, 'places_totales' => $places,
                    ];
                    $codesPris->put(mb_strtoupper($code), true);
                    break;
                }
            }
            if ($nouvelle === null) {
                $manques[] = 'Impossible de générer un code libre (A à Z) pour '.$prefixe.'.';
                continue;
            }
            if ($existantes->isNotEmpty() && $max === 0) {
                $avertissements[] = 'Nommage à vérifier pour '.$prefixe.' : des classes existent déjà sous un autre format.';
            }
            $classes[] = $nouvelle;
        }
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: $manques);
        }

        return new Proposition(
            titre: $titre,
            resume: count($classes).' classe(s) LMD de '.$places.' places sous '.$choix['type'].' '.$choix['nom'].'.',
            tableau: ['colonnes' => ['Cible', 'Niveau', 'Nouvelle classe', 'Code', 'Places'],
                'lignes' => array_map(function ($c) use ($choix, $retenus) {
                    $n = $retenus->firstWhere('id', $c['niveau_etude_id']);
                    return [$choix['nom'], $n->name, $c['name'], $c['code'], (string) $c['places_totales']];
                }, $classes)],
            avertissements: $avertissements,
            donnees: ['cible' => $choix, 'classes' => $classes],
            etat: ['cible' => $choix, 'classes_existantes' => $lignesEtat, 'annee_courante' => (int) $annee->id],
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        $choix = $proposition->donnees['cible'];
        $anneeId = ESBTPAnneeUniversitaire::where('is_current', true)->value('id');

        $ids = DB::transaction(function () use ($proposition, $choix, $anneeId, $user) {
            $codes = array_column($proposition->donnees['classes'], 'code');
            if (ESBTPClasse::withTrashed()->whereIn('code', $codes)->lockForUpdate()->exists()) {
                throw new PropositionPerimee('Un code de classe a été utilisé entre-temps. Relance la proposition.');
            }
            $crees = [];
            foreach ($proposition->donnees['classes'] as $c) {
                $c['annee_universitaire_id'] = $anneeId;
                $c['is_active'] = true;
                if ($choix['type'] === 'parcours') {
                    $c['parcours_id'] = $choix['id'];
                } else {
                    // La clé historique du formulaire porte l'ID de mention ;
                    // CreationClasseService la convertit en vraie filière.
                    $c['filiere_id'] = $choix['id'];
                }
                $crees[] = app(CreationClasseService::class)->creer($c, (int) $user->id)->id;
            }
            return $crees;
        });

        return [
            'message' => count($ids).' classe(s) LMD créée(s).',
            'lien' => route('esbtp.classes.index', [], false),
            'model_type' => ESBTPClasse::class,
            'model_id' => $ids[0] ?? null,
            'details' => ['ids' => $ids],
        ];
    }
}
