<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Task Manager - Simple & Efficient Task Management</title>

    <x-seo-meta
        title="Simple & Efficient Task Management"
        description="Task Manager is a simple and efficient task management tool that helps you stay organized and productive. Create, manage, and track your tasks with ease."
        :canonical="url('/')"
    />

    <x-count-stats />
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/@heroicons/vue@1.0.6/outline.js"></script>
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/github-fork-ribbon-css/0.2.3/gh-fork-ribbon.min.css" />
    <style>
        .github-fork-ribbon:before {
            background-color: #2cbe4e;
        }
    </style>
    </head>
    
    <body
        class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col">
        <a class="github-fork-ribbon" href="https://github.com/jpmorby/taskman/" data-ribbon="Fork me on GitHub"
            title="Fork me on GitHub">Fork me on GitHub</a>
        <header class="w-full lg:max-w-4xl max-w-[335px] text-sm mb-6 not-has-[nav]:hidden">
            @if (Route::has('login'))
                <nav class="flex items-center justify-end gap-4">
                    @auth
                        <a href="{{ url('/dashboard') }}"
                            class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] border-[#19140035] hover:border-[#1915014a] border text-[#1b1b18] dark:border-[#3E3E3A] dark:hover:border-[#62605b] rounded-sm text-sm leading-normal">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                            class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] text-[#1b1b18] border border-transparent hover:border-[#19140035] dark:hover:border-[#3E3E3A] rounded-sm text-sm leading-normal">
                            Log in / Sign up
                        </a>
                    @endauth
                </nav>
            @endif
        </header>
        <div
            class="flex items-center justify-center w-full transition-opacity opacity-100 duration-750 lg:grow starting:opacity-0">
            <main class="flex max-w-[335px] w-full flex-col-reverse lg:max-w-4xl lg:flex-row">
                @php
                    use App\Models\User;
                    use App\Models\Task;
                    $userCount = User::count();
                    $taskCount = Task::count();
                @endphp
                <flux:table>
                    <flux:table.rows>
                        <flux:table.row>
                            <flux:table.cell>Current Registered Users</flux:table.cell>
                            <flux:table.cell>{{  $userCount }}</flux:table.cell>
                        </flux:table.row>
                        <flux:table.row>
                            <flux:table.cell>Tracked Tasks </flux:table.cell>
                            <flux:table.cell>{{  $taskCount }}</flux:table.cell>
                        </flux:table.row>
                    </flux:table.rows>
                </flux:table>
                <flux:spacer class="hidden lg:block" />
                <div class="flex flex-col gap-4">
                    <h1 class="text-3xl font-bold text-center lg:text-left">Task Manager</h1>
                    <p class="text-sm text-center lg:text-left">
                        Task Manager is a simple and efficient task management tool that helps you stay organized and
                        productive.
                    </p>
                    <p class="text-sm text-center lg:text-left">
                        It allows you to create, manage, and track your tasks with ease.
                    </p>
                </div>
            </main>
        </div>
    
        @if (Route::has('login'))
            <div class="h-14.5 hidden lg:block"></div>
        @endif
    </body>

</html>