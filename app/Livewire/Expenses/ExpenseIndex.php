<?php

namespace App\Livewire\Expenses;

use App\Models\Expense;
use App\Models\Location;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Expenses')]
class ExpenseIndex extends Component
{
    use WithPagination;

    public $search = '';

    public $filterCategory = '';

    public $filterDateFrom = '';

    public $filterDateTo = '';

    // Form fields
    public $showModal = false;

    public $editMode = false;

    public $expenseId;

    public $category = '';

    public $amount = '';

    public $payment_method = 'CASH';

    public $description = '';

    public $expense_date;

    public $location_id;

    // Delete confirmation
    public $showDeleteModal = false;

    public $deleteId;

    protected function rules(): array
    {
        return [
            'category' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:CASH,TRANSFER,CARD',
            'description' => $this->category === 'other' ? 'required|string|max:500' : 'nullable|string|max:500',
            'expense_date' => 'required|date',
            'location_id' => 'nullable|exists:locations,id',
        ];
    }

    public function mount()
    {
        $this->expense_date = now()->format('Y-m-d');
        $this->filterDateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->filterDateTo = now()->format('Y-m-d');

        $user = Auth::user();
        if ($user->location_id) {
            $this->location_id = $user->location_id;
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterCategory()
    {
        $this->resetPage();
    }

    public function updatedFilterDateFrom()
    {
        $this->resetPage();
    }

    public function updatedFilterDateTo()
    {
        $this->resetPage();
    }

    public function create()
    {
        $this->reset(['expenseId', 'category', 'amount', 'payment_method', 'description', 'editMode']);
        $this->payment_method = 'CASH';
        $this->expense_date = now()->format('Y-m-d');

        $user = Auth::user();
        $this->location_id = $user->location_id;

        $this->showModal = true;
    }

    public function edit($id)
    {
        $expense = Expense::findOrFail($id);

        // Only allow editing own expenses or admin can edit all
        $user = Auth::user();
        if ($user->role !== 'admin' && $expense->user_id !== $user->id) {
            session()->flash('error', 'You can only edit your own expenses.');

            return;
        }

        $this->expenseId = $expense->id;
        $this->category = $expense->category;
        $this->amount = $expense->amount;
        $this->payment_method = $expense->payment_method ?? 'CASH';
        $this->description = $expense->description;
        $this->expense_date = $expense->expense_date->format('Y-m-d');
        $this->location_id = $expense->location_id;
        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'category' => $this->category,
            'amount' => $this->amount,
            'payment_method' => $this->payment_method,
            'description' => $this->description,
            'expense_date' => $this->expense_date,
            'location_id' => $this->location_id ?: null,
        ];

        if ($this->editMode) {
            $expense = Expense::findOrFail($this->expenseId);

            // Only allow editing own expenses or admin can edit all
            $user = Auth::user();
            if ($user->role !== 'admin' && $expense->user_id !== $user->id) {
                session()->flash('error', 'You can only edit your own expenses.');

                return;
            }

            $expense->update($data);
            session()->flash('message', 'Expense updated successfully.');
        } else {
            $data['user_id'] = Auth::id();
            Expense::create($data);
            session()->flash('message', 'Expense recorded successfully.');
        }

        $this->showModal = false;
        $this->reset(['expenseId', 'category', 'amount', 'payment_method', 'description', 'editMode']);
    }

    public function confirmDelete($id)
    {
        $this->deleteId = $id;
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        $expense = Expense::findOrFail($this->deleteId);

        // Only allow deleting own expenses or admin can delete all
        $user = Auth::user();
        if ($user->role !== 'admin' && $expense->user_id !== $user->id) {
            session()->flash('error', 'You can only delete your own expenses.');
            $this->showDeleteModal = false;

            return;
        }

        $expense->delete();

        $this->showDeleteModal = false;
        $this->deleteId = null;
        session()->flash('message', 'Expense deleted successfully.');
    }

    public function render()
    {
        $user = Auth::user();

        $query = Expense::with(['user', 'location'])
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('description', 'like', "%{$this->search}%")
                        ->orWhereHas('user', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->filterCategory, fn ($q) => $q->where('category', $this->filterCategory))
            ->when($this->filterDateFrom, fn ($q) => $q->whereDate('expense_date', '>=', $this->filterDateFrom))
            ->when($this->filterDateTo, fn ($q) => $q->whereDate('expense_date', '<=', $this->filterDateTo));

        // Cashiers only see their own expenses, admins see all
        if ($user->role !== 'admin') {
            $query->where('user_id', $user->id);
        }

        $expenses = $query->orderBy('expense_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Calculate totals for the filtered period
        $totalQuery = Expense::when($this->filterCategory, fn ($q) => $q->where('category', $this->filterCategory))
            ->when($this->filterDateFrom, fn ($q) => $q->whereDate('expense_date', '>=', $this->filterDateFrom))
            ->when($this->filterDateTo, fn ($q) => $q->whereDate('expense_date', '<=', $this->filterDateTo));

        if ($user->role !== 'admin') {
            $totalQuery->where('user_id', $user->id);
        }

        $totalExpenses = $totalQuery->sum('amount');

        $locations = Location::where('is_active', true)->get();

        return view('livewire.expenses.expense-index', [
            'expenses' => $expenses,
            'categories' => Expense::CATEGORIES,
            'locations' => $locations,
            'totalExpenses' => $totalExpenses,
        ]);
    }
}
