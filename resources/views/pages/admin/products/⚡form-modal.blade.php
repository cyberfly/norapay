<?php

use App\Models\Product;
use Flux\Flux;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public ?int $editingId = null;

    public string $name = '';

    public string $slug = '';

    public string $short_description = '';

    public string $description = '';

    public string $price = '';

    public bool $is_active = true;

    public ?string $image = null;

    /**
     * Newly selected featured image, pending upload.
     */
    public ?UploadedFile $photo = null;

    /**
     * Bumped every time the modal opens, so the rich text editor remounts with fresh content.
     */
    public int $formVersion = 0;

    public function mount(): void
    {
        Gate::authorize('access-admin');
    }

    #[On('create-product')]
    public function openForCreate(): void
    {
        $this->reset(['editingId', 'name', 'slug', 'short_description', 'description', 'price', 'image', 'photo']);
        $this->is_active = true;
        $this->formVersion++;
    }

    #[On('edit-product')]
    public function openForEdit(int $productId): void
    {
        $product = Product::findOrFail($productId);

        $this->editingId = $product->id;
        $this->name = $product->name;
        $this->slug = $product->slug;
        $this->short_description = (string) $product->short_description;
        $this->description = (string) $product->description;
        $this->price = (string) $product->price;
        $this->is_active = $product->is_active;
        $this->image = $product->image;
        $this->photo = null;
        $this->formVersion++;
    }

    public function removeImage(): void
    {
        $this->photo = null;
        $this->image = null;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ]);

        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['name']);

        $previousImage = Product::query()->whereKey($this->editingId)->value('image');

        unset($validated['photo']);

        if ($this->photo) {
            $validated['image'] = $this->photo->store('products', 'public');
        } elseif ($this->image === null) {
            $validated['image'] = null;
        }

        Product::updateOrCreate(['id' => $this->editingId], $validated);

        if ($previousImage && array_key_exists('image', $validated) && $validated['image'] !== $previousImage) {
            Storage::disk('public')->delete($previousImage);
        }

        $this->dispatch('product-saved');

        Flux::modal('product-form')->close();
        Flux::toast(variant: 'success', text: __('Product saved.'));
    }
}; ?>

@assets
    @vite('resources/js/tiptap-editor.js')
@endassets

