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
use App\Domain\Academique\ReferentielAcademique;
use App\Services\LMD\HierarchieLmd;
use App\Services\LMD\PreparationParcoursClassesLmd;
use App\Services\Classe\CreationClasseService;
use Illuminate\Support\Facades\DB;

/**
 * Crée des classes LMD avec structure existante ou complète sa hiérarchie
 * depuis les noms/codes confirmés par l'école. Proposition puis validation obligatoire.
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
            . "Si la structure manque, demander codes ET noms du domaine, de la mention et du parcours, "
            . "puis rappeler cet outil avec structure={domaine:{code,name},mention:{code,name},parcours:{code,name},filiere?:{code,name}}. "
            . "Réutiliser les éléments existants sans les renommer. Proposer aussi les niveaux LMD manquants ; "
            . "ne rien créer avant la validation humaine.";
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
                'structure' => ['type' => 'object', 'description' => 'À renseigner si les parcours/mentions n’existent pas : noms/codes EXACTS fournis par l’école.', 'properties' => [
                    'domaine' => ['type' => 'object', 'properties' => ['code' => ['type' => 'string'], 'name' => ['type' => 'string']]],
                    'mention' => ['type' => 'object', 'properties' => ['code' => ['type' => 'string'], 'name' => ['type' => 'string']]],
                    'parcours' => ['type' => 'object', 'properties' => ['code' => ['type' => 'string'], 'name' => ['type' => 'string']]],
                    'filiere' => ['type' => 'object', 'properties' => ['code' => ['type' => 'string'], 'name' => ['type' => 'string']]],
                ]],
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
        $structure = null;
        $avertissementsStructure = [];
        if ($candidats->isEmpty()) {
            $preparation = app(PreparationParcoursClassesLmd::class)->preparer(
                $cible, (array) ($args['structure'] ?? []), $typeDemande, $user
            );
            if ($preparation['manques'] !== []) {
                return new Proposition(titre: $titre, resume: '', manques: $preparation['manques'],
                    avertissements: $preparation['avertissements']);
            }
            $choix = $preparation['choix'];
            $structure = ['spec' => $preparation['spec'], 'apercu' => $preparation['apercu']];
            $avertissementsStructure = $preparation['avertissements'];
        } else {
            if ($candidats->count() > 1) {
                return new Proposition(titre: $titre, resume: '', manques: [
                    'Code ambigu : '.implode(' ; ', $candidats->map(fn ($c) => $c['type'].' '.$c['code'].' ('.$c['nom'].')')->all())
                    . '. Précise le parcours ou la mention exacte avant de créer.',
                ]);
            }
            $choix = $candidats->first();
        }
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->get()
            ->filter(fn ($n) => $n->estUnCycleLmd() && $n->anneeCoherenteAvecSonCycle() === true);

        $retenus = collect();
        $aCreer = [];
        foreach ($codesNiveaux as $code) {
            $cherche = mb_strtoupper(preg_replace('/[\s_-]+/u', '', trim((string) $code)));
            // Les codes internes peuvent varier ; le couple cycle + année
            // reste l'identité métier (Licence 1 = year 1, Master 1 = year 4).
            $cycles = ['L' => ['Licence', 0], 'M' => ['Master', 3], 'D' => ['Doctorat', 5]];
            $cycleDemande = null;
            $anneeDemande = null;
            if (preg_match('/^(?:LMD)?(L|M|D)([1-3])$/', $cherche, $m)) {
                [$cycleDemande, $decalage] = $cycles[$m[1]];
                $anneeDemande = (int) $m[2] + $decalage;
            } elseif (preg_match('/^(LICENCE|MASTER|DOCTORAT)([1-3])$/', $cherche, $m)) {
                $typeAbr = ['LICENCE' => 'L', 'MASTER' => 'M', 'DOCTORAT' => 'D'][$m[1]];
                [$cycleDemande, $decalage] = $cycles[$typeAbr];
                $anneeDemande = (int) $m[2] + $decalage;
            }
            if ($cycleDemande !== null && ! in_array($anneeDemande, ESBTPNiveauEtude::ANNEES_PAR_CYCLE_LMD[$cycleDemande], true)) {
                $manques[] = "Niveau LMD hors cycle : {$code}.";
                continue;
            }

            $correspondances = $niveaux->filter(function ($n) use ($cherche, $cycleDemande, $anneeDemande) {
                $codeNiveau = mb_strtoupper(preg_replace('/[\s_-]+/u', '', (string) $n->code));
                $nom = mb_strtoupper(preg_replace('/[\s_-]+/u', '', (string) $n->name));
                return $cherche === $codeNiveau || $cherche === $nom
                    || ($cycleDemande === $n->type && $anneeDemande === (int) $n->year);
            })->values();
            $exactes = $correspondances->filter(fn ($n) => mb_strtoupper(
                preg_replace('/[\s_-]+/u', '', (string) $n->code)
            ) === $cherche);
            $niveau = $exactes->count() === 1 ? $exactes->first()
                : ($correspondances->count() === 1 ? $correspondances->first() : null);
            if ($correspondances->count() > 1 && $exactes->count() !== 1) {
                $manques[] = 'Niveau ambigu : '.$code.'. Choisis son code exact : '
                    .$correspondances->pluck('code')->implode(', ').'.';
                continue;
            }
            if (! $niveau && $cycleDemande !== null) {
                $existant = ESBTPNiveauEtude::withTrashed()->where('type', $cycleDemande)
                    ->where('year', $anneeDemande)->first();
                if ($existant) {
                    $manques[] = "Le niveau {$code} existe mais est inactif ou supprimé : réactive-le avant de créer une classe.";
                    continue;
                }
                if (! $user->can('niveaux.create')) {
                    $manques[] = "Le niveau {$code} manque : permission niveaux.create requise pour le créer.";
                    continue;
                }
                $rang = $cycleDemande === 'Master' ? $anneeDemande - 3
                    : ($cycleDemande === 'Doctorat' ? $anneeDemande - 5 : $anneeDemande);
                $initiale = $cycleDemande[0];
                $nom = $cycleDemande.' '.$rang;
                $codeNouveau = 'LMD-'.$initiale.$rang;
                $aCreer[] = ['name' => $nom, 'type' => $cycleDemande, 'year' => $anneeDemande,
                    'code' => $codeNouveau, 'libelle' => $nom, 'is_active' => true];
                $niveau = new ESBTPNiveauEtude(['name' => $nom, 'code' => $codeNouveau,
                    'type' => $cycleDemande, 'year' => $anneeDemande, 'is_active' => true]);
            } elseif (! $niveau) {
                $manques[] = "Niveau LMD inconnu, inactif ou incohérent : {$code}.";
                continue;
            }
            $cle = $niveau->type.'#'.$niveau->year;
            if (! $retenus->contains(fn ($n) => $n->type.'#'.$n->year === $cle)) {
                $retenus->push($niveau);
            }
        }
        $planNiveaux = $aCreer === [] ? ['lignes' => [], 'refus' => []]
            : app(ReferentielAcademique::class)->planNiveaux($aCreer);
        $manques = array_merge($manques, $planNiveaux['refus']);
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: array_values(array_unique($manques)));
        }
        if ($retenus->count() > 30) {
            return new Proposition(titre: $titre, resume: '', manques: ['Trop de classes demandées en une opération.']);
        }

        $classes = [];
        $avertissements = $avertissementsStructure;
        $codesPris = ESBTPClasse::withTrashed()->pluck('code')->map(fn ($c) => mb_strtoupper((string) $c))->flip();
        $lignesEtat = [];
        foreach ($retenus as $niveau) {
            $existantes = ESBTPClasse::query()
                ->where('systeme_academique', 'LMD')
                ->where('niveau_etude_id', $niveau->id ?? 0)
                ->when($choix['type'] === 'parcours',
                    fn ($q) => $q->where('parcours_id', $choix['id'] ?? 0),
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
                        'niveau_etude_id' => (int) ($niveau->id ?? 0),
                        'niveau_cle' => $niveau->type.'#'.$niveau->year,
                        'niveau_nom' => $niveau->name,
                        'places_totales' => $places,
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

        if ($structure !== null) {
            foreach ($structure['apercu']['lignes'] as $ligne) {
                $avertissements[] = $ligne['niveau'].' '.$ligne['code'].' : '.($ligne['avant'] === null ? 'à créer' : 'à réutiliser');
            }
        }
        foreach ($planNiveaux['lignes'] as $ligne) {
            $avertissements[] = 'Niveau '.$ligne['name'].' ('.$ligne['code'].') : à créer avant les classes.';
        }

        return new Proposition(
            titre: $titre,
            resume: count($classes).' classe(s) LMD de '.$places.' places sous '.$choix['type'].' '.$choix['nom'].'.',
            tableau: ['colonnes' => ['Cible', 'Niveau', 'Nouvelle classe', 'Code', 'Places'],
                'lignes' => array_map(function ($c) use ($choix, $retenus) {
                    return [$choix['nom'], $c['niveau_nom'], $c['name'], $c['code'], (string) $c['places_totales']];
                }, $classes)],
            avertissements: $avertissements,
            donnees: ['cible' => $choix, 'classes' => $classes, 'structure' => $structure,
                'niveaux_a_creer' => $planNiveaux['lignes']],
            etat: ['cible' => $choix, 'structure' => $structure?['apercu']['lignes'],
                'niveaux_a_creer' => $planNiveaux['lignes'],
                'classes_existantes' => $lignesEtat, 'annee_courante' => (int) $annee->id],
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        $choix = $proposition->donnees['cible'];
        $anneeId = ESBTPAnneeUniversitaire::where('is_current', true)->value('id');

        $ids = DB::transaction(function () use ($proposition, $choix, $anneeId, $user) {
            $structure = $proposition->donnees['structure'];
            if ($structure !== null) {
                if (! $user->can('lmd.structure.manage')) {
                    throw new PropositionPerimee('La permission de structure LMD a été retirée.');
                }
                $service = app(HierarchieLmd::class);
                if ($service->apercu($structure['spec'])['lignes'] !== $structure['apercu']['lignes']) {
                    throw new PropositionPerimee('La hiérarchie LMD a changé : relance la proposition.');
                }
                $resultat = $service->installer($structure['spec'], (int) $user->id);
                $choix['id'] = (int) $resultat[$choix['type']]->id;
            }
            $niveaux = $proposition->donnees['niveaux_a_creer'];
            if ($niveaux !== []) {
                if (! $user->can('niveaux.create')) {
                    throw new PropositionPerimee('La permission de créer des niveaux LMD a été retirée.');
                }
                $serviceNiveaux = app(ReferentielAcademique::class);
                $plan = $serviceNiveaux->planNiveaux(array_map(fn ($l) => [
                    'name' => $l['name'], 'type' => $l['type'], 'year' => $l['year'],
                    'code' => $l['code'], 'libelle' => $l['libelle'], 'is_active' => true,
                ], $niveaux));
                if ($plan['refus'] !== [] || $plan['lignes'] !== $niveaux) {
                    throw new PropositionPerimee('La configuration des niveaux a changé : relance la proposition.');
                }
                $serviceNiveaux->enregistrerNiveaux($niveaux);
            }
            $codes = array_column($proposition->donnees['classes'], 'code');
            if (ESBTPClasse::withTrashed()->whereIn('code', $codes)->lockForUpdate()->exists()) {
                throw new PropositionPerimee('Un code de classe a été utilisé entre-temps. Relance la proposition.');
            }
            $crees = [];
            foreach ($proposition->donnees['classes'] as $c) {
                // La création de niveaux dans cette même transaction peut
                // attribuer des IDs nouveaux, jamais connus à la proposition.
                [$type, $year] = explode('#', $c['niveau_cle']);
                $niveau = ESBTPNiveauEtude::where('type', $type)->where('year', (int) $year)
                    ->where('is_active', true)->firstOrFail();
                $c['niveau_etude_id'] = (int) $niveau->id;
                unset($c['niveau_cle'], $c['niveau_nom']);
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
