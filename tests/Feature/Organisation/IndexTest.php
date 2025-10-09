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

class IndexTest extends TestCase
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

    public function test_admin_can_view_organisations_index()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $org1 = Organisation::factory()->create();
        $org2 = Organisation::factory()->create();
        
        $org1->users()->attach($admin);
        $org2->users()->attach($admin);

        $response = $this->actingAs($admin)->get('/organisations');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('organisations/Index', false)
            ->has('organisations.data', 2)
        );
    }

    public function test_recruiter_can_view_organisations_index()
    {
        $recruiter = User::factory()->create();
        $recruiter->assignRole('recruiter');
        
        $org = Organisation::factory()->create();
        $org->users()->attach($recruiter);

        $response = $this->actingAs($recruiter)->get('/organisations');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
        ->component('organisations/Index', false)
            ->has('organisations.data', 1)
        );
    }

    public function test_candidate_cannot_view_organisations_index()
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('candidate');

        $response = $this->actingAs($candidate)->get('/organisations');

        $response->assertForbidden();
    }

    public function test_user_only_sees_their_own_organisations()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        
        $myOrg1 = Organisation::factory()->create();
        $myOrg2 = Organisation::factory()->create();
        $otherOrg = Organisation::factory()->create(); // Pas membre
        
        $myOrg1->users()->attach($user);
        $myOrg2->users()->attach($user);

        $response = $this->actingAs($user)->get('/organisations');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('organisations.data', 2)
            ->where('organisations.data.0.id', $myOrg1->id)
            ->where('organisations.data.1.id', $myOrg2->id)
        );
    }

    public function test_guest_cannot_view_organisations_index()
    {
        $response = $this->get('/organisations');

        $response->assertRedirect('/login');
    }
}
