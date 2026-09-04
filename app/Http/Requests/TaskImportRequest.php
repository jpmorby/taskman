<?php

namespace App\Http\Requests;

use App\Support\TaskImportRules;
use Illuminate\Foundation\Http\FormRequest;

/*
 * Validation for a task backup posted to the import endpoint. The per task
 * rules live in TaskImportRules so the Livewire import path can apply the same
 * ones without reaching into App\Http.
 */

class TaskImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge([
            'data' => ['required', 'array'],
            'data.metadata' => ['required', 'array'],
            'duplicate_action' => ['required', 'in:skip,overwrite,keep_both'],
        ], TaskImportRules::tasks('data.tasks'));
    }
}
