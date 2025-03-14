<flux:table class="items-center w-full table-fixed" :paginate="$this->tasks">
    <flux:table.columns>
        <flux:table.column class="w-1/12" sortable :sorted="$sortBy === 'completed'" :direction="$sortDirection"
            wire:click="sort('completed')">
            <flux:icon.adjustments-vertical />
            &nbsp;
        </flux:table.column>
        <flux:table.column class="w-2/12" sortable :sorted="$sortBy === 'title'" :direction="$sortDirection"
            wire:click="sort('title')">
            Title
        </flux:table.column>
        <flux:table.column class="w-3/12" sortable :sorted="$sortBy === 'desc'" :direction="$sortDirection"
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

                    <flux:table.cell>{{  Str::limit($task->title, 15) }}</flux:table.cell>

                    <flux:table.cell>{{ Str::limit($task->desc, 30) }}</flux:table.cell>

                    {{-- <flux:table.cell>{{ $task->created_at->diffForHumans() }}</flux:table.cell> --}}

                    <flux:table.cell>{{ ($task->due ? $task->due->diffForHumans() : "Not Set")}}</flux:table.cell>
                    <flux:table.cell>{{  $task->priority }}</flux:table.cell>

                    {{-- @if($editItem)
                    <flux:table.cell>
                        <flux:input label="Title" wire:model="task->title" description="Edit title" />
                        <flux:input wire:model="task->desc" />
                    </flux:table.cell>
                    @else --}}
                    <flux:table.cell class="w-2/12">
                        <flux:button variant="primary" wire:click="edit({{ $task->id }})">Edit</flux:button> &nbsp;
                        <flux:button variant="danger" wire:click="delete({{ $task->id }})">Delete</flux:button>
                    </flux:table.cell>
                    {{-- @endif --}}
                </flux:table.row>
            @endforeach
        </flux:table.rows>
        </flux:table>