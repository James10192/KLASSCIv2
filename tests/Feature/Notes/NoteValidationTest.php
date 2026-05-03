<?php

namespace Tests\Feature\Notes;

use App\Http\Requests\Notes\StoreBulkNotesRequest;
use App\Http\Requests\Notes\StoreNoteRequest;
use App\Rules\NoteRespectsBareme;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Tests des validations strictes sur les endpoints notes.
 *
 * Ces tests visent les couches qui peuvent être testées sans accès DB
 * (Custom Rules, FormRequests via Validator manuel, registration des routes).
 * Les tests d'intégration (route + DB + permissions) requièrent une DB
 * tenant configurée et sont laissés à la CI / staging.
 */
class NoteValidationTest extends TestCase
{
    /**
     * Provide the rule set from StoreNoteRequest without DB-touching custom rules.
     * Laisse tomber NoteRespectsBareme (testé séparément).
     */
    private function storeNoteRulesWithoutBareme(): array
    {
        $rules = (new StoreNoteRequest)->rules();

        // Retire la rule custom NoteRespectsBareme pour les tests qui ne
        // veulent pas toucher la DB (existence évaluation).
        $rules['note'] = array_values(array_filter(
            $rules['note'],
            fn ($r) => ! ($r instanceof NoteRespectsBareme)
        ));

        // Idem pour exists:* qui touche la DB
        $rules['evaluation_id'] = ['required', 'integer'];
        $rules['etudiant_id'] = ['required', 'integer'];

        return $rules;
    }

    private function storeBulkRulesWithoutDb(): array
    {
        $rules = (new StoreBulkNotesRequest)->rules();
        $rules['classe_id'] = ['nullable', 'integer'];
        $rules['matiere_id'] = ['nullable', 'integer'];
        $rules['notes.*.evaluation_id'] = ['required', 'integer'];
        $rules['notes.*.etudiant_id'] = ['required', 'integer'];

        return $rules;
    }

    /**
     * Test 1 — La validation rejette une note négative (single).
     */
    public function test_it_rejects_negative_note(): void
    {
        $validator = Validator::make([
            'evaluation_id' => 1,
            'etudiant_id' => 1,
            'note' => -2,
        ], $this->storeNoteRulesWithoutBareme());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('note', $validator->errors()->toArray());
    }

    /**
     * Test 2 — La validation rejette une note non numérique (single).
     */
    public function test_it_rejects_non_numeric_note(): void
    {
        $validator = Validator::make([
            'evaluation_id' => 1,
            'etudiant_id' => 1,
            'note' => 'not-a-number',
        ], $this->storeNoteRulesWithoutBareme());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('note', $validator->errors()->toArray());
    }

    /**
     * Test 3 — La validation accepte une note absente / nullable.
     */
    public function test_it_accepts_null_note_for_absent_student(): void
    {
        $validator = Validator::make([
            'evaluation_id' => 1,
            'etudiant_id' => 1,
            'note' => null,
            'is_absent' => true,
        ], $this->storeNoteRulesWithoutBareme());

        $this->assertFalse($validator->fails());
    }

    /**
     * Test 4 — La validation rejette un commentaire trop long (>500 chars).
     */
    public function test_it_rejects_oversize_comment(): void
    {
        $validator = Validator::make([
            'evaluation_id' => 1,
            'etudiant_id' => 1,
            'note' => 12,
            'commentaire' => str_repeat('x', 501),
        ], $this->storeNoteRulesWithoutBareme());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('commentaire', $validator->errors()->toArray());
    }

    /**
     * Test 5 — La validation bulk rejette plus de 500 notes par requête.
     */
    public function test_it_rejects_bulk_exceeding_max_batch(): void
    {
        $notes = [];
        for ($i = 1; $i <= StoreBulkNotesRequest::MAX_NOTES_PER_BATCH + 1; $i++) {
            $notes[] = ['evaluation_id' => 1, 'etudiant_id' => $i, 'note' => 12];
        }

        $validator = Validator::make(['notes' => $notes], $this->storeBulkRulesWithoutDb());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('notes', $validator->errors()->toArray());
    }

    /**
     * Test 6 — La validation bulk rejette un tableau notes vide.
     */
    public function test_it_rejects_bulk_with_empty_notes_array(): void
    {
        $validator = Validator::make(['notes' => []], $this->storeBulkRulesWithoutDb());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('notes', $validator->errors()->toArray());
    }

    /**
     * Test 7 — La validation bulk rejette une note > 100 (borne haute).
     */
    public function test_it_rejects_bulk_note_exceeding_upper_bound(): void
    {
        $validator = Validator::make([
            'notes' => [
                ['evaluation_id' => 1, 'etudiant_id' => 1, 'note' => 250],
            ],
        ], $this->storeBulkRulesWithoutDb());

        $this->assertTrue($validator->fails());
        $errors = $validator->errors()->toArray();
        $this->assertArrayHasKey('notes.0.note', $errors);
    }

