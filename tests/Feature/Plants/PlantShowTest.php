<?php

namespace Tests\Feature\Plants;

use App\Models\Plant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class PlantShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(), 'sanctum');
    }

    public function test_it_gets_a_single_plant_by_id(): void
    {
        $plant = Plant::create([
            'name' => 'Monstera Deliciosa',
            'price' => 24.50,
            'category_type' => 'Interior',
            'image_url' => 'plants/monstera.webp',
            'active' => true,
        ]);

        $response = $this->getJson("/api/v1/plants/{$plant->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'price',
                    'categoryType',
                    'imageUrl',
                    'imagePath',
                    'active',
                    'createdAt',
                    'updatedAt',
                ],
            ])
            ->assertJsonPath('data.id', $plant->id)
            ->assertJsonPath('data.name', 'Monstera Deliciosa')
            ->assertJsonPath('data.price', 24.5)
            ->assertJsonPath('data.categoryType', 'Interior')
            ->assertJsonPath('data.imagePath', 'plants/monstera.webp')
            ->assertJsonPath('data.active', true);
    }

    public function test_it_returns_404_when_plant_is_not_found(): void
    {
        $response = $this->getJson('/api/v1/plants/99999');

        $response->assertNotFound()
            ->assertJson([
                'message' => 'Planta no encontrada',
                'codigo' => 404,
            ]);
    }

    public function test_it_returns_404_when_plant_is_soft_deleted(): void
    {
        $plant = Plant::create([
            'name' => 'Planta Descontinuada',
            'price' => 15.00,
            'active' => true,
        ]);
        $plant->delete();

        $response = $this->getJson("/api/v1/plants/{$plant->id}");

        $response->assertNotFound()
            ->assertJson([
                'message' => 'Planta no encontrada',
                'codigo' => 404,
            ]);
    }

    public function test_guests_cannot_view_plant_details(): void
    {
        Auth::forgetGuards();

        $plant = Plant::create([
            'name' => 'Planta Protegida',
            'price' => 18.00,
            'active' => true,
        ]);

        $response = $this->getJson("/api/v1/plants/{$plant->id}");

        $response->assertUnauthorized();
    }
}
