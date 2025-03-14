<div>
    <flux:header>To Do List</flux:header>
    <div id="search" class="px-5 py-5 mb-10">
        <flux:input wire:keydown.meta.k="search" kbd="⌘K" label="Search: " icon="magnifying-glass"
            wire:model.debounce.500ms.live="needle" />
    </div>

    <div id="limits" class="px-5 py-5 mb-10">
        <flux:dropdown @if ($tasks) <div class="p-10">
                    <flux:card>

                        @include("livewire.todo-card")

                    </flux:card>
            </div>

        @else
            <div class="p-10">
                <flux:card>
                    <h1>You have no tasks at this time. Create one!</h1>
                </flux:card>
            </div>
        @endif

    <flux:button class="mt-5 py-10" wire:click="addTask">Add Task</flux:button>


    <flux:modal name="addTask" class="w-6/12">
        <form>
            <flux:input label="title" name="title" wire:model="title" />
            <flux:input label="desc" type="text" name="desc" wire:model="desc" />
            <flux:date-picker wire:model="due" />
            <flux:button class="mt-5" wire:click="create">Save</flux:button>
        </form>
    </flux:modal>


</div>