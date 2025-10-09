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

class DeleteTest extends TestCase
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

    public function test_admin_can_delete_their_organisation()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $org = Organisation::factory()->create();
        $org->users()->attach($admin);

        $response = $this->actingAs($admin)->delete("/organisations/{$org->slug}");

        $response->assertRedirect('/organisations');
        $this->assertSoftDeleted('organisations', ['id' => $org->id]);
    }

    public function test_recruiter_cannot_delete_organisation()
    {
        $recruiter = User::factory()->create();
        $recruiter->assignRole('recruiter');
        
        $org = Organisation::factory()->create();
        $org->users()->attach($recruiter);

        $response = $this->actingAs($recruiter)->delete("/organisations/{$org->slug}");

        $response->assertForbidden();
        $this->assertDatabaseHas('organisations', ['id' => $org->id]);
    }

    public function test_admin_cannot_delete_organisation_they_dont_belong_to()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $org = Organisation::factory()->create();

        $response = $this->actingAs($admin)->delete("/organisations/{$org->slug}");

        $response->assertForbidden();
        $this->assertDatabaseHas('organisations', ['id' => $org->id]);
    }

    public function test_candidate_cannot_delete_organisation()
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('candidate');
        
        $org = Organisation::factory()->create();

        $response = $this->actingAs($candidate)->delete("/organisations/{$org->slug}");

        $response->assertForbidden();
    }

    public function test_deleting_organisation_keeps_pivot_records()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $org = Organisation::factory()->create();
        $org->users()->attach($admin);

        $this->actingAs($admin)->delete("/organisations/{$org->slug}");

        // Soft delete garde les relations pivot
        $this->assertDatabaseHas('organisation_user', [
            'organisation_id' => $org->id,
            'user_id' => $admin->id,
        ]);
    }
}
