<?php

namespace App\Livewire\Sales;

use App\Models\Sale;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Sales History')]
class SaleIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $startDate = '';
    public $endDate = '';
    public $paymentMethodFilter = '';
    public $showReceiptModal = false;
    public $selectedSale = null;

    public function mount()
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    public function applyFilters($filters)
    {
        $this->search = $filters['search'];
        $this->startDate = $filters['startDate'];
        $this->endDate = $filters['endDate'];
        $this->paymentMethodFilter = $filters['paymentMethod'];
        $this->resetPage();
    }

    public function showReceipt($saleId)
    {
        $this->selectedSale = Sale::with(['user', 'location', 'items.product', 'attendant'])->find($saleId);
        $this->showReceiptModal = true;
    }

    public function closeReceipt()
    {
        $this->showReceiptModal = false;
        $this->selectedSale = null;
    }

    public function render()
    {
        $user = Auth::user();
        $isAdmin = $user->role === 'admin';

        $sales = Sale::with(['user', 'location', 'items.product', 'attendant'])
            ->when(!$isAdmin, fn($q) => $q->where('user_id', Auth::id()))
            ->when($this->search, function ($q) {
                $q->where('sale_number', 'like', "%{$this->search}%");
            })
            ->when($this->startDate, fn($q) => $q->whereDate('sale_date', '>=', $this->startDate))
            ->when($this->endDate, fn($q) => $q->whereDate('sale_date', '<=', $this->endDate))
            ->when($this->paymentMethodFilter, fn($q) => $q->where('payment_method', $this->paymentMethodFilter))
            ->orderBy('sale_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('livewire.sales.sale-index', [
            'sales' => $sales,
        ]);
    }
}
