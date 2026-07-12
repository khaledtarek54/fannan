<?php

namespace Tests\Feature;

use App\Filament\Resources\RatingResource\Pages\ListRatings;
use App\Models\Rating;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Filtering the read-only ratings/reviews moderation list. Admin-panel only; no API impact.
 */
class RatingListFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function makeRating(?string $notes): Rating
    {
        return Rating::create([
            'client_id' => User::factory()->client()->create()->id,
            'artist_id' => User::factory()->artist()->create()->id,
            'stars' => 5,
            'notes' => $notes,
        ]);
    }

    public function test_ratings_can_be_filtered_to_those_with_a_written_review(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $withReview = $this->makeRating('Great work');
        $starsOnly = $this->makeRating(null);

        Livewire::test(ListRatings::class)
            ->filterTable('has_review', true)
            ->assertCanSeeTableRecords([$withReview])
            ->assertCanNotSeeTableRecords([$starsOnly]);
    }

    public function test_ratings_can_be_filtered_to_those_without_a_written_review(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $withReview = $this->makeRating('Great work');
        $starsOnly = $this->makeRating(null);
        $emptyNotes = $this->makeRating('');

        Livewire::test(ListRatings::class)
            ->filterTable('has_review', false)
            ->assertCanSeeTableRecords([$starsOnly, $emptyNotes]) // both NULL and '' count as no review
            ->assertCanNotSeeTableRecords([$withReview]);
    }

    public function test_ratings_can_be_filtered_by_client(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $mine = $this->makeRating('a');
        $other = $this->makeRating('b');

        Livewire::test(ListRatings::class)
            ->filterTable('client_id', $mine->client_id)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$other]);
    }
}
