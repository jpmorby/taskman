<div x-data="{
    initShortcuts() {
        document.addEventListener('keydown', (e) => {
            if ((e.metaKey || e.ctrlKey) && e.key === 'a') {
                e.preventDefault();
                @this.addTask();
            }
        });
    }
}" x-init="initShortcuts()">

    <!-- Header Section -->
    <div class="flex justify-between items-center mb-6">
        <flux:heading size="lg">{{ __('To Do List') }}</flux:heading>
        <flux:button 
            wire:click="addTask" 
            variant="primary" 
            kbd="⌘A"
        >
        {{ __("Add Task")}}
        </flux:button>
    </div>

    <!-- Search Box -->
    <div class="mb-6">
        @include('livewire.search-box')
    </div>

    @include('components.time-filters')

    <!-- Tasks Content -->
    <flux:card class="mb-6 p-5">
        @if ($this->tasks->isNotEmpty())
            <div class="xl:block hidden item-center w-full">
                @include('livewire.todo-card')
            </div>
            <div class="xl:hidden block item-center w-full">
                @include('livewire.todo-card-mobile')
            </div>
        @elseif ($activeFilter)
            <div class="py-12 text-center">
                <flux:icon name="clipboard-document" class="mx-auto h-12 w-12 text-gray-400" />
                <flux:heading class="mt-4">{{__('No tasks match the filter')}}</flux:heading>
                <p class="mt-2 text-sm text-gray-500">{{ __('Try changing your filter criteria or create a new task.') }}</p>
                <flux:button wire:click="addTask" variant="primary" class="mt-4">{{ __('Create New Task') }}</flux:button>
            </div>
        @else
            <div class="py-12 text-center">
                <flux:icon name="clipboard-document" class="mx-auto h-12 w-12 text-gray-400" />
                <flux:heading class="mt-4">{{ __('You have no tasks') }}</flux:heading>
                <p class="mt-2 text-sm text-gray-500">{{ __('Get started by creating your first task.') }}</p>
                <flux:button wire:click="addTask" variant="primary" class="mt-4">{{ __('Create New Task') }}</flux:button>
            </div>
        @endif
    </flux:card>

@include('components.add-task-modal')
</div>
