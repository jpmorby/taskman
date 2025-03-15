<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Task;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Use string with length 36 instead of uuid type
            $table->string('uuid', 36)
                ->nullable()
                ->after('id')
                ->index();
        });

        // Generate UUIDs for existing tasks
        Task::whereNull('uuid')->each(function (Task $task) {
            $task->uuid = (string) Str::uuid();
            $task->save();
        });

        // Make UUID required after populating existing records
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('uuid', 36)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};