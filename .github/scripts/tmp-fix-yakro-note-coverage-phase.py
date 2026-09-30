from pathlib import Path


def replace_once(path: str, old: str, new: str) -> None:
    p = Path(path)
    text = p.read_text()
    count = text.count(old)
    if count != 1:
        raise SystemExit(f"{path}: expected one occurrence, found {count}: {old[:180]!r}")
    p.write_text(text.replace(old, new, 1))


resolver = 'app/Domain/AcademicPilotage/Services/ExpectedSubjectsResolver.php'
coverage = 'app/Domain/AcademicPilotage/Services/AcademicNoteCoverageService.php'
script = 'resources/views/esbtp/partials/_couverture-notes-script.blade.php'
test = 'tests/Unit/Domain/AcademicPilotage/AcademicNoteCoverageServiceTest.php'

# 1) Au S2 de BTS1, une classe de specialite ne doit plus heriter du referentiel
# du tronc commun quand la maquette semestrielle n'a pas encore ete totalement
# validee. Le tronc commun reste visible au S1 / annuel, comme avant.
replace_once(
    resolver,
    "use App\\Models\\ESBTPMatiere;\n",
    "use App\\Models\\ESBTPMatiere;\nuse App\\Models\\ESBTPMatiereFilierNiveau;\n",
)

replace_once(
    resolver,
    """        // Periode annuelle : le semestre ne discrimine pas, on attend l'union.\n        // Un etat autre que COMPLET laisse la maquette de cote et rend la liste\n        // entiere : on prefere attendre une matiere de trop qu'en oublier une\n        // sur laquelle des notes existent.\n        if ($semestre === null || $etat !== BtsMaquette::ETAT_COMPLET) {\n            return $this->reponse($this->btsResolver->subjectsForClasse($classe), $etat, $semestre, $systeme);\n        }\n\n        return $this->reponse(\n""",
    """        // Periode annuelle : le semestre ne discrimine pas, on attend l'union.\n        if ($semestre === null) {\n            return $this->reponse($this->btsResolver->subjectsForClasse($classe), $etat, $semestre, $systeme);\n        }\n\n        // BTS1 Yakro : apres le semestre de tronc commun, la classe de\n        // specialite porte son PROPRE referentiel. Tant que la maquette\n        // semestrielle n'est pas totalement validee, l'ancien repli rendait\n        // l'union [specialite + parent TC] et le suivi annoncait les matieres\n        // du S1 « sans evaluation » au S2, alors que leurs evaluations vivent\n        // legitimement dans la classe de tronc commun.\n        //\n        // Une maquette COMPLETE reste souveraine : elle sait expliciter qu'une\n        // matiere du parent continue au S2. Le repli phase-aware ne s'applique\n        // donc qu'a l'etat AUCUN/PARTIEL. BTS2 et les filieres sans TC restent\n        // strictement inchanges.\n        if ($etat !== BtsMaquette::ETAT_COMPLET) {\n            $matieres = $this->estSpecialiteBts1ApresTroncCommun($classe, $semestre)\n                ? $this->matieresDuComboDeClasse($classe)\n                : $this->btsResolver->subjectsForClasse($classe);\n\n            return $this->reponse($matieres, $etat, $semestre, $systeme);\n        }\n\n        return $this->reponse(\n""",
)

