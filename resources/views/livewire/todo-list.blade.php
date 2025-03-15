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

    <flux:heading size="lg">To Do List</flux:heading>
    @include('livewire.search-box')
    <div class="flex flex-row gap-4">
        <flux:button wire:click="addTask" variant="primary" class="mb-5 mt-5" kbd="⌘A">Add Task</flux:button>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
        <flux:button wire:click="setFilter('all')" size="xs"
            variant="{{ $activeFilter === 'all' ? 'primary' : 'ghost' }}">
            All
        </flux:button>

        <flux:button wire:click="setFilter('active')" size="xs"
            variant="{{ $activeFilter === 'active' ? 'primary' : 'ghost' }}">
            Active
        </flux:button>

        <flux:button wire:click="setFilter('completed')" size="xs"
            variant="{{ $activeFilter === 'completed' ? 'primary' : 'ghost' }}">
            Completed
        </flux:button>

        <flux:button wire:click="setFilter('overdue')" size="xs"
            variant="{{ $activeFilter === 'overdue' ? 'primary' : 'ghost' }}">
            Overdue
        </flux:button>

        <flux:button wire:click="setFilter('today')" size="xs"
            variant="{{ $activeFilter === 'today' ? 'primary' : 'ghost' }}">
            Today
        </flux:button>

        <flux:button wire:click="setFilter('thisWeek')" size="xs"
            variant="{{ $activeFilter === 'thisWeek' ? 'primary' : 'ghost' }}">
            This Week
        </flux:button>

        <flux:button wire:click="setFilter('next7Days')" size="xs"
            variant="{{ $activeFilter === 'next7Days' ? 'primary' : 'ghost' }}">
            Next 7 Days
        </flux:button>

        <flux:button wire:click="setFilter('next30Days')" size="xs"
            variant="{{ $activeFilter === 'next30Days' ? 'primary' : 'ghost' }}">
            Next 30 Days
        </flux:button>
        <flux:spacer />
        <div class="mr-5">
            <flux:select wire:model.live="tableLength" label="Show">
                <flux:select.option value="5">5</flux:select.option>
                <flux:select.option value="10">10</flux:select.option>
                <flux:select.option value="25">25</flux:select.option>
                <flux:select.option value="50">50</flux:select.option>
                <flux:select.option value="100">100</flux:select.option>
            </flux:select>
        </div>
    </div>

    <div id="limits" class="mb-5 p-5">
        <flux:card>
            @if ($this->tasks->isNotEmpty())
                @include('livewire.todo-card')
            @elseif ($activeFilter)
                <h1>No tasks match the filter ({{ $activeFilter }})</h1>
            @else
                <h1>You have no tasks at this time. Create one!</h1>
            @endif
        </flux:card>


        <flux:modal name="addTask" class="w-6/12">

            <form wire:submit="create">
                <flux:input label="Title" wire:model="title" class="mb-2" />

                <flux:textarea label="Description" wire:model="desc" class="mb-2" />

                <flux:spacer />
                <flux:table>
                    <flux:table.rows>
                        <flux:table.row>
                            <flux:table.cell>
                                <flux:input type="date" max="2999-12-31" label="Due Date" wire:model="due"
                                    class="mt-2" />

                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:select label="Priority" wire:model="priority" class="mt-2">

                                    <flux:select.option value="CRITICAL">Critical</flux:select.option>
                                    <flux:select.option value="HIGH">High</flux:select.option>
                                    <flux:select.option value="MEDIUM">Medium</flux:select.option>
                                    <flux:select.option value="LOW">Low</flux:select.option>
                                    <flux:select.option value="NONE">None</flux:select.option>
                                </flux:select>
                            </flux:table.cell>
                        </flux:table.row>
                    </flux:table.rows>
                </flux:table>

                <flux:spacer />

                <flux:button type="submit" class="mt-5">Save</flux:button>
                <flux:button variant="ghost" class="mt-5" wire:click="closeTaskWindow()">Cancel</flux:button>
            </form>
        </flux:modal>
    </div>
</div>
