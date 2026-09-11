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

    public function search(array $filters): Collection
    {
        return Customer::query()
            ->select([
                'id',
                'legal_name',
                'trade_name',
                'phone',
                'email',
                'address',
            ])
            ->when($filters['name'] ?? null, function ($query, string $name) {
                $name = mb_strtolower(trim($name));

                $query->where(function ($query) use ($name) {
                    $query->whereRaw('LOWER(legal_name) LIKE ?', ["%{$name}%"])
                        ->orWhereRaw('LOWER(trade_name) LIKE ?', ["%{$name}%"]);
                });
            })
            ->when($filters['phone'] ?? null, function ($query, string $phone) {
                $query->where('phone', 'like', "%{$phone}%");
            })
            ->where('status', 'active')
            ->orderBy('legal_name')
            ->limit(20)
            ->get();
    }
}