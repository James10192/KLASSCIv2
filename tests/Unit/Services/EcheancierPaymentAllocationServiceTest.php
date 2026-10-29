<?php

namespace Tests\Unit\Services;

use App\Services\EcheancierPaymentAllocationService;
use Illuminate\Support\Collection;
use stdClass;
use Tests\TestCase;

class EcheancierPaymentAllocationServiceTest extends TestCase
{
    public function test_it_allocates_payments_to_oldest_lines_in_the_same_category(): void
    {
        $service = new EcheancierPaymentAllocationService();

        $allocated = $service->allocate([
            $this->dueLine('second', 1, 400, '2026-06-01'),
            $this->dueLine('first', 1, 300, '2026-05-01'),
            $this->dueLine('other-category', 2, 200, '2026-05-15'),
        ], collect([
            $this->payment(1, 500),
            $this->payment(2, 50),
        ]));

        $this->assertSame('first', $allocated[0]['line_key']);
        $this->assertSame(300.0, $allocated[0]['paid_amount']);
        $this->assertSame(0.0, $allocated[0]['remaining_amount']);

        $this->assertSame('other-category', $allocated[1]['line_key']);
        $this->assertSame(50.0, $allocated[1]['paid_amount']);
        $this->assertSame(150.0, $allocated[1]['remaining_amount']);

        $this->assertSame('second', $allocated[2]['line_key']);
        $this->assertSame(200.0, $allocated[2]['paid_amount']);
        $this->assertSame(200.0, $allocated[2]['remaining_amount']);
    }

    public function test_it_keeps_global_payments_separate_from_categorized_fees(): void
    {
        $service = new EcheancierPaymentAllocationService();

        $allocated = $service->allocate([
            $this->dueLine('global', null, 100, '2026-05-01'),
            $this->dueLine('category', 9, 100, '2026-05-01'),
        ], collect([
            $this->payment(null, 80),
        ]));

        $byKey = collect($allocated)->keyBy('line_key');

        $this->assertSame(80.0, $byKey['global']['paid_amount']);
        $this->assertSame(20.0, $byKey['global']['remaining_amount']);
        $this->assertSame(0.0, $byKey['category']['paid_amount']);
        $this->assertSame(100.0, $byKey['category']['remaining_amount']);
    }

    private function dueLine(string $key, ?int $categoryId, float $amount, string $dueDate): array
    {
        return [
            'line_key' => $key,
            'category_id' => $categoryId,
            'amount' => $amount,
            'due_date' => $dueDate,
        ];
    }

    private function payment(?int $categoryId, float $amount): stdClass
    {
        return (object) [
            'frais_category_id' => $categoryId,
            'montant' => $amount,
        ];
    }
}
