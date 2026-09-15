<?php

namespace Tests\Unit\Domain\EmploiTemps;

use App\Domain\EmploiTemps\DetectionDesConflits;
use PHPUnit\Framework\TestCase;

/**
 * Les conflits d'horaire du bandeau de `/esbtp/seances-cours`.
 *
 * `PHPUnit\Framework\TestCase` et non celui de Laravel : le détecteur ne touche
 * ni la base ni le conteneur, et c'est tout l'intérêt de l'avoir sorti du
 * contrôleur — ces cas se rejouent là où MySQL n'est pas disponible.
 *
 * Les séances sont des objets nus. Le détecteur ne lit que ces champs, et les
 * garder nus interdit qu'un cas passe par accident grâce à un accesseur
 * Eloquent que le code réel n'utilise pas.
 */
class DetectionDesConflitsTest extends TestCase
{
    /** @param array<string, mixed> $attributs */
    private function seance(array $attributs = []): object
    {
        return (object) array_merge([
            'id' => 1,
            'annee_universitaire_id' => 7,
            'jour' => 'lundi',
            'heure_debut' => '08:00',
            'heure_fin' => '10:00',
            'teacher_id' => null,
            'salle' => null,
            'emploiTemps' => null,
            'teacher' => null,
        ], $attributs);
    }

    private function emploiTemps(int $classeId, string $nomDeClasse = 'Licence 1'): object
    {
        return (object) [
            'classe_id' => $classeId,
            'classe' => (object) ['name' => $nomDeClasse],
        ];
    }

    /** @param iterable<object> $seances */
    private function types(iterable $seances): array
    {
        return array_map(
            fn (array $conflit) => $conflit['type'],
            (new DetectionDesConflits)->depuis($seances)
        );
    }

    // --- Ce que le détecteur doit trouver ---

    public function test_un_enseignant_sur_deux_seances_qui_se_chevauchent_est_un_conflit(): void
    {
        $conflits = (new DetectionDesConflits)->depuis([
            $this->seance(['id' => 1, 'teacher_id' => 3, 'heure_debut' => '08:00', 'heure_fin' => '10:00']),
            $this->seance(['id' => 2, 'teacher_id' => 3, 'heure_debut' => '09:00', 'heure_fin' => '11:00']),
        ]);

        $this->assertSame(['Enseignant'], array_column($conflits, 'type'));
    }

    public function test_une_salle_occupee_deux_fois_est_un_conflit(): void
    {
        $conflits = (new DetectionDesConflits)->depuis([
            $this->seance(['id' => 1, 'salle' => 'Amphi A']),
            $this->seance(['id' => 2, 'salle' => 'Amphi A']),
        ]);

        $this->assertSame(['Salle'], array_column($conflits, 'type'));
        $this->assertSame('Amphi A', $conflits[0]['nom']);
    }

    public function test_une_classe_convoquee_deux_fois_est_un_conflit(): void
    {
        $conflits = (new DetectionDesConflits)->depuis([
            $this->seance(['id' => 1, 'emploiTemps' => $this->emploiTemps(12, 'Licence 1 GC')]),
            $this->seance(['id' => 2, 'emploiTemps' => $this->emploiTemps(12, 'Licence 1 GC')]),
        ]);

        $this->assertSame(['Classe'], array_column($conflits, 'type'));
        $this->assertSame('Licence 1 GC', $conflits[0]['nom']);
    }

    // --- Ce qu'il ne doit PAS trouver : les faux positifs corrigés ---

    public function test_deux_seances_sans_enseignant_ne_sont_pas_en_conflit(): void
    {
        // Le défaut d'origine. Le détecteur lisait la colonne texte morte
        // `enseignant`, nulle partout, et `null == null` étant vrai, TOUTE paire
        // qui se chevauche était déclarée en conflit d'enseignant. Le bandeau
        // était donc du bruit intégral.
        $conflits = (new DetectionDesConflits)->depuis([
            $this->seance(['id' => 1]),
            $this->seance(['id' => 2]),
        ]);

        $this->assertSame([], $conflits);
    }

    public function test_deux_seances_sans_salle_ne_sont_pas_en_conflit(): void
    {
        $conflits = (new DetectionDesConflits)->depuis([
            $this->seance(['id' => 1, 'salle' => '   ']),
            $this->seance(['id' => 2, 'salle' => null]),
        ]);

        $this->assertSame([], $conflits);
    }

    public function test_un_meme_enseignant_sur_deux_annees_universitaires_n_est_pas_en_conflit(): void
    {
        // Un permanent qui tient le lundi 8h-10h en 2024-2025 et de nouveau en
        // 2026-2027 n'est pas en conflit avec lui-même. Sur une instance qui
        // porte plusieurs années en base, c'était une catégorie entière de
        // faux conflits.
        $conflits = (new DetectionDesConflits)->depuis([
            $this->seance(['id' => 1, 'annee_universitaire_id' => 7, 'teacher_id' => 3]),
            $this->seance(['id' => 2, 'annee_universitaire_id' => 8, 'teacher_id' => 3]),
        ]);

        $this->assertSame([], $conflits);
    }

