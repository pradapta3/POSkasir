<?php

namespace App\Livewire\Admin\Categories;

use App\Livewire\Actions\Logout;
use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.pos')]
class Index extends Component
{
    public bool $showFormModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public ?string $description = null;

    public bool $isActive = true;

    #[Computed]
    public function categories(): Collection
    {
        return Category::query()->withCount('products')->orderBy('name')->get();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function edit(int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);

        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->description = $category->description;
        $this->isActive = $category->is_active;

        $this->resetValidation();
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $attributes = [
            'name' => $this->name,
            'slug' => $this->uniqueSlug(Str::slug($this->name)),
            'description' => $this->description,
            'is_active' => $this->isActive,
        ];

        if ($this->editingId) {
            Category::whereKey($this->editingId)->update($attributes);
        } else {
            Category::create($attributes);
        }

        $this->showFormModal = false;
        $this->resetForm();
        unset($this->categories);
    }

    public function delete(int $categoryId): void
    {
        // products.category_id is nullOnDelete(), so this can't fail on FK
        // constraints — any products in this category just become uncategorized.
        Category::whereKey($categoryId)->delete();
        unset($this->categories);
    }

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/login', navigate: true);
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base;
        $suffix = 1;

        while (Category::where('slug', $slug)->where('id', '!=', $this->editingId ?? 0)->exists()) {
            $slug = $base.'-'.(++$suffix);
        }

        return $slug;
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'description']);
        $this->isActive = true;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.categories.index');
    }
}
