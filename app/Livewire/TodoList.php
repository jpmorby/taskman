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

class TodoList extends Component
{
    use WithPagination;

    #[Rule('required|min:5|max:250')]
    public $title;

    #[Rule('required')]
    public string $desc;

    #[Rule('required')]
    public $priority;

    #[Rule('required|string|max:255')]
    public string $slug;

    public array $media;

    #[Rule('date|required')]
    public $due;

    public bool $completed = false;

    public $editItem;

    public $user_id;
    // public $tasks;

    #[Rule('string|min:3')]
    public $needle = '';

    public $searchResults;

    public $sortBy = 'due';

    public $sortDirection = 'asc';

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

        Flux::modal('addTask')->show();

    }

    public function create()
    {
        Log::info('create');

        $this->slug = Str::of($this->title)->slug();

        Log::debug("Slug: $this->slug");

        $this->priority = 'LOW';

        $this->validate();

        Auth::user()->tasks()->create([
            'user_id' => Auth::id(),
            'title' => $this->title,
            'slug' => $this->slug,
            'desc' => $this->desc,
            'due' => $this->due,
            'completed' => false,
        ]);

        $this->reset('title', 'desc', 'due');

        Flux::modal('addTask')->close();

        $this->dispatch('task-created');
    }

    // public function store()
    // {
    //     $this->dispatch('task-updated');

    //     // save record into db
    //     Log::info('Store');
    // }

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
        return Auth::user()->tasks()
            ->where('title', 'like', '%'.$this->needle.'%')
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(3);
    }

    public function render()
    {
        return view('livewire.todo-list');
    }
}
