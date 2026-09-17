<?php

namespace Tests\Unit\EmploiTemps;

use App\Domain\EmploiTemps\DetectionDesConflits;
use App\Models\ESBTPSeanceCours;
use PHPUnit\Framework\TestCase;

/**
 * Le détecteur de conflits, éprouvé sans base.
 *
 * Le docbloc de `DetectionDesConflits` justifiait sa sortie du contrôleur en
 * disant qu'elle y était « jamais prouvée autrement que par un commentaire » et
 * qu'ici elle « se rejoue en trois assertions ». Aucun test ne l'accompagnait :
 * la phrase était, elle aussi, plus affirmative que le code.
 *
 * Ce test la rend vraie, et verrouille au passage le défaut qu'une revue
 * adverse a trouvé dans le bandeau : les heures sortaient en `Carbon`, donc
 * s'affichaient « 2026-09-17 08:00:00 à 2026-09-17 10:00:00 » au lieu de
 * « 08:00 à 10:00 ». Le tamis du piège #14 ne pouvait pas le voir, la vue lisant
 * `$conflit['heure_debut']` et non `->heure_debut`.
 *
 * Aucune base n'est touchée : `depuis()` ne fait que parcourir ce qu'on lui
 * donne, et les modèles sont hydratés en mémoire — avec leur accesseur, donc
 * avec le `Carbon` qui est précisément le sujet.
 */
class DetectionDesConflitsTest extends TestCase
{
    private function seance(array $attributs): ESBTPSeanceCours
    {
        $seance = new ESBTPSeanceCours;
        $seance->forceFill(array_merge([
            'id' => 1,
            'annee_universitaire_id' => 7,
            'jour' => 1,
            'heure_debut' => '08:00:00',
            'heure_fin' => '10:00:00',
            'teacher_id' => null,
            'salle' => null,
        ], $attributs));

        // Les relations sont POSÉES, même à null. Sans cela Eloquent les charge
        // paresseusement et réclame une connexion : le détecteur lit
        // `$seance->teacher?->user?->name` et `$seance->emploiTemps`. En
        // production, l'appelant les pré-charge (c'est écrit dans la signature
        // de `depuis()`) ; ici on reproduit cet état, on ne le contourne pas.
        $seance->setRelation('teacher', $attributs['teacher'] ?? null);
        $seance->setRelation('emploiTemps', $attributs['emploiTemps'] ?? null);

        return $seance;
    }

    /** @test */
    public function les_heures_du_bandeau_sortent_en_h_i_et_non_en_date(): void
    {
        // L'attribut lui-même est bien un Carbon daté du jour : c'est l'accesseur
        // du modèle, et c'est ce qui rendait l'affichage faux.
        $seance = $this->seance(['id' => 1, 'teacher_id' => 42]);
        $this->assertInstanceOf(\Carbon\Carbon::class, $seance->heure_debut);

        $conflits = (new DetectionDesConflits)->depuis([
            $seance,
            $this->seance(['id' => 2, 'teacher_id' => 42, 'heure_debut' => '09:00:00', 'heure_fin' => '11:00:00']),
        ]);

        $this->assertCount(1, $conflits);
        $this->assertSame('Enseignant', $conflits[0]['type']);

        // La paire est ancrée sur la séance de plus petit identifiant — c'est
        // elle que vise le lien « Corriger » du bandeau —, donc 08:00-10:00.
        $this->assertSame(1, $conflits[0]['seance_id']);
        $this->assertSame('08:00', $conflits[0]['heure_debut']);
        $this->assertSame('10:00', $conflits[0]['heure_fin']);

        // La garde qui compte : aucune date ne doit atteindre le bandeau.
        $this->assertStringNotContainsString('-', (string) $conflits[0]['heure_debut']);
        $this->assertStringNotContainsString('-', (string) $conflits[0]['heure_fin']);
    }

    /** @test */
    public function deux_seances_sans_salle_ne_sont_pas_en_conflit_de_salle(): void
    {
        $conflits = (new DetectionDesConflits)->depuis([
            $this->seance(['id' => 1]),
            $this->seance(['id' => 2]),
        ]);

        $this->assertSame([], $conflits);
    }

    /** @test */
    public function deux_annees_universitaires_differentes_ne_sont_pas_comparees(): void
    {
        // Un permanent qui tient le même créneau deux années de suite n'est pas
        // en conflit avec lui-même.
        $conflits = (new DetectionDesConflits)->depuis([
            $this->seance(['id' => 1, 'teacher_id' => 42, 'annee_universitaire_id' => 6]),
            $this->seance(['id' => 2, 'teacher_id' => 42, 'annee_universitaire_id' => 7]),
        ]);

        $this->assertSame([], $conflits);
    }

    /** @test */
    public function un_meme_conflit_ne_compte_qu_une_ligne(): void
    {
        // Trois séances du même enseignant sur le même créneau produisent trois
        // paires, mais un seul conflit à montrer.
        $conflits = (new DetectionDesConflits)->depuis([
            $this->seance(['id' => 1, 'teacher_id' => 42]),
            $this->seance(['id' => 2, 'teacher_id' => 42]),
            $this->seance(['id' => 3, 'teacher_id' => 42]),
        ]);

        $this->assertCount(1, $conflits);
    }
}
