<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Avinash-EYE') }} - {{ $title ?? 'Image Analysis & Semantic Search' }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

    <!-- Styles -->
    <style>
        [x-cloak] { display: none !important; }
        
        /* Navigation loading indicator */
        .wire-loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        .wire-loading-bar {
            position: fixed;
            top: 0;
            left: 0;
            height: 3px;
            background: var(--primary-color);
            z-index: 9999;
            transition: width 0.3s ease;
            width: 0%;
        }
        
        .wire-loading-bar.loading {
            width: 100%;
            animation: pulse 1.5s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #1a73e8;
            --secondary-color: #5f6368;
            --border-color: #dadce0;
            --hover-bg: #f1f3f4;
            --shadow-sm: 0 1px 2px 0 rgba(60, 64, 67, 0.3), 0 1px 3px 1px rgba(60, 64, 67, 0.15);
            --shadow-md: 0 1px 3px 0 rgba(60, 64, 67, 0.3), 0 4px 8px 3px rgba(60, 64, 67, 0.15);
            --transition: all 0.2s cubic-bezier(0.4, 0.0, 0.2, 1);
        }

        body {
            font-family: 'Roboto', 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #fff;
            color: #202124;
            line-height: 1.5;
        }

        /* Top Navigation Bar */
        .top-nav {
            position: sticky;
            top: 0;
            z-index: 100;
            background: white;
            border-bottom: 1px solid var(--border-color);
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.375rem;
            font-weight: 500;
            color: var(--secondary-color);
            text-decoration: none;
            font-family: 'Google Sans', sans-serif;
        }

        .logo-icon {
            font-size: 1.75rem;
        }

        .search-bar-container {
            flex: 1;
            max-width: 720px;
            position: relative;
        }

        .search-bar {
            width: 100%;
            padding: 0.75rem 3rem 0.75rem 3.5rem;
            border: 1px solid var(--border-color);
            border-radius: 24px;
            font-size: 1rem;
            transition: var(--transition);
            background: var(--hover-bg);
        }

        .search-bar:focus {
            outline: none;
            background: white;
            box-shadow: var(--shadow-sm);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary-color);
            pointer-events: none;
        }

        .nav-links {
            display: flex;
            gap: 0.25rem;
            align-items: center;
        }

        .nav-link {
            padding: 0.5rem 1.25rem;
            border-radius: 24px;
            text-decoration: none;
            color: var(--secondary-color);
            font-weight: 500;
            transition: var(--transition);
            font-size: 0.875rem;
            letter-spacing: 0.25px;
        }

        .nav-link:hover {
            background: var(--hover-bg);
        }

        .nav-link.active {
            background: #e8f0fe;
            color: var(--primary-color);
        }

        .icon-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            background: transparent;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--secondary-color);
            transition: var(--transition);
        }

        .icon-btn:hover {
            background: var(--hover-bg);
        }

        /* Main Content */
        .main-content {
            padding: 1.5rem;
            max-width: 1920px;
            margin: 0 auto;
        }

        /* Image Masonry Grid */
        .photos-grid {
            columns: 5 250px;
            column-gap: 4px;
            row-gap: 4px;
            margin-top: 1rem;
        }

        .photo-item {
            break-inside: avoid;
            margin-bottom: 4px;
            position: relative;
            cursor: pointer;
            overflow: hidden;
            border-radius: 2px;
            background: var(--hover-bg);
        }

        .photo-item img {
            width: 100%;
            height: auto;
            display: block;
            transition: transform 0.3s ease;
        }

        .photo-item:hover img {
            transform: scale(1.05);
        }

        .photo-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 50%);
            opacity: 0;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 1rem;
            color: white;
        }

        .photo-item:hover .photo-overlay {
            opacity: 1;
        }

        .photo-overlay-title {
            font-weight: 500;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }

        .photo-overlay-meta {
            font-size: 0.75rem;
            opacity: 0.9;
        }

        /* Checkbox overlay for selection */
        .photo-checkbox {
            position: absolute;
            top: 0.5rem;
            left: 0.5rem;
            width: 20px;
            height: 20px;
            border: 2px solid white;
            border-radius: 50%;
            background: rgba(0,0,0,0.3);
            opacity: 0;
            transition: var(--transition);
            cursor: pointer;
        }

        .photo-item:hover .photo-checkbox {
            opacity: 1;
        }

        /* Date Separator */
        .date-separator {
            padding: 1.5rem 0 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--secondary-color);
            letter-spacing: 0.25px;
            text-transform: uppercase;
            column-span: all;
        }

        /* Modal/Lightbox */
        .lightbox {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.9);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.2s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .lightbox-content {
            max-width: 90vw;
            max-height: 90vh;
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .lightbox-image-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            max-width: 70vw;
        }

        .lightbox-image {
            max-width: 100%;
            max-height: 85vh;
            object-fit: contain;
            border-radius: 8px;
        }

        .lightbox-sidebar {
            width: 360px;
            height: 85vh;
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            overflow-y: auto;
        }

        .lightbox-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--secondary-color);
            transition: var(--transition);
        }

        .lightbox-close:hover {
            background: white;
            transform: scale(1.1);
        }

        /* Tags */
        .tag {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 16px;
            background: #e8f0fe;
            color: var(--primary-color);
            font-size: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .tag:hover {
            background: #d2e3fc;
        }

        /* Buttons */
        .btn {
            padding: 0.625rem 1.5rem;
            border-radius: 24px;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.875rem;
            letter-spacing: 0.25px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover:not(:disabled) {
            box-shadow: var(--shadow-sm);
            background: #1765cc;
        }

        .btn-secondary {
            background: transparent;
            color: var(--primary-color);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover:not(:disabled) {
            background: var(--hover-bg);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Cards */
        .card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-state-icon {
            font-size: 6rem;
            opacity: 0.3;
            margin-bottom: 1rem;
        }

        .empty-state-title {
            font-size: 1.5rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--secondary-color);
        }

        .empty-state-description {
            color: var(--secondary-color);
            margin-bottom: 1.5rem;
        }

        /* File Upload */
        .file-upload-area {
            border: 2px dashed var(--border-color);
            border-radius: 8px;
            padding: 3rem 2rem;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            background: #fafafa;
        }

        .file-upload-area:hover {
            border-color: var(--primary-color);
            background: #f8f9fa;
        }

        /* Spinner */
        .spinner {
            border: 3px solid var(--hover-bg);
            border-top: 3px solid var(--primary-color);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 0.8s linear infinite;
            margin: 2rem auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Alert */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-error {
            background: #fce8e6;
            color: #d93025;
        }

        .alert-success {
            background: #e6f4ea;
            color: #137333;
        }

        .alert-info {
            background: #e8f0fe;
            color: #1967d2;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .top-nav {
                flex-wrap: wrap;
                gap: 1rem;
            }

            .search-bar-container {
                order: 3;
                flex-basis: 100%;
            }

            .photos-grid {
                columns: 2 150px;
            }

            .lightbox-content {
                flex-direction: column;
            }

            .lightbox-sidebar {
                width: 100%;
                max-width: 90vw;
                height: auto;
                max-height: 40vh;
            }
        }

        /* Material Icons */
        .material-symbols-outlined {
            font-variation-settings:
            'FILL' 0,
            'wght' 400,
            'GRAD' 0,
            'opsz' 24
        }
    </style>

    @livewireStyles
</head>
<body>
    <!-- Navigation loading bar -->
    <div class="wire-loading-bar" id="wire-loading-bar"></div>
    <!-- Top Navigation -->
    <nav class="top-nav">
        <a wire:navigate href="{{ route('home') }}" class="logo">
            <span class="logo-icon">📸</span>
            <span>Avinash-EYE</span>
        </a>

        <div class="search-bar-container">
            <span class="search-icon material-symbols-outlined">search</span>
            <input type="text" class="search-bar" placeholder="Search your photos" id="global-search">
        </div>

                <div class="nav-links">
                    <a wire:navigate href="{{ route('gallery') }}" class="nav-link {{ request()->routeIs('gallery') ? 'active' : '' }}">
                        Photos
                    </a>
                    <a wire:navigate href="{{ route('collections') }}" class="nav-link {{ request()->routeIs('collections') ? 'active' : '' }}">
                        Collections
                    </a>
                    <a wire:navigate href="{{ route('people') }}" class="nav-link {{ request()->routeIs('people') ? 'active' : '' }}">
                        👥 People & Pets
                    </a>
                    <a wire:navigate href="{{ route('instant-upload') }}" class="nav-link {{ request()->routeIs('instant-upload') ? 'active' : '' }}">
                        ⚡ Instant Upload
                    </a>
                    <a wire:navigate href="{{ route('processing-status') }}" class="nav-link {{ request()->routeIs('processing-status') ? 'active' : '' }}">
                        Processing
                    </a>
                    <a wire:navigate href="{{ route('search') }}" class="nav-link {{ request()->routeIs('search') ? 'active' : '' }}">
                        Search
                    </a>
                </div>

        <a wire:navigate href="{{ route('system-monitor') }}" class="icon-btn" title="System Monitor">
            <span class="material-symbols-outlined">monitoring</span>
        </a>
        <a wire:navigate href="{{ route('settings') }}" class="icon-btn" title="Settings">
            <span class="material-symbols-outlined">settings</span>
        </a>
        
        @auth
        <div style="position: relative; display: inline-block;">
            <button class="icon-btn" id="user-menu-btn" title="{{ Auth::user()->name }}">
                <span class="material-symbols-outlined">account_circle</span>
            </button>
            <div id="user-menu" style="display: none; position: absolute; top: 100%; right: 0; margin-top: 0.5rem; background: white; border: 1px solid var(--border-color); border-radius: 8px; box-shadow: var(--shadow-md); min-width: 200px; z-index: 1000;">
                <div style="padding: 1rem; border-bottom: 1px solid var(--border-color);">
                    <div style="font-weight: 500; color: #202124;">{{ Auth::user()->name }}</div>
                    <div style="font-size: 0.75rem; color: var(--secondary-color); margin-top: 0.25rem;">{{ Auth::user()->email }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                    @csrf
                    <button type="submit" style="width: 100%; padding: 0.75rem 1rem; border: none; background: transparent; text-align: left; cursor: pointer; color: var(--error-color); font-size: 0.875rem; display: flex; align-items: center; gap: 0.5rem; transition: var(--transition);">
                        <span class="material-symbols-outlined" style="font-size: 18px;">logout</span>
                        Sign out
                    </button>
                </form>
            </div>
        </div>
        @else
        <a wire:navigate href="{{ route('login') }}" class="nav-link" style="padding: 0.5rem 1rem;">
            Sign in
        </a>
        @endauth
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        @isset($slot)
            {{ $slot }}
        @else
            @yield('content')
        @endisset
    </main>

    @livewireScripts

    <script>
        // Global search functionality - redirect to gallery with search query
        const globalSearchInput = document.getElementById('global-search');
        
        if (globalSearchInput) {
            globalSearchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && this.value.trim()) {
                    // Use wire:navigate for SPA-like navigation
                    const link = document.createElement('a');
                    link.href = '{{ route("gallery") }}?q=' + encodeURIComponent(this.value);
                    link.setAttribute('wire:navigate', '');
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
            });
            
            // Pre-fill search from URL if on gallery page
            @if(request()->routeIs('gallery') && request()->query('q'))
                globalSearchInput.value = '{{ request()->query("q") }}';
            @else
                // Clear search input if no query in URL
                if (window.location.pathname === '{{ route("gallery") }}') {
                    globalSearchInput.value = '';
                }
            @endif
        }
        
        // Navigation loading indicator
        const loadingBar = document.getElementById('wire-loading-bar');
        
        document.addEventListener('livewire:navigating', () => {
            if (loadingBar) {
                loadingBar.classList.add('loading');
            }
        });
        
        document.addEventListener('livewire:navigated', () => {
            if (loadingBar) {
                loadingBar.classList.remove('loading');
                setTimeout(() => {
                    loadingBar.style.width = '0%';
                }, 300);
            }
            
            // Update search input from URL
            const urlParams = new URLSearchParams(window.location.search);
            const searchQuery = urlParams.get('q');
            const searchInput = document.getElementById('global-search');
            
            if (searchInput) {
                searchInput.value = searchQuery || '';
            }
        });
        
        // User menu toggle
        const userMenuBtn = document.getElementById('user-menu-btn');
        const userMenu = document.getElementById('user-menu');
        
        if (userMenuBtn && userMenu) {
            userMenuBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                userMenu.style.display = userMenu.style.display === 'none' ? 'block' : 'none';
            });
            
            // Close menu when clicking outside
            document.addEventListener('click', (e) => {
                if (!userMenuBtn.contains(e.target) && !userMenu.contains(e.target)) {
                    userMenu.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>

