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

class ShowTest extends TestCase
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

    public function test_admin_can_view_organisation_they_belong_to()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $org = Organisation::factory()->create(['name' => 'Test Org']);
        $org->users()->attach($admin);

        $response = $this->actingAs($admin)->get("/organisations/{$org->slug}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Organisations/Show')
            ->where('organisation.name', 'Test Org')
        );
    }

    public function test_recruiter_can_view_organisation_they_belong_to()
    {
        $recruiter = User::factory()->create();
        $recruiter->assignRole('recruiter');
        
        $org = Organisation::factory()->create(['name' => 'Test Org']);
        $org->users()->attach($recruiter);

        $response = $this->actingAs($recruiter)->get("/organisations/{$org->slug}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Organisations/Show')
        );
    }

    public function test_user_cannot_view_organisation_they_dont_belong_to()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $org = Organisation::factory()->create();

        $response = $this->actingAs($admin)->get("/organisations/{$org->slug}");

        $response->assertForbidden();
    }

    public function test_candidate_cannot_view_organisation()
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('candidate');
        
        $org = Organisation::factory()->create();

        $response = $this->actingAs($candidate)->get("/organisations/{$org->slug}");

        $response->assertForbidden();
    }
}