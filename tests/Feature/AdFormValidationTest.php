<?php

namespace Tests\Feature;

use App\Filament\Resources\AdResource\Pages\CreateAd;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Ad form validation — the link must be a real URL. Admin-panel only; no API impact.
 */
class AdFormValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_a_non_url_link_is_rejected(): void
    {
        Livewire::test(CreateAd::class)
            ->fillForm(['name' => 'Promo', 'link' => 'not a url'])
            ->call('create')
            ->assertHasFormErrors(['link']);
    }

    public function test_a_valid_url_passes_link_validation(): void
    {
        // Other required fields (image, adable) may still error; we only assert `link` itself is valid.
        Livewire::test(CreateAd::class)
            ->fillForm(['name' => 'Promo', 'link' => 'https://example.com'])
            ->call('create')
            ->assertHasNoFormErrors(['link']);
    }
}