replace_once(
    resolver,
    """    /**\n     * Comportement d'avant, conserve pour le LMD : les matieres rattachees au\n""",
    """    /**\n     * Une classe de specialite de BTS1 a depasse sa phase de tronc commun.\n     *\n     * `semestres_tronc_commun` est porte par la filiere parente. On limite\n     * volontairement cette regle au niveau 1 : au BTS2, « semestre 1 » designe\n     * le premier semestre de la DEUXIEME annee, pas le semestre 1 du cursus.\n     */\n    private function estSpecialiteBts1ApresTroncCommun(ESBTPClasse $classe, int $semestre): bool\n    {\n        if ((int) ($classe->niveau?->year ?? 0) !== 1) {\n            return false;\n        }\n\n        $filiere = $classe->filiere;\n        $parent = $filiere?->parent;\n\n        if (! $filiere || ! $parent || ! $parent->isTroncCommun()) {\n            return false;\n        }\n\n        $finTroncCommun = max(1, (int) ($parent->semestres_tronc_commun ?: 1));\n\n        return $semestre > $finTroncCommun;\n    }\n\n    /**\n     * Referentiel du combo physique de la classe, sans l'union TC parente.\n     *\n     * Les matieres explicitement rattachees a la specialite restent donc\n     * attendues. Une evaluation sur une autre matiere reste visible comme\n     * « hors maquette », ce qui est preferable a fabriquer une fausse dette de\n     * saisie sur tout le tronc commun du semestre precedent.\n     *\n     * @return Collection<int, ESBTPMatiere>\n     */\n    private function matieresDuComboDeClasse(ESBTPClasse $classe): Collection\n    {\n        if (! $classe->filiere_id || ! $classe->niveau_etude_id) {\n            return collect();\n        }\n\n        $ids = ESBTPMatiereFilierNiveau::query()\n            ->forCombo($classe->filiere_id, $classe->niveau_etude_id)\n            ->whereHas('matiere', fn ($query) => $query->where('is_active', true)->btsOnly())\n            ->pluck('matiere_id')\n            ->unique()\n            ->values();\n\n        return ESBTPMatiere::query()\n            ->whereIn('id', $ids)\n            ->btsOnly()\n            ->where('is_active', true)\n            ->orderBy('name')\n            ->get(['id', 'name', 'code']);\n    }\n\n    /**\n     * Comportement d'avant, conserve pour le LMD : les matieres rattachees au\n""",
)

# 2) Une classe sans cohorte sur la periode garde son referentiel dans l'API,
# mais ces matieres sont NON APPLICABLES. Elles ne doivent jamais etre classees
# « sans evaluation » : c'est le cas d'une specialite avant son ouverture, ou
# du TC apres le passage en specialite.
replace_once(
    coverage,
    """        $subjectRows = $subjectRows->concat($orphanRows)->values();\n        $incompleteStudents = $this->incompleteStudents($studentIndex, $subjectRows);\n""",
    """        $subjectRows = $subjectRows->concat($orphanRows)->values();\n\n        // Une classe BTS peut n'exister que sur un semestre : TC au S1 puis\n        // specialite au S2. Quand la cohorte canonique est vide sur la periode,\n        // le referentiel reste utile au diagnostic mais il n'y a AUCUNE action\n        // de saisie a reclamer. Marquer ces lignes « non_evaluee » fabriquait\n        // precisement les faux « matieres sans evaluation » vus a Yakro.\n        if ($studentIndex->isEmpty()) {\n            $subjectRows = $subjectRows->map(static function (array $row): array {\n                if (! ($row['is_orphan'] ?? false)) {\n                    $row['statut'] = 'non_applicable';\n                }\n\n                return $row;\n            });\n        }\n\n        $incompleteStudents = $this->incompleteStudents($studentIndex, $subjectRows);\n""",
)

# 3) Le bandeau ne montre pas les lignes non applicables dans ses compteurs ni
# son detail. Le resume global conserve « Aucun etudiant sur cette periode ».
replace_once(
    script,
    """            categorie(matiere) {\n                if (matiere.is_orphan || matiere.statut === 'hors_maquette') return 'hors_maquette';\n""",
    """            categorie(matiere) {\n                if (matiere.statut === 'non_applicable') return 'non_applicable';\n                if (matiere.is_orphan || matiere.statut === 'hors_maquette') return 'hors_maquette';\n""",
)

replace_once(
    script,
    """            matieresParCategorie(categorie) {\n                var subjects = (this.donnees && this.donnees.subjects) || [];\n                // « Toutes » est la vue globale, pas une catégorie métier.\n""",
    """            matieresParCategorie(categorie) {\n                var subjects = ((this.donnees && this.donnees.subjects) || [])\n                    .filter((matiere) => this.categorie(matiere) !== 'non_applicable');\n                // « Toutes » est la vue globale, pas une catégorie métier.\n""",
)

replace_once(
    script,
    """            matieresAffichees() {\n                var subjects = (this.donnees && this.donnees.subjects) || [];\n                var filtre = this.filtre;\n""",
    """            matieresAffichees() {\n                var subjects = ((this.donnees && this.donnees.subjects) || [])\n                    .filter((matiere) => this.categorie(matiere) !== 'non_applicable');\n                var filtre = this.filtre;\n""",
)

