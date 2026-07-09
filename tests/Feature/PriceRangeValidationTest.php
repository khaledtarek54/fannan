<?php

namespace Tests\Feature;

use App\Filament\Resources\PriceRangeResource\Pages\CreatePriceRange;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Price-range form validation — bad ranges must not be saved (they're shown to the mobile app).
 * Admin-panel only; no API impact.
 */
class PriceRangeValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_the_upper_bound_must_be_greater_than_the_lower_bound(): void
    {
        Livewire::test(CreatePriceRange::class)
            ->fillForm(['from' => 100, 'to' => 50])
            ->call('create')
            ->assertHasFormErrors(['to']);
    }

    public function test_equal_bounds_are_rejected(): void
    {
        // Locks in strict `gt` (not `gte`): a zero-width range is invalid.
        Livewire::test(CreatePriceRange::class)
            ->fillForm(['from' => 50, 'to' => 50])
            ->call('create')
            ->assertHasFormErrors(['to']);
    }

    public function test_a_negative_bound_is_rejected(): void
    {
        Livewire::test(CreatePriceRange::class)
            ->fillForm(['from' => -5, 'to' => 100])
            ->call('create')
            ->assertHasFormErrors(['from']);
    }

    public function test_a_non_numeric_bound_is_rejected(): void
    {
        Livewire::test(CreatePriceRange::class)
            ->fillForm(['from' => 'abc', 'to' => 100])
            ->call('create')
            ->assertHasFormErrors(['from']);
    }

    public function test_a_valid_range_is_created(): void
    {
        Livewire::test(CreatePriceRange::class)
            ->fillForm(['from' => 10, 'to' => 100])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('price_ranges', ['from' => 10, 'to' => 100]);
    }
}
