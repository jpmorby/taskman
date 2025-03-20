<div>
    <!-- Export Button -->
    <flux:button wire:click="exportTasks" wire:loading.attr="disabled" wire:target="exportTasks" icon="arrow-down-tray"
        variant="ghost" class="mr-2">
        <span wire:loading.remove wire:target="exportTasks">Export Tasks</span>
        <span wire:loading wire:target="exportTasks">Exporting...</span>
    </flux:button>

    <!-- Import Button -->
    <flux:button x-on:click="$flux.modal('import-tasks').show()" icon="arrow-up-tray" variant="ghost">
        Import Tasks
    </flux:button>

    <!-- Import Modal -->
    <flux:modal name="import-tasks" class="max-w-md">
        <flux:heading class="mb-4">Import Tasks from Backup</flux:heading>

        <form wire:submit.prevent="validateBackup" class="space-y-4">
            <flux:input type="file" wire:model="backupFile" label="Select Backup File" accept=".json"
                hint="Upload your previously exported task backup (JSON format)" required
                error="{{ $errors->first('backupFile') }}" />

            <div class="flex justify-between pt-4">
                <flux:button type="button" variant="ghost" x-on:click="$flux.modal('import-tasks').close()">
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="validateBackup">
                    <span wire:loading.remove wire:target="validateBackup">Import Tasks</span>
                    <span wire:loading wire:target="validateBackup">Validating...</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Duplicate Resolution Modal -->
    <flux:modal name="resolve-duplicates" class="max-w-lg">
        <flux:heading class="mb-4">Duplicate Tasks Found</flux:heading>

        <div class="space-y-4">
            <p>
                We found {{ count($potentialDuplicates) }} tasks in your backup that already exist in your account.
                How would you like to handle these duplicate tasks?
            </p>

            <div class="space-y-3">
                <flux:radio.group wire:model="duplicateAction">
                    <flux:radio id="skip" value="skip" label="Skip duplicates (import only new tasks)" />
                    <flux:radio id="overwrite" value="overwrite" label="Overwrite existing tasks with imported data" />
                    <flux:radio id="keep_both" value="keep_both" label="Keep both (create duplicate tasks)" />
                </flux:radio.group>
            </div>

            @if (count($potentialDuplicates) > 0)
                <div class="mt-4">
                    <flux:heading class="mb-2 text-sm">Duplicate Task Preview</flux:heading>
                    <div class="max-h-40 overflow-auto rounded-md border bg-gray-50 p-3 dark:bg-gray-800">
                        <table class="min-w-full">
                            <thead>
                                <tr>
                                    <th class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Title
                                    </th>
                                    <th class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        In System
                                    </th>
                                    <th class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        In Backup
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (array_slice($potentialDuplicates, 0, 3) as $duplicate)
                                    <tr>
                                        <td class="text-sm">{{ $duplicate['existing']['title'] }}</td>
                                        <td class="text-sm">
                                            {{ \Carbon\Carbon::parse($duplicate['existing']['updated_at'])->diffForHumans() }}
                                        </td>
                                        <td class="text-sm">
                                            @if(isset($duplicate['imported']['updated_at']))
                                                {{ \Carbon\Carbon::parse($duplicate['imported']['updated_at'])->diffForHumans() }}
                                            @elseif(isset($duplicate['imported']['created_at']))
                                                {{ \Carbon\Carbon::parse($duplicate['imported']['created_at'])->diffForHumans() }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                @if (count($potentialDuplicates) > 3)
                                    <tr>
                                        <td colspan="3" class="text-center text-sm text-gray-500">
                                            And {{ count($potentialDuplicates) - 3 }} more...
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="flex justify-between border-t pt-4">
                <flux:button wire:click="cancelImport" variant="ghost">
                    Cancel
                </flux:button>
                <flux:button wire:click="processImport" variant="primary">
                    Continue Import
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
