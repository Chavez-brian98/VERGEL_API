<?php

namespace Tests\Feature\Plants;

use App\Models\Plant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class PlantIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(), 'sanctum');
    }

    public function test_it_lists_plants_with_pagination(): void
    {
        Plant::create(['name' => 'Planta 1', 'price' => 10.00, 'category_type' => 'Interior', 'active' => true]);
        Plant::create(['name' => 'Planta 2', 'price' => 20.00, 'category_type' => 'Exterior', 'active' => true]);

        $response = $this->getJson('/api/v1/plants');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
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
                ],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'from', 'last_page', 'per_page', 'to', 'total'],
            ]);
    }

    public function test_it_filters_plants_by_search_term(): void
    {
        Plant::create(['name' => 'Ficus Lyrata', 'price' => 25.00, 'category_type' => 'Interior', 'active' => true]);
        Plant::create(['name' => 'Monstera Deliciosa', 'price' => 30.00, 'category_type' => 'Interior', 'active' => true]);
        Plant::create(['name' => 'Cactus San Pedro', 'price' => 15.00, 'category_type' => 'Suculenta', 'active' => true]);

        $response = $this->getJson('/api/v1/plants?search=lyrata');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Ficus Lyrata');
    }

    public function test_it_filters_plants_by_category(): void
    {
        Plant::create(['name' => 'Ficus Lyrata', 'price' => 25.00, 'category_type' => 'Interior', 'active' => true]);
        Plant::create(['name' => 'Helecho', 'price' => 12.00, 'category_type' => 'Sombra', 'active' => true]);

        $response = $this->getJson('/api/v1/plants?category_type=Sombra');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Helecho');
    }

    public function test_it_filters_plants_by_active_status(): void
    {
        Plant::create(['name' => 'Activa 1', 'price' => 10.00, 'active' => true]);
        Plant::create(['name' => 'Inactiva 1', 'price' => 15.00, 'active' => false]);

        $response = $this->getJson('/api/v1/plants?active=false');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Inactiva 1');
    }

    public function test_it_filters_plants_by_price_range(): void
    {
        Plant::create(['name' => 'Barata', 'price' => 5.00, 'active' => true]);
        Plant::create(['name' => 'Media', 'price' => 25.00, 'active' => true]);
        Plant::create(['name' => 'Cara', 'price' => 80.00, 'active' => true]);

        $response = $this->getJson('/api/v1/plants?min_price=10&max_price=30');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Media');
    }

    public function test_it_supports_custom_per_page_and_limit(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            Plant::create(['name' => "Planta {$i}", 'price' => $i * 10, 'active' => true]);
        }

        $response = $this->getJson('/api/v1/plants?per_page=2');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 5);
    }

    public function test_it_does_not_return_soft_deleted_plants(): void
    {
        $plant = Plant::create(['name' => 'Eliminada', 'price' => 10.00, 'active' => true]);
        $plant->delete();

        $response = $this->getJson('/api/v1/plants');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_guests_cannot_list_plants(): void
    {
        Auth::forgetGuards();

        $response = $this->getJson('/api/v1/plants');

        $response->assertUnauthorized();
    }
}
