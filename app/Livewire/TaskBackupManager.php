<?php

/*
 * (C) Jon Morby 2025.  All Rights Reserved.
 *
 */

namespace App\Livewire;

use App\Support\TaskImportRules;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

/*
 * This component is used to manage task backups.
 * It allows users to export and import tasks, handle duplicates, and validate backup files.
 *
 * @property string $backupFile
 * @property string $duplicateAction
 * @property bool $duplicateFound
 * @property array $potentialDuplicates
 * @property array|null $backupData
 */

class TaskBackupManager extends Component
{
    use WithFileUploads;

    public $backupFile;

    public $duplicateAction = 'skip'; // Options: 'skip', 'overwrite', 'keep_both'

    public $duplicateFound = false;

    public $potentialDuplicates = [];

    public $backupData = null;

    protected $rules = [
        'duplicateAction' => 'required|in:skip,overwrite,keep_both',
        'backupFile' => 'required|file|mimes:json|max:10240',
    ];

    public function render()
    {
        return view('livewire.task-backup-manager');
    }

    public function exportTasks()
    {
        try {
            // Get authenticated user's tasks
            $tasks = Auth::user()->tasks()->get();

            // Create backup data structure with metadata
            $backupData = [
                'metadata' => [
                    'version' => '1.0',
                    'created_at' => now()->toIso8601String(),
                    'user_id' => Auth::id(),
                    'user_email' => Auth::user()->email,
                    'task_count' => $tasks->count(),
                ],
                'tasks' => $tasks->toArray(),
            ];

            // Convert to JSON
            $jsonContent = json_encode($backupData, JSON_PRETTY_PRINT);

            // Generate filename with timestamp
            $filename = 'taskman_backup_'.date('Y-m-d_His').'.json';

            Log::debug('User '.Auth::id().' exported '.$tasks->count().' tasks');

            // Stream the backup straight to the browser. Nothing is written to
            // server storage, so one user's tasks can never be left on disk for
            // another user (or anyone else) to pick up.
            return response()->streamDownload(function () use ($jsonContent) {
                echo $jsonContent;
            }, $filename, [
                'Content-Type' => 'application/json',
            ]);

        } catch (\Exception $e) {
            Log::error('Task export failed: '.$e->getMessage());
            Flux::toast('Failed to export tasks: '.$e->getMessage(),
                heading: 'Failed', variant: 'danger');
        }
    }

