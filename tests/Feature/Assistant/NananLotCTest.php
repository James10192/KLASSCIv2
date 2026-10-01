<?php

namespace Tests\Feature\Assistant;

use App\Models\ESBTPBulletin;
use App\Domain\Assistant\Actions\Bulletins\EnregistrerMoyennes;
use App\Domain\Assistant\Actions\Bulletins\GenererBulletinsManquants;
use App\Domain\Assistant\Actions\ContexteDEchange;
use App\Domain\Assistant\Actions\Evaluations\ChangerMatiereEvaluation;
use App\Domain\Assistant\Actions\Evaluations\DeplacerEvaluationsDePeriode;
use App\Domain\Assistant\Actions\Lmd\AffecterEnseignantsLmd;
use App\Domain\Assistant\Actions\Lmd\ImporterMaquetteLmd;
use App\Domain\Assistant\Actions\Lmd\InstallerHierarchieLmd;
use App\Domain\Assistant\Actions\Lmd\RattacherClassesAuParcours;
use App\Domain\Assistant\Actions\Notes\CorrigerNotes;
use App\Domain\Assistant\Flux\UiMessageStream;
use App\Domain\Assistant\Fournisseurs\RequeteModele;
use App\Domain\Assistant\Harnais\BoucleAgent;
use App\Domain\Assistant\Harnais\ConstructeurDePrompt;
use App\Domain\Assistant\Modeles\ModeleIa;
use App\Domain\Assistant\Outils\CatalogueOutils;
use App\Domain\Assistant\Pieces\PiecesJointes;
use App\Domain\Bulletins\Taches\BulletinTache;
use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\PaywallMiddleware;
use App\Models\ChatbotConversation;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPLMDDomaine;
use App\Models\ESBTPLMDMention;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPNote;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPResultat;
use App\Models\ESBTPUniteEnseignement;
use App\Models\User;
use App\Services\LMD\LMDImportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;
use Tests\Unit\Domain\Assistant\FauxFournisseur;

/**
 * Lot C : neuf opérations faites en CLI pour les écoles, apprises à Nanan.
 * Pour chacune : rien n'est écrit avant « Valider », tout l'est après ; ce qui
 * manque est demandé ; une donnée qui bouge entre-temps annule la validation.
 */
class NananLotCTest extends TestCase
{
    use DatabaseTransactions;
    use MonteUneClasseBts;

