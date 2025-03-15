<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

use Flux\Flux;

use App\Models\Task;

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
                'tasks' => $tasks->toArray()
            ];

            // Convert to JSON
            $jsonContent = json_encode($backupData, JSON_PRETTY_PRINT);

            // Generate filename with timestamp
            $filename = 'taskman_backup_' . date('Y-m-d_His') . '.json';

            // Store temporary file
            $path = 'exports/' . $filename;
            Storage::put($path, $jsonContent);

            Log::info('User ' . Auth::id() . ' exported ' . $tasks->count() . ' tasks');

            // Download the file
            return Storage::download($path, $filename, [
                'Content-Type' => 'application/json',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);

        } catch (\Exception $e) {
            Log::error('Task export failed: ' . $e->getMessage());
            Flux::toast('Failed to export tasks: ' . $e->getMessage(), 'danger');
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
            if (!isset($this->backupData['metadata']) || !isset($this->backupData['tasks'])) {
                Flux::toast('Invalid backup file format.', 'danger');
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
                    $existingTask = Task::where('uuid', $taskData['uuid'])->first();
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
            Log::error('Failed to validate backup: ' . $e->getMessage());
            Flux::toast('Error validating backup file: ' . $e->getMessage(), 'danger');
            return false;
        }
    }

    public function processImport()
    {
        Log::info("processImport called");
        if ($this->backupData === null) {
            Log::info('No backup data loaded for import.');
            Flux::toast('No backup file loaded.', 'danger');
            return;
        }

        try {
            // Import tasks
            $importCount = 0;
            $skippedCount = 0;
            $updatedCount = 0;
            $user = Auth::user();
            $existingTasksByUuid = $user->tasks()->pluck('id', 'uuid')->toArray();

            foreach ($this->backupData['tasks'] as $taskData) {
                // Always remove the database ID
                unset($taskData['id']);
                unset($taskData['created_at']);
                unset($taskData['updated_at']);

                // Ensure the task is assigned to the current user
                $taskData['user_id'] = $user->id;

                // Check for a UUID
                $uuid = $taskData['uuid'] ?? Str::uuid()->toString();
                $taskData['uuid'] = $uuid;

                // Check if this UUID already exists in the user's tasks
                if (array_key_exists($uuid, $existingTasksByUuid)) {
                    // Handle based on duplicate action
                    switch ($this->duplicateAction) {
                        case 'skip':
                            $skippedCount++;
                            continue 2; // Skip this task

                        case 'overwrite':
                            // Update existing task
                            $existingTask = Task::find($existingTasksByUuid[$uuid]);
                            $existingTask->update($taskData);
                            $updatedCount++;
                            break;

                        case 'keep_both':
                            // Create as a new task with a new UUID
                            $taskData['uuid'] = Str::uuid()->toString();
                            $user->tasks()->create($taskData);
                            $importCount++;
                            break;
                    }
                } else {
                    // Create new task with the original UUID
                    $user->tasks()->create($taskData);
                    $importCount++;
                }
            }

            // Log results
            Log::info("User {$user->id} imported {$importCount} tasks, updated {$updatedCount}, skipped {$skippedCount}");

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
            Flux::toast($message, 'success');

        } catch (\Exception $e) {
            Log::error('Task import failed: ' . $e->getMessage());
            Flux::toast('Failed to import tasks: ' . $e->getMessage(), 'danger');
        }
    }

    public function cancelImport()
    {
        $this->reset(['backupFile', 'backupData', 'duplicateFound', 'potentialDuplicates']);
        Flux::modal('import-tasks')->close();
        Flux::modal('resolve-duplicates')->close();
        Flux::toast('Import canceled.', 'info');
    }
}