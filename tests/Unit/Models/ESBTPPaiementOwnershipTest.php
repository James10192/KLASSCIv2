<?php

namespace Tests\Unit\Models;

use App\Models\ESBTPPaiement;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Mockery;
use Tests\TestCase;

/**
 * Tests unit du Lot 13 — Paiements ownership.
 *
 * Pas de RefreshDatabase : on teste le scope et la relation au niveau API
 * Eloquent (via Mockery), pas en intégration DB.
 */
class ESBTPPaiementOwnershipTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_creator_relation_is_belongs_to_user_via_created_by(): void
    {
        $paiement = new ESBTPPaiement();

        $relation = $paiement->creator();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $relation,
            'creator() doit retourner une relation BelongsTo'
        );

        $this->assertSame(
            'created_by',
            $relation->getForeignKeyName(),
            'La relation creator doit utiliser la foreign key created_by'
        );

        $this->assertSame(
            User::class,
            get_class($relation->getRelated()),
            'La relation creator doit pointer vers App\\Models\\User'
        );
    }

    public function test_created_by_relation_still_works_for_backward_compat(): void
    {
        // Garde-fou : la relation createdBy() historique ne doit pas être cassée
        // par l'ajout de creator() (alias).
        $paiement = new ESBTPPaiement();

        $relation = $paiement->createdBy();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $relation
        );
        $this->assertSame('created_by', $relation->getForeignKeyName());
    }

    public function test_scope_owned_by_filters_with_user_object(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getKey')->andReturn(42);

        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('where')
            ->once()
            ->with('created_by', 42)
            ->andReturnSelf();

        $paiement = new ESBTPPaiement();
        $result = $paiement->scopeOwnedBy($query, $user);

        $this->assertSame($query, $result, 'Le scope doit retourner le builder pour chainage');
    }

    public function test_scope_owned_by_filters_with_user_id_int(): void
    {
        // On peut aussi passer un id directement (utile dans des batches)
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('where')
            ->once()
            ->with('created_by', 7)
            ->andReturnSelf();

        $paiement = new ESBTPPaiement();
        $result = $paiement->scopeOwnedBy($query, 7);

        $this->assertSame($query, $result);
    }

    public function test_scope_owned_by_does_not_apply_other_filters(): void
    {
        // Vérifie que le scope ne fait QUE le where created_by, sans effets de bord
        // (pas de toucher aux autres filtres comme status, date, etc.).
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getKey')->andReturn(99);

        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('where')
            ->once()
            ->with('created_by', 99)
            ->andReturnSelf();

        // Aucun autre appel attendu
        $query->shouldNotReceive('orWhere');
        $query->shouldNotReceive('whereDate');
        $query->shouldNotReceive('whereHas');

        $paiement = new ESBTPPaiement();
        $result = $paiement->scopeOwnedBy($query, $user);

        $this->assertSame($query, $result);
        // Mockery vérifie automatiquement les expectations à teardown
        $this->addToAssertionCount(1);
    }
}
