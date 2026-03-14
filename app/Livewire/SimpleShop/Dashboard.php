<?php

namespace App\Livewire\SimpleShop;

use App\Models\DailyBalance;
use App\Models\Expense;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title("POS Shop")]
class Dashboard extends Component
{
    use WithPagination;

    // Balance form
    public $closingBalance = '';

    public $balanceNotes = '';

    public $selectedDate;

    // Expense form
    public $showExpenseModal = false;

    public $expenseCategory = '';

    public $expenseAmount = '';

    public $expenseDescription = '';

    public $expensePaymentMethod = 'CASH';

    public $expenseDate;

    public $editExpenseId;

    public $editExpenseMode = false;

    // Delete confirmation
    public $showDeleteModal = false;

    public $deleteExpenseId;

    public function mount(): void
    {
        $this->selectedDate = now()->format('Y-m-d');
        $this->expenseDate = now()->format('Y-m-d');
        $this->loadTodayBalance();
    }

    public function loadTodayBalance(): void
    {
        $user = Auth::user();
        $todayBalance = DailyBalance::forLocation($user->location_id)
            ->forDate($this->selectedDate)
            ->first();

        if ($todayBalance && $todayBalance->closing_balance !== null) {
            $this->closingBalance = $todayBalance->closing_balance;
            $this->balanceNotes = $todayBalance->notes ?? '';
        } else {
            $this->closingBalance = '';
            $this->balanceNotes = '';
        }
    }

    public function updatedSelectedDate(): void
    {
        $this->loadTodayBalance();
    }

    public function saveClosingBalance(): void
    {
        $this->validate([
            'closingBalance' => 'required|numeric|min:0',
            'balanceNotes' => 'nullable|string|max:500',
            'selectedDate' => 'required|date',
        ]);

        $user = Auth::user();
        $locationId = $user->location_id;

        // Get or create today's balance record
        $todayBalance = DailyBalance::firstOrCreate(
            [
                'location_id' => $locationId,
                'balance_date' => $this->selectedDate,
            ],
            [
                'user_id' => $user->id,
                'opening_balance' => $this->getOpeningBalance(),
            ]
        );

        $openingBalance = $todayBalance->opening_balance;
        $todayExpenses = Expense::where('location_id', $locationId)
            ->whereDate('expense_date', $this->selectedDate)
            ->sum('amount');

        $profit = (float) $this->closingBalance - $openingBalance - $todayExpenses;

        $todayBalance->update([
            'closing_balance' => $this->closingBalance,
            'profit' => $profit,
            'notes' => $this->balanceNotes,
        ]);

        // Auto-create next day's opening balance
        $nextDate = \Carbon\Carbon::parse($this->selectedDate)->addDay()->format('Y-m-d');
        DailyBalance::updateOrCreate(
            [
                'location_id' => $locationId,
                'balance_date' => $nextDate,
            ],
            [
                'user_id' => $user->id,
                'opening_balance' => $this->closingBalance,
            ]
        );

        session()->flash('message', 'Closing balance saved successfully.');
    }

    protected function getOpeningBalance(): float
    {
        $user = Auth::user();

        // Look for previous day's closing balance
        $previousBalance = DailyBalance::forLocation($user->location_id)
            ->where('balance_date', '<', $this->selectedDate)
            ->whereNotNull('closing_balance')
            ->orderBy('balance_date', 'desc')
            ->first();

        return $previousBalance ? (float) $previousBalance->closing_balance : 0;
    }

    public function createExpense(): void
    {
        $this->reset(['editExpenseId', 'expenseCategory', 'expenseAmount', 'expenseDescription', 'editExpenseMode']);
        $this->expensePaymentMethod = 'CASH';
        $this->expenseDate = $this->selectedDate;
        $this->showExpenseModal = true;
    }

    public function editExpense(int $id): void
    {
        $expense = Expense::findOrFail($id);
        $this->editExpenseId = $expense->id;
        $this->expenseCategory = $expense->category;
        $this->expenseAmount = $expense->amount;
        $this->expenseDescription = $expense->description;
        $this->expensePaymentMethod = $expense->payment_method ?? 'CASH';
        $this->expenseDate = $expense->expense_date->format('Y-m-d');
        $this->editExpenseMode = true;
        $this->showExpenseModal = true;
    }

