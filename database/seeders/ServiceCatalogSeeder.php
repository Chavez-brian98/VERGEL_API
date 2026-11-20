<?php

namespace Database\Seeders;

use App\Models\CountryConfig;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class ServiceCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $elSalvador = CountryConfig::where('country', 'El Salvador')->first();
        $countryId = $elSalvador?->id;

        $categories = [
            ['name' => 'Mantenimiento General', 'description' => 'Servicios periódicos de corte de césped, limpieza y mantenimiento de jardines.'],
            ['name' => 'Podas Especializadas', 'description' => 'Poda estética, formativa y de altura para árboles, arbustos y setos.'],
            ['name' => 'Fumigación y Control de Plagas', 'description' => 'Tratamientos fitosanitarios orgánicos y químicos para el control de plagas y hongos.'],
            ['name' => 'Diseño y Paisajismo', 'description' => 'Planificación, remodelación y siembra de áreas verdes residenciales y comerciales.'],
            ['name' => 'Sistemas de Riego', 'description' => 'Instalación, reparación y programación de sistemas de riego por goteo o aspersión.'],
            ['name' => 'Fertilización y Nutrición', 'description' => 'Enmiendas de suelo, abonos orgánicos, sustratos y fertilización foliar.'],
        ];

        $categoryMap = [];
        foreach ($categories as $cat) {
            $created = ServiceCategory::firstOrCreate(['name' => $cat['name']]);
            $categoryMap[$cat['name']] = $created->id;
        }

        $services = [
            // Mantenimiento
            ['name' => 'Corte de césped residencial (hasta 100 m²)', 'category' => 'Mantenimiento General', 'price' => 35.00, 'unit' => 'visita', 'mult' => 1.0, 'desc' => 'Corte, orillado y recolección de residuos vegetales.'],
            ['name' => 'Corte de césped residencial (100 a 300 m²)', 'category' => 'Mantenimiento General', 'price' => 65.00, 'unit' => 'visita', 'mult' => 1.0, 'desc' => 'Corte con máquina rotativa, perfilado de orillas y descarte.'],
            ['name' => 'Corte de césped comercial/industrial (>300 m²)', 'category' => 'Mantenimiento General', 'price' => 120.00, 'unit' => 'visita', 'mult' => 1.2, 'desc' => 'Mantenimiento de áreas extensas con tractor cortacésped.'],
            ['name' => 'Limpieza y deshierbe manual de arriates', 'category' => 'Mantenimiento General', 'price' => 25.00, 'unit' => 'hora', 'mult' => 1.0, 'desc' => 'Retiro manual de maleza de raíz en jardineras y arriates.'],
            ['name' => 'Mantenimiento integral de jardineras en terrazas', 'category' => 'Mantenimiento General', 'price' => 45.00, 'unit' => 'servicio', 'mult' => 1.0, 'desc' => 'Revisión de drenajes, limpieza de follaje seco y remoción de tierra.'],

            // Podas
            ['name' => 'Poda de formación de arbustos y setos', 'category' => 'Podas Especializadas', 'price' => 40.00, 'unit' => 'hora', 'mult' => 1.0, 'desc' => 'Perfilado geométrico y estético de cercos vivos.'],
            ['name' => 'Poda sanitaria de árboles frutales', 'category' => 'Podas Especializadas', 'price' => 50.00, 'unit' => 'árbol', 'mult' => 1.1, 'desc' => 'Eliminación de ramas secas, enfermas y chupones.'],
            ['name' => 'Poda de altura y desmoche controlado', 'category' => 'Podas Especializadas', 'price' => 125.00, 'unit' => 'árbol', 'mult' => 1.5, 'desc' => 'Poda técnica de árboles grandes con arnés y equipo de seguridad.'],
            ['name' => 'Poda de palmeras ornamentales (hasta 6 metros)', 'category' => 'Podas Especializadas', 'price' => 45.00, 'unit' => 'palmera', 'mult' => 1.1, 'desc' => 'Limpieza de corona y retiro de pencas secas e inflorescencias.'],
            ['name' => 'Tala técnica y destoconado de árbol mediano', 'category' => 'Podas Especializadas', 'price' => 200.00, 'unit' => 'árbol', 'mult' => 1.5, 'desc' => 'Derribo controlado por secciones y corte al ras de tocón.'],

            // Fumigación
            ['name' => 'Fumigación contra plagas de jardín (pulgón, cochinilla, mosca blanca)', 'category' => 'Fumigación y Control de Plagas', 'price' => 45.00, 'unit' => 'aplicación', 'mult' => 1.2, 'desc' => 'Aplicación de insecticida sistémico y ecológico.'],
            ['name' => 'Tratamiento fungicida para césped (mancha parda/fusarium)', 'category' => 'Fumigación y Control de Plagas', 'price' => 55.00, 'unit' => 'aplicación', 'mult' => 1.2, 'desc' => 'Control biológico y químico contra hongos en grama.'],
            ['name' => 'Control de hormiga arriera / zompopo', 'category' => 'Fumigación y Control de Plagas', 'price' => 50.00, 'unit' => 'nido', 'mult' => 1.0, 'desc' => 'Insuflación de polvo insecticida y cebos granulados.'],
            ['name' => 'Fumigación preventiva orgánica con aceite de neem y jabón potásico', 'category' => 'Fumigación y Control de Plagas', 'price' => 40.00, 'unit' => 'aplicación', 'mult' => 1.0, 'desc' => '100% orgánico, seguro para mascotas y niños.'],

            // Diseño y Paisajismo
            ['name' => 'Diseño paisajístico digital en 3D (hasta 200 m²)', 'category' => 'Diseño y Paisajismo', 'price' => 180.00, 'unit' => 'diseño', 'mult' => 2.0, 'desc' => 'Planos en planta, selección botánica y renders fotorrealistas.'],
            ['name' => 'Siembra y nivelación de césped San Agustín', 'category' => 'Diseño y Paisajismo', 'price' => 6.50, 'unit' => 'm²', 'mult' => 1.2, 'desc' => 'Incluye tierra negra, compostaje y tepes frescos.'],
            ['name' => 'Siembra de césped Bermuda híbrido', 'category' => 'Diseño y Paisajismo', 'price' => 7.50, 'unit' => 'm²', 'mult' => 1.2, 'desc' => 'Ideal para áreas soleadas de alto tráfico.'],
            ['name' => 'Creación de jardín de piedras y suculentas (Xerojardinería)', 'category' => 'Diseño y Paisajismo', 'price' => 150.00, 'unit' => 'proyecto', 'mult' => 1.5, 'desc' => 'Jardín de bajo consumo hídrico con rocallas y suculentas.'],
            ['name' => 'Instalación de geotextil y grava decorativa', 'category' => 'Diseño y Paisajismo', 'price' => 18.00, 'unit' => 'm²', 'mult' => 1.0, 'desc' => 'Control de maleza con acabado de piedra volcánica o blanca.'],

            // Riego
            ['name' => 'Diagnóstico y reparación de fuga en tubería de riego', 'category' => 'Sistemas de Riego', 'price' => 35.00, 'unit' => 'reparación', 'mult' => 1.0, 'desc' => 'Localización de rotura, cambio de fitting y purga del circuito.'],
            ['name' => 'Mantenimiento y calibración de cabezales y aspersores', 'category' => 'Sistemas de Riego', 'price' => 40.00, 'unit' => 'servicio', 'mult' => 1.0, 'desc' => 'Limpieza de boquillas, regulación de radio y arco de aspersión.'],
            ['name' => 'Instalación de programador automático digital de riego', 'category' => 'Sistemas de Riego', 'price' => 95.00, 'unit' => 'instalación', 'mult' => 1.3, 'desc' => 'Montaje de temporizador multizona a corriente o batería.'],
            ['name' => 'Instalación de línea de microgoteo para setos (por tramo 25m)', 'category' => 'Sistemas de Riego', 'price' => 75.00, 'unit' => 'tramo', 'mult' => 1.2, 'desc' => 'Tubería autocompensante, goteros y válvula reductora.'],

            // Fertilización
            ['name' => 'Abonado profundo con humus de lombriz y compost maduro', 'category' => 'Fertilización y Nutrición', 'price' => 30.00, 'unit' => 'saco aplicado', 'mult' => 1.0, 'desc' => 'Incorporación al suelo para mejorar retención y microbiología.'],
            ['name' => 'Fertilización química granulada NPK de liberación lenta', 'category' => 'Fertilización y Nutrición', 'price' => 25.00, 'unit' => 'aplicación', 'mult' => 1.0, 'desc' => 'Aporte equilibrado de nitrógeno, fósforo y potasio.'],
            ['name' => 'Tratamiento quelatado de hierro contra clorosis foliar', 'category' => 'Fertilización y Nutrición', 'price' => 35.00, 'unit' => 'aplicación', 'mult' => 1.1, 'desc' => 'Corrección rápida de hojas amarillentas en cítricos y ornamentales.'],
        ];

        foreach ($services as $srv) {
            Service::firstOrCreate(
                ['name' => $srv['name'], 'price' => $srv['price']],
                [
                    'category_id' => $categoryMap[$srv['category']],
                    'unit_of_measure' => $srv['unit'],
                    'loyalty_points_multiplier' => $srv['mult'],
                    'description' => $srv['desc'],
                    'country_config_id' => $countryId,
                    'active' => true,
                ]
            );
        }
    }
}
