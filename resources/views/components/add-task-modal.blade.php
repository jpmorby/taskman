<!-- Add/Edit Task Modal -->
<flux:modal name="addTask" class="w-full max-w-2xl" @close="closeTaskWindow()">
    <flux:heading class="mb-4">{{ $editItem ? __('Edit Task') : __('Add New Task') }}</flux:heading>

    <form wire:submit="create" class="space-y-4">
        <flux:input label="{{ __('Title') }}" wire:model="title" placeholder="{{ __('Enter task title') }}" />

        <flux:editor label="{{ __('Description') }}" wire:model="desc" placeholder="{{ __('Enter task description') }}"
            rows="4" />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:date-picker with-today selectable-header label="{{ __('Due Date') }}" wire:model="due"
                hint="{{ __('When should this task be completed?') }}" />

            <flux:select label="{{ __('Priority') }}" wire:model="priority"
                hint="{{ __('How important is this task?') }}">
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