    public function saveExpense(): void
    {
        $this->validate([
            'expenseCategory' => 'required|string',
            'expenseAmount' => 'required|numeric|min:0.01',
            'expenseDescription' => $this->expenseCategory === 'other' ? 'required|string|max:500' : 'nullable|string|max:500',
            'expensePaymentMethod' => 'required|in:CASH,TRANSFER,CARD',
            'expenseDate' => 'required|date',
        ]);

        $user = Auth::user();
        $data = [
            'category' => $this->expenseCategory,
            'amount' => $this->expenseAmount,
            'description' => $this->expenseDescription,
            'payment_method' => $this->expensePaymentMethod,
            'expense_date' => $this->expenseDate,
            'location_id' => $user->location_id,
        ];

        if ($this->editExpenseMode) {
            $expense = Expense::findOrFail($this->editExpenseId);
            $expense->update($data);
            session()->flash('message', 'Expense updated successfully.');
        } else {
            $data['user_id'] = $user->id;
            Expense::create($data);
            session()->flash('message', 'Expense recorded successfully.');
        }

        $this->showExpenseModal = false;
        $this->reset(['editExpenseId', 'expenseCategory', 'expenseAmount', 'expenseDescription', 'editExpenseMode']);
    }

    public function confirmDeleteExpense(int $id): void
    {
        $this->deleteExpenseId = $id;
        $this->showDeleteModal = true;
    }

    public function deleteExpense(): void
    {
        Expense::findOrFail($this->deleteExpenseId)->delete();
        $this->showDeleteModal = false;
        $this->deleteExpenseId = null;
        session()->flash('message', 'Expense deleted successfully.');
    }

    public function render()
    {
        $user = Auth::user();
        $locationId = $user->location_id;

        $todayBalance = DailyBalance::forLocation($locationId)
            ->forDate($this->selectedDate)
            ->first();

        $openingBalance = $todayBalance ? (float) $todayBalance->opening_balance : $this->getOpeningBalance();

        $todayExpenses = Expense::where('location_id', $locationId)
            ->whereDate('expense_date', $this->selectedDate)
            ->sum('amount');

        $closingBalanceValue = $todayBalance ? $todayBalance->closing_balance : null;
        $profit = $closingBalanceValue !== null
            ? (float) $closingBalanceValue - $openingBalance - $todayExpenses
            : null;

        // Recent balances (last 30 days)
        $recentBalances = DailyBalance::forLocation($locationId)
            ->whereNotNull('closing_balance')
            ->orderBy('balance_date', 'desc')
            ->take(30)
            ->get();

        // Today's expenses list
        $expenses = Expense::where('location_id', $locationId)
            ->whereDate('expense_date', $this->selectedDate)
            ->orderBy('created_at', 'desc')
            ->get();

        // Monthly summary
        $monthStart = \Carbon\Carbon::parse($this->selectedDate)->startOfMonth()->format('Y-m-d');
        $monthEnd = \Carbon\Carbon::parse($this->selectedDate)->endOfMonth()->format('Y-m-d');

        $monthExpenses = Expense::where('location_id', $locationId)
            ->whereBetween('expense_date', [$monthStart, $monthEnd])
            ->sum('amount');

        $monthProfit = DailyBalance::forLocation($locationId)
            ->whereBetween('balance_date', [$monthStart, $monthEnd])
            ->whereNotNull('profit')
            ->sum('profit');

        return view('livewire.simple-shop.dashboard', [
            'openingBalance' => $openingBalance,
            'todayExpenses' => $todayExpenses,
            'closingBalanceValue' => $closingBalanceValue,
            'profit' => $profit,
            'recentBalances' => $recentBalances,
            'expenses' => $expenses,
            'categories' => Expense::CATEGORIES,
            'monthExpenses' => $monthExpenses,
            'monthProfit' => $monthProfit,
        ]);
    }
}
