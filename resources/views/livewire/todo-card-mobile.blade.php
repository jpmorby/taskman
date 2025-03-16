<div class="space-y-4">
    @foreach($this->tasks as $task)
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4 border border-zinc-200 dark:border-zinc-700" wire:key="{{ $task->id }}">
            <!-- Task header with checkbox, title, and priority -->
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center space-x-3 flex-1">
                    <flux:checkbox wire:key="{{ $task->id }}"
                        :checked="$task->completed" 
                        wire:click.stop="toggleCompleted({{ $task->id }})" 
                    />
                    <h3 class="font-medium text-sm {{ $task->completed ? 'line-through text-zinc-500' : '' }}"
                        wire:click.stop="edit({{ $task->id }})">
                        {{ $task->title }}
                    </h3>
                </div>
                
                <!-- Priority badge -->
                <div class="flex-shrink-0 overflow:hidden">          
                    @switch($task->priority)
                        @case(\App\Enums\PriorityLevel::CRITICAL)
                        <flux:badge color="purple">{{ __($task->priority->label()) }}</flux:badge>
                            @break
                        @case(\App\Enums\PriorityLevel::HIGH)
                        <flux:badge color="red">{{ __($task->priority->label()) }}</flux:badge>
                            @break
                        @case(\App\Enums\PriorityLevel::MEDIUM)  
                        <flux:badge color="lime">{{ __($task->priority->label()) }}</flux:badge>
                            @break
                        @default
                        <flux:badge color="cyan">{{ __($task->priority->label()) }}</flux:badge>
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
                        <span class="text-zinc-400">{{ __('No Due Date') }}</span>
                    @endif
                </div>
                
                <!-- Delete button with confirmation - using Alpine.js -->
                <div class="flex space-x-2" x-data="{ confirmDelete: false }">
                    <flux:button 
                        icon="pencil" 
                        wire:click.stop="edit({{ $task->id }})" 
                        size="xs" 
                        variant="ghost"
                    />
                    
                    <!-- Delete button that shows confirmation -->
                    <div x-show="!confirmDelete">
                        <flux:button 
                            variant="danger" 
                            icon="trash" 
                            x-on:click.stop="confirmDelete = true" 
                            size="xs"
                        />
                    </div>
                    
                    <!-- Confirmation buttons -->
                    <div x-show="confirmDelete" x-cloak class="flex space-x-1 items-center">
                        <span class="text-xs text-red-500">{{ __('Delete?') }}</span>
                        <flux:button 
                            variant="danger" 
                            size="xs"
                            wire:click.stop="delete({{ $task->id }})" 
                        >
                            {{ __('Yes') }}
                        </flux:button>
                        <flux:button 
                            variant="ghost" 
                            size="xs"
                            x-on:click.stop="confirmDelete = false" 
                        >
                            {{ __('No') }}
                        </flux:button>
                    </div>
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