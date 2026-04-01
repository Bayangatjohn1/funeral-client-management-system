<?php

namespace Tests\Unit;

use App\Models\Package;
use App\Support\Discount\CaseDiscountResolver;
use Carbon\Carbon;
use Tests\TestCase;

class CaseDiscountResolverTest extends TestCase
{
    public function test_returns_no_discount_when_no_senior_and_no_active_promo(): void
    {
        $resolver = new CaseDiscountResolver();
        $package = new Package([
            'promo_is_active' => false,
        ]);

        $resolved = $resolver->resolve($package, 30, 10000, Carbon::parse('2026-02-26 10:00:00'));

        $this->assertSame('NONE', $resolved['discount_type']);
        $this->assertSame(0.0, (float) $resolved['discount_amount']);
    }

    public function test_applies_senior_discount_when_eligible(): void
    {
        config()->set('funeral.senior_discount_percent', 20);

        $resolver = new CaseDiscountResolver();
        $package = new Package([
            'promo_is_active' => false,
        ]);

        $resolved = $resolver->resolve($package, 60, 10000, Carbon::parse('2026-02-26 10:00:00'));

        $this->assertSame('SENIOR', $resolved['discount_type']);
        $this->assertSame('PERCENT', $resolved['discount_value_type']);
        $this->assertSame(2000.0, (float) $resolved['discount_amount']);
    }

    public function test_applies_promo_when_higher_than_senior(): void
    {
        config()->set('funeral.senior_discount_percent', 20);

        $resolver = new CaseDiscountResolver();
        $package = new Package([
            'promo_is_active' => true,
            'promo_label' => 'Promo 30%',
            'promo_value_type' => 'PERCENT',
            'promo_value' => 30,
            'promo_starts_at' => Carbon::parse('2026-02-01 00:00:00'),
            'promo_ends_at' => Carbon::parse('2026-02-28 23:59:59'),
        ]);

        $resolved = $resolver->resolve($package, 70, 10000, Carbon::parse('2026-02-26 10:00:00'));

        $this->assertSame('CUSTOM', $resolved['discount_type']);
        $this->assertSame('PROMO', $resolved['source']);
        $this->assertSame(3000.0, (float) $resolved['discount_amount']);
    }

    public function test_no_stacking_uses_higher_discount_only(): void
    {
        config()->set('funeral.senior_discount_percent', 20);

        $resolver = new CaseDiscountResolver();
        $package = new Package([
            'promo_is_active' => true,
            'promo_label' => 'Promo 10%',
            'promo_value_type' => 'PERCENT',
            'promo_value' => 10,
            'promo_starts_at' => Carbon::parse('2026-02-01 00:00:00'),
            'promo_ends_at' => Carbon::parse('2026-02-28 23:59:59'),
        ]);

        $resolved = $resolver->resolve($package, 65, 10000, Carbon::parse('2026-02-26 10:00:00'));

        $this->assertSame('SENIOR', $resolved['source']);
        $this->assertSame(2000.0, (float) $resolved['discount_amount']);
    }
}