    private const PERMISSIONS = ['notes.edit', 'bulletins.edit', 'evaluations.edit', 'classes.edit',
        'lmd.structure.manage', 'lmd.planning.edit', 'admin.access'];

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([PaywallMiddleware::class, EnsureInstalled::class, CheckInstalled::class]);
        Role::findOrCreate('superAdmin', 'web');
        Role::findOrCreate('enseignant', 'web');
        foreach (self::PERMISSIONS as $p) {
            Permission::findOrCreate($p, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        ESBTPAnneeUniversitaire::query()->update(['is_current' => false]);
        $this->monterLaClasse();
        $this->annee->update(['is_current' => true]);
        $this->admin = User::withoutEvents(fn () => User::factory()->create(['username' => 'u_'.Str::lower(Str::random(8))]));
        $this->admin->assignRole('superAdmin');
        app(ContexteDEchange::class)->conversation = ChatbotConversation::create([
            'user_id' => $this->admin->id, 'session_id' => (string) Str::uuid(), 'last_activity_at' => now(),
        ]);
    }

    private function valider(array $resultat): array
    {
        $this->assertSame('approbation', $resultat['widget']['kind'] ?? null, json_encode($resultat, JSON_UNESCAPED_UNICODE));

        return $this->actingAs($this->admin)
            ->postJson($resultat['widget']['valider_url'], ['jeton' => $resultat['widget']['jeton']])
            ->json();
    }

    private function manques(array $resultat): string
    {
        $this->assertArrayNotHasKey('widget', $resultat, 'une proposition incomplète ne doit pas être présentée');

        return implode(' ', $resultat['manques'] ?? []);
    }

    private function piece(array $colonnes, array $lignes): string
    {
        return app(PiecesJointes::class)->garder($this->admin->id, 'fichier.xlsx', ['colonnes' => $colonnes, 'lignes' => $lignes, 'tronque' => false]);
    }

    // --- Notes et moyennes ---------------------------------------------------------

    public function test_corriger_une_note_n_ecrit_rien_avant_valider_puis_recalcule_la_moyenne(): void
    {
        $matiere = $this->matiereConfiguree();
        $eleve = $this->etudiantInscrit();
        $evaluation = $this->evaluationDe($matiere);
        $this->noter($eleve, $evaluation, 8);

        $r = app(CorrigerNotes::class)->executeAuthorized(['matricule' => $eleve->matricule, 'motif' => 'Copie revue après réclamation',
            'notes' => [['evaluation_id' => $evaluation->id, 'note' => 15]]], $this->admin);
        $this->assertSame(8.0, (float) ESBTPNote::where('etudiant_id', $eleve->id)->value('note'), 'rien avant Valider');

        $this->assertSame('executee', $this->valider($r)['statut']);
        $this->assertSame(15.0, (float) ESBTPNote::where('etudiant_id', $eleve->id)->value('note'));
        $this->assertSame(15.0, (float) ESBTPResultat::where(['etudiant_id' => $eleve->id, 'matiere_id' => $matiere->id])->value('moyenne'));
    }

    public function test_corriger_demande_le_motif_et_renvoie_une_note_jamais_saisie_vers_la_saisie(): void
    {
        $matiere = $this->matiereConfiguree();
        $eleve = $this->etudiantInscrit();
        $evaluation = $this->evaluationDe($matiere);

        $this->assertStringContainsString('motif', $this->manques(app(CorrigerNotes::class)->executeAuthorized(
            ['etudiant_id' => $eleve->id, 'motif' => '', 'notes' => [['evaluation_id' => $evaluation->id, 'note' => 12]]], $this->admin)));
        $this->assertStringContainsString('proposer_saisie_notes', $this->manques(app(CorrigerNotes::class)->executeAuthorized(
            ['etudiant_id' => $eleve->id, 'motif' => 'Réclamation de l’élève', 'notes' => [['evaluation_id' => $evaluation->id, 'note' => 12]]], $this->admin)));
    }

    public function test_une_note_modifiee_entre_temps_rend_la_proposition_perimee(): void
    {
        $evaluation = $this->evaluationDe($this->matiereConfiguree());
        $eleve = $this->etudiantInscrit();
        $this->noter($eleve, $evaluation, 8);
        $r = app(CorrigerNotes::class)->executeAuthorized(['etudiant_id' => $eleve->id, 'motif' => 'Copie revue après réclamation',
            'notes' => [['evaluation_id' => $evaluation->id, 'note' => 15]]], $this->admin);

        ESBTPNote::where('etudiant_id', $eleve->id)->update(['note' => 11]);

        $this->assertSame('perimee', $this->valider($r)['statut']);
        $this->assertSame(11.0, (float) ESBTPNote::where('etudiant_id', $eleve->id)->value('note'));
    }

    public function test_enregistrer_une_moyenne_puis_la_retirer(): void
    {
        $matiere = $this->matiereConfiguree();
        $eleve = $this->etudiantInscrit();
        $args = ['etudiant_id' => $eleve->id, 'classe' => (string) $this->classe->id, 'periode' => 'S1', 'motif' => 'Décision du conseil de classe'];

        $r = app(EnregistrerMoyennes::class)->executeAuthorized($args + ['moyennes' => [['matiere_id' => $matiere->id, 'moyenne' => 12.5]]], $this->admin);
        $this->assertSame(0, ESBTPResultat::where('etudiant_id', $eleve->id)->count(), 'rien avant Valider');
        $this->valider($r);
        $this->assertSame(12.5, (float) ESBTPResultat::where(['etudiant_id' => $eleve->id, 'matiere_id' => $matiere->id])->value('moyenne'));

        $this->valider(app(EnregistrerMoyennes::class)->executeAuthorized($args + ['moyennes' => [['matiere_id' => $matiere->id, 'retirer' => true]]], $this->admin));
        $this->assertSame(0, ESBTPResultat::where('etudiant_id', $eleve->id)->count());
    }

    public function test_moyenne_refusee_hors_inscription_ou_en_classe_lmd(): void
    {
        $matiere = $this->matiereConfiguree();
        $absent = \App\Models\ESBTPEtudiant::factory()->create();
        $args = ['classe' => (string) $this->classe->id, 'periode' => 'S1', 'motif' => 'Décision du conseil de classe',
            'moyennes' => [['matiere_id' => $matiere->id, 'moyenne' => 12]]];

        $this->assertStringContainsString("n'est pas inscrit", $this->manques(app(EnregistrerMoyennes::class)->executeAuthorized($args + ['etudiant_id' => $absent->id], $this->admin)));

        $eleve = $this->etudiantInscrit();
        $this->classe->update(['systeme_academique' => 'LMD']);
        $this->assertStringContainsString('Classe LMD', $this->manques(app(EnregistrerMoyennes::class)->executeAuthorized($args + ['etudiant_id' => $eleve->id], $this->admin)));
    }

    // --- Évaluations ---------------------------------------------------------------

    public function test_ranger_une_evaluation_en_s2_deplace_ses_notes(): void
    {
        $evaluation = $this->evaluationDe($this->matiereConfiguree());
        $this->noter($this->etudiantInscrit(), $evaluation, 14);

        $r = app(DeplacerEvaluationsDePeriode::class)->executeAuthorized(['evaluation_ids' => [$evaluation->id], 'periode' => 'S2'], $this->admin);
        $this->assertSame('semestre1', $evaluation->fresh()->periode, 'rien avant Valider');
        $this->valider($r);

        $this->assertSame('semestre2', $evaluation->fresh()->periode);
        $this->assertSame(2, (int) ESBTPEvaluation::numeroDeSemestre((string) ESBTPNote::where('evaluation_id', $evaluation->id)->value('semestre')));

        $this->assertStringContainsString('Rien à déplacer', $this->manques(app(DeplacerEvaluationsDePeriode::class)
            ->executeAuthorized(['evaluation_ids' => [$evaluation->id], 'periode' => 'S2'], $this->admin)));
        $this->assertStringContainsString('introuvable', $this->manques(app(DeplacerEvaluationsDePeriode::class)
            ->executeAuthorized(['evaluation_ids' => [999999999], 'periode' => 'S1'], $this->admin)));
    }

    public function test_rebasculer_une_evaluation_posee_sur_une_ecue_vers_la_matiere_bts(): void
    {
        $bts = $this->matiereConfiguree();
        $ecue = ESBTPMatiere::factory()->create(['unite_enseignement_id' => $this->uniteLmd()->id]);
        // Posée avant le garde d'écriture : c'est l'état à réparer.
        $evaluation = ESBTPEvaluation::withoutEvents(fn () => ESBTPEvaluation::factory()->create([
            'matiere_id' => $ecue->id, 'classe_id' => $this->classe->id, 'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1', 'status' => 'published', 'bareme' => 20, 'coefficient' => 1,
        ]));
        $this->noter($this->etudiantInscrit(), $evaluation, 12);

        // Le sens inverse romprait la cohérence : refusé.
        $this->assertStringContainsString('Refus', $this->manques(app(ChangerMatiereEvaluation::class)
            ->executeAuthorized(['evaluation_id' => $this->evaluationDe($bts)->id, 'matiere_id' => $ecue->id], $this->admin)));

        $r = app(ChangerMatiereEvaluation::class)->executeAuthorized(['evaluation_id' => $evaluation->id, 'matiere_id' => $bts->id], $this->admin);
        $this->assertSame($ecue->id, (int) $evaluation->fresh()->matiere_id, 'rien avant Valider');
        $this->valider($r);

        $this->assertSame($bts->id, (int) $evaluation->fresh()->matiere_id);
        $this->assertSame([$bts->id], ESBTPNote::where('evaluation_id', $evaluation->id)->pluck('matiere_id')->map(fn ($v) => (int) $v)->all());
    }

    // --- Bulletins -----------------------------------------------------------------

    /** Le professeur du bulletin se lit sur un bulletin de la classe : un porteur hors cohorte. */
    private function professeurs(array $parMatiere): void
    {
        ESBTPBulletin::factory()->create([
            'etudiant_id' => \App\Models\ESBTPEtudiant::factory()->create()->id, 'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id, 'periode' => 'semestre1', 'professeurs' => json_encode($parMatiere),
        ]);
    }

    public function test_generer_les_bulletins_lance_la_tache_seulement_apres_valider(): void
    {
        $matiere = $this->matiereConfiguree();
        $evaluation = $this->evaluationDe($matiere);
        $eleves = [$this->etudiantInscrit(), $this->etudiantInscrit()];
        foreach ($eleves as $e) {
            $this->noter($e, $evaluation);
        }
        $this->professeurs([$matiere->id => 'M. Kone']);

        $r = app(GenererBulletinsManquants::class)->executeAuthorized(['classe' => (string) $this->classe->id, 'periode' => 'S1'], $this->admin);
        $this->assertSame(0, BulletinTache::where('classe_id', $this->classe->id)->count(), 'rien avant Valider');
        $this->assertSame('executee', $this->valider($r)['statut']);

        $tache = BulletinTache::where('classe_id', $this->classe->id)->sole();
        $this->assertEqualsCanonicalizing(array_map(fn ($e) => $e->id, $eleves), array_map('intval', $tache->elements));
    }

    public function test_generer_relaie_le_blocage_et_refuse_une_classe_lmd(): void
    {
        // Aucun étudiant : le pré-contrôle bloque, et Nanan le dit au lieu de lancer.
        $this->manques(app(GenererBulletinsManquants::class)->executeAuthorized(['classe' => (string) $this->classe->id, 'periode' => 'S1'], $this->admin));
        $this->assertSame(0, BulletinTache::where('classe_id', $this->classe->id)->count());

        $this->classe->update(['systeme_academique' => 'LMD']);
        $this->assertStringContainsString('LMD', $this->manques(app(GenererBulletinsManquants::class)
            ->executeAuthorized(['classe' => (string) $this->classe->id, 'periode' => 'S1'], $this->admin)));
    }


    // --- LMD -----------------------------------------------------------------------

    private function uniteLmd(): ESBTPUniteEnseignement
    {
        $this->importer('ZPAR', [['ZUE1', 'Unité test', 30, [['ZEC1', 'Élément test', 30]]]]);

        return ESBTPUniteEnseignement::where('code', 'ZUE1')->sole();
    }

    /** @param list<array{0:string,1:string,2:int,3:list<array{0:string,1:string,2:int}>}> $ues */
    private function importer(string $parcours, array $ues): void
    {
        app(LMDImportService::class)->import([
            'domaine' => ['name' => 'Sciences Z', 'code' => 'ZST'], 'mention' => ['name' => 'Génie Z', 'code' => 'ZGC'],
            'parcours' => ['name' => 'Parcours '.$parcours, 'code' => $parcours], 'filiere' => ['name' => 'Filière '.$parcours, 'code' => 'F'.$parcours],
            'niveaux' => [['name' => 'Licence 1', 'year' => 1]],
            'ues' => array_map(fn ($u) => ['code' => $u[0], 'name' => $u[1], 'type_ue' => 'fondamentale', 'credit' => $u[2], 'niveau_year' => 1, 'semestre' => 1,
                'ecues' => array_map(fn ($e) => ['code' => $e[0], 'name' => $e[1], 'credit_ecue' => $e[2], 'cm' => 10], $u[3])], $ues),
        ], $this->admin->id);
    }

    public function test_poser_une_hierarchie_lmd_avec_ses_codes(): void
    {
        $args = ['domaine' => ['name' => 'Sciences Y', 'code' => 'YST'], 'mention' => ['name' => 'Génie Y', 'code' => 'YGC'],
            'parcours' => ['name' => 'Bâtiment Y', 'code' => 'YBU'], 'filiere' => ['name' => 'Bâtiment Y', 'code' => 'YBU']];

        $this->assertStringContainsString('code', $this->manques(app(InstallerHierarchieLmd::class)->executeAuthorized(
            ['domaine' => ['name' => 'Sciences Y'], 'mention' => $args['mention'], 'parcours' => $args['parcours']], $this->admin)));

        $r = app(InstallerHierarchieLmd::class)->executeAuthorized($args, $this->admin);
        $this->assertSame(0, ESBTPLMDParcours::where('code', 'YBU')->count(), 'rien avant Valider');
        $this->valider($r);

        $parcours = ESBTPLMDParcours::with('mention.domaine', 'filiere')->where('code', 'YBU')->sole();
        $this->assertSame('YST', $parcours->mention->domaine->code);
        $this->assertSame('YBU', $parcours->filiere->code);
    }

    public function test_une_mention_d_un_autre_domaine_n_est_jamais_deplacee(): void
    {
        $autre = ESBTPLMDDomaine::create(['name' => 'Lettres X', 'code' => 'XLT', 'is_active' => true]);
        ESBTPLMDMention::create(['name' => 'Gestion', 'code' => 'XGES', 'domaine_id' => $autre->id, 'is_active' => true]);

        $m = $this->manques(app(InstallerHierarchieLmd::class)->executeAuthorized([
            'domaine' => ['name' => 'Économie X', 'code' => 'XEC'], 'mention' => ['name' => 'Gestion', 'code' => 'XGES'],
            'parcours' => ['name' => 'Gestion X', 'code' => 'XGP']], $this->admin));

        $this->assertStringContainsString('XGES', $m);
        $this->assertSame($autre->id, (int) ESBTPLMDMention::where('code', 'XGES')->value('domaine_id'));
    }

    private const COLONNES_MAQUETTE = ['Semestre', 'Code UE', 'UE', 'Type', 'Crédits UE', 'Code ECUE', 'ECUE', 'Crédits ECUE', 'CM'];

    private function argsMaquette(string $pieceId, array $plus = []): array
    {
        return $plus + [
            'domaine' => ['name' => 'Sciences W', 'code' => 'WST'], 'mention' => ['name' => 'Génie W', 'code' => 'WGC'],
            'parcours' => ['name' => 'Bâtiment W', 'code' => 'WBU'], 'filiere' => ['name' => 'Bâtiment W', 'code' => 'WBU'],
            'annee' => 1,
            'piece' => ['piece_id' => $pieceId, 'colonnes' => [
                'semestre' => 'Semestre', 'ue_code' => 'Code UE', 'ue_intitule' => 'UE', 'ue_type' => 'Type', 'ue_credit' => 'Crédits UE',
                'ecue_code' => 'Code ECUE', 'ecue_intitule' => 'ECUE', 'ecue_credit' => 'Crédits ECUE', 'cm' => 'CM',
            ]],
        ];
    }

    public function test_importer_une_maquette_jointe_relit_le_fichier_et_n_ecrit_qu_a_la_validation(): void
    {
        // Une UE sur deux lignes (cellules fusionnées) : la seconde prolonge la première.
        $piece = $this->piece(self::COLONNES_MAQUETTE, [
            ['S1', 'WMAT11', 'Mathématiques', 'UE Fondamentale', '20', 'WMAT111', 'Analyse', '12', '30'],
            ['', '', '', '', '', 'WMAT112', 'Algèbre', '8', '20'],
            ['1', 'WLAN11', 'Langues', 'Transversale', '10', 'WLAN111', 'Anglais', '10', '15'],
        ]);

        $r = app(ImporterMaquetteLmd::class)->executeAuthorized($this->argsMaquette($piece), $this->admin);
        $this->assertSame(0, ESBTPUniteEnseignement::whereIn('code', ['WMAT11', 'WLAN11'])->count(), 'rien avant Valider (simulation annulée)');
        $this->assertSame(0, ESBTPLMDParcours::where('code', 'WBU')->count());
        $this->valider($r);

        $ue = ESBTPUniteEnseignement::where('code', 'WMAT11')->sole();
        $this->assertSame(20, (int) $ue->credit);
        $this->assertSame(['WMAT111', 'WMAT112'], ESBTPMatiere::where('unite_enseignement_id', $ue->id)->orderBy('code')->pluck('code')->all());
        $this->assertSame('transversale', ESBTPUniteEnseignement::where('code', 'WLAN11')->sole()->getRawOriginal('type_ue'));
    }

    public function test_un_semestre_qui_ne_fait_pas_trente_credits_est_une_question(): void
    {
        $piece = $this->piece(self::COLONNES_MAQUETTE, [['S1', 'WMAT11', 'Mathématiques', 'Fondamentale', '20', 'WMAT111', 'Analyse', '20', '30']]);

        $this->assertStringContainsString('S1 = 20', $this->manques(app(ImporterMaquetteLmd::class)->executeAuthorized($this->argsMaquette($piece), $this->admin)));

        // Confirmé partiel par la personne : proposé.
        $r = app(ImporterMaquetteLmd::class)->executeAuthorized($this->argsMaquette($piece, ['credits_incomplets_confirmes' => true]), $this->admin);
        $this->assertSame('approbation', $r['widget']['kind'] ?? null);
    }

    public function test_un_type_d_ue_inconnu_et_un_ecue_d_une_autre_ue_sont_refuses(): void
    {
        $this->assertStringContainsString('inconnu', $this->manques(app(ImporterMaquetteLmd::class)->executeAuthorized($this->argsMaquette(
            $this->piece(self::COLONNES_MAQUETTE, [['S1', 'WMAT11', 'Maths', 'Majeure', '30', 'WMAT111', 'Analyse', '30', '30']])), $this->admin)));

        // L'ECUE ZEC1 appartient déjà à l'UE ZUE1 : l'importer sous une autre UE la reparenterait.
        $this->uniteLmd();
        $m = $this->manques(app(ImporterMaquetteLmd::class)->executeAuthorized($this->argsMaquette(
            $this->piece(self::COLONNES_MAQUETTE, [['S1', 'WAUT11', 'Autre', 'Fondamentale', '30', 'ZEC1', 'Élément test', '30', '10']])), $this->admin));
        $this->assertStringContainsString('ZEC1', $m);
        $this->assertSame(0, ESBTPUniteEnseignement::where('code', 'WAUT11')->count());
    }

    public function test_rattacher_des_classes_lmd_a_un_parcours(): void
    {
        $this->uniteLmd();
        $licence = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'Licence']);
        $classe = ESBTPClasse::factory()->create(['code' => 'ZL1A', 'niveau_etude_id' => $licence->id, 'filiere_id' => $this->filiere->id]);

