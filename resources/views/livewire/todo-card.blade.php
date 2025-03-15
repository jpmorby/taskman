<flux:table class=" items-center w-full table-fixed" :paginate="$this->tasks">
    <flux:table.columns>
        <flux:table.column class="w-1/12" sortable :sorted="$sortBy === 'completed'" :direction="$sortDirection"
            wire:click="sort('completed')">
            <flux:icon.bars-3-bottom-right />
            &nbsp;
        </flux:table.column>
        <flux:table.column class="w-2/12" sortable :sorted="$sortBy === 'title'" :direction="$sortDirection"
            wire:click="sort('title')">
            Title
        </flux:table.column>
        <flux:table.column class="w-5/12" sortable :sorted="$sortBy === 'desc'" :direction="$sortDirection"
            wire:click="sort('desc')">
            Description
        </flux:table.column>
        {{-- <flux:table.column class="w-2/12" sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection"
            wire:click="sort('created_at')">
            Created
        </flux:table.column> --}}
        <flux:table.column class="w-2/12" sortable :sorted="$sortBy === 'due'" :direction="$sortDirection"
            wire:click="sort('due')">
            Due Date
        </flux:table.column>
        <flux:table.column class="w-1/12" sortable :sorted="$sortBy === 'priority'" :direction="$sortDirection"
            wire:click="sort('priority')">
            Priority
        </flux:table.column>
    </flux:table.columns>

    
    <flux:table.rows>
        @foreach($this->tasks as $task)
            <flux:table.row :wire:key="$task->id">
                <flux:table.cell>
                    <flux:checkbox :checked="$task->completed" wire:click="toggleCompleted({{  $task->id }})" />
                </flux:table.cell>

                <flux:table.cell :wire:key="$task->id" wire:click="edit({{ $task->id }})">
                    <flux:label>{{  Str::limit($task->title, 20) }}</flux:label>
                </flux:table.cell>

                <flux:table.cell :wire:key="$task->id" wire:click="edit({{ $task->id }})">

                    {{ Str::limit($task->desc, 40) }}
                </flux:table.cell>

                {{-- <flux:table.cell>{{ $task->created_at->diffForHumans() }}</flux:table.cell> --}}

                <flux:table.cell :wire:key="$task->id" wire:click="edit({{ $task->id }})">
                    {{ ($task->due ? $task->due->diffForHumans() : "Not Set")}}
                </flux:table.cell>

                <flux:table.cell :wire:key="$task->id" wire:click="edit({{ $task->id }})">
                    <flux:badge color="{{ $this->badgeColour($task->priority) }}" class="w-full text-center">
                        {{  $task->priority->label() }}
                    </flux:badge>
                </flux:table.cell>


                <flux:table.cell class="w-2/12">
                    <flux:button variant="danger" icon="trash" wire:click="delete({{ $task->id }})" size="xs" />

                </flux:table.cell>
            </flux:table.row>
        @endforeach
    </flux:table.rows>
    </flux:table>
    <!-- Mobile Tasks Content -->