<?php

namespace Database\Seeders;

use App\Models\CountryConfig;
use Illuminate\Database\Seeder;

class CountryConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = [
            [
                'country' => 'El Salvador',
                'currency' => 'USD',
                'tax_percentage' => 13.00,
                'card_fee_percentage' => 3.50,
            ],
            [
                'country' => 'Guatemala',
                'currency' => 'GTQ',
                'tax_percentage' => 12.00,
                'card_fee_percentage' => 3.50,
            ],
            [
                'country' => 'Honduras',
                'currency' => 'HNL',
                'tax_percentage' => 15.00,
                'card_fee_percentage' => 3.50,
            ],
            [
                'country' => 'Nicaragua',
                'currency' => 'NIO',
                'tax_percentage' => 15.00,
                'card_fee_percentage' => 3.50,
            ],
            [
                'country' => 'Costa Rica',
                'currency' => 'CRC',
                'tax_percentage' => 13.00,
                'card_fee_percentage' => 3.50,
            ],
            [
                'country' => 'Panamá',
                'currency' => 'USD',
                'tax_percentage' => 7.00,
                'card_fee_percentage' => 3.50,
            ],
        ];

        foreach ($countries as $country) {
            CountryConfig::updateOrCreate(
                ['country' => $country['country']],
                $country
            );
        }
    }
}
