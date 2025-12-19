<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Avinash-EYE') }} - {{ $title ?? 'Media Analysis & Semantic Search' }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;600;700&family=Roboto:wght@300;400;500;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Livewire Styles -->
    @livewireStyles
</head>
<body class="antialiased">
    
    @auth
        <!-- Main App Shell -->
        <div x-data="{ 
            sidebarOpen: window.innerWidth >= 1024,
            searchFocused: false,
            searchQuery: '',
            showNotifications: false
        }" 
        @resize.window="if (window.innerWidth < 1024) sidebarOpen = false"
        class="min-h-screen bg-surface-variant">
            
            <!-- Top App Bar -->
            <header class="fixed top-0 left-0 right-0 z-[100] bg-white border-b border-outline shadow-sm">
                <div class="flex items-center h-16 px-4 gap-4">
                    
                    <!-- Menu Button & Logo -->
                    <div class="flex items-center gap-2">
                        <button @click="sidebarOpen = !sidebarOpen" 
                                class="p-2 text-gray-700 hover:bg-gray-100 rounded-full transition-colors duration-200 lg:hidden">
                            <span class="material-symbols-outlined text-2xl">menu</span>
                        </button>
                        
                        <a href="{{ route('gallery') }}" class="flex items-center gap-2 text-gray-900 hover:text-primary-600 transition-colors duration-200">
                            <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-primary-700 rounded-lg flex items-center justify-center shadow-md3-1">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </div>
                            <span class="text-xl font-display font-semibold hidden sm:block">Avinash-EYE</span>
                        </a>
                    </div>

                    <!-- Search Bar -->
                    <div class="flex-1 max-w-2xl mx-auto">
                        @php
                            $currentRoute = request()->route()->getName();
                            $searchAction = $currentRoute === 'documents' ? route('documents') : route('gallery');
                            $searchPlaceholder = $currentRoute === 'documents' ? 'Search files...' : 'Search photos...';
                        @endphp
                        <form action="{{ $searchAction }}" method="GET" class="relative">
                            <input 
                                type="text" 
                                name="q"
                                x-model="searchQuery"
                                @focus="searchFocused = true"
                                @blur="searchFocused = false"
                                placeholder="{{ $searchPlaceholder }}"
                                class="w-full px-4 py-2.5 pl-12 pr-12 bg-surface-variant hover:bg-white focus:bg-white border-2 border-transparent focus:border-primary-500 rounded-full text-sm transition-all duration-200 focus:outline-none focus:shadow-md3-2"
                            >
                            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-xl pointer-events-none">
                                search
                            </span>
                            <button type="button" 
                                    x-show="searchQuery.length > 0"
                                    @click="searchQuery = ''"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-gray-500 hover:bg-gray-100 rounded-full transition-colors duration-200">
                                <span class="material-symbols-outlined text-lg">close</span>
                            </button>
                        </form>
                    </div>

                    <!-- Right Actions -->
                    <div class="flex items-center gap-1">
                        <!-- Notifications -->
                        <div class="relative">
                            <button @click="showNotifications = !showNotifications"
                                    class="relative p-2 text-gray-700 hover:bg-gray-100 rounded-full transition-colors duration-200">
                                <span class="material-symbols-outlined text-2xl">notifications</span>
                                <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full"></span>
                            </button>
                        </div>

                        <!-- Settings -->
                        <a href="{{ route('settings') }}" 
                           class="p-2 text-gray-700 hover:bg-gray-100 rounded-full transition-colors duration-200"
                           title="Settings">
                            <span class="material-symbols-outlined text-2xl">settings</span>
                        </a>

                        <!-- User Profile -->
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" 
                                    class="flex items-center gap-2 p-1.5 hover:bg-gray-100 rounded-full transition-colors duration-200">
                                <div class="w-8 h-8 bg-primary-600 rounded-full flex items-center justify-center text-white text-sm font-medium">
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                </div>
                            </button>

                            <div x-show="open" 
                                 @click.away="open = false"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-md3-3 overflow-hidden z-[200]">
                                
                                <div class="p-4 border-b border-outline">
                                    <p class="font-medium text-gray-900">{{ auth()->user()->name }}</p>
                                    <p class="text-sm text-gray-500">{{ auth()->user()->email }}</p>
                                </div>

                                <div class="py-2">
                                    <a href="{{ route('settings') }}" 
                                       class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                                        <span class="material-symbols-outlined text-xl">settings</span>
                                        <span>Settings</span>
                                    </a>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" 
                                                class="w-full flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors duration-200">
                                            <span class="material-symbols-outlined text-xl">logout</span>
                                            <span>Sign out</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Sidebar Navigation -->
            <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" 
                   class="fixed left-0 top-16 bottom-0 w-64 bg-white border-r border-outline transition-transform duration-300 ease-in-out z-40 overflow-y-auto">
                
                <nav class="p-2">
                    @php
                        $currentRoute = request()->route()->getName();
                        $navItems = [
                            ['route' => 'gallery', 'icon' => 'photo_library', 'label' => 'Photos'],
                            ['route' => 'documents', 'icon' => 'description', 'label' => 'Documents'],
                            ['route' => 'collections', 'icon' => 'folder', 'label' => 'Collections'],
                            ['route' => 'people-and-pets', 'icon' => 'face', 'label' => 'People & Pets'],
                            ['route' => 'instant-upload', 'icon' => 'upload', 'label' => 'Upload'],
                        ];
                        $systemItems = [
                            ['route' => 'system-monitor', 'icon' => 'monitoring', 'label' => 'System Monitor'],
                            ['route' => 'settings', 'icon' => 'settings', 'label' => 'Settings'],
                        ];
                    @endphp

                    <div class="space-y-1">
                        @foreach ($navItems as $item)
                            <a href="{{ route($item['route']) }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200
                                      {{ $currentRoute === $item['route'] 
                                         ? 'bg-primary-50 text-primary-600' 
                                         : 'text-gray-700 hover:bg-gray-100' }}">
                                <span class="material-symbols-outlined text-xl">{{ $item['icon'] }}</span>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>

                    <div class="h-px bg-outline my-4"></div>

                    <div class="space-y-1">
                        <p class="px-3 py-2 text-xs font-medium text-gray-500 uppercase tracking-wider">
                            System
                        </p>
                        @foreach ($systemItems as $item)
                            <a href="{{ route($item['route']) }}" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200
                                      {{ $currentRoute === $item['route'] 
                                         ? 'bg-primary-50 text-primary-600' 
                                         : 'text-gray-700 hover:bg-gray-100' }}">
                                <span class="material-symbols-outlined text-xl">{{ $item['icon'] }}</span>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </nav>
            </aside>

            <!-- Sidebar Overlay (Mobile) -->
            <div x-show="sidebarOpen" 
                 @click="sidebarOpen = false"
                 x-transition:enter="transition-opacity ease-linear duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-linear duration-300"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-black/50 z-30 lg:hidden"></div>

            <!-- Main Content -->
            <main class="pt-16 lg:pl-64 transition-all duration-300">
                <div class="animate-fade-in">
                    {{ $slot }}
                </div>
            </main>
        </div>
    @else
        <!-- Guest Layout (Login/Register) -->
        <div class="min-h-screen">
            {{ $slot }}
        </div>
    @endauth

    <!-- Livewire Scripts (includes Alpine.js) -->
    @livewireScripts
</body>
</html>