<flux:modal name="product-form" class="w-full max-w-3xl">
    <form wire:submit="save" class="space-y-6">
        <flux:heading size="lg">{{ $editingId ? __('Edit product') : __('New product') }}</flux:heading>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <flux:input wire:model="name" :label="__('Name')" required />

            <flux:input wire:model="slug" :label="__('Slug (optional)')" placeholder="{{ __('Auto-generated from name') }}" />
        </div>

        <flux:field>
            <flux:label>{{ __('Featured image (optional)') }}</flux:label>

            <div class="flex items-start gap-4">
                @if ($photo)
                    <img src="{{ $photo->temporaryUrl() }}" alt="" class="h-24 w-24 rounded-lg object-cover">
                @elseif ($image)
                    <img src="{{ Storage::disk('public')->url($image) }}" alt="" class="h-24 w-24 rounded-lg object-cover">
                @endif

                <div class="flex-1 space-y-2">
                    <input type="file" wire:model="photo" accept="image/*" class="block w-full text-sm text-zinc-600 file:mr-4 file:rounded-lg file:border-0 file:bg-zinc-100 file:px-4 file:py-2 file:text-sm file:font-medium hover:file:bg-zinc-200 dark:text-zinc-300 dark:file:bg-zinc-700 dark:hover:file:bg-zinc-600">

                    <div wire:loading wire:target="photo" class="text-sm text-zinc-500">{{ __('Uploading...') }}</div>

                    @if ($photo || $image)
                        <flux:button type="button" size="sm" variant="ghost" wire:click="removeImage">
                            {{ __('Remove image') }}
                        </flux:button>
                    @endif
                </div>
            </div>

            <flux:error name="photo" />
        </flux:field>

        <flux:input wire:model="short_description" :label="__('Short description (optional)')" />

        <flux:field>
            <flux:label>{{ __('Description (optional)') }}</flux:label>

            <div
                wire:key="description-editor-{{ $formVersion }}"
                x-data="richTextEditor(@js($description))"
                class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700"
            >
                <div class="flex flex-wrap items-center gap-1 border-b border-zinc-200 bg-zinc-50 p-1.5 dark:border-zinc-700 dark:bg-zinc-800">
                    @php
                        $toolbarButton = 'rounded px-2 py-1 text-sm font-medium text-zinc-600 hover:bg-zinc-200 dark:text-zinc-300 dark:hover:bg-zinc-700';
                        $toolbarButtonActive = 'bg-zinc-200 text-zinc-900 dark:bg-zinc-700 dark:text-white';
                    @endphp

                    <button type="button" class="{{ $toolbarButton }}" :class="isBold && '{{ $toolbarButtonActive }}'" x-on:mousedown.prevent="toggleBold" title="{{ __('Bold') }}"><span class="font-bold">B</span></button>
                    <button type="button" class="{{ $toolbarButton }}" :class="isItalic && '{{ $toolbarButtonActive }}'" x-on:mousedown.prevent="toggleItalic" title="{{ __('Italic') }}"><span class="italic">I</span></button>
                    <button type="button" class="{{ $toolbarButton }}" :class="isStrike && '{{ $toolbarButtonActive }}'" x-on:mousedown.prevent="toggleStrike" title="{{ __('Strikethrough') }}"><span class="line-through">S</span></button>

                    <span class="mx-1 h-5 w-px bg-zinc-300 dark:bg-zinc-600"></span>

                    <button type="button" class="{{ $toolbarButton }}" :class="isHeading2 && '{{ $toolbarButtonActive }}'" x-on:mousedown.prevent="toggleHeading2" title="{{ __('Heading') }}">H2</button>
                    <button type="button" class="{{ $toolbarButton }}" :class="isHeading3 && '{{ $toolbarButtonActive }}'" x-on:mousedown.prevent="toggleHeading3" title="{{ __('Subheading') }}">H3</button>

                    <span class="mx-1 h-5 w-px bg-zinc-300 dark:bg-zinc-600"></span>

                    <button type="button" class="{{ $toolbarButton }}" :class="isBulletList && '{{ $toolbarButtonActive }}'" x-on:mousedown.prevent="toggleBulletList" title="{{ __('Bullet list') }}">•⁝</button>
                    <button type="button" class="{{ $toolbarButton }}" :class="isOrderedList && '{{ $toolbarButtonActive }}'" x-on:mousedown.prevent="toggleOrderedList" title="{{ __('Numbered list') }}">1.</button>
                    <button type="button" class="{{ $toolbarButton }}" :class="isBlockquote && '{{ $toolbarButtonActive }}'" x-on:mousedown.prevent="toggleBlockquote" title="{{ __('Quote') }}">&rdquo;</button>

                    <span class="mx-1 h-5 w-px bg-zinc-300 dark:bg-zinc-600"></span>

                    <button type="button" class="{{ $toolbarButton }}" x-on:mousedown.prevent="undo" title="{{ __('Undo') }}">&#8630;</button>
                    <button type="button" class="{{ $toolbarButton }}" x-on:mousedown.prevent="redo" title="{{ __('Redo') }}">&#8631;</button>
                </div>

                <div wire:ignore>
                    <div x-ref="element" class="rich-text"></div>
                </div>
            </div>

            <input type="hidden" wire:model="description" x-ref="hidden">

            <flux:error name="description" />
        </flux:field>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <flux:input wire:model="price" :label="__('Price')" type="number" step="0.01" min="0" required />

            <flux:switch wire:model="is_active" :label="__('Active')" />
        </div>

        <div class="flex justify-end space-x-2 rtl:space-x-reverse">
            <flux:modal.close>
                <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>

            <flux:button variant="primary" type="submit">
                {{ __('Save') }}
            </flux:button>
        </div>
    </form>
</flux:modal>
