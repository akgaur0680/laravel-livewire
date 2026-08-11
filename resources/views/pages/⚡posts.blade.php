<?php

use App\Models\Post;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * A Livewire "single file component": the PHP class lives on top, the Blade
 * markup below. There is no controller and no separate view file — this one
 * file replaces both.
 */
new #[Title('Posts')] class extends Component {
    // Gives us $this->resetPage() and keeps the ?page= number in the URL.
    use WithPagination;

    /**
     * Public properties are the component's state. Livewire serialises them
     * after every request and sends them back on the next one, so they survive
     * between clicks. This is what replaces old() and hidden form inputs.
     */
    public ?int $editingId = null;

    public string $title = '';

    public string $body = '';

    public bool $published = false;

    public string $search = '';

    /**
     * Same rules array you would put in a FormRequest, but the keys point at
     * the public properties above instead of at request input.
     */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'published' => ['boolean'],
        ];
    }

    /**
     * A computed property runs on every render, so the list is always fresh
     * after a save or delete — no manual "refresh the collection" step.
     * The view reads it as $this->posts.
     */
    #[Computed]
    public function posts()
    {
        return Post::query()
            ->when($this->search, fn ($query) => $query->where('title', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(10);
    }

    /**
     * Livewire calls updatedX() automatically right after the $x property
     * changes. Here it sends the user back to page 1 when they search.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Public methods are "actions" — the view calls them straight from the
     * browser with wire:click / wire:submit. No route, no controller method.
     */
    public function create(): void
    {
        $this->reset(['editingId', 'title', 'body', 'published']);
        $this->resetValidation();

        Flux::modal('post-form')->show();
    }

    /**
     * Type-hinting the model gives you route-model binding inside actions:
     * the view passes the id, Livewire resolves the Post for you.
     */
    public function edit(Post $post): void
    {
        $this->editingId = $post->id;
        $this->title = $post->title;
        $this->body = $post->body;
        $this->published = $post->published;
        $this->resetValidation();

        Flux::modal('post-form')->show();
    }

    public function save(): void
    {
        // Validates the public properties and returns only the valid ones.
        $validated = $this->validate();

        // findOrNew(null) hands back an empty Post, so this covers both
        // "create" and "update" without an if/else.
        Post::findOrNew($this->editingId)->fill($validated)->save();

        Flux::modal('post-form')->close();

        Flux::toast(variant: 'success', text: $this->editingId ? __('Post updated.') : __('Post created.'));
    }

    public function delete(Post $post): void
    {
        $post->delete();

        Flux::toast(variant: 'success', text: __('Post deleted.'));
    }
}; ?>

{{--
    Everything below is the component's view. Livewire re-renders it after each
    action and patches only the changed DOM — that is why nothing here does a
    full page reload. It must have exactly one root element.
--}}
<div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">{{ __('Posts') }}</flux:heading>

        <flux:button variant="primary" icon="plus" wire:click="create">
            {{ __('New post') }}
        </flux:button>
    </div>

    {{-- wire:model.live binds the input to $search and re-renders as you type. --}}
    <flux:input
        wire:model.live.debounce.300ms="search"
        icon="magnifying-glass"
        :placeholder="__('Search posts...')"
        class="max-w-sm"
    />

    <flux:table :paginate="$this->posts">
        <flux:table.columns>
            <flux:table.column>{{ __('Title') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Created') }}</flux:table.column>
            <flux:table.column />
        </flux:table.columns>

        <flux:table.rows>
            {{-- wire:key tells Livewire which row is which, so it can reorder
                 and remove rows correctly instead of reusing the wrong one. --}}
            @foreach ($this->posts as $post)
                <flux:table.row :key="$post->id">
                    <flux:table.cell class="font-medium">{{ $post->title }}</flux:table.cell>

                    <flux:table.cell>
                        <flux:badge size="sm" :color="$post->published ? 'green' : 'zinc'" inset="top bottom">
                            {{ $post->published ? __('Published') : __('Draft') }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>{{ $post->created_at->diffForHumans() }}</flux:table.cell>

                    <flux:table.cell class="flex justify-end gap-2">
                        <flux:button size="sm" icon="pencil" wire:click="edit({{ $post->id }})" />

                        {{-- wire:confirm shows a native confirm dialog and only
                             calls the action if the user accepts. --}}
                        <flux:button
                            size="sm"
                            variant="danger"
                            icon="trash"
                            wire:click="delete({{ $post->id }})"
                            wire:confirm="{{ __('Delete this post?') }}"
                        />
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    {{-- One modal serves both create and edit; $editingId decides which. --}}
    <flux:modal name="post-form" class="max-w-lg">
        {{-- wire:submit calls save() over AJAX and blocks the normal submit. --}}
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">
                {{ $editingId ? __('Edit post') : __('New post') }}
            </flux:heading>

            <flux:input wire:model="title" :label="__('Title')" />

            <flux:textarea wire:model="body" :label="__('Body')" rows="5" />

            <flux:switch wire:model="published" :label="__('Published')" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled" type="button">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
