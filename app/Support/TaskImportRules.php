<?php

namespace App\Support;

use App\Enums\PriorityLevel;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/*
 * Shared validation and whitelisting for a task backup being imported, used by
 * both the API route and the TaskBackupManager Livewire component so the two
 * paths accept exactly the same task payload.
 */

class TaskImportRules
{
    /**
     * Rules for the list of tasks, keyed for wherever that list sits in the payload.
     *
     * These mirror the task creation rules so an import cannot write values the
     * application itself would reject.
     *
     * @return array<string, mixed>
     */
    public static function tasks(string $key): array
    {
        return [
            $key => ['required', 'array'],
            $key.'.*' => ['required', 'array'],
            $key.'.*.title' => ['required', 'string', 'min:5', 'max:250'],
            $key.'.*.desc' => ['required', 'string'],
            $key.'.*.priority' => ['nullable', Rule::enum(PriorityLevel::class)],
            $key.'.*.due' => ['nullable', 'date'],
            $key.'.*.completed' => ['nullable', 'boolean'],
            $key.'.*.uuid' => ['nullable', 'string', 'uuid'],
        ];
    }

    /**
     * The attributes an import is allowed to write, taken from a validated task.
     *
     * Anything else in the backup file (id, timestamps, user_id, completed_at,
     * media, ...) is dropped rather than mass assigned.
     *
     * @param  array<string, mixed>  $taskData
     * @return array<string, mixed>
     */
    public static function attributes(array $taskData): array
    {
        $attributes = [
            'title' => $taskData['title'],
            'desc' => $taskData['desc'],
            'slug' => Str::of($taskData['title'])->slug(),
            'due' => $taskData['due'] ?? null,
        ];

        if (isset($taskData['priority'])) {
            $attributes['priority'] = $taskData['priority'];
        }

        if (isset($taskData['completed'])) {
            $attributes['completed'] = (bool) $taskData['completed'];
        }

        return $attributes;
    }
}
