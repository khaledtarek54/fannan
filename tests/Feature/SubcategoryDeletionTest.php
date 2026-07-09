<?php

namespace Tests\Feature;

use App\Filament\Resources\SubCategoryResource\Pages\ListSubCategories;
use App\Models\SubCategory;
use App\Models\User;
use App\Models\UserCategory;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * SubCategory has no soft-deletes and user_categories.subcategory_id is onDelete('set null'), so
 * deleting an in-use subcategory silently wipes the specialization of every artist tied to it. The
 * admin panel must block that. Admin-panel only; no API impact.
 */
class SubcategoryDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_a_subcategory_in_use_by_an_artist_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $artist = User::factory()->artist()->create();
        $sub = SubCategory::create(['name' => 'Oud']);
        UserCategory::create(['user_id' => $artist->id, 'subcategory_id' => $sub->id]);

        $this->actingAs($admin);
        Livewire::test(ListSubCategories::class)
            ->callTableAction('delete', $sub);

        $this->assertDatabaseHas('sub_categories', ['id' => $sub->id]);
    }

    public function test_an_unused_subcategory_can_still_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $sub = SubCategory::create(['name' => 'Unused']);

        $this->actingAs($admin);
        Livewire::test(ListSubCategories::class)
            ->callTableAction('delete', $sub);

        $this->assertDatabaseMissing('sub_categories', ['id' => $sub->id]);
    }
}
