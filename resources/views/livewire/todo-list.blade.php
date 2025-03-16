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

    <!-- Filters Section -->
    <flux:card class="mb-6 p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Time-based Filters -->
            <div>
                <flux:heading class="mb-2">{{ __('Time Filters') }}</flux:heading>
                <div class="flex flex-wrap gap-2">
                    <flux:button wire:click="setFilter('all')" size="xs"
                        variant="{{ $activeFilter === 'all' ? 'primary' : 'ghost' }}">
                        {{ __('All') }}
                    </flux:button>
                    <flux:button wire:click="setFilter('active')" size="xs"
                        variant="{{ $activeFilter === 'active' ? 'primary' : 'ghost' }}">
                    {{ __('Active') }}
                    </flux:button>
                    <flux:button wire:click="setFilter('completed')" size="xs"
                        variant="{{ $activeFilter === 'completed' ? 'primary' : 'ghost' }}">
                        {{__('Completed') }}
                    </flux:button>
                    <flux:button wire:click="setFilter('overdue')" size="xs"
                        variant="{{ $activeFilter === 'overdue' ? 'primary' : 'ghost' }}">
                        {{__('Overdue')}}
                    </flux:button>
                    <flux:button wire:click="setFilter('today')" size="xs"
                        variant="{{ $activeFilter === 'today' ? 'primary' : 'ghost' }}">
                        {{__('Today')}}
                    </flux:button>
                </div>
                <div class="flex flex-wrap gap-2 mt-2">
                    <flux:button wire:click="setFilter('thisWeek')" size="xs"
                        variant="{{ $activeFilter === 'thisWeek' ? 'primary' : 'ghost' }}">
                        {{__('This Week')}}
                    </flux:button>
                    <flux:button wire:click="setFilter('next7Days')" size="xs"
                        variant="{{ $activeFilter === 'next7Days' ? 'primary' : 'ghost' }}">
                        {{__('Next 7 Days')}}
                    </flux:button>
                    <flux:button wire:click="setFilter('next30Days')" size="xs"
                        variant="{{ $activeFilter === 'next30Days' ? 'primary' : 'ghost' }}">
                        {{__('Next 30 Days')}}
                    </flux:button>
                </div>
            </div>

            <!-- Other Filters and Controls -->
            <div class="flex items-end justify-end gap-4">
                <flux:select wire:model.live="activePriorityFilter" label="{{ __('Priority') }}" class="w-full md:w-40">
                    <flux:select.option value="">{{ __('All Priorities') }}</flux:select.option>
                    <flux:select.option value="CRITICAL">{{ __('Critical') }}</flux:select.option>
                    <flux:select.option value="HIGH">{{ __('High') }}</flux:select.option>
                    <flux:select.option value="MEDIUM">{{ __('Medium') }}</flux:select.option>
                    <flux:select.option value="LOW">{{ __('Low') }}</flux:select.option>
                    <flux:select.option value="NONE">{{ __('None') }}</flux:select.option>
                </flux:select>
                
                <flux:select wire:model.live="tableLength" label="{{ __('Items Per Page') }}" class="w-full md:w-32">
                    <flux:select.option value="5">5 {{ __('items') }}</flux:select.option>
                    <flux:select.option value="10">10 {{ __('items') }}</flux:select.option>
                    <flux:select.option value="25">25 {{ __('items') }}</flux:select.option>
                    <flux:select.option value="50">50 {{ __('items') }}</flux:select.option>
                    <flux:select.option value="100">100 {{ __('items') }}</flux:select.option>
                </flux:select>
            </div>
        </div>
    </flux:card>

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

    <!-- Add/Edit Task Modal -->
    <flux:modal name="addTask" class="w-full max-w-2xl" @close="closeTaskWindow()">
        <flux:heading class="mb-4">{{ $editItem ? __('Edit Task') : __('Add New Task') }}</flux:heading>
        
        <form wire:submit="create" class="space-y-4">
            <flux:input label="{{ __('Title') }}" wire:model="title" placeholder="{{ __('Enter task title') }}" />
            
            <flux:textarea 
                label="{{ __('Description') }}" 
                wire:model="desc" 
                placeholder="{{ __('Enter task description') }}"
                rows="4"
            />
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:date-picker 
                    label="{{ __('Due Date') }}" 
                    wire:model="due"
                    hint="{{ __('When should this task be completed?') }}"
                />
                
                <flux:select 
                    label="{{ __('Priority') }}" 
                    wire:model="priority"
                    hint="{{ __('How important is this task?') }}"
                >
                    <flux:select.option value="CRITICAL">{{ __('Critical') }}</flux:select.option>
                    <flux:select.option value="HIGH">{{ __('High') }}</flux:select.option>
                    <flux:select.option value="MEDIUM">{{ __('Medium') }}</flux:select.option>
                    <flux:select.option value="LOW">{{ __('Low') }}</flux:select.option>
                    <flux:select.option value="NONE">{{ __('None') }}</flux:select.option>
                </flux:select>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4 border-t">
                <flux:button variant="ghost" wire:click="closeTaskWindow" type="button">
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary">
                    {{ $editItem ? __('Update Task') : __('Create Task') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
