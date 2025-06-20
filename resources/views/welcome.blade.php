<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laravel</title>
</head>

<body>
    <div class="bg-gray-50 text-black/50 dark:bg-black dark:text-white/50">
        <img id="background" class="absolute -left-20 top-0 max-w-[877px]" src="https://laravel.com/assets/img/welcome/background.svg" alt="Laravel background" />
        <div class="relative min-h-screen flex flex-col items-center justify-center selection:bg-[#FF2D20] selection:text-white">
            <div class="relative w-full max-w-2xl px-6 lg:max-w-7xl">
                <header class="grid grid-cols-2 items-center gap-2 py-10 lg:grid-cols-3">
                    <div class="flex lg:justify-center lg:col-start-2">
                        <svg class="h-12 w-auto text-white lg:h-16 lg:text-[#FF2D20]" viewBox="0 0 62 65" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <!-- SVG Content -->
                        </svg>
                    </div>
                    @if (\Illuminate\Support\Facades\Route::has('login'))
                    <nav class="-mx-3 flex flex-1 justify-end">
                        @auth
                        <a href="{{ url('/dashboard') }}" class="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white">
                            Dashboard
                        </a>
                        @else
                        <a href="{{ route('login') }}" class="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white">
                            Login
                        </a>
                        @if (\Illuminate\Support\Facades\Route::has('register'))
                        <a href="{{ route('register') }}" class="ml-4 rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white">
                            Register
                        </a>
                        @endif
                        @endauth
                    </nav>
                    @endif
                </header>
                <main class="text-center">
                    <h1 class="text-4xl font-bold dark:text-white">Welcome to Laravel</h1>
                    <p class="mt-4 text-lg text-gray-700 dark:text-gray-300">
                        Your journey with Laravel starts here. Explore the documentation and get started!
                    </p>
                    <div class="mt-6 flex justify-center space-x-4">
                        <a href="https://laravel.com/docs" target="_blank" class="text-[#FF2D20] hover:underline">
                            Documentation
                        </a>
                        <a href="https://laracasts.com" target="_blank" class="text-[#FF2D20] hover:underline">
                            Laracasts
                        </a>
                        <a href="https://laravel-news.com" target="_blank" class="text-[#FF2D20] hover:underline">
                            News
                        </a>
                    </div>
                </main>
            </div>
        </div>
    </div>
</body>

</html>