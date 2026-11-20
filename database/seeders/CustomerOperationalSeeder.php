<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\CustomerPlanHistory;
use App\Models\DteInvoice;
use App\Models\Employee;
use App\Models\Inspection;
use App\Models\InspectionPhoto;
use App\Models\LedgerEntry;
use App\Models\LoyaltyPoint;
use App\Models\LoyaltyPointTransaction;
use App\Models\LoyaltyRedemption;
use App\Models\LoyaltyReward;
use App\Models\Movement;
use App\Models\Payment;
use App\Models\Plant;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\SubscriptionMaintenanceConsumption;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CustomerOperationalSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener datos de referencia
        $plans = SubscriptionPlan::all();
        $services = Service::all();
        $plants = Plant::all();
        $technicians = Employee::whereHas('role', fn ($q) => $q->where('role_name', 'Tecnico de campo'))->get();
        $receptionists = Employee::whereHas('role', fn ($q) => $q->where('role_name', 'Recepcionista'))->get();
        $adminEmployee = Employee::whereHas('role', fn ($q) => $q->where('role_name', 'Administrador'))->first();

        // 55 Clientes realistas salvadoreños
        $customerNames = [
            // Residenciales
            ['legal' => 'Alejandro José Pineda', 'trade' => null, 'dui' => '02345678-1', 'nrc' => null, 'phone' => '7845-1201', 'email' => 'alejandro.pineda@gmail.com', 'zone' => 'Colonia Escalón', 'muni' => 'San Salvador'],
            ['legal' => 'Beatriz Eugenia Menéndez', 'trade' => null, 'dui' => '03456789-2', 'nrc' => null, 'phone' => '7845-1202', 'email' => 'beatriz.menendez@hotmail.com', 'zone' => 'Santa Elena', 'muni' => 'Antiguo Cuscatlán'],
            ['legal' => 'Carlos Alberto Solórzano', 'trade' => null, 'dui' => '04567890-3', 'nrc' => null, 'phone' => '7845-1203', 'email' => 'carlos.solorzano@yahoo.com', 'zone' => 'Cumbres de Cuscatlán', 'muni' => 'Antiguo Cuscatlán'],
            ['legal' => 'Diana Marcela Henríquez', 'trade' => null, 'dui' => '05678901-4', 'nrc' => null, 'phone' => '7845-1204', 'email' => 'diana.henriquez@outlook.com', 'zone' => 'Colonia San Benito', 'muni' => 'San Salvador'],
            ['legal' => 'Eduardo René Calderón', 'trade' => null, 'dui' => '06789012-5', 'nrc' => null, 'phone' => '7845-1205', 'email' => 'eduardo.calderon@gmail.com', 'zone' => 'Miramonte', 'muni' => 'San Salvador'],
            ['legal' => 'Fátima Lucía Barillas', 'trade' => null, 'dui' => '07890123-6', 'nrc' => null, 'phone' => '7845-1206', 'email' => 'fatima.barillas@gmail.com', 'zone' => 'La Sultana', 'muni' => 'Antiguo Cuscatlán'],
            ['legal' => 'Guillermo Antonio Palomo', 'trade' => null, 'dui' => '08901234-7', 'nrc' => null, 'phone' => '7845-1207', 'email' => 'guillermo.palomo@gmail.com', 'zone' => 'Lomas de San Francisco', 'muni' => 'San Salvador'],
            ['legal' => 'Helena María Guirola', 'trade' => null, 'dui' => '09012345-8', 'nrc' => null, 'phone' => '7845-1208', 'email' => 'helena.guirola@gmail.com', 'zone' => 'Colonia Maquilishuat', 'muni' => 'San Salvador'],
            ['legal' => 'Ignacio Francisco Simán', 'trade' => null, 'dui' => '01123456-9', 'nrc' => null, 'phone' => '7845-1209', 'email' => 'ignacio.siman@gmail.com', 'zone' => 'Residencial Palmira', 'muni' => 'Santa Tecla'],
            ['legal' => 'Jacqueline Michelle Regalado', 'trade' => null, 'dui' => '02234567-0', 'nrc' => null, 'phone' => '7845-1210', 'email' => 'jacqueline.regalado@hotmail.com', 'zone' => 'Residencial Las Colinas', 'muni' => 'Santa Tecla'],
            ['legal' => 'Kevin Daniel Batlle', 'trade' => null, 'dui' => '03345678-1', 'nrc' => null, 'phone' => '7845-1211', 'email' => 'kevin.batlle@gmail.com', 'zone' => 'Residencial Villas de Suiza', 'muni' => 'Santa Tecla'],
            ['legal' => 'Lorena Elizabeth Wright', 'trade' => null, 'dui' => '04456789-2', 'nrc' => null, 'phone' => '7845-1212', 'email' => 'lorena.wright@gmail.com', 'zone' => 'Altos de las Piletas', 'muni' => 'Santa Tecla'],
            ['legal' => 'Mauricio Ernesto Kriete', 'trade' => null, 'dui' => '05567890-3', 'nrc' => null, 'phone' => '7845-1213', 'email' => 'mauricio.kriete@gmail.com', 'zone' => 'Residencial Tuscania', 'muni' => 'Zaragoza'],
            ['legal' => 'Natalia Sofía Meza', 'trade' => null, 'dui' => '06678901-4', 'nrc' => null, 'phone' => '7845-1214', 'email' => 'natalia.meza@gmail.com', 'zone' => 'Vía del Mar', 'muni' => 'Nuevo Cuscatlán'],
            ['legal' => 'Óscar Armando Cristiani', 'trade' => null, 'dui' => '07789012-5', 'nrc' => null, 'phone' => '7845-1215', 'email' => 'oscar.cristiani@gmail.com', 'zone' => 'Residencial Santa Rosa', 'muni' => 'Santa Tecla'],
            ['legal' => 'Patricia Carolina Quirós', 'trade' => null, 'dui' => '08890123-6', 'nrc' => null, 'phone' => '7845-1216', 'email' => 'patricia.quiros@hotmail.com', 'zone' => 'Residencial Pinares de Suiza', 'muni' => 'Santa Tecla'],
            ['legal' => 'Rodrigo Javier Dutriz', 'trade' => null, 'dui' => '09901234-7', 'nrc' => null, 'phone' => '7845-1217', 'email' => 'rodrigo.dutriz@gmail.com', 'zone' => 'Colonia San Mateo', 'muni' => 'San Salvador'],
            ['legal' => 'Silvia Margarita Borgonovo', 'trade' => null, 'dui' => '01012345-8', 'nrc' => null, 'phone' => '7845-1218', 'email' => 'silvia.borgonovo@gmail.com', 'zone' => 'Sierra Santa Elena', 'muni' => 'Antiguo Cuscatlán'],
            ['legal' => 'Tomás Andrés Safie', 'trade' => null, 'dui' => '02123456-9', 'nrc' => null, 'phone' => '7845-1219', 'email' => 'tomas.safie@gmail.com', 'zone' => 'Colonia Campestre', 'muni' => 'San Salvador'],
            ['legal' => 'Vanessa Alexandra Hill', 'trade' => null, 'dui' => '03234567-0', 'nrc' => null, 'phone' => '7845-1220', 'email' => 'vanessa.hill@gmail.com', 'zone' => 'Lomas de Altamira', 'muni' => 'San Salvador'],
            ['legal' => 'Walter Alexander Salaverría', 'trade' => null, 'dui' => '04345678-1', 'nrc' => null, 'phone' => '7845-1221', 'email' => 'walter.salaverria@gmail.com', 'zone' => 'Colonia Flor Blanca', 'muni' => 'San Salvador'],
            ['legal' => 'Xenia Carolina Dueñas', 'trade' => null, 'dui' => '05456789-2', 'nrc' => null, 'phone' => '7845-1222', 'email' => 'xenia.duenas@gmail.com', 'zone' => 'Los Sueños Residencial', 'muni' => 'Nuevo Cuscatlán'],
            ['legal' => 'Yamileth Beatriz Bahaia', 'trade' => null, 'dui' => '06567890-3', 'nrc' => null, 'phone' => '7845-1223', 'email' => 'yamileth.bahaia@gmail.com', 'zone' => 'Altos de Cuscatlán', 'muni' => 'Antiguo Cuscatlán'],
            ['legal' => 'Zulma Jeannette Saca', 'trade' => null, 'dui' => '07678901-4', 'nrc' => null, 'phone' => '7845-1224', 'email' => 'zulma.saca@gmail.com', 'zone' => 'Colonia San Francisco', 'muni' => 'San Salvador'],
            ['legal' => 'Alonso Javier Álvarez', 'trade' => null, 'dui' => '08789012-5', 'nrc' => null, 'phone' => '7845-1225', 'email' => 'alonso.alvarez@gmail.com', 'zone' => 'Residencial Utila', 'muni' => 'Santa Tecla'],
            ['legal' => 'Brenda Verónica Araujo', 'trade' => null, 'dui' => '09890123-6', 'nrc' => null, 'phone' => '7845-1226', 'email' => 'brenda.araujo@gmail.com', 'zone' => 'Bosques de Santa Elena', 'muni' => 'Antiguo Cuscatlán'],
            ['legal' => 'César Augusto Belismelis', 'trade' => null, 'dui' => '01901234-7', 'nrc' => null, 'phone' => '7845-1227', 'email' => 'cesar.belismelis@gmail.com', 'zone' => 'Arboledas Residencial', 'muni' => 'Colón'],
            ['legal' => 'Débora María Cader', 'trade' => null, 'dui' => '02012345-8', 'nrc' => null, 'phone' => '7845-1228', 'email' => 'debora.cader@gmail.com', 'zone' => 'Residencial Los Chorros', 'muni' => 'Colón'],
            ['legal' => 'Emilio Salvador Canessa', 'trade' => null, 'dui' => '03123456-9', 'nrc' => null, 'phone' => '7845-1229', 'email' => 'emilio.canessa@gmail.com', 'zone' => 'San Salvador Centro', 'muni' => 'San Salvador'],
            ['legal' => 'Flavia Inés Cristiani', 'trade' => null, 'dui' => '04234567-0', 'nrc' => null, 'phone' => '7845-1230', 'email' => 'flavia.cristiani@gmail.com', 'zone' => 'Santa Elena Poniente', 'muni' => 'Antiguo Cuscatlán'],

            // Clientes Comerciales y Corporativos (Con NRC y Razón Social)
            ['legal' => 'Inversiones Bolívar S.A. de C.V.', 'trade' => 'Torre Alisios 115', 'dui' => null, 'nrc' => '102345-1', 'phone' => '2248-7001', 'email' => 'administracion@alisios115.com', 'zone' => 'Zona Rosa, San Benito', 'muni' => 'San Salvador'],
            ['legal' => 'Hotel Real InterContinental San Salvador S.A.', 'trade' => 'Real InterContinental', 'dui' => null, 'nrc' => '102345-2', 'phone' => '2248-7002', 'email' => 'jardineria@intercontinental.com.sv', 'zone' => 'Bulevar Los Héroes', 'muni' => 'San Salvador'],
            ['legal' => 'Operadora Plaza Madero S.A. de C.V.', 'trade' => 'Plaza Madero Santa Elena', 'dui' => null, 'nrc' => '102345-3', 'phone' => '2248-7003', 'email' => 'operaciones@plazamadero.com', 'zone' => 'Bulevar Orden de Malta', 'muni' => 'Antiguo Cuscatlán'],
            ['legal' => 'Condominio Puerta Los Faros Propietarios', 'trade' => 'Torres Los Faros', 'dui' => null, 'nrc' => '102345-4', 'phone' => '2248-7004', 'email' => 'junta@puertalosfaros.com', 'zone' => 'Carretera a La Libertad', 'muni' => 'Zaragoza'],
            ['legal' => 'Corporación Multi Inversiones SV S.A.', 'trade' => 'Complejo CMI El Salvador', 'dui' => null, 'nrc' => '102345-5', 'phone' => '2248-7005', 'email' => 'facility@cmi.com.gt', 'zone' => 'Plan de La Laguna', 'muni' => 'Antiguo Cuscatlán'],
            ['legal' => 'Restaurante Cadejo Brewing Co. S.A. de C.V.', 'trade' => 'Cadejo San Benito', 'dui' => null, 'nrc' => '102345-6', 'phone' => '2248-7006', 'email' => 'gerencia@cervezacadejo.com', 'zone' => 'Calle La Reforma #222', 'muni' => 'San Salvador'],
            ['legal' => 'Colegio Británico de El Salvador S.A.', 'trade' => 'The British Academy', 'dui' => null, 'nrc' => '102345-7', 'phone' => '2248-7007', 'email' => 'campus@britishacademy.edu.sv', 'zone' => 'Carretera al Boquerón', 'muni' => 'Santa Tecla'],
            ['legal' => 'Hospital Diagnóstico Escalón S.A. de C.V.', 'trade' => 'Hospital Diagnóstico', 'dui' => null, 'nrc' => '102345-8', 'phone' => '2248-7008', 'email' => 'mantenimiento@hdiagnostico.com.sv', 'zone' => 'Villavicencio Plaza, Escalón', 'muni' => 'San Salvador'],
            ['legal' => 'Centro Financiero Gigante S.A. de C.V.', 'trade' => 'Torres CFG', 'dui' => null, 'nrc' => '102345-9', 'phone' => '2248-7009', 'email' => 'servicios@cfgigante.com', 'zone' => 'Alameda Roosevelt', 'muni' => 'San Salvador'],
            ['legal' => 'Desarrollos Urbanos Quintas de San Agustín', 'trade' => 'Club Residencial San Agustín', 'dui' => null, 'nrc' => '102345-0', 'phone' => '2248-7010', 'email' => 'contacto@quintasdesanagustin.com', 'zone' => 'Km 18 Carretera al Puerto', 'muni' => 'Zaragoza'],
            ['legal' => 'Bambú City Center S.A. de C.V.', 'trade' => 'Bambú Plaza & Corporativo', 'dui' => null, 'nrc' => '102346-1', 'phone' => '2248-7011', 'email' => 'administracion@bambucitycenter.com', 'zone' => 'Zona Rosa', 'muni' => 'San Salvador'],
            ['legal' => 'Asociación Club Campestre Cuscatlán', 'trade' => 'Club Campestre', 'dui' => null, 'nrc' => '102346-2', 'phone' => '2248-7012', 'email' => 'areasverdes@clubcampestre.com.sv', 'zone' => 'Paseo General Escalón', 'muni' => 'San Salvador'],
            ['legal' => 'Autos El Salvador S.A. de C.V.', 'trade' => 'Agencia Santa Tecla', 'dui' => null, 'nrc' => '102346-3', 'phone' => '2248-7013', 'email' => 'facilities@autoselsalvador.com', 'zone' => 'Carretera Panamericana Km 11', 'muni' => 'Santa Tecla'],
            ['legal' => 'Plaza Presidente Propietarios S.A.', 'trade' => 'Torre & Hotel Presidente', 'dui' => null, 'nrc' => '102346-4', 'phone' => '2248-7014', 'email' => 'mantenimiento@plazapresidente.com', 'zone' => 'San Benito', 'muni' => 'San Salvador'],
            ['legal' => 'Edificio Millennium Plaza S.A. de C.V.', 'trade' => 'Millennium Plaza', 'dui' => null, 'nrc' => '102346-5', 'phone' => '2248-7015', 'email' => 'operaciones@millenniumplaza.com.sv', 'zone' => 'Paseo General Escalón', 'muni' => 'San Salvador'],
            ['legal' => 'Residencial Cumbres de la Esmeralda Junta Directiva', 'trade' => 'Parque La Esmeralda', 'dui' => null, 'nrc' => '102346-6', 'phone' => '2248-7016', 'email' => 'junta@cumbresesmeralda.com', 'zone' => 'Carretera a Comasagua', 'muni' => 'Santa Tecla'],
            ['legal' => 'Café de Don Pedro S.A. de C.V.', 'trade' => 'Café Don Pedro Escalón', 'dui' => null, 'nrc' => '102346-7', 'phone' => '2248-7017', 'email' => 'administracion@cafededonpedro.com', 'zone' => 'Alameda Manuel Enrique Araujo', 'muni' => 'San Salvador'],
            ['legal' => 'Grupo Roble Metrocentro S.A.', 'trade' => 'Jardines Metrocentro San Salvador', 'dui' => null, 'nrc' => '102346-8', 'phone' => '2248-7018', 'email' => 'paisajismo@gruporoble.com', 'zone' => 'Bulevar de Los Héroes', 'muni' => 'San Salvador'],
            ['legal' => 'Universidad Dr. José Matías Delgado', 'trade' => 'Campus 1 El Trébol', 'dui' => null, 'nrc' => '102346-9', 'phone' => '2248-7019', 'email' => 'infraestructura@ujmd.edu.sv', 'zone' => 'Carretera a Santa Tecla', 'muni' => 'Antiguo Cuscatlán'],
            ['legal' => 'Parque Industrial Merliot S.A.', 'trade' => 'Zona Franca Merliot', 'dui' => null, 'nrc' => '102347-0', 'phone' => '2248-7020', 'email' => 'green@parquemerliot.com', 'zone' => 'Ciudad Merliot', 'muni' => 'Santa Tecla'],
            ['legal' => 'Fideicomiso Residencial Quinta Real', 'trade' => 'Quinta Real', 'dui' => null, 'nrc' => '102347-1', 'phone' => '2248-7021', 'email' => 'comite@quintareal.com.sv', 'zone' => 'Planes de Renderos', 'muni' => 'Panchimalco'],
            ['legal' => 'Restaurante Las Brumas Grill S.A.', 'trade' => 'Las Brumas El Boquerón', 'dui' => null, 'nrc' => '102347-2', 'phone' => '2248-7022', 'email' => 'gerencia@lasbrumas.com.sv', 'zone' => 'Km 19.5 Volcán de San Salvador', 'muni' => 'Santa Tecla'],
            ['legal' => 'Condominio Campestre Los Naranjos', 'trade' => 'Villas Los Naranjos', 'dui' => null, 'nrc' => '102347-3', 'phone' => '2248-7023', 'email' => 'admon@losnaranjos.sv', 'zone' => 'Paso del Guayabo', 'muni' => 'Juayúa'],
            ['legal' => 'Villas del Mar Resort Costa del Sol S.A.', 'trade' => 'Villas del Mar', 'dui' => null, 'nrc' => '102347-4', 'phone' => '2248-7024', 'email' => 'costa@villasdelmar.com.sv', 'zone' => 'Bulevar Costa del Sol Km 68', 'muni' => 'San Luis La Herradura'],
            ['legal' => 'Laboratorios Suizos de El Salvador S.A.', 'trade' => 'Planta Farmacéutica Suizos', 'dui' => null, 'nrc' => '102347-5', 'phone' => '2248-7025', 'email' => 'ecologia@labsuizos.com.sv', 'zone' => 'Plan de La Laguna', 'muni' => 'Antiguo Cuscatlán'],
        ];

        $fakerIdx = 1;
        $createdCustomers = [];

        foreach ($customerNames as $cData) {
            $assignedPlan = $plans[$fakerIdx % $plans->count()];
            $taxId = $cData['dui'] ?? sprintf('0614-%02d0890-10%d-%d', ($fakerIdx % 28) + 1, ($fakerIdx % 9) + 1, $fakerIdx % 10);

            $customer = Customer::create([
                'legal_name' => $cData['legal'],
                'trade_name' => $cData['trade'],
                'tax_id' => $taxId,
                'nrc' => $cData['nrc'],
                'economic_activity' => $cData['nrc'] ? 'Servicios comerciales / Corporativo' : 'Consumidor final / Residencial',
                'address' => $cData['zone'].', Polígono '.chr(65 + ($fakerIdx % 10)).' #'.($fakerIdx * 2),
                'department' => in_array($cData['muni'], ['San Luis La Herradura']) ? 'La Paz' : (in_array($cData['muni'], ['Juayúa']) ? 'Sonsonate' : 'San Salvador'),
                'municipality' => $cData['muni'],
                'phone' => $cData['phone'],
                'email' => $cData['email'],
                'status' => 'active',
                'visit_frequency_value' => $assignedPlan->frequency_value,
                'visit_frequency_unit' => $assignedPlan->frequency_unit,
                'current_plan_id' => $assignedPlan->id,
            ]);

            // Billetera y EcoPuntos inicializados
            $initialCash = round(($fakerIdx * 15.5) % 350, 2);
            $reservedCash = round(($assignedPlan->price * 2), 2);
            $wallet = Wallet::create([
                'customer_id' => $customer->id,
                'cash_balance' => $initialCash,
                'reserved_subscription_balance' => $reservedCash,
            ]);

            $initialPoints = ($fakerIdx * 45) % 600;
            $loyalty = LoyaltyPoint::create([
                'customer_id' => $customer->id,
                'points_balance' => $initialPoints,
            ]);

            // Historial de planes
            CustomerPlanHistory::create([
                'customer_id' => $customer->id,
                'plan_id' => $assignedPlan->id,
                'start_date' => Carbon::now()->subMonths(6)->toDateString(),
                'end_date' => null,
            ]);

            // Suscripción activa
            $subscription = Subscription::create([
                'customer_id' => $customer->id,
                'plan_id' => $assignedPlan->id,
                'status' => Subscription::STATUS_ACTIVE,
                'payment_date' => Carbon::now()->subDays(10)->toDateString(),
                'amount_paid' => $assignedPlan->price,
                'reserved_balance' => $assignedPlan->price,
            ]);

            SubscriptionPayment::create([
                'subscription_id' => $subscription->id,
                'employee_id' => $receptionists->random()->id,
                'amount' => $assignedPlan->price,
                'payment_date' => Carbon::now()->subDays(10)->toDateTimeString(),
                'payment_method' => 'card',
                'description' => 'Pago mensual s/ suscripción #'.$subscription->id,
            ]);

            // Movimiento en billetera
            $walletTx = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'transaction_type' => 'top_up',
                'amount' => 100.00,
                'payment_method' => 'transfer',
                'is_annual_payment' => false,
                'created_at' => Carbon::now()->subDays(20),
            ]);

            // Movimiento de puntos
            LoyaltyPointTransaction::create([
                'loyalty_points_id' => $loyalty->id,
                'transaction_type' => 'earned_purchase',
                'points' => 50,
                'description' => 'Puntos ganados por servicio contratado',
                'created_at' => Carbon::now()->subDays(15),
            ]);

            $createdCustomers[] = $customer;
            $fakerIdx++;
        }

        // Catálogo de Recompensas EcoPuntos (5 recompensas)
        $rewardsData = [
            ['name' => 'Fumigación orgánica preventiva gratis', 'cost' => 120, 'srv' => $services->where('category_id', 3)->first()?->id],
            ['name' => 'Bolsa de abono orgánico y humus (25 lbs)', 'cost' => 80, 'srv' => null],
            ['name' => 'Planta de interior Monstera en maceta', 'cost' => 180, 'srv' => null],
            ['name' => 'Poda estética de arbustos complementaria', 'cost' => 150, 'srv' => $services->where('category_id', 2)->first()?->id],
            ['name' => 'Diagnóstico fitosanitario y análisis de suelo', 'cost' => 90, 'srv' => null],
        ];

        $createdRewards = [];
        foreach ($rewardsData as $idx => $rw) {
            $createdRewards[] = LoyaltyReward::firstOrCreate(
                ['reward_code' => sprintf('REW-%03d', $idx + 1)],
                [
                    'reward_name' => $rw['name'],
                    'description' => $rw['name'],
                    'points_cost' => $rw['cost'],
                    'service_id' => $rw['srv'],
                    'active' => true,
                    'valid_from' => '2026-01-01',
                    'valid_until' => '2026-12-31',
                ]
            );
        }

        // Registrar canjes de recompensa para los primeros 10 clientes
        for ($i = 0; $i < 10; $i++) {
            $cust = $createdCustomers[$i];
            LoyaltyRedemption::create([
                'customer_id' => $cust->id,
                'reward_id' => $createdRewards[$i % count($createdRewards)]->id,
                'employee_id' => $receptionists->random()->id,
                'points_spent' => 100,
                'status' => 'fulfilled',
                'requested_at' => Carbon::now()->subDays(12 + $i),
                'approved_at' => Carbon::now()->subDays(11 + $i),
                'fulfilled_at' => Carbon::now()->subDays(10 + $i),
            ]);
        }

        // Crear 60 Cotizaciones con Ítems, Facturas DTE y Pagos
        for ($q = 1; $q <= 60; $q++) {
            $customer = $createdCustomers[($q - 1) % count($createdCustomers)];
            $assignedPlan = $plans[($q - 1) % $plans->count()];
            $tech = $technicians[($q - 1) % $technicians->count()];
            $recep = $receptionists[($q - 1) % $receptionists->count()];

            $srv1 = $services[($q * 2) % $services->count()];
            $plant1 = $plants[($q * 3) % $plants->count()];

            $item1Total = round(2 * $srv1->price, 2);
            $item2Total = round(3 * $plant1->price, 2);
            $basePrice = $item1Total + $item2Total;
            $discountPct = (float) $assignedPlan->discount_percentage;
            $discountedSubtotal = round($basePrice * (1 - ($discountPct / 100)), 2);
            $vat = round($discountedSubtotal * 0.13, 2);
            $total = $discountedSubtotal + $vat;
            $pointsEarned = (int) floor($discountedSubtotal * 1.0);

            $status = ($q % 3 === 0) ? 'pending' : 'paid';

            $quote = Quote::create([
                'customer_id' => $customer->id,
                'employee_id' => $recep->id,
                'applied_plan_id' => $assignedPlan->id,
                'quote_number' => sprintf('COT-%s%04d', Carbon::now()->format('Ymd'), $q),
                'base_price' => $basePrice,
                'discounted_subtotal' => $discountedSubtotal,
                'includes_vat' => false,
                'loyalty_points_used' => 0,
                'loyalty_points_earned' => $pointsEarned,
                'total' => $total,
                'status' => $status,
                'created_at' => Carbon::now()->subDays(60 - $q),
                'updated_at' => Carbon::now()->subDays(60 - $q),
            ]);

            // Ítems de la cotización
            QuoteItem::create([
                'quote_id' => $quote->id,
                'item_type' => 'service',
                'service_id' => $srv1->id,
                'plant_id' => null,
                'quantity' => 2,
                'unit_price' => $srv1->price,
                'total' => $item1Total,
            ]);

            QuoteItem::create([
                'quote_id' => $quote->id,
                'item_type' => 'plant',
                'service_id' => null,
                'plant_id' => $plant1->id,
                'quantity' => 3,
                'unit_price' => $plant1->price,
                'total' => $item2Total,
            ]);

            // Si está pagada, crear Payment, Factura DTE y Movimiento Contable
            if ($status === 'paid') {
                $payment = Payment::create([
                    'quote_id' => $quote->id,
                    'employee_id' => $recep->id,
                    'payment_date' => Carbon::now()->subDays(60 - $q)->toDateTimeString(),
                    'amount_paid' => $total,
                    'payment_method' => ($q % 2 === 0) ? 'card' : 'transfer',
                ]);

                // Factura Electrónica DTE (El Salvador)
                $dte = DteInvoice::create([
                    'quote_id' => $quote->id,
                    'customer_id' => $customer->id,
                    'employee_id' => $recep->id,
                    'document_type' => $customer->nrc ? 'tax_credit' : 'final_consumer',
                    'environment' => 'test',
                    'subtotal' => $discountedSubtotal,
                    'vat_amount' => $vat,
                    'card_fee' => ($payment->payment_method === 'card') ? round($total * 0.035, 2) : 0.00,
                    'total' => $total,
                    'control_number' => sprintf('DTE-01-M001P001-%08d', $q),
                    'receipt_stamp' => sprintf('STAMP-%012d', $q),
                    'issue_date' => Carbon::now()->subDays(60 - $q)->toDateTimeString(),
                    'transaction_status' => 'paid',
                ]);

                // Movimiento Contable General
                Movement::create([
                    'movement_type' => 'sale',
                    'movementable_type' => Quote::class,
                    'movementable_id' => $quote->id,
                    'document_type' => $customer->nrc ? 'credito_fiscal' : 'factura_electronica',
                    'document_number' => sprintf('DTE-01-%08d', $q),
                    'entity_name' => $customer->legal_name,
                    'entity_tax_id' => $customer->tax_id,
                    'movement_date' => Carbon::now()->subDays(60 - $q)->toDateString(),
                    'amount' => $discountedSubtotal,
                    'vat_amount' => $vat,
                    'total' => $total,
                    'status' => 'completed',
                    'notes' => 'Liquidación confirmada vía '.$payment->payment_method,
                    'employee_id' => $recep->id,
                ]);

                // Libro Mayor / Ledger
                LedgerEntry::create([
                    'entry_type' => 'sale',
                    'invoice_id' => $dte->id,
                    'amount' => $discountedSubtotal,
                    'vat_amount' => $vat,
                    'total' => $total,
                    'created_at' => Carbon::now()->subDays(60 - $q),
                ]);
            }
        }

        // Crear 55 Inspecciones Técnicas de Campo
        for ($ins = 1; $ins <= 55; $ins++) {
            $customer = $createdCustomers[($ins - 1) % count($createdCustomers)];
            $tech = $technicians[($ins - 1) % $technicians->count()];
            $width = 10 + ($ins % 15);
            $length = 15 + ($ins % 20);
            $area = round($width * $length, 2);

            $inspection = Inspection::create([
                'customer_id' => $customer->id,
                'employee_id' => $tech->id,
                'inspection_date' => Carbon::now()->subDays(55 - $ins)->toDateString(),
                'findings' => 'Inspección técnica de rutina. Se detecta follaje seco en arbustos y necesidad de resembrado de césped en sector norte.',
                'terrain_width' => $width,
                'terrain_length' => $length,
                'total_area' => $area,
                'area_unit' => 'm2',
                'terrain_notes' => 'Topografía regular con pendiente ligera (3%). Buen drenaje pluvial.',
                'status' => ($ins % 4 === 0) ? 'draft' : 'submitted',
                'created_at' => Carbon::now()->subDays(55 - $ins),
            ]);

            // Servicios sugeridos
            $inspection->services()->attach([
                $services[($ins % $services->count())]->id => [
                    'suggested_quantity' => 1,
                    'notes' => 'Prioridad alta de atención.',
                ],
                $services[(($ins + 3) % $services->count())]->id => [
                    'suggested_quantity' => 2,
                    'notes' => 'Complementario tras poda principal.',
                ],
            ]);

            // Foto de referencia
            InspectionPhoto::create([
                'inspection_id' => $inspection->id,
                'image_path' => 'inspections/evidencia_campo_'.str_pad((string) $ins, 3, '0', STR_PAD_LEFT).'.jpg',
                'caption' => 'Área de jardín frontal evaluada por técnico de campo.',
            ]);
        }

        // Crear 60 Citas en Calendario y Agenda (Appointments)
        for ($ap = 1; $ap <= 60; $ap++) {
            $customer = $createdCustomers[($ap - 1) % count($createdCustomers)];
            $tech = $technicians[($ap - 1) % $technicians->count()];
            $srv = $services[($ap % $services->count())];

            // Distribuir horarios entre mañana (08:00) y tarde (14:00) cumpliendo la regla de 5 horas
            $slotHour = ($ap % 2 === 0) ? '08:00:00' : '14:00:00';
            $appointmentDate = Carbon::now()->subDays(30)->addDays((int) floor($ap / 2));

            $appointment = Appointment::create([
                'customer_id' => $customer->id,
                'service_id' => $srv->id,
                'employee_id' => $tech->id,
                'appointment_date' => $appointmentDate->toDateString(),
                'appointment_time' => $slotHour,
                'status' => ($appointmentDate->isPast()) ? 'completed' : 'confirmed',
                'origin' => 'manual',
                'notes' => 'Servicio técnico programado: '.$srv->name,
                'created_at' => Carbon::now()->subDays(35),
            ]);

            // Consumo de mantenimiento para citas completadas de clientes con suscripción
            if ($appointment->status === 'completed' && $ap <= 20) {
                $sub = Subscription::where('customer_id', $customer->id)->first();
                $custWallet = Wallet::where('customer_id', $customer->id)->first();
                if ($sub && $custWallet) {
                    $deductTx = WalletTransaction::create([
                        'wallet_id' => $custWallet->id,
                        'transaction_type' => 'maintenance_deduction',
                        'amount' => 25.00,
                        'payment_method' => 'wallet',
                        'is_annual_payment' => false,
                    ]);

                    SubscriptionMaintenanceConsumption::create([
                        'subscription_id' => $sub->id,
                        'appointment_id' => $appointment->id,
                        'employee_id' => $tech->id,
                        'maintenance_cost' => 25.00,
                        'wallet_transaction_id' => $deductTx->id,
                        'reversed_at' => null,
                    ]);
                }
            }
        }

        // Bitácora de Auditoría (AuditLog - 50 registros)
        for ($al = 1; $al <= 50; $al++) {
            $actions = ['insert', 'update', 'delete', 'restore', 'denied'];
            $tables = ['quotes', 'customers', 'employees', 'plants', 'services', 'appointments'];
            $act = $actions[$al % count($actions)];
            $tbl = $tables[$al % count($tables)];

            AuditLog::create([
                'employee_id' => $adminEmployee->id,
                'action' => $act,
                'affected_table' => $tbl,
                'affected_record_id' => ($al * 2) % 40 + 1,
                'old_data' => ($act !== 'insert') ? ['status' => 'pending', 'updated_by' => 'system'] : null,
                'new_data' => ($act !== 'delete') ? ['status' => 'active', 'authorized' => true] : null,
                'ip_address' => '190.86.'.(($al * 3) % 250).'.'.(($al * 7) % 250),
                'created_at' => Carbon::now()->subHours(50 - $al),
            ]);
        }
    }
}