        $this->assertStringContainsString('non-LMD', $this->manques(app(RattacherClassesAuParcours::class)
            ->executeAuthorized(['parcours' => 'ZPAR', 'classes' => [(string) $this->classe->id]], $this->admin)));

        $r = app(RattacherClassesAuParcours::class)->executeAuthorized(['parcours' => 'ZPAR', 'classes' => ['ZL1A']], $this->admin);
        $this->assertNull($classe->fresh()->parcours_id, 'rien avant Valider');
        $this->valider($r);

        $this->assertSame((int) ESBTPLMDParcours::where('code', 'ZPAR')->value('id'), (int) $classe->fresh()->parcours_id);
        $this->assertSame('LMD', $classe->fresh()->systeme_academique);
    }

    public function test_affecter_un_enseignant_existant_et_signaler_un_inconnu_sans_creer_de_compte(): void
    {
        $this->importer('ZPAR', [['ZUE1', 'Unité test', 30, [['ZEC1', 'Élément test', 20], ['ZEC2', 'Second', 10]]]]);
        $prof = User::withoutEvents(fn () => User::factory()->create(['name' => 'KOUAME Yao', 'username' => 'u_'.Str::lower(Str::random(8))]));
        $prof->assignRole('enseignant');
        $usersAvant = User::count();
        $piece = $this->piece(['ECUE', 'Enseignant'], [['ZEC1', 'Kouame Yao'], ['ZEC2', 'Personne Inconnue']]);
        $args = ['piece' => ['piece_id' => $piece, 'colonne_ecue' => 'ECUE', 'colonne_enseignant' => 'Enseignant']];
        $ecue = ESBTPMatiere::where('code', 'ZEC1')->sole();

        $r = app(AffecterEnseignantsLmd::class)->executeAuthorized($args, $this->admin);
        $this->assertStringContainsString('Personne Inconnue', implode(' ', $r['widget']['avertissements'] ?? []));
        $this->assertNull(ESBTPPlanificationAcademique::where('matiere_id', $ecue->id)->value('enseignant_principal_id'), 'rien avant Valider');
        $this->valider($r);

        $this->assertSame($prof->id, (int) ESBTPPlanificationAcademique::where('matiere_id', $ecue->id)->value('enseignant_principal_id'));
        $this->assertSame($usersAvant, User::count(), 'aucun compte créé');
        $this->assertNull(ESBTPPlanificationAcademique::where('matiere_id', ESBTPMatiere::where('code', 'ZEC2')->value('id'))->value('enseignant_principal_id'));
    }

    // --- Droits et séance ----------------------------------------------------------

    public function test_sans_le_droit_de_l_ecran_l_action_n_est_pas_proposee(): void
    {
        $simple = User::withoutEvents(fn () => User::factory()->create(['username' => 'u_'.Str::lower(Str::random(8))]));
        $noms = array_column(app(CatalogueOutils::class)->schemas($simple), 'name');

        foreach ([CorrigerNotes::class, EnregistrerMoyennes::class, DeplacerEvaluationsDePeriode::class, ChangerMatiereEvaluation::class,
            GenererBulletinsManquants::class, InstallerHierarchieLmd::class, ImporterMaquetteLmd::class,
            RattacherClassesAuParcours::class, AffecterEnseignantsLmd::class] as $classe) {
            $action = app($classe);
            $this->assertFalse($action->isAvailableFor($simple), $action->name());
            $this->assertNotContains($action->name(), $noms);
            $this->assertTrue($action->isAvailableFor($this->admin), $action->name());
        }
    }

    /**
     * Séance d'entraînement : la vraie boucle, le vrai catalogue, le vrai
     * prompt ; le modèle scripté suit le mode opératoire d'une réclamation.
     */
    public function test_seance_d_entrainement_reclamation(): void
    {
        $evaluation = $this->evaluationDe($this->matiereConfiguree());
        $eleve = $this->etudiantInscrit();
        $this->noter($eleve, $evaluation, 8);

        $faux = new FauxFournisseur();
        $this->app->instance(FauxFournisseur::class, $faux);
        config(['assistant.adaptateurs.faux' => FauxFournisseur::class, 'assistant.limites.tours' => 3, 'assistant.limites.budget_tokens' => 0]);
        $faux->scripts['m'] = [
            FauxFournisseur::outil('t1', 'proposer_correction_notes', ['matricule' => $eleve->matricule, 'motif' => 'Copie revue après réclamation',
                'notes' => [['evaluation_id' => $evaluation->id, 'note' => 15]]]),
            FauxFournisseur::texte('Je propose de passer la note à 15 : relisez puis validez.'),
        ];

        $catalogue = app(CatalogueOutils::class);
        $systeme = app(ConstructeurDePrompt::class)->systeme($this->admin, null, null);
        foreach (['proposer_correction_notes', 'proposer_import_maquette_lmd', 'proposer_generation_bulletins', 'proposer_deplacement_periode'] as $outil) {
            $this->assertStringContainsString($outil, $systeme);
        }

        $r = (new BoucleAgent($catalogue))->executer(
            [new ModeleIa('m', 'faux', 'faux', 'm', 'M', true, true, 'cle', 'https://faux.test/')],
            new RequeteModele($systeme, [['role' => 'user', 'texte' => "L'élève conteste sa note de devoir : la copie revue vaut 15."]], $catalogue->schemas($this->admin)),
            $this->admin,
            new UiMessageStream(fn () => null),
        );

        $this->assertSame(['proposer_correction_notes'], array_column($r->appels, 'tool'));
        $this->assertSame(8.0, (float) ESBTPNote::where('etudiant_id', $eleve->id)->value('note'));
        $this->assertSame(1, DB::table('chatbot_actions_log')->where('action_type', 'correction_notes')->where('status', 'proposed')->count());
    }
}
