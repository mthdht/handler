<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Organisation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class OrganisationTest extends TestCase
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

    // ==================== INDEX ====================

    /** @test */
    public function admin_can_view_organisations_index()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $org1 = Organisation::factory()->create();
        $org2 = Organisation::factory()->create();
        
        $org1->users()->attach($admin);
        $org2->users()->attach($admin);

        $response = $this->actingAs($admin)->get('/organisations');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('organisations', 2)
        );
    }

    /** @test */
    public function recruiter_can_view_organisations_index()
    {
        $recruiter = User::factory()->create();
        $recruiter->assignRole('recruiter');
        
        $org = Organisation::factory()->create();
        $org->users()->attach($recruiter);

        $response = $this->actingAs($recruiter)->get('/organisations');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('organisations', 1)
        );
    }

    /** @test */
    public function candidate_cannot_view_organisations_index()
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('candidate');

        $response = $this->actingAs($candidate)->get('/organisations');

        $response->assertForbidden();
    }

    /** @test */
    public function user_only_sees_their_own_organisations()
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
            ->has('organisations', 2)
            ->where('organisations.0.id', $myOrg1->id)
            ->where('organisations.1.id', $myOrg2->id)
        );
    }

    /** @test */
    public function guest_cannot_view_organisations_index()
    {
        $response = $this->get('/organisations');

        $response->assertRedirect('/login');
    }

    // ==================== CREATE ====================

    /** @test */
    public function admin_can_view_create_form()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get('/organisations/create');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('organisations/Create')
        );
    }

    /** @test */
    public function recruiter_cannot_view_create_form()
    {
        $recruiter = User::factory()->create();
        $recruiter->assignRole('recruiter');

        $response = $this->actingAs($recruiter)->get('/organisations/create');

        $response->assertForbidden();
    }

    /** @test */
    public function candidate_cannot_view_create_form()
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('candidate');

        $response = $this->actingAs($candidate)->get('/organisations/create');

        $response->assertForbidden();
    }

    // ==================== STORE ====================

    /** @test */
    public function admin_can_create_organisation()
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

    /** @test */
    public function recruiter_cannot_create_organisation()
    {
        $recruiter = User::factory()->create();
        $recruiter->assignRole('recruiter');

        $response = $this->actingAs($recruiter)->post('/organisations', [
            'name' => 'Nouvelle Organisation',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('organisations', 0);
    }

    /** @test */
    public function candidate_cannot_create_organisation()
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('candidate');

        $response = $this->actingAs($candidate)->post('/organisations', [
            'name' => 'Nouvelle Organisation',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('organisations', 0);
    }

    /** @test */
    public function organisation_name_is_required()
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

    /** @test */
    public function organisation_name_has_maximum_length()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post('/organisations', [
            'name' => str_repeat('a', 256), // Trop long
        ]);

        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function organisation_email_must_be_valid()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post('/organisations', [
            'name' => 'Test Org',
            'email' => 'invalid-email',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function organisation_slug_is_unique()
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

        // Le slug devrait être incrémenté automatiquement
        $response->assertRedirect();
        $this->assertDatabaseHas('organisations', [
            'slug' => 'mon-entreprise-1',
        ]);
    }

    // ==================== SHOW ====================

    /** @test */
    public function admin_can_view_organisation_they_belong_to()
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

    /** @test */
    public function recruiter_can_view_organisation_they_belong_to()
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

    /** @test */
    public function user_cannot_view_organisation_they_dont_belong_to()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $org = Organisation::factory()->create();

        $response = $this->actingAs($admin)->get("/organisations/{$org->slug}");

        $response->assertForbidden();
    }

    /** @test */
    public function candidate_cannot_view_organisation()
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('candidate');
        
        $org = Organisation::factory()->create();

        $response = $this->actingAs($candidate)->get("/organisations/{$org->slug}");

        $response->assertForbidden();
    }

    // ==================== EDIT ====================

    /** @test */
    public function admin_can_view_edit_form_for_their_organisation()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $org = Organisation::factory()->create();
        $org->users()->attach($admin);

        $response = $this->actingAs($admin)->get("/organisations/{$org->slug}/edit");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Organisations/Edit')
            ->where('organisation.id', $org->id)
        );
    }

    /** @test */
    public function recruiter_cannot_view_edit_form()
    {
        $recruiter = User::factory()->create();
        $recruiter->assignRole('recruiter');
        
        $org = Organisation::factory()->create();
        $org->users()->attach($recruiter);

        $response = $this->actingAs($recruiter)->get("/organisations/{$org->slug}/edit");

        $response->assertForbidden();
    }

    /** @test */
    public function admin_cannot_view_edit_form_for_organisation_they_dont_belong_to()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $org = Organisation::factory()->create();

        $response = $this->actingAs($admin)->get("/organisations/{$org->slug}/edit");

        $response->assertForbidden();
    }

    // ==================== UPDATE ====================

    /** @test */
    public function admin_can_update_their_organisation()
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

    /** @test */
    public function recruiter_cannot_update_organisation()
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

    /** @test */
    public function admin_cannot_update_organisation_they_dont_belong_to()
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

    /** @test */
    public function candidate_cannot_update_organisation()
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('candidate');
        
        $org = Organisation::factory()->create(['name' => 'Original']);

        $response = $this->actingAs($candidate)->put("/organisations/{$org->slug}", [
            'name' => 'Hacked',
        ]);

        $response->assertForbidden();
    }

    /** @test */
    public function updating_organisation_name_regenerates_slug()
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

    // ==================== DESTROY ====================

    /** @test */
    public function admin_can_delete_their_organisation()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $org = Organisation::factory()->create();
        $org->users()->attach($admin);

        $response = $this->actingAs($admin)->delete("/organisations/{$org->slug}");

        $response->assertRedirect('/organisations');
        $this->assertSoftDeleted('organisations', ['id' => $org->id]);
    }

    /** @test */
    public function recruiter_cannot_delete_organisation()
    {
        $recruiter = User::factory()->create();
        $recruiter->assignRole('recruiter');
        
        $org = Organisation::factory()->create();
        $org->users()->attach($recruiter);

        $response = $this->actingAs($recruiter)->delete("/organisations/{$org->slug}");

        $response->assertForbidden();
        $this->assertDatabaseHas('organisations', ['id' => $org->id]);
    }

    /** @test */
    public function admin_cannot_delete_organisation_they_dont_belong_to()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $org = Organisation::factory()->create();

        $response = $this->actingAs($admin)->delete("/organisations/{$org->slug}");

        $response->assertForbidden();
        $this->assertDatabaseHas('organisations', ['id' => $org->id]);
    }

    /** @test */
    public function candidate_cannot_delete_organisation()
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('candidate');
        
        $org = Organisation::factory()->create();

        $response = $this->actingAs($candidate)->delete("/organisations/{$org->slug}");

        $response->assertForbidden();
    }

    /** @test */
    public function deleting_organisation_keeps_pivot_records()
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

    // ==================== VALIDATION ====================

    /** @test */
    public function organisation_optional_fields_can_be_null()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post('/organisations', [
            'name' => 'Simple Org',
            'description' => null,
            'email' => null,
            'phone' => null,
            'website' => null,
            'address' => null,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('organisations', [
            'name' => 'Simple Org',
            'description' => null,
            'email' => null,
        ]);
    }

    /** @test */
    public function organisation_website_must_be_valid_url()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post('/organisations', [
            'name' => 'Test Org',
            'website' => 'not-a-url',
        ]);

        $response->assertSessionHasErrors('website');
    }

    /** @test */
    public function organisation_phone_must_be_string()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post('/organisations', [
            'name' => 'Test Org',
            'phone' => '0123456789',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('organisations', [
            'phone' => '0123456789',
        ]);
    }
}