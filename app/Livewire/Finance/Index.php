<?php

namespace App\Livewire\Finance;

use App\Models\Department;
use App\Models\FinancialTransaction;
use App\Models\IncomeCategory;
use App\Models\Service;
use App\Models\Zone;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

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

    public function updatedSearch(): void
    {
        $this->resetPage();
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

        $attributes = [
            'income_category_id' => $validated['income_category_id'],
            'service_id' => $validated['service_id'],
            'department_id' => $validated['department_id'],
            'zone_id' => $validated['zone_id'],
            'recorded_by_user_id' => Auth::id(),
            'amount' => $validated['amount'],
            'transaction_date' => $validated['transaction_date'],
            'reference_number' => $validated['reference_number'] ?: null,
            'notes' => $validated['notes'] ?: null,
        ];

        $this->editingTransactionId
            ? FinancialTransaction::findOrFail($this->editingTransactionId)->update($attributes)
            : FinancialTransaction::create($attributes);

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
        FinancialTransaction::findOrFail($transactionId)->delete();

        if ($this->editingTransactionId === $transactionId) {
            $this->resetForm();
        }

        $this->dispatch('transaction-deleted');
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
            ->with(['incomeCategory', 'service', 'department', 'zone', 'recordedBy'])
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

        return view('livewire.finance.index', [
            'transactions' => $transactions,
            'incomeCategories' => IncomeCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'services' => Service::query()->latest('service_date')->limit(50)->get(),
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(),
            'zones' => Zone::query()->where('is_active', true)->orderBy('name')->get(),
            'monthTotal' => $monthTotal,
            'todayTotal' => $todayTotal,
        ]);
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

        $this->resetErrorBag();
    }
}
