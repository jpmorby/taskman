<div class="space-y-4">
    @foreach($this->tasks as $task)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700" :wire:key="$task->id">
            <!-- Task header with checkbox, title, and priority -->
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center space-x-3 flex-1">
                    <flux:checkbox 
                        :checked="$task->completed" 
                        wire:click.stop="toggleCompleted({{ $task->id }})" 
                    />
                    <h3 class="font-medium text-sm {{ $task->completed ? 'line-through text-gray-500' : '' }}"
                        wire:click.stop="edit({{ $task->id }})">
                        {{ $task->title }}
                    </h3>
                </div>
                
                <!-- Priority badge -->
                <div class="flex-shrink-0">
                    @switch($task->priority)
                        @case('high')
                            <span class="inline-flex items-center px-2 py-1 text-xs rounded-md font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                High
                            </span>
                            @break
                        @case('medium')  
                            <span class="inline-flex items-center px-2 py-1 text-xs rounded-md font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                Medium
                            </span>
                            @break
                        @default
                            <span class="inline-flex items-center px-2 py-1 text-xs rounded-md font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                Low
                            </span>
                    @endswitch
                </div>
            </div>
            
            <!-- Description - collapsible -->
            <div x-data="{ expanded: false }" class="mb-3">
                <p class="text-sm text-gray-600 dark:text-gray-400" x-show="expanded">
                    {{ $task->desc }}
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400" x-show="!expanded" x-cloak>
                    {{ Str::limit($task->desc, 75) }}
                </p>
                @if(strlen($task->desc) > 75)
                    <flux:button 
                        class="text-xs text-blue-600 dark:text-blue-400 mt-1"
                        x-on:click="expanded = !expanded"
                        x-text="expanded ? 'Show less' : 'Show more'"
                    />
                @endif
            </div>
            
            <!-- Due date and action buttons -->
            <div class="flex items-center justify-between text-xs">
                <div class="text-gray-500 dark:text-gray-400">
                    @if($task->due)
                        <span class="{{ $task->due->isPast() && !$task->completed ? 'text-red-600' : '' }}">
                            <flux:icon.clock class="inline-block w-3 h-3 mr-1" />
                            {{ $task->due->diffForHumans() }}
                        </span>
                    @else
                        <span class="text-gray-400">No due date</span>
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