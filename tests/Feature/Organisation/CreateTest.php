<?php

namespace Tests\Feature\Organisation;

use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Organisation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Inertia\Testing\AssertableInertia as Assert;

class CreateTest extends TestCase
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
    
    public function test_admin_can_view_create_form()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get('/organisations/create');

        $response->assertOk();
    }

    public function test_recruiter_cannot_view_create_form()
    {
        $recruiter = User::factory()->create();
        $recruiter->assignRole('recruiter');

        $response = $this->actingAs($recruiter)->get('/organisations/create');

        $response->assertForbidden();
    }
    
    public function test_candidate_cannot_view_create_form()
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('candidate');

        $response = $this->actingAs($candidate)->get('/organisations/create');

        $response->assertForbidden();
    }
}
