<?php

namespace Tests\Feature\Organisations;

use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Organisation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Inertia\Testing\AssertableInertia as Assert;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer les rôles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'recruiter']);
        Role::create(['name' => 'candidate']);
        
        // Créer les permissions
        Permission::create(['name' => 'manage organisations']);
        Permission::create(['name' => 'view organisations']);
        
        // Assigner les permissions aux rôles
        $admin = Role::findByName('admin');
        $admin->givePermissionTo(['manage organisations', 'view organisations']);
        
        $recruiter = Role::findByName('recruiter');
        $recruiter->givePermissionTo('view organisations');
    }

    public function test_admin_can_update_their_organisation()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $org = Organisation::factory()->create(['name' => 'Old Name']);
        $org->users()->attach($admin);

        $response = $this->actingAs($admin)->put("/organisations/{$org->slug}", [
            'name' => 'New Name',
            'description' => 'New Description',
            'email' => 'new@email.com',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('organisations', [
            'id' => $org->id,
            'name' => 'New Name',
            'slug' => 'new-name',
            'description' => 'New Description',
            'email' => 'new@email.com',
        ]);
    }

    public function test_recruiter_cannot_update_organisation()
    {
        $recruiter = User::factory()->create();
        $recruiter->assignRole('recruiter');
        
        $org = Organisation::factory()->create(['name' => 'Original Name']);
        $org->users()->attach($recruiter);

        $response = $this->actingAs($recruiter)->put("/organisations/{$org->slug}", [
            'name' => 'Hacked Name',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('organisations', [
            'name' => 'Original Name', // Pas changé
        ]);
    }

    public function test_admin_cannot_update_organisation_they_dont_belong_to()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $org = Organisation::factory()->create(['name' => 'Original']);

        $response = $this->actingAs($admin)->put("/organisations/{$org->slug}", [
            'name' => 'Hacked',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('organisations', [
            'name' => 'Original',
        ]);
    }

    public function test_candidate_cannot_update_organisation()
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('candidate');
        
        $org = Organisation::factory()->create(['name' => 'Original']);

        $response = $this->actingAs($candidate)->put("/organisations/{$org->slug}", [
            'name' => 'Hacked',
        ]);

        $response->assertForbidden();
    }

    public function test_updating_organisation_name_regenerates_slug()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $org = Organisation::factory()->create([
            'name' => 'Old Name',
            'slug' => 'old-name'
        ]);
        $org->users()->attach($admin);

        $this->actingAs($admin)->put("/organisations/{$org->slug}", [
            'name' => 'Completely New Name',
        ]);

        $this->assertDatabaseHas('organisations', [
            'id' => $org->id,
            'slug' => 'completely-new-name',
        ]);
    }
}
