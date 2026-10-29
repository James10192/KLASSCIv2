<?php

namespace Tests\Unit\Enums;

use App\Enums\TpeDeclarationStatut;
use PHPUnit\Framework\TestCase;

class TpeDeclarationStatutTest extends TestCase
{
    public function test_exposes_three_workflow_states(): void
    {
        $values = TpeDeclarationStatut::values();

        $this->assertCount(3, $values);
        $this->assertContains('valide', $values);
        $this->assertContains('en_attente', $values);
        $this->assertContains('rejete', $values);
    }

    public function test_try_from_recognizes_all_canonical_values(): void
    {
        foreach (['valide', 'en_attente', 'rejete'] as $value) {
            $this->assertInstanceOf(
                TpeDeclarationStatut::class,
                TpeDeclarationStatut::tryFrom($value),
                "Statut '{$value}' devrait être reconnu",
            );
        }
    }

    public function test_try_from_returns_null_for_unknown_value(): void
    {
        $this->assertNull(TpeDeclarationStatut::tryFrom('inconnu'));
        $this->assertNull(TpeDeclarationStatut::tryFrom(''));
        $this->assertNull(TpeDeclarationStatut::tryFrom('VALIDE'));   // pas de casse mixte
        $this->assertNull(TpeDeclarationStatut::tryFrom('en attente')); // espace pas underscore
    }

    public function test_label_is_french_humanized(): void
    {
        $this->assertSame('Validé', TpeDeclarationStatut::VALIDE->label());
        $this->assertSame('En attente', TpeDeclarationStatut::EN_ATTENTE->label());
        $this->assertSame('Rejeté', TpeDeclarationStatut::REJETE->label());
    }

    public function test_badge_class_returns_premium_semantic_classes(): void
    {
        $this->assertSame('tj-badge--success', TpeDeclarationStatut::VALIDE->badgeClass());
        $this->assertSame('tj-badge--warning', TpeDeclarationStatut::EN_ATTENTE->badgeClass());
        $this->assertSame('tj-badge--danger', TpeDeclarationStatut::REJETE->badgeClass());
    }

    public function test_icon_returns_fontawesome_class(): void
    {
        foreach (TpeDeclarationStatut::cases() as $case) {
            $this->assertStringStartsWith('fa-', $case->icon(),
                "L'icône doit être une classe Font Awesome (préfixe fa-) pour {$case->value}");
        }
    }

    public function test_is_editable_by_student_only_when_en_attente(): void
    {
        $this->assertFalse(TpeDeclarationStatut::VALIDE->isEditableByStudent());
        $this->assertTrue(TpeDeclarationStatut::EN_ATTENTE->isEditableByStudent());
        $this->assertFalse(TpeDeclarationStatut::REJETE->isEditableByStudent());
    }

    public function test_is_pending_teacher_action_only_when_en_attente(): void
    {
        $this->assertFalse(TpeDeclarationStatut::VALIDE->isPendingTeacherAction());
        $this->assertTrue(TpeDeclarationStatut::EN_ATTENTE->isPendingTeacherAction());
        $this->assertFalse(TpeDeclarationStatut::REJETE->isPendingTeacherAction());
    }

    public function test_values_match_db_column_string_storage(): void
    {
        // Les valeurs sont stockées en string snake_case dans la DB (cf migration).
        // Tout drift ici casserait les enregistrements existants en prod.
        $values = TpeDeclarationStatut::values();
        foreach ($values as $value) {
            $this->assertMatchesRegularExpression(
                '/^[a-z_]+$/',
                $value,
                "Statut '{$value}' doit être en snake_case ASCII pour matcher la colonne DB",
            );
        }
    }
}
