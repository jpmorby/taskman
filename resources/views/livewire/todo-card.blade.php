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

                <flux:table.cell :wire:key="$task->id">
                    <flux:label>{{  Str::limit($task->title, 20) }}</flux:label>
                </flux:table.cell>

                <flux:table.cell :wire:key="$task->id">
                    <flux:accordion transition variant="reverse">
                        <flux:accordion.item>
                            <flux:accordion.heading>
                                <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {!! Str::limit($task->desc, 50) !!}
                                </div>
                            </flux:accordion.heading>
                            <flux:accordion.content>
                                <p>{!! Str::markdown($task->desc) !!}</p>
                            </flux:accordion.content>
                        </flux:accordion.item>
                    </flux:accordion>
                </flux:table.cell>


                <flux:table.cell :wire:key="$task->id">
                    {{ ($task->due ? $task->due->diffForHumans() : "Not Set")}}
                </flux:table.cell>

                <flux:table.cell :wire:key="$task->id">
                    <flux:badge color="{{ $this->badgeColour($task->priority) }}" class="w-full text-center">
                        {{  $task->priority->label() }}
                    </flux:badge>
                </flux:table.cell>

                <flux:table.cell :wire:key="$task->id">
                    <flux:button variant="primary" icon="pencil" wire:click="edit({{ $task->id }})" size="xs" />
                </flux:table.cell>
                <flux:table.cell class="w-2/12" x-data="{ confirmDelete: false }">
                    <div x-show="!confirmDelete" class="flex justify-center">
                        <flux:button variant="danger" icon="trash" x-on:click.stop="confirmDelete = true" size="xs" />
                    </div>

                    <div x-show="confirmDelete" x-cloak class="flex items-center space-x-1 justify-center">
                        <flux:button variant="danger" size="xs" wire:click.stop="delete({{ $task->id }})">
                            Yes
                        </flux:button>
                        <flux:button variant="ghost" size="xs" x-on:click.stop="confirmDelete = false">
                            No
                        </flux:button>
                    </div>
                </flux:table.cell>
            </flux:table.row>
        @endforeach
    </flux:table.rows>
</flux:table>
