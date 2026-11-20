<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            ['name' => 'Vivero San Andrés S.A. de C.V.', 'trade' => 'Vivero San Andrés', 'tax' => '0614-120188-101-1', 'nrc' => '120456-1', 'activity' => 'Cultivo y comercialización de plantas ornamentales', 'phone' => '2245-1101', 'email' => 'ventas@viverosanandres.com', 'contact' => 'Lic. Roberto Morales'],
            ['name' => 'Agroquímica Cuscatlán S.A. de C.V.', 'trade' => 'AgroCuscatlán', 'tax' => '0614-230592-102-2', 'nrc' => '134567-2', 'activity' => 'Venta de fertilizantes, abonos y agroquímicos', 'phone' => '2245-1102', 'email' => 'pedidos@agrocuscatlan.com.sv', 'contact' => 'Ing. Marta Calderón'],
            ['name' => 'Importadora de Riego El Salvador S.A.', 'trade' => 'RiegoSV', 'tax' => '0614-150895-103-3', 'nrc' => '145678-3', 'activity' => 'Importación de mangueras, electroválvulas y aspersores', 'phone' => '2245-1103', 'email' => 'ventas@riegosv.com', 'contact' => 'Carlos Benítez'],
            ['name' => 'Macetas y Cerámicas de Oriente S.A. de C.V.', 'trade' => 'Cerámicas de Oriente', 'tax' => '0614-100499-104-4', 'nrc' => '156789-4', 'activity' => 'Fabricación de macetas de barro, fibra de vidrio y cemento', 'phone' => '2245-1104', 'email' => 'contacto@ceramicasoriente.sv', 'contact' => 'Guillermo Serrano'],
            ['name' => 'Sustratos y Compost de Centroamérica S.A.', 'trade' => 'BioSustratos SV', 'tax' => '0614-051101-105-5', 'nrc' => '167890-5', 'activity' => 'Tierra negra tamizada, turba, perlita y humus de lombriz', 'phone' => '2245-1105', 'email' => 'comercial@biosustratos.com', 'contact' => 'Ing. Juan Carlos Umaña'],
            ['name' => 'Equipos de Jardinería y Motores S.A. de C.V.', 'trade' => 'Motores & Jardín SV', 'tax' => '0614-180203-106-6', 'nrc' => '178901-6', 'activity' => 'Venta y repuestos de desbrozadoras, motosierras y cortacéspedes', 'phone' => '2245-1106', 'email' => 'ventas@motoresyjardin.com', 'contact' => 'Eduardo Castaneda'],
            ['name' => 'Gramas y Tepes de Occidente S.A. de C.V.', 'trade' => 'GramaVerde SV', 'tax' => '0614-290705-107-7', 'nrc' => '189012-7', 'activity' => 'Producción de tepe de grama San Agustín y Bermuda', 'phone' => '2245-1107', 'email' => 'info@gramaverdesv.com', 'contact' => 'Héctor Valencia'],
            ['name' => 'Piedras y Rocallas del Pacífico S.A.', 'trade' => 'Piedras El Pacífico', 'tax' => '0614-030907-108-8', 'nrc' => '190123-8', 'activity' => 'Grava volcánica roja, piedra pómez y lajas decorativas', 'phone' => '2245-1108', 'email' => 'ventas@piedraselpacifico.com', 'contact' => 'Manuel Rivas'],
            ['name' => 'Distribuidora Fitosanitaria La Paz S.A.', 'trade' => 'AgroLaPaz', 'tax' => '0614-141209-109-9', 'nrc' => '201234-9', 'activity' => 'Control biológico, pesticidas ecológicos y trampas para plagas', 'phone' => '2245-1109', 'email' => 'servicio@agrolapaz.com.sv', 'contact' => 'Dra. Andrea Salguero'],
            ['name' => 'Semillas Certificadas de El Salvador S.A.', 'trade' => 'Semillas SV', 'tax' => '0614-250311-110-0', 'nrc' => '212345-0', 'activity' => 'Semillas de flores, césped, cobertura y hortalizas', 'phone' => '2245-1110', 'email' => 'ventas@semillassv.com', 'contact' => 'Felipe Monge'],
        ];

        foreach ($suppliers as $s) {
            Supplier::firstOrCreate(
                ['tax_id' => $s['tax']],
                [
                    'name' => $s['name'],
                    'trade_name' => $s['trade'],
                    'nrc' => $s['nrc'],
                    'economic_activity' => $s['activity'],
                    'phone' => $s['phone'],
                    'email' => $s['email'],
                    'address' => 'Carretera Panamericana Km 14, Santa Tecla, La Libertad',
                    'contact_name' => $s['contact'],
                    'contact_phone' => $s['phone'],
                    'status' => 'active',
                    'notes' => 'Proveedor evaluado y homologado con crédito a 30 días.',
                ]
            );
        }
    }
}
