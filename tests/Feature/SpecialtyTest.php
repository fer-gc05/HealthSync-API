<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Specialty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class SpecialtyTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear roles y permisos
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    /** @test */
    public function usuarios_publicos_solo_ven_especialidades_activas()
    {
        // Crear especialidades de prueba
        Specialty::factory()->create(['name' => 'Cardiología', 'active' => true]);
        Specialty::factory()->create(['name' => 'Pediatría', 'active' => false]);
        Specialty::factory()->create(['name' => 'Neurología', 'active' => true, 'deleted_at' => now()]);

        $response = $this->getJson('/api/specialties');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'Cardiología'])
            ->assertJsonMissing(['name' => 'Pediatría'])
            ->assertJsonMissing(['name' => 'Neurología']);
    }

    /** @test */
    public function admin_puede_ver_todas_las_especialidades_incluyendo_eliminadas()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Especialidades activas (no eliminadas)
        Specialty::factory()->create(['name' => 'Cardiología']);

        // Especialidad eliminada (soft delete)
        $especialidadEliminada = Specialty::factory()->create(['name' => 'Pediatría']);
        $especialidadEliminada->delete(); // Soft delete

        $response = $this->actingAs($admin, 'api')
            ->getJson('/api/specialties?with_trashed=1');

        $response->assertOk()
            ->assertJsonCount(2, 'data'); // 1 activa + 1 eliminada
    }

    /** @test */
    public function admin_puede_filtrar_por_especialidades_activas()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Specialty::factory()->create(['name' => 'Cardiología', 'active' => true]);
        Specialty::factory()->create(['name' => 'Pediatría', 'active' => false]);

        $response = $this->actingAs($admin, 'api')
            ->getJson('/api/specialties?active=1');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'Cardiología']);
    }

    /** @test */
    public function admin_puede_filtrar_solo_especialidades_eliminadas()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Specialty::factory()->create(['name' => 'Cardiología', 'active' => true]);
        $deleted = Specialty::factory()->create(['name' => 'Pediatría', 'deleted_at' => now()]);

        $response = $this->actingAs($admin, 'api')
            ->getJson('/api/specialties?only_trashed=1');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'Pediatría']);
    }

    /** @test */
    public function puede_buscar_especialidades_por_nombre()
    {
        Specialty::factory()->create(['name' => 'Cardiología', 'active' => true]);
        Specialty::factory()->create(['name' => 'Pediatría', 'active' => true]);

        $response = $this->getJson('/api/specialties?q=cardio');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'Cardiología']);
    }

    /** @test */
    public function puede_buscar_especialidades_por_descripcion()
    {
        Specialty::factory()->create([
            'name' => 'Cardiología',
            'description' => 'Especialidad del corazón',
            'active' => true
        ]);

        $response = $this->getJson('/api/specialties?q=corazón');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'Cardiología']);
    }

    /** @test */
    public function puede_ordenar_especialidades()
    {
        Specialty::factory()->create(['name' => 'Cardiología', 'active' => true]);
        Specialty::factory()->create(['name' => 'Pediatría', 'active' => true]);
        Specialty::factory()->create(['name' => 'Neurología', 'active' => true]);

        $response = $this->getJson('/api/specialties?sort_by=name&sort_dir=asc');

        $response->assertOk();
        $data = $response->json('data');

        $this->assertEquals('Cardiología', $data[0]['name']);
        $this->assertEquals('Neurología', $data[1]['name']);
        $this->assertEquals('Pediatría', $data[2]['name']);
    }

    /** @test */
    public function respeta_paginacion_personalizada()
    {
        Specialty::factory()->count(10)->create(['active' => true]);

        $response = $this->getJson('/api/specialties?per_page=2');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('per_page', 2)
            ->assertJsonPath('total', 10);
    }

    /** @test */
    public function admin_puede_crear_especialidad()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $data = [
            'name' => 'Cardiología',
            'description' => 'Especialidad del corazón',
            'active' => true
        ];

        $response = $this->actingAs($admin, 'api')
            ->postJson('/api/admin/specialties', $data);

        $response->assertCreated()
            ->assertJsonFragment([
                'success' => true,
                'message' => 'Specialty created successfully.'
            ]);

        $this->assertDatabaseHas('specialties', ['name' => 'Cardiología']);
    }

    /** @test */
    public function no_puede_crear_especialidad_con_nombre_duplicado()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Specialty::factory()->create(['name' => 'Cardiología']);

        $response = $this->actingAs($admin, 'api')
            ->postJson('/api/admin/specialties', [
                'name' => 'Cardiología',
                'description' => 'Duplicado'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function admin_puede_actualizar_especialidad()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $specialty = Specialty::factory()->create(['name' => 'Cardiología']);

        $response = $this->actingAs($admin, 'api')
            ->putJson("/api/admin/specialties/{$specialty->id}", [
                'name' => 'Cardiología Avanzada',
                'active' => false
            ]);

        $response->assertOk()
            ->assertJsonFragment([
                'message' => 'Specialty updated successfully.',
                'name' => 'Cardiología Avanzada'
            ]);

        $this->assertDatabaseHas('specialties', [
            'id' => $specialty->id,
            'name' => 'Cardiología Avanzada',
            'active' => false
        ]);
    }

    /** @test */
    public function admin_puede_ver_detalles_de_especialidad()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $specialty = Specialty::factory()->create([
            'name' => 'Cardiología',
            'description' => 'Especialidad del corazón'
        ]);

        $response = $this->actingAs($admin, 'api')
            ->getJson("/api/specialties/{$specialty->id}");

        $response->assertOk()
            ->assertJsonFragment([
                'name' => 'Cardiología',
                'description' => 'Especialidad del corazón'
            ]);
    }

    /** @test */
    public function admin_puede_eliminar_especialidad()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $specialty = Specialty::factory()->create(['name' => 'Cardiología']);

        $response = $this->actingAs($admin, 'api')
            ->deleteJson("/api/admin/specialties/{$specialty->id}");

        $response->assertOk()
            ->assertJsonFragment([
                'message' => 'Specialty deleted successfully.'
            ]);

        $this->assertSoftDeleted('specialties', ['id' => $specialty->id]);
    }

    /** @test */
    public function admin_puede_restaurar_especialidad()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $specialty = Specialty::factory()->create([
            'name' => 'Cardiología',
            'deleted_at' => now()
        ]);

        $response = $this->actingAs($admin, 'api')
            ->postJson("/api/admin/specialties/{$specialty->id}/restore");

        $response->assertOk()
            ->assertJsonFragment([
                'message' => 'Specialty restored successfully.'
            ]);

        $this->assertDatabaseHas('specialties', [
            'id' => $specialty->id,
            'deleted_at' => null
        ]);
    }

    /** @test */
    public function admin_puede_eliminar_permanentemente_especialidad()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $specialty = Specialty::factory()->create([
            'name' => 'Cardiología',
            'deleted_at' => now()
        ]);

        $response = $this->actingAs($admin, 'api')
            ->deleteJson("/api/admin/specialties/{$specialty->id}/force");

        $response->assertOk()
            ->assertJsonFragment([
                'message' => 'Specialty permanently deleted.'
            ]);

        $this->assertDatabaseMissing('specialties', ['id' => $specialty->id]);
    }
}
