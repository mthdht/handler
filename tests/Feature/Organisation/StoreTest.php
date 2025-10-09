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

class StoreTest extends TestCase
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

    public function test_admin_can_create_organisation()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post('/organisations', [
            'name' => 'Nouvelle Organisation',
            'description' => 'Description test',
            'email' => 'contact@org.com',
            'phone' => '0123456789',
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('organisations', [
            'name' => 'Nouvelle Organisation',
            'slug' => 'nouvelle-organisation',
            'owner_id' => $admin->id,
        ]);

        // Vérifier que l'admin est attaché à l'organisation
        $org = Organisation::where('slug', 'nouvelle-organisation')->first();
        $this->assertTrue($org->users->contains($admin));
    }

    public function test_recruiter_cannot_create_organisation()
    {
        $recruiter = User::factory()->create();
        $recruiter->assignRole('recruiter');

        $response = $this->actingAs($recruiter)->post('/organisations', [
            'name' => 'Nouvelle Organisation',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('organisations', 0);
    }

    public function test_candidate_cannot_create_organisation()
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('candidate');

        $response = $this->actingAs($candidate)->post('/organisations', [
            'name' => 'Nouvelle Organisation',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('organisations', 0);
    }

    public function test_organisation_name_is_required()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post('/organisations', [
            'name' => '',
            'description' => 'Test',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('organisations', 0);
    }

    public function test_organisation_name_has_maximum_length()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post('/organisations', [
            'name' => str_repeat('a', 256), // Trop long
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_organisation_email_must_be_valid()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post('/organisations', [
            'name' => 'Test Org',
            'email' => 'invalid-email',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_organisation_slug_is_unique()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        Organisation::factory()->create([
            'name' => 'Mon Entreprise',
            'slug' => 'mon-entreprise',
        ]);

        $response = $this->actingAs($admin)->post('/organisations', [
            'name' => 'Mon Entreprise',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('name');
    }
}
