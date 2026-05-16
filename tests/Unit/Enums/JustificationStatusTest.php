<?php

namespace Tests\Unit\Enums;

use App\Enums\JustificationStatus;
use PHPUnit\Framework\TestCase;

class JustificationStatusTest extends TestCase
{
    public function test_exposes_three_workflow_states(): void
    {
        $values = JustificationStatus::values();

        $this->assertCount(3, $values);
        $this->assertContains('pending', $values);
        $this->assertContains('approved', $values);
        $this->assertContains('rejected', $values);
    }

    public function test_try_from_recognizes_all_canonical_values(): void
    {
        foreach (['pending', 'approved', 'rejected'] as $value) {
            $this->assertInstanceOf(
                JustificationStatus::class,
                JustificationStatus::tryFrom($value),
                "Statut '{$value}' devrait être reconnu",
            );
        }
    }

    public function test_try_from_returns_null_for_unknown_value(): void
    {
        $this->assertNull(JustificationStatus::tryFrom('inconnu'));
        $this->assertNull(JustificationStatus::tryFrom(''));
        $this->assertNull(JustificationStatus::tryFrom('PENDING'));   // pas de casse mixte
        $this->assertNull(JustificationStatus::tryFrom('en_attente')); // pas la valeur canonique
    }

    public function test_label_is_french_humanized(): void
    {
        $this->assertSame('En attente', JustificationStatus::PENDING->label());
        $this->assertSame('Validée', JustificationStatus::APPROVED->label());
        $this->assertSame('Rejetée', JustificationStatus::REJECTED->label());
    }

    public function test_badge_class_returns_premium_semantic_classes(): void
    {
        $this->assertSame('ja-badge--warning', JustificationStatus::PENDING->badgeClass());
        $this->assertSame('ja-badge--success', JustificationStatus::APPROVED->badgeClass());
        $this->assertSame('ja-badge--danger', JustificationStatus::REJECTED->badgeClass());
    }

    public function test_icon_returns_fontawesome_class(): void
    {
        foreach (JustificationStatus::cases() as $case) {
            $this->assertStringStartsWith('fa-', $case->icon(),
                "L'icône doit être une classe Font Awesome (préfixe fa-) pour {$case->value}");
        }
    }

    public function test_is_editable_by_student_only_when_rejected(): void
    {
        // Re-soumission autorisee UNIQUEMENT si REJETE
        $this->assertFalse(JustificationStatus::PENDING->isEditableByStudent());
        $this->assertFalse(JustificationStatus::APPROVED->isEditableByStudent());
        $this->assertTrue(JustificationStatus::REJECTED->isEditableByStudent());
    }

    public function test_is_pending_teacher_action_only_when_pending(): void
    {
        $this->assertTrue(JustificationStatus::PENDING->isPendingTeacherAction());
        $this->assertFalse(JustificationStatus::APPROVED->isPendingTeacherAction());
        $this->assertFalse(JustificationStatus::REJECTED->isPendingTeacherAction());
    }

    public function test_values_match_db_column_string_storage(): void
    {
        // Les valeurs sont stockees en string snake_case dans la DB (cf migration).
        // Tout drift ici casserait les enregistrements existants en prod.
        $values = JustificationStatus::values();
        foreach ($values as $value) {
            $this->assertMatchesRegularExpression(
                '/^[a-z_]+$/',
                $value,
                "Statut '{$value}' doit être en snake_case ASCII pour matcher la colonne DB",
            );
        }
    }

    public function test_workflow_state_transitions_are_clear(): void
    {
        // Sanity check : pour un statut donne, l'etudiant et l'admin n'agissent
        // pas en meme temps.
        foreach (JustificationStatus::cases() as $case) {
            $this->assertFalse(
                $case->isEditableByStudent() && $case->isPendingTeacherAction(),
                "Statut {$case->value} : etudiant ET admin actionnables en meme temps (contradiction workflow)"
            );
        }
    }
}
