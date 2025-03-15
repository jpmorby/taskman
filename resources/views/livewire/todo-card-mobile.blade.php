<div class="space-y-4">
    @foreach($this->tasks as $task)
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4 border border-zinc-200 dark:border-zinc-700" :wire:key="$task->id">
            <!-- Task header with checkbox, title, and priority -->
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center space-x-3 flex-1">
                    <flux:checkbox 
                        :checked="$task->completed" 
                        wire:click.stop="toggleCompleted({{ $task->id }})" 
                    />
                    <h3 class="font-medium text-sm {{ $task->completed ? 'line-through text-zinc-500' : '' }}"
                        wire:click.stop="edit({{ $task->id }})">
                        {{ $task->title }}
                    </h3>
                </div>
                
                <!-- Priority badge -->
                <div class="flex-shrink-0">          
                    @switch($task->priority)
                        @case(\App\Enums\PriorityLevel::CRITICAL)
                        <flux:badge color="purple">{{ $task->priority->label() }}</flux:badge>

                            @break
                        @case(\App\Enums\PriorityLevel::HIGH)
                        <flux:badge color="red">{{ $task->priority->label() }}</flux:badge>
                            @break
                        @case(\App\Enums\PriorityLevel::MEDIUM)  
                          <flux:badge color="lime">{{ $task->priority->label() }}</flux:badge>

                            @break
                        @default
                        <flux:badge color="cyan">{{ $task->priority->label() }}</flux:badge>

                    @endswitch
                </div>
            </div>
            
            <!-- Description - collapsible -->
            <div x-data="{ expanded: false }" class="mb-3">
                <p class="text-sm text-zinc-600 dark:text-zinc-400" x-show="expanded">
                    {{ $task->desc }}
                </p>
                <p class="text-sm text-zinc-600 dark:text-zinc-400" x-show="!expanded" x-cloak>
                    {{ Str::limit($task->desc, 75) }}
                </p>
                @if(strlen($task->desc) > 75)
                    <flux:label 
                        class="text-xs text-blue-600 dark:text-blue-400 mt-1"
                        x-on:click="expanded = !expanded"
                        x-text="expanded ? 'Show less' : 'Show more'"
                        size="xs"
                    />
                @endif
            </div>
            
            <!-- Due date and action buttons -->
            <div class="flex items-center justify-between text-xs">
                <div class="text-zinc-500 dark:text-zinc-400">
                    @if($task->due)
                        <span class="{{ $task->due->isPast() && !$task->completed ? 'text-red-600' : '' }}">
                            <flux:icon.clock class="inline-block w-3 h-3 mr-1" />
                            {{ $task->due->diffForHumans() }}
                        </span>
                    @else
                        <span class="text-zinc-400">No due date</span>
                    @endif
                </div>
                
                <div class="flex space-x-2">
                    <flux:button 
                        icon="pencil" 
                        wire:click.stop="edit({{ $task->id }})" 
                        size="xs" 
                        variant="ghost"
                    />
                    <flux:button 
                        variant="danger" 
                        icon="trash" 
                        wire:click.stop="delete({{ $task->id }})" 
                        size="xs"
                    />
                </div>
            </div>
        </div>
    @endforeach
    
    <!-- Pagination -->
    @if($this->tasks->hasPages())
        <div class="mt-4">
            {{ $this->tasks->links() }}
        </div>
    @endif
</div>