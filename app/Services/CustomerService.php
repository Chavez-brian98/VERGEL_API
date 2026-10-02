<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerService
{
    public function getAll(): Collection
    {
        return Customer::all();
    }

    public function create(array $data): Customer
    {
        if (!empty($data['nrc'])) {
            $existsNrc = Customer::where('nrc', $data['nrc'])->exists();
            if ($existsNrc) {
                throw ValidationException::withMessages([
                    'nrc' => ['El NRC ya se encuentra registrado.'],
                ]);
            }
        }

        return DB::transaction(function () use ($data) {
            $customer = Customer::create($data);

            $customer->wallet()->create([
                'cash_balance' => 0.00,
                'reserved_subscription_balance' => 0.00,
            ]);

            $customer->loyaltyPoint()->create([
                'points_balance' => 0,
            ]);

            return $customer;
        });
    }



    public function search(array $filters = []): Collection
    {
        // Limpia elementos nulos o cadenas vacías
        $filters = array_filter($filters, fn($value) => !is_null($value) && $value !== '');

        return Customer::query()
            ->select([
                'id',
                'legal_name',
                'trade_name',
                'phone',
                'email',
                'address',
            ])
            // Si hay filtros, solo busca los activos; si $filters está vacío, no aplica este filtro y trae todos
            ->when(!empty($filters), fn($query) => $query->where('status', 'active'))
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $search = mb_strtolower(trim($search));

                $query->where(function ($q) use ($search) {
                    $q->whereRaw('LOWER(legal_name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(trade_name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(id) LIKE ?', ["%{$search}%"])
                    ;
                });
            })
            ->when($filters['phone'] ?? null, function ($query, string $phone) {
                $query->where('phone', 'like', '%' . trim($phone) . '%');
            })
            ->when($filters['desde'] ?? null, fn($query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['hasta'] ?? null, fn($query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->orderBy('legal_name')
            ->when(!empty($filters), fn($query) => $query->limit(20))
            ->get();
    }
}