    /**
     * Test 8 — La validation bulk accepte un payload valide.
     */
    public function test_it_accepts_valid_bulk_payload(): void
    {
        $validator = Validator::make([
            'classe_id' => 1,
            'matiere_id' => 2,
            'notes' => [
                ['evaluation_id' => 1, 'etudiant_id' => 1, 'note' => 12.5, 'is_absent' => false],
                ['evaluation_id' => 1, 'etudiant_id' => 2, 'note' => null, 'is_absent' => true],
            ],
        ], $this->storeBulkRulesWithoutDb());

        $this->assertFalse($validator->fails(), 'Payload should pass: '.$validator->errors()->first());
    }

    /**
     * Test 9 — Les routes critiques sont throttlées.
     *
     * Vérifie que les routes notes.save-ajax (30/min) / save-ajax-bulk (10/min) /
     * store-batch (10/min) sont bien enregistrées avec leur middleware throttle.
     */
    public function test_it_registers_throttle_middleware_on_critical_routes(): void
    {
        $expectations = [
            'esbtp.notes.save-ajax' => 'throttle:30,1',
            'esbtp.notes.save-ajax-bulk' => 'throttle:10,1',
            'esbtp.notes.store-batch' => 'throttle:10,1',
        ];

        foreach ($expectations as $routeName => $expectedMiddleware) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route {$routeName} should be registered");
            $this->assertContains(
                $expectedMiddleware,
                $route->gatherMiddleware(),
                "Route {$routeName} should have middleware {$expectedMiddleware}"
            );
        }
    }

    /**
     * Test 10 — L'évaluation StoreEvaluationRequest valide la borne min sur barème.
     *
     * Garde-fou critique : un barème à 0 causerait une division par zéro
     * dans ESBTPNote::getNoteVingtAttribute.
     */
    public function test_it_rejects_evaluation_with_zero_bareme(): void
    {
        $rules = (new \App\Http\Requests\Evaluations\StoreEvaluationRequest)->rules();

        // Strip exists:* / les règles qui touchent la DB
        $rules['classe_id'] = ['required', 'integer'];
        $rules['matiere_id'] = ['required', 'integer'];

        $validator = Validator::make([
            'titre' => 'Examen test',
            'classe_id' => 1,
            'matiere_id' => 1,
            'type' => 'examen',
            'date_evaluation' => '2026-05-15',
            'heure_debut' => '08:00',
            'heure_fin' => '10:00',
            'bareme' => 0,
            'coefficient' => 1,
            'periode' => 'semestre1',
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('bareme', $validator->errors()->toArray());
    }

    /**
     * Test 11 — L'évaluation StoreEvaluationRequest valide la borne max sur coefficient.
     */
    public function test_it_rejects_evaluation_with_excessive_coefficient(): void
    {
        $rules = (new \App\Http\Requests\Evaluations\StoreEvaluationRequest)->rules();
        $rules['classe_id'] = ['required', 'integer'];
        $rules['matiere_id'] = ['required', 'integer'];

        $validator = Validator::make([
            'titre' => 'Examen test',
            'classe_id' => 1,
            'matiere_id' => 1,
            'type' => 'examen',
            'date_evaluation' => '2026-05-15',
            'heure_debut' => '08:00',
            'heure_fin' => '10:00',
            'bareme' => 20,
            'coefficient' => 50,
            'periode' => 'semestre1',
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('coefficient', $validator->errors()->toArray());
    }

    /**
     * Test 12 — Le custom Rule NoteRespectsBareme rejette une note > barème.
     *
     * On utilise un evaluation_id null pour tester sans DB :
     * la rule fail immédiatement avec "introuvable".
     */
    public function test_note_respects_bareme_rule_fails_when_evaluation_id_invalid(): void
    {
        $rule = new NoteRespectsBareme(null);

        $this->assertFalse($rule->passes('note', 15));
        $this->assertStringContainsString('introuvable', $rule->message());
    }

    /**
     * Test 13 — Le custom Rule passe quand value est null.
     */
    public function test_note_respects_bareme_rule_passes_for_null_value(): void
    {
        $rule = new NoteRespectsBareme(1);

        $this->assertTrue($rule->passes('note', null), 'Null value should pass (gérée par nullable)');
    }

    /**
     * Test 14 — Le custom Rule passe quand value est non numérique
     * (déléguée à la règle 'numeric').
     */
    public function test_note_respects_bareme_rule_passes_for_non_numeric(): void
    {
        $rule = new NoteRespectsBareme(1);

        $this->assertTrue($rule->passes('note', 'abc'), 'Non-numeric value should pass (géré par numeric)');
    }
}
