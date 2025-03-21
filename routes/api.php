<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Task;
use Illuminate\Support\Str;
use App\Enums\PriorityLevel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// API v1 Routes
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    
    // Get all tasks with optional filtering
    Route::get('/tasks', function (Request $request) {
        $query = $request->user()->tasks();
        
        // Apply search filter
        if ($request->has('search')) {
            $needle = $request->input('search');
            $query->where(function ($q) use ($needle) {
                $q->where('title', 'like', "%{$needle}%")
                  ->orWhere('desc', 'like', "%{$needle}%");
            });
        }
        
        // Apply status filter
        if ($request->has('status')) {
            $status = $request->input('status');
            
            switch ($status) {
                case 'completed':
                    $query->where('completed', true);
                    break;
                case 'uncompleted':
                    $query->where('completed', false);
                    break;
                case 'overdue':
                    $query->where('due', '<', now())
                          ->where('completed', false);
                    break;
                case 'today':
                    $query->whereDate('due', now());
                    break;
                case 'this_week':
                    $query->where('due', '>=', now()->startOfWeek())
                          ->where('due', '<=', now()->endOfWeek());
                    break;
                case 'this_month':
                    $query->where('due', '>=', now()->startOfMonth())
                          ->where('due', '<=', now()->endOfMonth());
                    break;
                case 'this_year':
                    $query->where('due', '>=', now()->startOfYear())
                          ->where('due', '<=', now()->endOfYear());
                    break;
                case 'next_7_days':
                    $query->where('due', '>=', now())
                          ->where('due', '<=', now()->addDays(7));
                    break;
                case 'next_30_days':
                    $query->where('due', '>=', now())
                          ->where('due', '<=', now()->addDays(30));
                    break;
                case 'next_90_days':
                    $query->where('due', '>=', now())
                          ->where('due', '<=', now()->addDays(90));
                    break;
            }
        }
        
        // Apply priority filter
        if ($request->has('priority')) {
            $query->where('priority', $request->input('priority'));
        }
        
        // Apply sorting
        $sortBy = $request->input('sort_by', 'due');
        $sortDirection = $request->input('sort_direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);
        
        // Pagination
        $perPage = $request->input('per_page', 10);
        
        return response()->json([
            'tasks' => $query->paginate($perPage),
        ]);
    });

    // Export tasks
    Route::get('/tasks/export', function (Request $request) {
        // Get authenticated user's tasks
        $tasks = $request->user()->tasks()->get();

        // Create backup data structure with metadata
        $backupData = [
            'metadata' => [
                'version'    => '1.0',
                'created_at' => now()->toIso8601String(),
                'user_id'    => $request->user()->id,
                'user_email' => $request->user()->email,
                'task_count' => $tasks->count(),
            ],
            'tasks'    => $tasks->toArray()
        ];

        return response()->json($backupData);
    });

    // Import tasks
    Route::post('/tasks/import', function (Request $request) {
        $validated = $request->validate([
            'data'             => 'required|array',
            'data.metadata'    => 'required|array',
            'data.tasks'       => 'required|array',
            'duplicate_action' => 'required|in:skip,overwrite,keep_both',
        ]);

        $backupData = $validated['data'];
        $duplicateAction = $validated['duplicate_action'];

        $importCount = 0;
        $skippedCount = 0;
        $updatedCount = 0;
        $user = $request->user();
        $existingTasksByUuid = $user->tasks()->pluck('id', 'uuid')->toArray();

        foreach ($backupData['tasks'] as $taskData) {
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
                switch ($duplicateAction) {
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
        Log::debug("User {$user->id} imported {$importCount} tasks, updated {$updatedCount}, skipped {$skippedCount}");

        return response()->json([
            'message' => 'Tasks imported successfully',
            'stats'   => [
                'imported' => $importCount,
                'updated'  => $updatedCount,
                'skipped'  => $skippedCount,
            ]
        ]);
    });

    // Get a single task
    Route::get('/tasks/{id}', function (Request $request, $id) {
        $task = $request->user()->tasks()->findOrFail($id);
        
        return response()->json([
            'task' => $task,
        ]);
    });
    
    // Create a new task
    Route::post('/tasks', function (Request $request) {
        $validated = $request->validate([
            'title' => 'required|min:5|max:250',
            'desc' => 'required',
            'priority' => ['required', Rule::enum(PriorityLevel::class)],
            'due' => 'nullable|date',
        ]);
        
        $task = $request->user()->tasks()->create([
            'title' => $validated['title'],
            'slug' => Str::of($validated['title'])->slug(),
            'desc' => $validated['desc'],
            'priority' => $validated['priority'],
            'due' => $validated['due'] ?? null,
            'completed' => false,
        ]);
        
        return response()->json([
            'message' => 'Task created successfully',
            'task' => $task,
        ], 201);
    });
    
    // Update a task
    Route::put('/tasks/{id}', function (Request $request, $id) {
        $task = $request->user()->tasks()->findOrFail($id);
        
        $validated = $request->validate([
            'title' => 'sometimes|required|min:5|max:250',
            'desc' => 'sometimes|required',
            'priority' => ['sometimes', 'required', Rule::enum(PriorityLevel::class)],
            'due' => 'sometimes|nullable|date',
            'completed' => 'sometimes|boolean',
        ]);
        
        // Only update the slug if title is changing
        if (isset($validated['title'])) {
            $validated['slug'] = Str::of($validated['title'])->slug();
        }
        
        $task->update($validated);
        
        return response()->json([
            'message' => 'Task updated successfully',
            'task' => $task,
        ]);
    });
    
    // Delete a task
    Route::delete('/tasks/{id}', function (Request $request, $id) {
        $task = $request->user()->tasks()->findOrFail($id);
        $task->delete();
        
        return response()->json([
            'message' => 'Task deleted successfully',
        ]);
    });
    
    // Toggle task completion status
    Route::patch('/tasks/{id}/toggle-completion', function (Request $request, $id) {
        $task = $request->user()->tasks()->findOrFail($id);
        
        $task->update([
            'completed' => !$task->completed,
            'completed_at' => !$task->completed ? now() : null,
        ]);
        
        return response()->json([
            'message' => 'Task completion toggled successfully',
            'task' => $task,
        ]);
    });
});