<?php

namespace App\Livewire;

use App\Models\Task;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use App\Enums\PriorityLevel;

class TodoList extends Component
{
    use WithPagination;

    #[Rule('required|min:5|max:250')]
    public $title;

    #[Rule('required')]
    public string $desc;

    #[Rule('required')]
    public $priority;

    public string $slug;

    public array $media;

    #[Rule('date|required')]
    public $due;
    
    public int $tableLength = 10;

    public bool $completed = false;

    public $editItem, $viewItem, $viewTask;

    public $user_id;

    #[Rule('string|min:3')]
    public $needle = '';

    public $searchResults;

    public $sortBy = 'due';

    public $sortDirection = 'asc';

    // Add this property to your class
    public string $activeFilter = 'active';

    public function mount()
    {
        $this->user_id = Auth::id();

    }

    public function sort($index)
    {
        if ($this->sortBy === $index) {
            // If clicking the same column, toggle direction
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            // New column, set as the sort field and default to ascending
            $this->sortBy = $index;
            $this->sortDirection = 'asc';
        }
    }

    public function addTask()
    {
        Log::info('addTask');
        $this->priority = PriorityLevel::LOW;

        Flux::modal('addTask')->show();

    }

    public function create()
    {
        if ($this->editItem) {
            // Validate with explicit error catching
            try {
                $validated = $this->validate();
            } catch (\Illuminate\Validation\ValidationException $e) {
                // Log validation errors
                Log::error('Validation failed: ' . json_encode($e->errors()));
                throw $e;
            }

            // If we are editing an existing task, we don't want to create a new one
            Log::info('create - editItem');

            try {
                Auth::user()->tasks()->findOrFail($this->editItem->id)->update([
                    'title'    => $this->title,
                    'slug'     => Str::of($this->title)->slug(),
                    'desc'     => $this->desc,
                    'due'      => $this->due,
                    'priority' => $this->priority,
                ]);

                $this->editItem = null;
                $this->reset(['title', 'desc', 'due', 'priority', 'slug']);
                Flux::modal('addTask')->close();
                $this->dispatch('task-updated');
            } catch (\Exception $e) {
                Log::error('Update failed: ' . $e->getMessage());
                throw $e;
            }

            return;
        }

        Log::info('create');

        try {
            // Set slug before validation
            $this->slug = Str::of($this->title)->slug();
            Log::debug("Slug: $this->slug");

            // Validate with explicit error catching
            $validated = $this->validate();

            Auth::user()->tasks()->create([
                'user_id'   => Auth::id(),
                'title'     => $this->title,
                'slug'      => $this->slug,
                'desc'      => $this->desc,
                'due'       => $this->due,
                'priority'  => $this->priority,
                'completed' => false,
            ]);

            $this->reset(['title', 'desc', 'due', 'priority', 'slug']);
            Flux::modal('addTask')->close();
            $this->dispatch('task-created');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log validation errors
            Log::error('Validation failed: ' . json_encode($e->errors()));
            throw $e;

        } catch (\Exception $e) {
            Log::error('Create task failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function edit($id)
    {
        Log::info("Edit ($id)");

        $this->editItem = Task::findOrFail($id);

        $this->title = $this->editItem->title;
        $this->desc = $this->editItem->desc;
        $this->due = $this->editItem->due;
        $this->priority = $this->editItem->priority;

        Flux::modal('addTask')->show();
    }

    public function update()
    {
        // edit/update an existing task
        Log::info('update');

        $this->dispatch('task-updated');
    }

    public function delete($id)
    {
        Log::info("Delete ($id)");

        Task::findOrFail($id)->delete();

        $this->dispatch('task-deleted');

    }

    public function updated(string $name, mixed $value): void
    {
        // dd($name, $value);
    }

    public function toggleCompleted($id)
    {
        $task = Task::findOrFail($id);

        $task->update([
            'completed' => ! $task->completed,
        ]);
    }

    #[Computed()]
    public function tasks(): LengthAwarePaginator
    {
        // Get the right method name based on the activeFilter property
        $methodName = 'show' . ucfirst($this->activeFilter);

        // If the method exists, use it, otherwise fall back to showAll
        if (method_exists($this, $methodName)) {
            return $this->$methodName();
        }

        return $this->showAll();
    }

    public function render()
    {
        return view('livewire.todo-list');
    }

    public function getListeners(): array
    {
        return [
            'task-updated' => '$refresh',
            'task-created' => '$refresh',
            'task-deleted' => '$refresh',
        ];
    }

    private function baseQuery()
    {
        return Auth::user()->tasks()
            ->where(function ($query) {
                $query->where('title', 'like', '%' . $this->needle . '%')
                      ->orWhere('desc', 'like', '%' . $this->needle . '%');
            });
    }

    public function showAll()
    {
        return $this->baseQuery()
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->tableLength);
    }

    public function showActive()
    {
        return $this->baseQuery()
            ->where('completed', false)
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->tableLength);
    }

    public function showCompleted()
    {
        return $this->baseQuery()
            ->where('completed', true)
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->tableLength);
    }

    public function showUncompleted()
    {
        return $this->baseQuery()
            ->where('completed', false)
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->tableLength);
    }

    public function showOverdue()
    {
        return $this->baseQuery()
            ->where('due', '<', now())
            ->where('completed', false) 
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->tableLength);
    }

    public function showToday()
    {
        return $this->baseQuery()
            ->where('due', now())
            ->where( 'completed', false)
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->tableLength);
    }
    public function showThisWeek()
    {
        return $this->baseQuery()
            ->where('due', '>=', now()->startOfWeek())
            ->where('due', '<=', now()->endOfWeek())
            ->where( 'completed', false)
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->tableLength);
    }
    public function showThisMonth()
    {
        return $this->baseQuery()
            ->where('due', '>=', now()->startOfMonth())
            ->where('due', '<=', now()->endOfMonth())
            ->where('completed', false)
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->tableLength);
    }
    public function showThisYear()
    {
        return $this->baseQuery()
            ->where('due', '>=', now()->startOfYear())
            ->where('due', '<=', now()->endOfYear())
            ->where('completed', false)
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->tableLength);
    }
    public function showNext7Days()
    {
        return $this->baseQuery()
            ->where('due', '>=', now())
            ->where('due', '<=', now()->addDays(7))
            ->where('completed', false)
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->tableLength);
    }
    public function showNext30Days()
    {
        return $this->baseQuery()
            ->where('due', '>=', now())
            ->where('due', '<=', now()->addDays(30))
            ->where('completed', false)
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->tableLength);
    }
    public function showNext90Days()
    {
        return $this->baseQuery()
            ->where('due', '>=', now())
            ->where('due', '<=', now()->addDays(90))
            ->where('completed', false)
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->tableLength);
    }

    public function setFilter(string $filter)
    {
        $this->activeFilter = $filter;
        // Reset pagination when changing filters
        $this->resetPage();
    }

    public function updatedNeedle()
    {
        // Reset pagination when search term changes
        $this->resetPage();
    }

    public function showCard($id) {
        Log::info("Show Card ($id)");
        
        $this->viewItem = Task::findOrFail($id);

        $this->title = $this->viewItem->title;
        $this->desc = $this->viewItem->desc;
        $this->due = $this->viewItem->due;
        $this->priority = $this->viewItem->priority;

        Flux::modal('addTask')->show();
    }

    public function closeTaskWindow() {
        Log::info("Close Task Window");
        $this->viewItem = null;
        $this->editItem = null;
        Flux::modals()->close();

        $this->reset(['title', 'desc', 'due', 'priority', 'slug']);
        Flux::modal('addTask')->close();
    }

}