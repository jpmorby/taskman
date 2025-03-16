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
