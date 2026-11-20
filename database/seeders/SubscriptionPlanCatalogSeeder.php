<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'plan_code' => 'BAS-MENS',
                'plan_name' => 'Plan Verde Esencial (1 Visita/mes)',
                'maintenance_type' => 'Poda básica de césped y deshierbe manual',
                'frequency_value' => 30,
                'frequency_unit' => 'days',
                'visit_count' => 1,
                'discount_percentage' => 5.00,
                'loyalty_points_multiplier' => 1.00,
                'price' => 35.00,
                'active' => true,
            ],
            [
                'plan_code' => 'RES-QUINC',
                'plan_name' => 'Plan Hogar Verde Quincenal (2 Visitas/mes)',
                'maintenance_type' => 'Corte de césped, orillado, setos y fertilización',
                'frequency_value' => 15,
                'frequency_unit' => 'days',
                'visit_count' => 2,
                'discount_percentage' => 10.00,
                'loyalty_points_multiplier' => 1.25,
                'price' => 65.00,
                'active' => true,
            ],
            [
                'plan_code' => 'PREM-SEM',
                'plan_name' => 'Plan Jardín Impecable Semanal (4 Visitas/mes)',
                'maintenance_type' => 'Mantenimiento integral, fitosanitario y nutrición foliar',
                'frequency_value' => 7,
                'frequency_unit' => 'days',
                'visit_count' => 4,
                'discount_percentage' => 15.00,
                'loyalty_points_multiplier' => 1.50,
                'price' => 125.00,
                'active' => true,
            ],
            [
                'plan_code' => 'COM-EMP',
                'plan_name' => 'Plan Corporativo & Centros Comerciales',
                'maintenance_type' => 'Áreas verdes comerciales con tractor y motodesbrozadora',
                'frequency_value' => 15,
                'frequency_unit' => 'days',
                'visit_count' => 2,
                'discount_percentage' => 12.00,
                'loyalty_points_multiplier' => 1.30,
                'price' => 220.00,
                'active' => true,
            ],
            [
                'plan_code' => 'XERO-SOST',
                'plan_name' => 'Plan Jardín Seco & Suculentas',
                'maintenance_type' => 'Xerojardinería, rocallas y sustratos especiales',
                'frequency_value' => 30,
                'frequency_unit' => 'days',
                'visit_count' => 1,
                'discount_percentage' => 8.00,
                'loyalty_points_multiplier' => 1.10,
                'price' => 45.00,
                'active' => true,
            ],
            [
                'plan_code' => 'COND-VIP',
                'plan_name' => 'Plan Condominios y Residenciales Privadas',
                'maintenance_type' => 'Zonas comunes, redondeles y parques internos comunitarios',
                'frequency_value' => 7,
                'frequency_unit' => 'days',
                'visit_count' => 8,
                'discount_percentage' => 20.00,
                'loyalty_points_multiplier' => 2.00,
                'price' => 450.00,
                'active' => true,
            ],
        ];

        foreach ($plans as $p) {
            SubscriptionPlan::firstOrCreate(
                ['plan_code' => $p['plan_code']],
                $p
            );
        }
    }
}
