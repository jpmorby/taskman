<div>
    <flux:modal name="showItem">
        <flux:card>
            <form>
                <label name="{{ $task->title }}" />
                <flux:textarea wire:model="desc" />
                <flux:date-picker wire:model="due" label="Due Date" />
                <flux:button wire:model="update">Update</flux:button>
            </form>
        </flux:card>
    </flux:modal>
</div>