    public function test_deux_creneaux_qui_ne_se_touchent_pas_ne_sont_pas_en_conflit(): void
    {
        // Bornes adjacentes : 10h00 est la fin de l'un et le début de l'autre.
        $conflits = (new DetectionDesConflits)->depuis([
            $this->seance(['id' => 1, 'teacher_id' => 3, 'heure_debut' => '08:00', 'heure_fin' => '10:00']),
            $this->seance(['id' => 2, 'teacher_id' => 3, 'heure_debut' => '10:00', 'heure_fin' => '12:00']),
        ]);

        $this->assertSame([], $conflits);
    }

    public function test_deux_jours_differents_ne_sont_pas_en_conflit(): void
    {
        $conflits = (new DetectionDesConflits)->depuis([
            $this->seance(['id' => 1, 'jour' => 'lundi', 'teacher_id' => 3]),
            $this->seance(['id' => 2, 'jour' => 'mardi', 'teacher_id' => 3]),
        ]);

        $this->assertSame([], $conflits);
    }

    public function test_deux_salles_de_casse_differente_restent_distinctes(): void
    {
        // Choix assumé, pas un oubli : rapprocher « Amphi A » et « amphi A »
        // élargirait la détection sur des données existantes, ce qui est un
        // autre geste que celui-ci.
        $conflits = (new DetectionDesConflits)->depuis([
            $this->seance(['id' => 1, 'salle' => 'Amphi A']),
            $this->seance(['id' => 2, 'salle' => 'amphi a']),
        ]);

        $this->assertSame([], $conflits);
    }

    // --- Forme de la sortie ---

    public function test_une_paire_peut_porter_plusieurs_conflits_a_la_fois(): void
    {
        $conflits = $this->types([
            $this->seance(['id' => 1, 'teacher_id' => 3, 'salle' => 'B12', 'emploiTemps' => $this->emploiTemps(12)]),
            $this->seance(['id' => 2, 'teacher_id' => 3, 'salle' => 'B12', 'emploiTemps' => $this->emploiTemps(12)]),
        ]);

        $this->assertSame(['Enseignant', 'Salle', 'Classe'], $conflits);
    }

    public function test_une_paire_ne_rend_qu_une_ligne_par_type(): void
    {
        // La double boucle voyait chaque paire deux fois, dans les deux sens.
        // Sans la garde sur les identifiants, le bandeau afficherait tout en
        // double.
        $conflits = (new DetectionDesConflits)->depuis([
            $this->seance(['id' => 1, 'salle' => 'Amphi A']),
            $this->seance(['id' => 2, 'salle' => 'Amphi A']),
        ]);

        $this->assertCount(1, $conflits);
    }

    public function test_deux_seances_qui_se_chevauchent_sans_coincider_ne_rendent_qu_une_ligne(): void
    {
        // Le cas que la déduplication seule ne rattrapait PAS : sa clé porte les
        // horaires, et 8h-10h ancré sur l'une n'est pas 9h-11h ancré sur
        // l'autre. Un conflit, deux lignes de bandeau.
        $conflits = (new DetectionDesConflits)->depuis([
            $this->seance(['id' => 1, 'teacher_id' => 3, 'heure_debut' => '08:00', 'heure_fin' => '10:00']),
            $this->seance(['id' => 2, 'teacher_id' => 3, 'heure_debut' => '09:00', 'heure_fin' => '11:00']),
        ]);

        $this->assertCount(1, $conflits);
        // Ancré sur la séance de plus petit identifiant, donc déterministe.
        $this->assertSame(1, $conflits[0]['seance_id']);
        $this->assertSame('08:00', $conflits[0]['heure_debut']);
    }

    public function test_trois_seances_sur_le_meme_creneau_ne_rendent_qu_une_ligne(): void
    {
        // Trois paires en conflit, mais une seule chose à dire à l'utilisateur :
        // « cet enseignant est pris, lundi 8h-10h ».
        $conflits = (new DetectionDesConflits)->depuis([
            $this->seance(['id' => 1, 'teacher_id' => 3]),
            $this->seance(['id' => 2, 'teacher_id' => 3]),
            $this->seance(['id' => 3, 'teacher_id' => 3]),
        ]);

        $this->assertCount(1, $conflits);
    }

    public function test_le_libelle_de_l_enseignant_vient_de_son_compte(): void
    {
        $avecCompte = (object) ['user' => (object) ['name' => 'Kouamé Yao'], 'name' => null];

        $conflits = (new DetectionDesConflits)->depuis([
            $this->seance(['id' => 1, 'teacher_id' => 3, 'teacher' => $avecCompte]),
            $this->seance(['id' => 2, 'teacher_id' => 3, 'teacher' => $avecCompte]),
        ]);

        $this->assertSame('Kouamé Yao', $conflits[0]['nom']);
    }

    public function test_un_enseignant_sans_compte_charge_reste_identifiable(): void
    {
        // Plutôt qu'une ligne de bandeau sans nom : l'identifiant permet au
        // moins de retrouver la séance.
        $conflits = (new DetectionDesConflits)->depuis([
            $this->seance(['id' => 1, 'teacher_id' => 42]),
            $this->seance(['id' => 2, 'teacher_id' => 42]),
        ]);

        $this->assertSame('Enseignant #42', $conflits[0]['nom']);
    }

    public function test_aucune_seance_ne_rend_aucun_conflit(): void
    {
        $this->assertSame([], (new DetectionDesConflits)->depuis([]));
    }
}
