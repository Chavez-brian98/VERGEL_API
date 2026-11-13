<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\LoyaltyPoint;
use App\Models\LoyaltyPointTransaction;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\Subscription;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PortfolioService
{
    /**
     * Obtiene el resumen consolidado de la cartera del cliente: datos
     * generales, plan actual e historial, saldos, EcoPuntos, deuda,
     * suscripciones, citas y actividad reciente.
     *
     * @throws ModelNotFoundException
     */
    public function getSummary(int|string $customerId): Customer
    {
        $customer = Customer::with([
            'wallet',
            'loyaltyPoint',
            'currentPlan',
            'planHistories' => fn ($query) => $query
                ->with('plan')
                ->orderByDesc('start_date')
                ->orderByDesc('id'),
        ])->findOrFail($customerId);

        $pendingQuotes = Quote::query()
            ->where('customer_id', $customer->id)
            ->where('status', 'pending')
            ->withSum('payments as paid_amount', 'amount_paid')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get(['id', 'quote_number', 'total', 'created_at']);

        $totalQuoted = (float) $pendingQuotes->sum('total');
        $totalPaid = (float) $pendingQuotes->sum(
            fn (Quote $quote) => (float) ($quote->paid_amount ?? 0)
        );

        $customer->setAttribute('portfolio_pending_quotes', $pendingQuotes);
        $customer->setAttribute('portfolio_pending_quotes_count', $pendingQuotes->count());
        $customer->setAttribute(
            'portfolio_total_pending_amount',
            max(0, round($totalQuoted - $totalPaid, 2))
        );

        $upcomingAppointments = Appointment::query()
            ->where('customer_id', $customer->id)
            ->whereIn('status', [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED])
            ->whereDate('appointment_date', '>=', now()->toDateString())
            ->with(['service:id,name,price', 'employee:id,full_name'])
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get();

        $customer->setAttribute('portfolio_upcoming_appointments_count', $upcomingAppointments->count());
        $customer->setAttribute('portfolio_next_appointment', $upcomingAppointments->first());
        $customer->setAttribute(
            'portfolio_last_completed_appointment_date',
            Appointment::query()
                ->where('customer_id', $customer->id)
                ->where('status', Appointment::STATUS_COMPLETED)
                ->max('appointment_date')
        );

        $subscriptions = $customer->subscriptions()->with('plan')->get();
        $activeSubscriptions = $subscriptions->where('status', 'active');
        $currentSubscription = $activeSubscriptions
            ->sortByDesc(fn (Subscription $subscription) => $subscription->payment_date?->timestamp ?? 0)
            ->first();

        $customer->setAttribute('portfolio_subscription_active_count', $activeSubscriptions->count());
        $customer->setAttribute('portfolio_current_subscription', $currentSubscription);

        $customer->setAttribute(
            'portfolio_recent_quotes',
            Quote::query()
                ->where('customer_id', $customer->id)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(5)
                ->get([
                    'id',
                    'quote_number',
                    'total',
                    'status',
                    'loyalty_points_used',
                    'loyalty_points_earned',
                    'created_at',
                ])
        );

        $customer->setAttribute(
            'portfolio_recent_payments',
            Payment::query()
                ->whereHas('quote', fn ($query) => $query->where('customer_id', $customer->id))
                ->with('quote:id,quote_number')
                ->orderByDesc('payment_date')
                ->orderByDesc('id')
                ->limit(5)
                ->get(['id', 'quote_id', 'amount_paid', 'payment_method', 'payment_date'])
        );

        return $customer;
    }

    /**
     * Obtiene los resúmenes básicos de cartera de todos los clientes de forma paginada.
     */
    public function getSummaries(int $limit = 15): LengthAwarePaginator
    {
        return $this->summaryQuery()
            ->orderByDesc('id')
            ->paginate($limit);
    }

    private function summaryQuery(): Builder
    {
        return Customer::query()
            ->select(['id', 'legal_name', 'nrc', 'current_plan_id'])
            ->with([
                'wallet:id,customer_id,cash_balance,reserved_subscription_balance',
                'loyaltyPoint:id,customer_id,points_balance',
                'currentPlan:id,plan_name',
            ]);
    }

    /**
     * Obtiene el historial unificado de movimientos de dinero y EcoPuntos,
     * ordenado de forma descendente por fecha y paginado.
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     *
     * @throws ModelNotFoundException
     */
    public function getMovements(
        int|string $customerId,
        string $type = 'all',
        int $page = 1,
        int $limit = 15
    ): LengthAwarePaginator {
        $customer = Customer::with(['wallet', 'loyaltyPoint'])->findOrFail($customerId);

        $movements = collect();

        if (in_array($type, ['all', 'wallet'], true) && $customer->wallet) {
            $movements = $movements->merge($this->walletMovements($customer->wallet));
        }

        if (in_array($type, ['all', 'loyalty'], true) && $customer->loyaltyPoint) {
            $movements = $movements->merge($this->loyaltyMovements($customer->loyaltyPoint));
        }

        $sorted = $movements
            ->sortByDesc(fn (array $movement) => $movement['createdAt'].'|'.str_pad((string) $movement['id'], 10, '0', STR_PAD_LEFT))
            ->values();

        return new LengthAwarePaginator(
            $sorted->forPage($page, $limit)->values(),
            $sorted->count(),
            $limit,
            $page,
            ['path' => Paginator::resolveCurrentPath()]
        );
    }

    /**
     * Obtiene los movimientos de todos los clientes de forma paginada.
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function getAllMovements(
        string $type = 'all',
        int $page = 1,
        int $limit = 15
    ): LengthAwarePaginator {
        $walletMovements = DB::table('wallet_transactions as wt')
            ->join('wallets as w', 'w.id', '=', 'wt.wallet_id')
            ->select([
                'wt.id',
                'w.customer_id',
                'wt.transaction_type',
                'wt.amount',
            ])
            ->selectRaw('NULL as points')
            ->selectRaw('NULL as description')
            ->addSelect([
                'wt.payment_method',
                'wt.created_at',
            ])
            ->selectRaw("'wallet' as source");

        $loyaltyMovements = DB::table('loyalty_points_transactions as lpt')
            ->join('loyalty_points as lp', 'lp.id', '=', 'lpt.loyalty_points_id')
            ->select([
                'lpt.id',
                'lp.customer_id',
                'lpt.transaction_type',
            ])
            ->selectRaw('NULL as amount')
            ->addSelect('lpt.points')
            ->addSelect('lpt.description')
            ->selectRaw('NULL as payment_method')
            ->addSelect('lpt.created_at')
            ->selectRaw("'loyalty' as source");

        if ($type === 'wallet') {
            $query = $walletMovements;
        } elseif ($type === 'loyalty') {
            $query = $loyaltyMovements;
        } else {
            $query = $walletMovements->unionAll($loyaltyMovements);
        }

        $paginator = DB::query()
            ->fromSub($query, 'movements')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->orderBy('source')
            ->paginate($limit, ['*'], 'page', $page);

        $paginator->setCollection(
            $paginator->getCollection()->map(fn (object $movement): array => [
                'id' => $movement->id,
                'customerId' => $movement->customer_id,
                'source' => $movement->source,
                'transactionType' => $movement->transaction_type,
                'amount' => $movement->amount !== null ? (float) $movement->amount : null,
                'points' => $movement->points !== null ? (int) $movement->points : null,
                'description' => $movement->source === 'wallet'
                    ? $this->walletDescription(new WalletTransaction([
                        'transaction_type' => $movement->transaction_type,
                    ]))
                    : $movement->description,
                'paymentMethod' => $movement->payment_method,
                'createdAt' => $this->toIso8601($movement->created_at),
            ])
        );

        return $paginator;
    }

    /**
     * Registra una transacción monetaria e impacta el saldo de la billetera.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    public function createWalletTransaction(int|string $customerId, array $data): WalletTransaction
    {
        $customer = Customer::findOrFail($customerId);

        return DB::transaction(function () use ($customer, $data) {
            $wallet = Wallet::query()
                ->where('customer_id', $customer->id)
                ->lockForUpdate()
                ->first();

            if (! $wallet) {
                $wallet = $customer->wallet()->create([
                    'cash_balance' => 0.00,
                    'reserved_subscription_balance' => 0.00,
                ]);
            }

            $transactionType = (string) $data['transaction_type'];
            $amount = round((float) $data['amount'], 2);

            $cashDelta = match ($transactionType) {
                'top_up' => $amount,
                'annual_fee', 'maintenance_deduction', 'reserve_funds' => -abs($amount),
                'adjustment' => $amount,
                'spend_reserved_funds' => 0.0,
            };

            $reservedDelta = match ($transactionType) {
                'reserve_funds' => abs($amount),
                'spend_reserved_funds' => -abs($amount),
                default => 0.0,
            };

            $newCashBalance = round((float) $wallet->cash_balance + $cashDelta, 2);
            $newReservedBalance = round((float) $wallet->reserved_subscription_balance + $reservedDelta, 2);

            if ($newCashBalance < 0 && ! config('portfolio.allow_negative_cash_balance', false)) {
                throw ValidationException::withMessages([
                    'amount' => ['El saldo de la billetera no puede quedar en negativo.'],
                ]);
            }

            if ($newReservedBalance < 0) {
                throw ValidationException::withMessages([
                    'amount' => ['El saldo reservado para suscripción no puede quedar en negativo.'],
                ]);
            }

            $transaction = $wallet->transactions()->create([
                'transaction_type' => $transactionType,
                'amount' => $amount,
                'payment_method' => $data['payment_method'] ?? null,
                'is_annual_payment' => $transactionType === 'annual_fee'
                    ? true
                    : (bool) ($data['is_annual_payment'] ?? false),
            ]);

            $wallet->update([
                'cash_balance' => $newCashBalance,
                'reserved_subscription_balance' => $newReservedBalance,
            ]);

            $this->logAudit('insert', 'wallet_transactions', $transaction->id, null, [
                'transaction' => $transaction->toArray(),
                'cash_balance_after' => $newCashBalance,
                'reserved_subscription_balance_after' => $newReservedBalance,
            ]);

            return $transaction->load('wallet');
        });
    }

    /**
     * Registra una transacción de EcoPuntos e impacta el balance de lealtad.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    public function createLoyaltyTransaction(int|string $customerId, array $data): LoyaltyPointTransaction
    {
        $customer = Customer::findOrFail($customerId);

        return DB::transaction(function () use ($customer, $data) {
            $loyalty = LoyaltyPoint::query()
                ->where('customer_id', $customer->id)
                ->lockForUpdate()
                ->first();

            if (! $loyalty) {
                $loyalty = $customer->loyaltyPoint()->create([
                    'points_balance' => 0,
                ]);
            }

            $transactionType = (string) $data['transaction_type'];
            $points = (int) $data['points'];

            $delta = $transactionType === 'redeemed' ? -$points : $points;
            $newBalance = (int) $loyalty->points_balance + $delta;

            if ($newBalance < 0) {
                throw ValidationException::withMessages([
                    'points' => ['El saldo de EcoPuntos no puede quedar en negativo.'],
                ]);
            }

            $transaction = $loyalty->transactions()->create([
                'quote_id' => $data['quote_id'] ?? null,
                'transaction_type' => $transactionType,
                'points' => $points,
                'description' => $data['description'] ?? null,
            ]);

            $loyalty->update(['points_balance' => $newBalance]);

            $this->logAudit('insert', 'loyalty_points_transactions', $transaction->id, null, [
                'transaction' => $transaction->toArray(),
                'points_balance_after' => $newBalance,
            ]);

            return $transaction->load('loyaltyPoints');
        });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function walletMovements(Wallet $wallet): Collection
    {
        return WalletTransaction::query()
            ->where('wallet_id', $wallet->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (WalletTransaction $transaction) => [
                'id' => $transaction->id,
                'customerId' => $wallet->customer_id,
                'source' => 'wallet',
                'transactionType' => $transaction->transaction_type,
                'amount' => (float) $transaction->amount,
                'points' => null,
                'description' => $this->walletDescription($transaction),
                'paymentMethod' => $transaction->payment_method,
                'createdAt' => $this->toIso8601($transaction->created_at),
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function loyaltyMovements(LoyaltyPoint $loyalty): Collection
    {
        return LoyaltyPointTransaction::query()
            ->where('loyalty_points_id', $loyalty->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (LoyaltyPointTransaction $transaction) => [
                'id' => $transaction->id,
                'customerId' => $loyalty->customer_id,
                'source' => 'loyalty',
                'transactionType' => $transaction->transaction_type,
                'amount' => null,
                'points' => (int) $transaction->points,
                'description' => $transaction->description,
                'paymentMethod' => null,
                'createdAt' => $this->toIso8601($transaction->created_at),
            ]);
    }

    private function walletDescription(WalletTransaction $transaction): string
    {
        return match ($transaction->transaction_type) {
            'top_up' => 'Recarga de saldo en efectivo',
            'annual_fee' => 'Cargo por cuota anual',
            'maintenance_deduction' => 'Deducci��n por mantenimiento',
            'adjustment' => 'Ajuste manual de saldo',
            'reserve_funds' => 'Reserva de saldo para suscripción',
            'spend_reserved_funds' => 'Consumo de saldo reservado de suscripción',
            default => 'Movimiento de billetera',
        };
    }

    private function toIso8601(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        return Carbon::parse($value)->toIso8601String();
    }

    /**
     * Registra un evento en la bitácora de auditoría.
     */
    private function logAudit(string $action, string $table, int $recordId, ?array $oldData, ?array $newData): void
    {
        $employeeId = request()->user()?->employee_id ?? Employee::first()?->id;

        if (! $employeeId) {
            return;
        }

        AuditLog::create([
            'employee_id' => $employeeId,
            'affected_table' => $table,
            'affected_record_id' => $recordId,
            'action' => $action,
            'old_data' => $oldData,
            'new_data' => $newData,
            'ip_address' => request()->ip(),
        ]);
    }
}