# 4) Regression : le S2 d'une specialite BTS1 ne doit pas heriter des matieres
# du parent TC lorsque la maquette n'est pas encore semestrialisee.
marker = """    public function test_une_matiere_de_specialite_est_exclue_du_combo_de_tronc_commun(): void\n"""
new_test = """    public function test_le_s2_d_une_specialite_bts1_n_herite_pas_des_matieres_du_tronc_commun(): void\n    {\n        $this->createReferenceTables();\n\n        DB::table('esbtp_filieres')->insert([\n            ['id' => 90, 'name' => 'Tronc commun', 'code' => 'TC', 'is_tronc_commun' => true, 'parent_id' => null, 'is_active' => true],\n            ['id' => 100, 'name' => 'Batiment', 'code' => 'BAT', 'is_tronc_commun' => false, 'parent_id' => 90, 'is_active' => true],\n        ]);\n        $this->monterUneClasse(10, 100);\n\n        DB::table('esbtp_matieres')->insert([\n            ['id' => 501, 'name' => 'Socle TC', 'code' => 'SOC', 'is_active' => true],\n            ['id' => 502, 'name' => 'Specialite S2', 'code' => 'SPE', 'is_active' => true],\n        ]);\n        DB::table('esbtp_matiere_filiere_niveau')->insert([\n            // Maquette volontairement NON semestrialisee : c'est le cas de\n            // production qui faisait remonter le S1 dans le suivi du S2.\n            ['matiere_id' => 501, 'filiere_id' => 90, 'niveau_etude_id' => 200],\n            ['matiere_id' => 502, 'filiere_id' => 100, 'niveau_etude_id' => 200],\n        ]);\n        DB::table('esbtp_etudiants')->insert([\n            ['id' => 301, 'nom' => 'Kouadio', 'prenoms' => 'Awa', 'matricule' => 'M301'],\n        ]);\n        DB::table('esbtp_inscriptions')->insert([\n            ['id' => 1, 'etudiant_id' => 301, 'classe_id' => 10, 'annee_universitaire_id' => 20, 'status' => 'active', 'workflow_step' => 'etudiant_cree', 'created_at' => now(), 'updated_at' => now()],\n        ]);\n        DB::table('esbtp_evaluations')->insert([\n            ['id' => 702, 'titre' => 'Devoir S2', 'classe_id' => 10, 'matiere_id' => 502, 'annee_universitaire_id' => 20, 'periode' => 'semestre2', 'status' => 'completed', 'type' => 'devoir', 'date_evaluation' => now()],\n        ]);\n        DB::table('esbtp_notes')->insert([\n            ['evaluation_id' => 702, 'etudiant_id' => 301, 'matiere_id' => 502, 'note' => 14, 'is_absent' => false, 'created_at' => now(), 'updated_at' => now()],\n        ]);\n\n        $result = app(AcademicNoteCoverageService::class)->summarize(20, 'semestre2', 'BTS', 10);\n\n        $this->assertSame([502], collect($result['subjects'])->pluck('id')->all());\n        $this->assertSame('complete', collect($result['subjects'])->firstWhere('id', 502)['statut']);\n        $this->assertSame(0, collect($result['subjects'])->where('statut', 'non_evaluee')->count());\n    }\n\n"""
replace_once(test, marker, new_test + marker)

replace_once(
    test,
    """        // Zero manquant, mais zero etudiant : ce n'est pas « tout est note ».\n        $this->assertSame(0, $result['summary']['missing_results']);\n        $this->assertSame('cohorte_vide', $result['summary']['state']);\n""",
    """        // Zero manquant, mais zero etudiant : ce n'est pas « tout est note ».\n        $this->assertSame(0, $result['summary']['missing_results']);\n        $this->assertSame('cohorte_vide', $result['summary']['state']);\n        $this->assertSame('non_applicable', collect($result['subjects'])->firstWhere('id', 501)['statut']);\n        $this->assertSame(0, collect($result['subjects'])->where('statut', 'non_evaluee')->count());\n""",
)

# Contracts rapides, avant PHPUnit.
r = Path(resolver).read_text()
c = Path(coverage).read_text()
j = Path(script).read_text()
t = Path(test).read_text()
assert 'estSpecialiteBts1ApresTroncCommun' in r
assert 'matieresDuComboDeClasse' in r
assert "'non_applicable'" in c
assert "matiere.statut === 'non_applicable'" in j
assert 'test_le_s2_d_une_specialite_bts1_n_herite_pas_des_matieres_du_tronc_commun' in t
