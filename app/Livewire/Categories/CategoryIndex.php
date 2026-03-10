<?php

namespace App\Livewire\Categories;

use App\Models\Category;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Categories')]
class CategoryIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $showModal = false;
    public $showDeleteModal = false;
    public $editMode = false;
    public $categoryId;
    public $deleteId = null;
    
    // Form fields
    public $name = '';
    public $description = '';
    public $is_active = true;

    protected function rules()
    {
        $uniqueRule = 'unique:categories,name';
        if ($this->editMode) {
            $uniqueRule .= ',' . $this->categoryId;
        } else {
            $uniqueRule .= ',NULL,id,deleted_at,NULL';
        }

        return [
            'name' => 'required|string|max:255|' . $uniqueRule,
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function create()
    {
        $this->reset(['name', 'description', 'is_active', 'categoryId', 'editMode']);
        $this->is_active = true;
        $this->showModal = true;
    }

    public function edit(Category $category)
    {
        $this->categoryId = $category->id;
        $this->name = $category->name;
        $this->description = $category->description;
        $this->is_active = $category->is_active;
        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ];

        if ($this->editMode) {
            Category::find($this->categoryId)->update($data);
            session()->flash('message', 'Category updated successfully.');
        } else {
            Category::create($data);
            session()->flash('message', 'Category created successfully.');
        }

        $this->showModal = false;
        $this->reset(['name', 'description', 'is_active', 'categoryId', 'editMode']);
    }

    public function confirmDelete($id)
    {
        $this->deleteId = $id;
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        $category = Category::find($this->deleteId);
        
        if (!$category) {
            $this->showDeleteModal = false;
            return;
        }

        // Check if category has products
        if ($category->products()->count() > 0) {
            session()->flash('error', 'Cannot delete category with associated products.');
            $this->showDeleteModal = false;
            return;
        }
        
        $category->delete();
        $this->showDeleteModal = false;
        $this->deleteId = null;
        session()->flash('message', 'Category deleted successfully.');
    }

    public function render()
    {
        $categories = Category::query()
            ->withCount('products')
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.categories.category-index', [
            'categories' => $categories,
        ]);
    }
}
