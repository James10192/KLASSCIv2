<?php

namespace Tests\Unit\Etudiants;

use App\Services\PermissionRegistry;
use App\Services\PermissionSyncService;
use PHPUnit\Framework\TestCase;

/**
 * Le formulaire de modification d'un etudiant grisait sept champs — matricule,
 * nom, prenoms, sexe, date et lieu de naissance, nationalite — sur
 * `admin.access`. Ce droit dit « peut entrer dans l'espace d'administration » ;
 * il ne dit rien de qui modifie l'etat civil d'un eleve.
 *
 * Les deux ensembles ne coincident pas, et l'ecart se voyait a l'ecran :
 * `responsableScolarite` et `agentInscription` ont `students.edit` sans
 * `admin.access` — ils atteignaient la page et trouvaient les champs morts ;
 * `comptable` a l'inverse. Un role personnalise, lui, ne fonctionnait qu'en
 * cochant `admin.access`, qui n'a aucun rapport.
 */
class FormulaireEtudiantGardesTest extends TestCase
{
    private function lire(string $chemin): string
    {
        return file_get_contents(__DIR__ . '/../../../' . $chemin);
    }

    private function formulaire(): string
    {
        return $this->lire('resources/views/esbtp/etudiants/partials/edit-form.blade.php');
    }

    public function test_le_formulaire_ne_decide_plus_sur_le_droit_d_entrer_dans_l_administration(): void
    {
        $this->assertStringNotContainsString(
            "can('admin.access')",
            $this->formulaire(),
            "admin.access ouvre l'espace d'administration ; il ne dit pas qui modifie un etat civil."
        );

        $this->assertStringNotContainsString(
            "can('admin.access')",
            $this->lire('resources/views/esbtp/etudiants/partials/edit-form-scripts.blade.php')
        );
    }

    public function test_les_champs_d_etat_civil_suivent_le_droit_de_modifier_un_etudiant(): void
    {
        $formulaire = $this->formulaire();

        // Six champs : nom, prenoms, sexe, date et lieu de naissance, nationalite.
        // Le matricule, lui, a son propre droit — teste plus bas.
        $this->assertSame(
            6,
            preg_match_all("/can\('students\.edit'\) \? '' : '(?:readonly|disabled)'/", $formulaire),
            'Les six champs d etat civil doivent suivre students.edit.'
        );
    }

    public function test_le_matricule_a_son_propre_droit(): void
    {
        $formulaire = $this->formulaire();

        $this->assertMatchesRegularExpression(
            "/id=\"matriculeInput\".{0,200}?can\('students\.edit_matricule'\) \? '' : 'readonly'/s",
            $formulaire,
            'Le matricule doit dependre de students.edit_matricule, pas de students.edit.'
        );

        // Les boutons Generer / Verifier reecrivent le matricule : meme droit.
        $this->assertStringContainsString(
            "@if(auth()->user()->can('students.edit_matricule'))\n                            <button type=\"button\" class=\"btn btn-outline-primary\" id=\"generateMatriculeBtn\"",
            $formulaire
        );
    }

    public function test_le_droit_du_matricule_existe_au_registre_et_voyage_avec_le_code(): void
    {
        $registre = require __DIR__ . '/../../../config/permissions.php';

        $this->assertArrayHasKey('students.edit_matricule', $registre['permissions']);
        $this->assertSame('Étudiants', $registre['permissions']['students.edit_matricule']['group']);

        // Ceux qui pouvaient deja le modifier (admin.access + students.edit) le
        // gardent ; personne d'autre ne le recoit.
        foreach (['secretaire', 'coordinateur'] as $role) {
            $this->assertContains('students.edit_matricule', $registre['role_defaults'][$role]);
        }

        foreach (['responsableScolarite', 'agentInscription', 'comptable', 'caissier'] as $role) {
            $this->assertNotContains('students.edit_matricule', $registre['role_defaults'][$role]);
        }

        // Sans cette entree, les roles deja peuples ne recevraient jamais le
        // droit : la synchronisation les preserve.
        $service = new PermissionSyncService(new PermissionRegistry());
        $methode = new \ReflectionMethod($service, 'newFeaturePermissions');
        $methode->setAccessible(true);

        $this->assertContains('students.edit_matricule', $methode->invoke($service));
    }

    public function test_le_serveur_refuse_un_matricule_change_sans_le_droit(): void
    {
        // `readonly` est soumis quand meme, et s'enleve dans le navigateur. La
        // garde qui compte est celle du controleur.
        $controleur = $this->lire('app/Http/Controllers/ESBTPEtudiantController.php');

        $this->assertMatchesRegularExpression(
            "/!== \(string\) \\\$etudiant->matricule\s*\n\s*&& ! \\\$request->user\(\)\?->can\('students\.edit_matricule'\)/",
            $controleur,
            'update() doit refuser un matricule CHANGE sans le droit dedie.'
        );

        $this->assertStringContainsString('abort(403,', $controleur);
    }
}
