<?php

namespace Database\Seeders;

use App\Models\Plant;
use Illuminate\Database\Seeder;

class PlantCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $plants = [
            // Árboles y Frutales
            ['name' => 'Limón Pérsico Injerto', 'category' => 'Frutal', 'price' => 18.50],
            ['name' => 'Naranja Valencia', 'category' => 'Frutal', 'price' => 19.00],
            ['name' => 'Mango Tommy Atkins', 'category' => 'Frutal', 'price' => 22.00],
            ['name' => 'Aguacate Hass Injerto', 'category' => 'Frutal', 'price' => 28.00],
            ['name' => 'Guayaba Taiwanesa', 'category' => 'Frutal', 'price' => 15.00],
            ['name' => 'Maquilishuat (Árbol Nacional)', 'category' => 'Árbol Ornamental', 'price' => 25.00],
            ['name' => 'Cortez Blanco Flor', 'category' => 'Árbol Ornamental', 'price' => 24.00],
            ['name' => 'Ficus Benjamina Poda', 'category' => 'Árbol Ornamental', 'price' => 16.50],
            ['name' => 'Araucaria Excelsa', 'category' => 'Conífera', 'price' => 35.00],
            ['name' => 'Palmera Real Cubana', 'category' => 'Palmera', 'price' => 45.00],
            ['name' => 'Palmera Areca Tropical', 'category' => 'Palmera', 'price' => 20.00],
            ['name' => 'Palmera Cola de Zorro', 'category' => 'Palmera', 'price' => 55.00],

            // Arbustos y Setos
            ['name' => 'Ixora Enana Roja', 'category' => 'Arbusto Ornamental', 'price' => 4.50],
            ['name' => 'Ixora Amarilla Sol', 'category' => 'Arbusto Ornamental', 'price' => 4.50],
            ['name' => 'Crotón Petras Bicolor', 'category' => 'Arbusto Ornamental', 'price' => 8.50],
            ['name' => 'Duranta Dorada Seto', 'category' => 'Seto vivo', 'price' => 3.50],
            ['name' => 'Eugenia Mirto Cercas', 'category' => 'Seto vivo', 'price' => 5.00],
            ['name' => 'Falso Maní Cubresuelo', 'category' => 'Cubre suelo', 'price' => 2.00],
            ['name' => 'Cactus San Pedro Gigante', 'category' => 'Cactus', 'price' => 18.00],
            ['name' => 'Agave Azul Tequilero', 'category' => 'Suculenta', 'price' => 14.00],
            ['name' => 'Buganvilla Morada Trepadora', 'category' => 'Enredadera', 'price' => 9.50],
            ['name' => 'Jazmín de Madagascar Aromático', 'category' => 'Enredadera', 'price' => 12.00],

            // Plantas de Interior y Follaje
            ['name' => 'Monstera Deliciosa Costilla', 'category' => 'Follaje de Interior', 'price' => 26.00],
            ['name' => 'Monstera Adansonii Mini', 'category' => 'Follaje colgante', 'price' => 14.50],
            ['name' => 'Lengua de Suegra Sansevieria', 'category' => 'Purificadora', 'price' => 10.00],
            ['name' => 'Sansevieria Cilíndrica', 'category' => 'Purificadora', 'price' => 12.50],
            ['name' => 'Zamioculca Planta ZZ', 'category' => 'Interior resistente', 'price' => 22.00],
            ['name' => 'Pothos Dorado Colgante', 'category' => 'Follaje colgante', 'price' => 7.00],
            ['name' => 'Ficus Lyrata Hoja Violín', 'category' => 'Árbol de Interior', 'price' => 38.00],
            ['name' => 'Ficus Elástica Borgoña', 'category' => 'Árbol de Interior', 'price' => 24.00],
            ['name' => 'Paz Lily Cuna de Moisés', 'category' => 'Floral de Interior', 'price' => 11.00],
            ['name' => 'Anturio Rojo Clásico', 'category' => 'Floral de Interior', 'price' => 16.00],
            ['name' => 'Calathea Orbifolia Rayada', 'category' => 'Follaje decorativo', 'price' => 19.50],
            ['name' => 'Filodendro Birkin Variegado', 'category' => 'Follaje variegado', 'price' => 18.00],
            ['name' => 'Helecho Boston Rizado', 'category' => 'Helecho colgante', 'price' => 9.00],
            ['name' => 'Helecho Nido de Ave', 'category' => 'Helecho epífito', 'price' => 13.50],
            ['name' => 'Helecho Cuerno de Alce', 'category' => 'Helecho de pared', 'price' => 25.00],

            // Suculentas y Flores
            ['name' => 'Aloe Vera Genuino Sábila', 'category' => 'Medicinal', 'price' => 6.00],
            ['name' => 'Orquídea Phalaenopsis Blanca', 'category' => 'Orquídea', 'price' => 24.50],
            ['name' => 'Orquídea Cattleya Skinnieri', 'category' => 'Orquídea', 'price' => 32.00],
            ['name' => 'Rosa Roja Clásica Rosal', 'category' => 'Floral de Exterior', 'price' => 7.50],
            ['name' => 'Hortensia Azul Montaña', 'category' => 'Floral de Clima Fresco', 'price' => 15.00],
            ['name' => 'Echeveria Elegans Alabastro', 'category' => 'Suculenta', 'price' => 4.00],
            ['name' => 'Árbol de Jade Fortuna', 'category' => 'Suculenta arbustiva', 'price' => 8.50],
            ['name' => 'Corona de Cristo Roja Espinas', 'category' => 'Floral espinosa', 'price' => 6.50],
            ['name' => 'Bromelia Fasciata Rosada', 'category' => 'Bromelia', 'price' => 17.50],
            ['name' => 'Caladio Bicolor Alas Ángel', 'category' => 'Bulbosa estacional', 'price' => 7.00],
            ['name' => 'Singonio Blanco Punta Flecha', 'category' => 'Follaje trepador', 'price' => 5.50],
            ['name' => 'Peperomia Sandía Miniatura', 'category' => 'Follaje compacto', 'price' => 9.00],
            ['name' => 'Cica Revoluta Prehistórica', 'category' => 'Palmera ornamental', 'price' => 30.00],
            ['name' => 'Pata de Elefante Beaucarnea', 'category' => 'Cauliforme', 'price' => 36.00],
        ];

        foreach ($plants as $p) {
            Plant::firstOrCreate(
                ['name' => $p['name']],
                [
                    'category_type' => $p['category'],
                    'price' => $p['price'],
                    'image_url' => 'plants/'.strtolower(str_replace([' ', '/', '(', ')'], ['_', '', '', ''], $p['name'])).'.jpg',
                    'active' => true,
                ]
            );
        }
    }
}
