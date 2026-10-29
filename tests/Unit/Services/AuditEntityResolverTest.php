<?php

namespace Tests\Unit\Services;

use App\Services\Audit\AuditEntityResolver;
use OwenIt\Auditing\Models\Audit;
use PHPUnit\Framework\TestCase;

/**
 * Tests Unit isolés pour AuditEntityResolver — vérifient le routing
 * (auditable_type → bonne méthode) et la robustesse face aux entités
 * supprimées / aux classes inconnues, sans toucher à la DB.
 *
 * Les vrais appels DB (eager-load des relations Eloquent) sont couverts par
 * les tests Feature qui hydratent une migration sqlite en mémoire.
 */
class AuditEntityResolverTest extends TestCase
{
    private AuditEntityResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new AuditEntityResolver();
    }

    public function test_resolve_returns_empty_when_auditable_type_is_null(): void
    {
        $audit = new Audit();
        $audit->auditable_type = null;
        $audit->auditable_id = 1;

        $this->assertSame([], $this->resolver->resolve($audit));
    }

    public function test_resolve_returns_empty_when_class_does_not_exist(): void
    {
        $audit = new Audit();
        $audit->auditable_type = 'App\\Models\\NonExistentModel';
        $audit->auditable_id = 1;

        $this->assertSame([], $this->resolver->resolve($audit));
    }

    public function test_resolve_returns_empty_when_class_does_not_resolve(): void
    {
        // Cas où le mapping `match` ne couvre pas le type — return [] sans appeler la DB.
        $audit = new Audit();
        $audit->auditable_type = 'App\\Models\\Department'; // existe mais pas mappé
        $audit->auditable_id = 1;
        $audit->old_values = null;
        $audit->new_values = null;

        // Ne doit jamais lever d'erreur fatale, retourner array
        $result = $this->resolver->resolve($audit);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
