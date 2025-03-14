<div>
    <flux:heading size="lg">To Do List</flux:heading>

    <div id="search" class="p-5 mb-5">
        <flux:input
            wire:keydown.meta.k="search"
            kbd="⌘K"
            label="Search:"
            icon="magnifying-glass"
            wire:model.live.debounce.500ms="needle"
        />
    </div>

    <div id="limits" class="p-5 mb-5">
        <flux:card>
            @if ($this->tasks->isNotEmpty())
                @include("livewire.todo-card")
            @else
                <h1>You have no tasks at this time. Create one!</h1>
            @endif
        </flux:card>

        <flux:button wire:click="addTask" variant="primary" class="mt-5">Add Task</flux:button>

        <flux:modal name="addTask" class="w-6/12">
            <form wire:submit="create">
                <flux:input label="title" wire:model="title" />

                <flux:input label="desc" wire:model="desc" />

                <flux:date-picker wire:model="due" />

                <flux:button type="submit" class="mt-5">Save</flux:button>
            </form>
        </flux:modal>
    </div>
</div>
