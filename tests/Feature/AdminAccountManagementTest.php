<?php

namespace Tests\Feature;

use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Managing admin accounts in the Filament panel: creation must grant panel access, the list must
 * reflect real access, and no admin may lock everyone out. Admin-panel only; no API impact.
 */
class AdminAccountManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_admins_screen_lists_by_is_admin_not_the_role_column(): void
    {
        $realAdmin = User::factory()->admin()->create();
        // role='admin' is the DB default, so a plain user can carry it while is_admin stays false.
        $fakeAdmin = User::factory()->create();
        $fakeAdmin->forceFill(['role' => 'admin', 'is_admin' => false])->save();

        $ids = UserResource::getEloquentQuery()->pluck('id');

        $this->assertTrue($ids->contains($realAdmin->id), 'the real is_admin account must be listed');
        $this->assertFalse($ids->contains($fakeAdmin->id), 'a role=admin/is_admin=false row must NOT be listed');
    }

    public function test_creating_an_admin_sets_is_admin_so_the_account_can_log_in(): void
    {
        $user = (new CreateUser)->handleRecordCreation([
            'name' => 'New Admin',
            'email' => 'newadmin@example.com',
            'phone' => '+201000000001',
            'password' => 'secret123',
            'country_code' => 'EG',
        ]);

        $this->assertTrue($user->fresh()->is_admin, 'panel-created admin must have is_admin=true');
        $this->assertTrue($user->fresh()->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_an_admin_cannot_delete_the_last_remaining_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->callTableAction('delete', $admin);

        $this->assertNull($admin->fresh()->deleted_at, 'the last/self admin must not be deletable');
    }

    public function test_an_admin_can_delete_another_admin_when_not_the_last(): void
    {
        $me = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();
        $this->actingAs($me);

        Livewire::test(ListUsers::class)
            ->callTableAction('delete', $other);

        $this->assertNotNull($other->fresh()->deleted_at, 'a non-last, non-self admin should be deletable');
    }
}
