<?php

namespace App\Livewire\Products;

use App\Models\Product;
use App\Models\Category;
use App\Traits\WithToastNotifications;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

#[Layout('components.layouts.app')]
#[Title('Products')]
class ProductIndex extends Component
{
    use WithPagination, WithFileUploads, WithToastNotifications;

    public $search = '';
    public $stockFilter = '';
    public $showModal = false;
    public $showDeleteModal = false;
    public $editMode = false;
    public $productId;
    public $deleteId = null;
    
    protected $queryString = [
        'search' => ['except' => ''],
        'stockFilter' => ['except' => '', 'as' => 'stock'],
    ];
    
    // Form fields
    public $name = '';
    public $sku = '';
    public $description = '';
    public $barcode = '';
    public $category_id = '';
    public $cost_price = '';
    public $selling_price_per_item = '';
    public $low_stock_threshold = 10;
    public $is_active = true;
    public $image;
    public $existingImage = '';
    public $unit_type = 'piece';
    public $qty_per_unit = 1;
    public $number_of_units = 1;

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:50|unique:products,sku' . ($this->editMode ? ',' . $this->productId : ''),
            'description' => 'nullable|string',
            'barcode' => 'nullable|string|max:50|unique:products,barcode' . ($this->editMode ? ',' . $this->productId : ''),
            'category_id' => 'nullable|exists:categories,id',
            'cost_price' => 'required|numeric|min:0',
            'selling_price_per_item' => 'required|numeric|min:0',
            'low_stock_threshold' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'image' => 'nullable|image|max:2048', // Max 2MB
            'unit_type' => 'required|in:piece,pack,carton,box,dozen',
            'qty_per_unit' => 'required|integer|min:1',
            'number_of_units' => 'required|integer|min:1',
        ];
    }

    public function applyFilters($filters)
    {
        $this->search = $filters['search'];
        $this->stockFilter = $filters['stockFilter'];
        $this->resetPage();
    }

    public function create()
    {
        $this->reset(['name', 'sku', 'description', 'barcode', 'category_id', 'cost_price', 'selling_price_per_item', 'low_stock_threshold', 'is_active', 'productId', 'editMode', 'image', 'existingImage', 'unit_type', 'qty_per_unit', 'number_of_units']);
        $this->is_active = true;
        $this->low_stock_threshold = 10;
        $this->unit_type = 'piece';
        $this->qty_per_unit = 1;
        $this->number_of_units = 1;
        $this->showModal = true;
    }

    public function edit(Product $product)
    {
        $this->productId = $product->id;
        $this->name = $product->name;
        $this->sku = $product->sku;
        $this->description = $product->description;
        $this->barcode = $product->barcode;
        $this->category_id = $product->category_id;
        $this->cost_price = $product->cost_price;
        // Calculate selling price per item from stored total selling price
        $qtyPerUnit = $product->qty_per_unit ?? 1;
        $this->selling_price_per_item = $qtyPerUnit > 0 ? $product->selling_price / $qtyPerUnit : $product->selling_price;
        $this->low_stock_threshold = $product->low_stock_threshold;
        $this->is_active = $product->is_active;
        $this->existingImage = $product->image;
        $this->image = null;
        $this->unit_type = $product->unit_type ?? 'piece';
        $this->qty_per_unit = $product->qty_per_unit ?? 1;
        $this->number_of_units = 1;
        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        // Calculate total selling price from per-item price
        $totalSellingPrice = $this->selling_price_per_item * $this->qty_per_unit;

        // Calculate cost price per single unit (pack/carton)
        $numberOfUnits = max(1, (int) $this->number_of_units);
        $costPricePerUnit = $this->cost_price / $numberOfUnits;

        $data = [
            'name' => $this->name,
            'sku' => $this->sku ?: null,
            'description' => $this->description ?: null,
            'barcode' => $this->barcode ?: null,
            'category_id' => $this->category_id ?: null,
            'cost_price' => $costPricePerUnit,
            'selling_price' => $totalSellingPrice,
            'low_stock_threshold' => $this->low_stock_threshold,
            'is_active' => $this->is_active,
            'unit_type' => $this->unit_type,
            'qty_per_unit' => $this->qty_per_unit,
        ];

        // Handle image upload
        if ($this->image) {
            // Delete old image if exists
            if ($this->editMode && $this->existingImage) {
                Storage::disk('public')->delete($this->existingImage);
            }
            $data['image'] = $this->image->store('products', 'public');
        }

        if ($this->editMode) {
            Product::find($this->productId)->update($data);
            $this->success('Product updated successfully.');
        } else {
            Product::create($data);
            $this->success('Product created successfully.');
        }

        $this->showModal = false;
        $this->reset(['name', 'sku', 'description', 'barcode', 'category_id', 'cost_price', 'selling_price_per_item', 'low_stock_threshold', 'is_active', 'productId', 'editMode', 'image', 'existingImage', 'unit_type', 'qty_per_unit', 'number_of_units']);
    }

    public function confirmDelete($id)
    {
        $this->deleteId = $id;
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        $product = Product::find($this->deleteId);
        
        if ($product) {
            $product->delete();
            $this->success('Product deleted successfully.');
        }
        
        $this->showDeleteModal = false;
        $this->deleteId = null;
    }

    public function render()
    {
        $query = Product::query()
            ->with('categoryRelation')
            ->withSum('inventoryStocks', 'quantity')
            ->when($this->search, function ($q) {
                $q->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                      ->orWhere('sku', 'like', "%{$this->search}%")
                      ->orWhere('barcode', 'like', "%{$this->search}%");
                });
            })
            ->orderBy('name');

        // Apply low stock filter after fetching with withSum
        if ($this->stockFilter === 'low') {
            $query->where(function ($q) {
                $q->whereRaw('(SELECT COALESCE(SUM(quantity), 0) FROM inventory_stocks WHERE inventory_stocks.product_id = products.id) <= products.low_stock_threshold');
            });
        }

        $products = $query->paginate(10);

        $categories = Category::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('livewire.products.product-index', [
            'products' => $products,
            'categories' => $categories,
        ]);
    }
}
