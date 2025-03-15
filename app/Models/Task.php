<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Enums\PriorityLevel; // Make sure the namespace is correct
use Livewire\WithPagination;

class Task extends Model
{
    use HasFactory, WithPagination;

    protected $fillable = [
        'title',
        'desc',
        'due',
        'completed_at',
        'priority',
        'user_id',
        'uuid',
        'completed',
        'slug'
    ];

    // Add proper casting for the priority field using the correct namespace
    protected $casts = [
        'priority' => PriorityLevel::class,
        'due' => 'datetime',
        'completed_at' => 'datetime',
        'completed' => 'boolean',
    ];

    // Boot method to automatically generate UUIDs
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($task) {
            $task->uuid = $task->uuid ?? Str::uuid();
        });
    }

    // Your existing relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}