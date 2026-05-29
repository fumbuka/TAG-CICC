<?php

namespace App\Livewire\Finance;

use App\Models\Department;
use App\Models\FinancialTransaction;
use App\Models\IncomeCategory;
use App\Models\Member;
use App\Models\Pledge;
use App\Models\PledgePayment;
use App\Models\Service;
use App\Models\Zone;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public ?int $editingCategoryId = null;

    public string $category_name = '';

    public string $category_description = '';

    public bool $category_is_active = true;

    public ?int $editingTransactionId = null;

    public string $search = '';

    public ?int $income_category_id = null;

    public ?int $service_id = null;

    public ?int $department_id = null;

    public ?int $zone_id = null;

    public string $amount = '';

    public string $transaction_date = '';

    public string $reference_number = '';

    public string $notes = '';

    public ?int $editingPledgeId = null;

    public ?int $pledge_member_id = null;

    public string $donor_name = '';

    public ?int $pledge_income_category_id = null;

    public ?int $pledge_service_id = null;

    public ?int $pledge_department_id = null;

    public ?int $pledge_zone_id = null;

    public string $pledged_amount = '';

    public string $pledged_at = '';

    public string $due_date = '';

    public string $pledge_status = 'active';

    public string $pledge_notes = '';

    public ?int $payment_pledge_id = null;

    public string $payment_amount = '';

    public string $payment_date = '';

    public string $payment_reference_number = '';

    public string $payment_notes = '';

    public function mount(): void
    {
        $this->transaction_date = now()->toDateString();
        $this->pledged_at = now()->toDateString();
        $this->payment_date = now()->toDateString();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function saveCategory(): void
    {
        $validated = $this->validate([
            'category_name' => ['required', 'string', 'max:255', Rule::unique('income_categories', 'name')->ignore($this->editingCategoryId)],
            'category_description' => ['nullable', 'string', 'max:1000'],
            'category_is_active' => ['boolean'],
        ]);

        $attributes = [
            'name' => $validated['category_name'],
            'slug' => Str::slug($validated['category_name']),
            'description' => $validated['category_description'] ?: null,
            'is_active' => $validated['category_is_active'],
        ];

        $wasEditing = $this->editingCategoryId !== null;

        $wasEditing
            ? IncomeCategory::findOrFail($this->editingCategoryId)->update($attributes)
            : IncomeCategory::create($attributes);

        $this->resetCategoryForm();

        $this->dispatch($wasEditing ? 'category-updated' : 'category-created');
    }

    public function editCategory(int $categoryId): void
    {
        $category = IncomeCategory::findOrFail($categoryId);

        $this->editingCategoryId = $category->id;
        $this->category_name = $category->name;
        $this->category_description = $category->description ?? '';
        $this->category_is_active = $category->is_active;
    }

    public function cancelCategoryEdit(): void
    {
        $this->resetCategoryForm();
    }

    public function deleteCategory(int $categoryId): void
    {
        $category = IncomeCategory::withCount(['financialTransactions', 'pledges'])->findOrFail($categoryId);

        if ($category->financial_transactions_count > 0 || $category->pledges_count > 0) {
            $this->addError('category_action', __('messages.income_category_delete_blocked'));

            return;
        }

        $category->delete();

        $this->dispatch('category-deleted');
    }

    public function toggleCategoryActive(int $categoryId): void
    {
        $category = IncomeCategory::findOrFail($categoryId);

        $category->update([
            'is_active' => ! $category->is_active,
        ]);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'income_category_id' => ['required', 'integer', Rule::exists('income_categories', 'id')],
            'service_id' => ['nullable', 'integer', Rule::exists('services', 'id')],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'zone_id' => ['nullable', 'integer', Rule::exists('zones', 'id')],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $wasEditing = $this->editingTransactionId !== null;
        $transaction = $wasEditing ? FinancialTransaction::with('pledgePayment.pledge')->findOrFail($this->editingTransactionId) : null;

        if ($transaction?->pledgePayment) {
            $pledge = $transaction->pledgePayment->pledge;
            $otherPaymentsTotal = (float) $pledge->payments()
                ->where('id', '!=', $transaction->pledgePayment->id)
                ->sum('amount');

            if ((float) $validated['amount'] > max((float) $pledge->pledged_amount - $otherPaymentsTotal, 0)) {
                $this->addError('amount', __('messages.pledge_payment_exceeds_balance'));

                return;
            }

            $validated['income_category_id'] = $pledge->income_category_id;
            $validated['service_id'] = $pledge->service_id;
            $validated['department_id'] = $pledge->department_id;
            $validated['zone_id'] = $pledge->zone_id;
        }

        $attributes = [
            'income_category_id' => $validated['income_category_id'],
            'service_id' => $validated['service_id'],
            'department_id' => $validated['department_id'],
            'zone_id' => $validated['zone_id'],
            'recorded_by_user_id' => Auth::id(),
            'pledge_id' => $transaction?->pledge_id,
            'amount' => $validated['amount'],
            'transaction_date' => $validated['transaction_date'],
            'reference_number' => $validated['reference_number'] ?: null,
            'notes' => $validated['notes'] ?: null,
        ];

        if ($transaction) {
            $transaction->update($attributes);

            if ($transaction->pledgePayment) {
                $transaction->pledgePayment->update([
                    'amount' => $validated['amount'],
                    'payment_date' => $validated['transaction_date'],
                    'reference_number' => $validated['reference_number'] ?: null,
                    'notes' => $validated['notes'] ?: null,
                ]);

                $this->refreshPledgeStatus($transaction->pledgePayment->pledge);
            }
        } else {
            FinancialTransaction::create($attributes);
        }

        $this->resetForm();

        $this->dispatch($wasEditing ? 'transaction-updated' : 'transaction-created');
    }

    public function edit(int $transactionId): void
    {
        $transaction = FinancialTransaction::findOrFail($transactionId);

        $this->editingTransactionId = $transaction->id;
        $this->income_category_id = $transaction->income_category_id;
        $this->service_id = $transaction->service_id;
        $this->department_id = $transaction->department_id;
        $this->zone_id = $transaction->zone_id;
        $this->amount = (string) $transaction->amount;
        $this->transaction_date = $transaction->transaction_date?->toDateString() ?? '';
        $this->reference_number = $transaction->reference_number ?? '';
        $this->notes = $transaction->notes ?? '';
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function delete(int $transactionId): void
    {
        $transaction = FinancialTransaction::with('pledgePayment.pledge')->findOrFail($transactionId);
        $pledge = $transaction->pledgePayment?->pledge;

        $transaction->pledgePayment?->delete();
        $transaction->delete();

        if ($pledge) {
            $this->refreshPledgeStatus($pledge);
        }

        if ($this->editingTransactionId === $transactionId) {
            $this->resetForm();
        }

        $this->dispatch('transaction-deleted');
    }

    public function savePledge(): void
    {
        $validated = $this->validate([
            'pledge_member_id' => ['nullable', 'integer', Rule::exists('members', 'id')],
            'donor_name' => ['nullable', 'string', 'max:255'],
            'pledge_income_category_id' => ['required', 'integer', Rule::exists('income_categories', 'id')],
            'pledge_service_id' => ['nullable', 'integer', Rule::exists('services', 'id')],
            'pledge_department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'pledge_zone_id' => ['nullable', 'integer', Rule::exists('zones', 'id')],
            'pledged_amount' => ['required', 'numeric', 'min:0.01'],
            'pledged_at' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:pledged_at'],
            'pledge_status' => ['required', Rule::in(['active', 'completed', 'cancelled'])],
            'pledge_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        if (! $validated['pledge_member_id'] && ! $validated['donor_name']) {
            $this->addError('donor_name', __('messages.pledge_donor_required'));

            return;
        }

        $attributes = [
            'member_id' => $validated['pledge_member_id'],
            'income_category_id' => $validated['pledge_income_category_id'],
            'service_id' => $validated['pledge_service_id'],
            'department_id' => $validated['pledge_department_id'],
            'zone_id' => $validated['pledge_zone_id'],
            'recorded_by_user_id' => Auth::id(),
            'donor_name' => $validated['donor_name'] ?: null,
            'pledged_amount' => $validated['pledged_amount'],
            'pledged_at' => $validated['pledged_at'],
            'due_date' => $validated['due_date'] ?: null,
            'status' => $validated['pledge_status'],
            'notes' => $validated['pledge_notes'] ?: null,
        ];

        $wasEditing = $this->editingPledgeId !== null;

        if ($wasEditing) {
            $pledge = Pledge::withSum('payments', 'amount')->findOrFail($this->editingPledgeId);

            if ((float) $validated['pledged_amount'] < (float) ($pledge->payments_sum_amount ?? 0)) {
                $this->addError('pledged_amount', __('messages.pledge_amount_below_paid'));

                return;
            }

            $pledge->update($attributes);
        } else {
            $pledge = Pledge::create($attributes);
        }

        if ($pledge->status !== 'cancelled') {
            $this->refreshPledgeStatus($pledge);
        }

        $this->resetPledgeForm();

        $this->dispatch($wasEditing ? 'pledge-updated' : 'pledge-created');
    }

    public function editPledge(int $pledgeId): void
    {
        $pledge = Pledge::findOrFail($pledgeId);

        $this->editingPledgeId = $pledge->id;
        $this->pledge_member_id = $pledge->member_id;
        $this->donor_name = $pledge->donor_name ?? '';
        $this->pledge_income_category_id = $pledge->income_category_id;
        $this->pledge_service_id = $pledge->service_id;
        $this->pledge_department_id = $pledge->department_id;
        $this->pledge_zone_id = $pledge->zone_id;
        $this->pledged_amount = (string) $pledge->pledged_amount;
        $this->pledged_at = $pledge->pledged_at?->toDateString() ?? '';
        $this->due_date = $pledge->due_date?->toDateString() ?? '';
        $this->pledge_status = $pledge->status;
        $this->pledge_notes = $pledge->notes ?? '';
    }

    public function cancelPledgeEdit(): void
    {
        $this->resetPledgeForm();
    }

    public function deletePledge(int $pledgeId): void
    {
        $pledge = Pledge::withCount('payments')->findOrFail($pledgeId);

        if ($pledge->payments_count > 0) {
            $this->addError('pledge_action', __('messages.pledge_delete_blocked'));

            return;
        }

        $pledge->delete();

        $this->dispatch('pledge-deleted');
    }

    public function recordPledgePayment(): void
    {
        $validated = $this->validate([
            'payment_pledge_id' => ['required', 'integer', Rule::exists('pledges', 'id')],
            'payment_amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_reference_number' => ['nullable', 'string', 'max:255'],
            'payment_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $pledge = Pledge::with('incomeCategory')->findOrFail($validated['payment_pledge_id']);

        if ($pledge->status === 'cancelled') {
            $this->addError('payment_pledge_id', __('messages.pledge_cancelled_payment_blocked'));

            return;
        }

        if ((float) $validated['payment_amount'] > $pledge->balanceAmount()) {
            $this->addError('payment_amount', __('messages.pledge_payment_exceeds_balance'));

            return;
        }

        DB::transaction(function () use ($pledge, $validated): void {
            $transaction = FinancialTransaction::create([
                'income_category_id' => $pledge->income_category_id,
                'service_id' => $pledge->service_id,
                'department_id' => $pledge->department_id,
                'zone_id' => $pledge->zone_id,
                'recorded_by_user_id' => Auth::id(),
                'pledge_id' => $pledge->id,
                'amount' => $validated['payment_amount'],
                'transaction_date' => $validated['payment_date'],
                'reference_number' => $validated['payment_reference_number'] ?: null,
                'notes' => $validated['payment_notes'] ?: __('messages.pledge_payment_transaction_note'),
            ]);

            PledgePayment::create([
                'pledge_id' => $pledge->id,
                'financial_transaction_id' => $transaction->id,
                'recorded_by_user_id' => Auth::id(),
                'amount' => $validated['payment_amount'],
                'payment_date' => $validated['payment_date'],
                'reference_number' => $validated['payment_reference_number'] ?: null,
                'notes' => $validated['payment_notes'] ?: null,
            ]);

            $this->refreshPledgeStatus($pledge);
        });

        $this->resetPaymentForm();

        $this->dispatch('pledge-payment-created');
    }

    public function deletePledgePayment(int $paymentId): void
    {
        $payment = PledgePayment::with(['financialTransaction', 'pledge'])->findOrFail($paymentId);
        $pledge = $payment->pledge;

        $payment->financialTransaction?->delete();
        $payment->delete();

        $this->refreshPledgeStatus($pledge);

        $this->dispatch('pledge-payment-deleted');
    }

    public function render(): View
    {
        $transactionsQuery = FinancialTransaction::query();

        $monthTotal = (clone $transactionsQuery)
            ->whereBetween('transaction_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->sum('amount');

        $todayTotal = (clone $transactionsQuery)
            ->whereDate('transaction_date', now()->toDateString())
            ->sum('amount');

        $transactions = $transactionsQuery
            ->with(['incomeCategory', 'service', 'department', 'zone', 'recordedBy', 'pledge'])
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query
                        ->where('reference_number', 'like', "%{$this->search}%")
                        ->orWhere('notes', 'like', "%{$this->search}%")
                        ->orWhereDate('transaction_date', $this->search)
                        ->orWhereHas('incomeCategory', fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
                        ->orWhereHas('service', fn ($query) => $query->where('title', 'like', "%{$this->search}%"));
                });
            })
            ->latest('transaction_date')
            ->latest()
            ->paginate(10);

        $totalPledged = Pledge::query()->where('status', '!=', 'cancelled')->sum('pledged_amount');
        $totalPledgePaid = PledgePayment::query()
            ->whereHas('pledge', fn ($query) => $query->where('status', '!=', 'cancelled'))
            ->sum('amount');

        return view('livewire.finance.index', [
            'transactions' => $transactions,
            'incomeCategories' => IncomeCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'allIncomeCategories' => IncomeCategory::query()
                ->withCount(['financialTransactions', 'pledges'])
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get(),
            'services' => Service::query()->latest('service_date')->limit(50)->get(),
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(),
            'zones' => Zone::query()->where('is_active', true)->orderBy('name')->get(),
            'members' => Member::query()->orderBy('first_name')->orderBy('last_name')->get(),
            'pledges' => Pledge::query()
                ->with(['member', 'incomeCategory', 'service', 'department', 'zone'])
                ->withSum('payments', 'amount')
                ->latest('pledged_at')
                ->latest()
                ->get(),
            'pledgePayments' => PledgePayment::query()
                ->with(['pledge.member', 'pledge.incomeCategory', 'financialTransaction'])
                ->latest('payment_date')
                ->latest()
                ->limit(10)
                ->get(),
            'totalPledged' => $totalPledged,
            'totalPledgePaid' => $totalPledgePaid,
            'totalPledgeBalance' => max((float) $totalPledged - (float) $totalPledgePaid, 0),
            'monthTotal' => $monthTotal,
            'todayTotal' => $todayTotal,
        ]);
    }

    private function resetCategoryForm(): void
    {
        $this->reset(['editingCategoryId', 'category_name', 'category_description']);
        $this->category_is_active = true;
        $this->resetErrorBag();
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingTransactionId',
            'income_category_id',
            'service_id',
            'department_id',
            'zone_id',
            'amount',
            'transaction_date',
            'reference_number',
            'notes',
        ]);

        $this->transaction_date = now()->toDateString();
        $this->resetErrorBag();
    }

    private function resetPledgeForm(): void
    {
        $this->reset([
            'editingPledgeId',
            'pledge_member_id',
            'donor_name',
            'pledge_income_category_id',
            'pledge_service_id',
            'pledge_department_id',
            'pledge_zone_id',
            'pledged_amount',
            'due_date',
            'pledge_notes',
        ]);
        $this->pledged_at = now()->toDateString();
        $this->pledge_status = 'active';
        $this->resetErrorBag();
    }

    private function resetPaymentForm(): void
    {
        $this->reset([
            'payment_pledge_id',
            'payment_amount',
            'payment_reference_number',
            'payment_notes',
        ]);
        $this->payment_date = now()->toDateString();
        $this->resetErrorBag();
    }

    private function refreshPledgeStatus(Pledge $pledge): void
    {
        if ($pledge->status === 'cancelled') {
            return;
        }

        $pledge->refresh();

        $pledge->update([
            'status' => $pledge->paidAmount() >= (float) $pledge->pledged_amount ? 'completed' : 'active',
        ]);
    }
}
