<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_all_non_deleted_customers(): void
    {
        $customer = Customer::create([
            'legal_name' => 'Cliente de prueba',
            'address' => 'Direccion de prueba',
            'municipality' => 'San Salvador',
            'phone' => '2222-2222',
        ]);

        $deletedCustomer = Customer::create([
            'legal_name' => 'Cliente eliminado',
            'address' => 'Direccion eliminada',
            'municipality' => 'San Salvador',
            'phone' => '2222-2223',
        ]);
        $deletedCustomer->delete();

        $response = $this->getJson('/api/customers');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'id' => $customer->id,
                'legal_name' => 'Cliente de prueba',
            ])
            ->assertJsonMissing([
                'legal_name' => 'Cliente eliminado',
            ]);
    }
}