    public function validateBackup()
    {
        $this->validate([
            'backupFile' => 'required|file|mimes:json|max:10240', // max 10MB
        ]);

        try {
            // Get file contents
            $jsonContent = $this->backupFile->get();
            $this->backupData = json_decode($jsonContent, true);

            // Validate backup data structure
            if (! isset($this->backupData['metadata']) || ! isset($this->backupData['tasks'])) {
                Flux::toast('Invalid backup file format.', heading: 'Error', variant: 'danger');

                return false;
            }

            // Check every task in the file before offering to import any of them
            if ($this->validatedTasks() === null) {
                return false;
            }

            // Check for potential duplicates by UUID
            $user = Auth::user();
            $existingTaskUuids = $user->tasks()->pluck('uuid')->toArray();

            $this->potentialDuplicates = [];
            $hasDuplicates = false;

            foreach ($this->backupData['tasks'] as $taskData) {
                if (isset($taskData['uuid']) && in_array($taskData['uuid'], $existingTaskUuids)) {
                    $hasDuplicates = true;
                    $existingTask = $user->tasks()->where('uuid', $taskData['uuid'])->first();
                    if ($existingTask) {
                        $this->potentialDuplicates[] = [
                            'existing' => $existingTask->toArray(),
                            'imported' => $taskData,
                        ];
                    }
                }
            }

            $this->duplicateFound = $hasDuplicates;

            if ($hasDuplicates) {
                // Use Flux API for modal handling
                Flux::modal('resolve-duplicates')->show();
                Flux::modal('import-tasks')->close();
            } else {
                // Proceed with import
                $this->processImport();
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to validate backup: '.$e->getMessage());
            Flux::toast('Error validating backup file: '.$e->getMessage(), heading: 'Error', variant: 'danger');

            return false;
        }
    }

    /**
     * Validate the tasks held in the loaded backup file.
     *
     * Returns the validated tasks, or null (having toasted the first problem)
     * if the file contains a task the application would not otherwise accept.
     *
     * @return array<int, array<string, mixed>>|null
     */
    protected function validatedTasks(): ?array
    {
        try {
            $validated = Validator::make(
                ['tasks' => $this->backupData['tasks'] ?? null],
                TaskImportRules::tasks('tasks')
            )->validate();
        } catch (ValidationException $e) {
            Log::debug('Backup file rejected: '.$e->validator->errors()->first());
            Flux::toast($e->validator->errors()->first(), heading: 'Invalid backup file', variant: 'danger');

            return null;
        }

        return $validated['tasks'];
    }

    public function processImport()
    {

        Log::debug('processImport called');

        if ($this->backupData === null) {
            Log::debug('No backup data loaded for import.');
            Flux::toast('No backup file loaded.', heading: 'Error', variant: 'danger');

            return;
        }

        // backupData is a client writable property, so the file contents are
        // validated again here and not only in validateBackup().
        $tasks = $this->validatedTasks();

        if ($tasks === null) {
            return;
        }

        $this->validate(['duplicateAction' => 'required|in:skip,overwrite,keep_both']);

        try {
            $user = Auth::user();
            $duplicateAction = $this->duplicateAction;

            // The whole file imports or none of it does, so a task that fails
            // to write cannot leave a half finished import behind.
            [$importCount, $updatedCount, $skippedCount] = DB::transaction(function () use ($user, $tasks, $duplicateAction) {
                $importCount = 0;
                $skippedCount = 0;
                $updatedCount = 0;
                $existingTasksByUuid = $user->tasks()->pluck('id', 'uuid')->toArray();

                foreach ($tasks as $taskData) {
                    // Only the whitelisted attributes are written; user_id comes
                    // from the relationship, never from the file.
                    $attributes = TaskImportRules::attributes($taskData);

                    $uuid = $taskData['uuid'] ?? Str::uuid()->toString();

                    // Check if this UUID already exists in the user's tasks
                    if (array_key_exists($uuid, $existingTasksByUuid)) {
                        switch ($duplicateAction) {
                            case 'skip':
                                $skippedCount++;
                                break;

                            case 'overwrite':
                                $user->tasks()->findOrFail($existingTasksByUuid[$uuid])->update($attributes);
                                $updatedCount++;
                                break;

                            case 'keep_both':
                                // Create as a new task with a new UUID
                                $attributes['uuid'] = Str::uuid()->toString();
                                $user->tasks()->create($attributes);
                                $importCount++;
                                break;
                        }

                        continue;
                    }

                    // Create new task with the original UUID
                    $attributes['uuid'] = $uuid;
                    $existingTasksByUuid[$uuid] = $user->tasks()->create($attributes)->id;
                    $importCount++;
                }

                return [$importCount, $updatedCount, $skippedCount];
            });

            // Log results
            Log::debug("User {$user->id} imported {$importCount} tasks, updated {$updatedCount}, skipped {$skippedCount}");

            // Clean up
            $this->reset(['backupFile', 'backupData', 'duplicateFound', 'potentialDuplicates']);

            // Close modals using Flux
            Flux::modal('import-tasks')->close();
            Flux::modal('resolve-duplicates')->close();

            // Refresh the task list
            $this->dispatch('task-list-refresh');

            // Show success message
            $message = "Successfully imported {$importCount} tasks";
            if ($updatedCount > 0) {
                $message .= ", updated {$updatedCount}";
            }
            if ($skippedCount > 0) {
                $message .= ", skipped {$skippedCount}";
            }
            Flux::toast($message, heading: 'Success', variant: 'success');

        } catch (\Exception $e) {
            Log::error('Task import failed: '.$e->getMessage());
            Flux::toast('Failed to import tasks: '.$e->getMessage(), heading: 'Import Failed', variant: 'danger');
        }
    }

    public function cancelImport()
    {
        $this->reset(['backupFile', 'backupData', 'duplicateFound', 'potentialDuplicates']);
        Flux::modal('import-tasks')->close();
        Flux::modal('resolve-duplicates')->close();
        Flux::toast('Import canceled.', heading: 'Cancelled', variant: 'warning');
    }
}
