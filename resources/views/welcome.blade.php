<x-layouts.app>
<!-- Animated Hero Section with Gradient Background -->
<div class="relative overflow-hidden">
    <!-- Animated Gradient Background -->
    <div class="absolute inset-0 bg-gradient-to-br from-purple-600 via-blue-500 to-purple-700 opacity-10"></div>
    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiM4ODgiIGZpbGwtb3BhY2l0eT0iMC4wMyI+PHBhdGggZD0iTTM2IDE2YzAgMS4xLS45IDItMiAycy0yLS45LTItMiAuOS0yIDItMiAyIC45IDIgMm0tOCAwYzAgMS4xLS45IDItMiAycy0yLS45LTItMiAuOS0yIDItMiAyIC45IDIgMiIvPjwvZz48L2c+PC9zdmc+')] opacity-40"></div>
    
    <!-- Hero Content -->
    <div class="relative text-center py-20 px-8 max-w-6xl mx-auto">
        <!-- Badge -->
        <div class="inline-flex items-center gap-2 px-4 py-2 bg-purple-100 text-purple-700 rounded-full text-sm font-medium mb-8 animate-fade-in">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-purple-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-purple-500"></span>
            </span>
            100% Local • No Cloud • Complete Privacy
        </div>

        <!-- Main Heading -->
        <h1 class="text-6xl md:text-7xl font-bold mb-6 text-gray-900 font-google-sans leading-tight animate-slide-up">
            Your Personal AI-Powered
            <span class="bg-gradient-to-r from-purple-600 to-blue-600 bg-clip-text text-transparent">
                Media Manager
            </span>
        </h1>

        <!-- Subheading -->
        <p class="text-xl md:text-2xl text-gray-600 max-w-3xl mx-auto mb-12 leading-relaxed animate-slide-up-delay">
            Organize, search, and analyze your photos, videos, documents, and audio files with cutting-edge AI. 
            All processing happens locally on your machine - <strong>your privacy is guaranteed</strong>.
        </p>

        <!-- CTA Buttons -->
        <div class="flex gap-4 justify-center flex-wrap mb-12 animate-slide-up-delay-2">
            @auth
                <a wire:navigate href="{{ route('instant-upload') }}" class="group btn-primary text-lg px-10 py-4 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                    <span class="material-symbols-outlined text-2xl group-hover:scale-110 transition-transform">upload</span>
                    Upload Files
                </a>
                <a wire:navigate href="{{ route('gallery') }}" class="group btn-secondary text-lg px-10 py-4 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                    <span class="material-symbols-outlined text-2xl group-hover:scale-110 transition-transform">photo_library</span>
                    View Gallery
                </a>
            @else
                <a wire:navigate href="{{ route('register') }}" class="group btn-primary text-lg px-10 py-4 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                    <span class="material-symbols-outlined text-2xl group-hover:scale-110 transition-transform">person_add</span>
                    Get Started Free
                </a>
                <a wire:navigate href="{{ route('login') }}" class="group btn-secondary text-lg px-10 py-4 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                    <span class="material-symbols-outlined text-2xl group-hover:scale-110 transition-transform">login</span>
                    Sign In
                </a>
            @endauth
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 max-w-4xl mx-auto animate-fade-in-delay">
            <div class="text-center">
                <div class="text-4xl font-bold text-purple-600 mb-2">100%</div>
                <div class="text-sm text-gray-600">Local Processing</div>
            </div>
            <div class="text-center">
                <div class="text-4xl font-bold text-blue-600 mb-2">8+</div>
                <div class="text-sm text-gray-600">AI Models</div>
            </div>
            <div class="text-center">
                <div class="text-4xl font-bold text-purple-600 mb-2">∞</div>
                <div class="text-sm text-gray-600">Storage</div>
            </div>
            <div class="text-center">
                <div class="text-4xl font-bold text-blue-600 mb-2">0</div>
                <div class="text-sm text-gray-600">API Calls</div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Features Showcase -->
<div class="bg-gradient-to-b from-white to-gray-50 py-16">
    <div class="max-w-6xl mx-auto px-8">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-bold text-gray-900 mb-4">Everything You Need</h2>
            <p class="text-xl text-gray-600">Powerful features that respect your privacy</p>
        </div>
        
        <div class="grid md:grid-cols-3 gap-8">
            <!-- Feature 1 -->
            <div class="group bg-white rounded-3xl p-8 shadow-md hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 border border-gray-100">
                <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-4xl text-white">smart_toy</span>
                </div>
                <h3 class="text-2xl font-bold mb-3 text-gray-900">AI-Powered Search</h3>
                <p class="text-gray-600 leading-relaxed">
                    Find anything using natural language. Search "sunset on beach" or "dog playing in snow" 
                    and get instant results with semantic understanding.
                </p>
            </div>

            <!-- Feature 2 -->
            <div class="group bg-white rounded-3xl p-8 shadow-md hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 border border-gray-100">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-4xl text-white">face</span>
                </div>
                <h3 class="text-2xl font-bold mb-3 text-gray-900">Face Recognition</h3>
                <p class="text-gray-600 leading-relaxed">
                    Automatically detect and group faces with 99.38% accuracy. 
                    Name people and pets, then find all their photos instantly.
                </p>
            </div>

            <!-- Feature 3 -->
            <div class="group bg-white rounded-3xl p-8 shadow-md hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 border border-gray-100">
                <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-4xl text-white">folder_open</span>
                </div>
                <h3 class="text-2xl font-bold mb-3 text-gray-900">Multi-Format Support</h3>
                <p class="text-gray-600 leading-relaxed">
                    Photos, videos, documents, audio, and archives. Upload anything and let AI analyze, 
                    transcribe, and make it searchable.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Comprehensive Features Grid -->
<div class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-8">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-bold text-gray-900 mb-4">Powerful AI Features</h2>
            <p class="text-xl text-gray-600">Advanced capabilities for your entire media library</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Feature 1 -->
            <div class="bg-gradient-to-br from-purple-50 to-white rounded-2xl p-6 shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border border-purple-100">
                <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-2xl text-white">photo_library</span>
                </div>
                <h3 class="text-lg font-bold mb-2 text-gray-900">Image Analysis</h3>
                <p class="text-gray-600 text-sm leading-relaxed">
                    AI-powered captions with Florence-2 & BLIP models
                </p>
            </div>

            <!-- Feature 2 -->
            <div class="bg-gradient-to-br from-blue-50 to-white rounded-2xl p-6 shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border border-blue-100">
                <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-2xl text-white">search</span>
                </div>
                <h3 class="text-lg font-bold mb-2 text-gray-900">Semantic Search</h3>
                <p class="text-gray-600 text-sm leading-relaxed">
                    Find anything using natural language queries
                </p>
            </div>

            <!-- Feature 3 -->
            <div class="bg-gradient-to-br from-purple-50 to-white rounded-2xl p-6 shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border border-purple-100">
                <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-2xl text-white">face</span>
                </div>
                <h3 class="text-lg font-bold mb-2 text-gray-900">Face Recognition</h3>
                <p class="text-gray-600 text-sm leading-relaxed">
                    99.38% accuracy with automatic clustering
                </p>
            </div>

            <!-- Feature 4 -->
            <div class="bg-gradient-to-br from-blue-50 to-white rounded-2xl p-6 shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border border-blue-100">
                <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-2xl text-white">movie</span>
                </div>
                <h3 class="text-lg font-bold mb-2 text-gray-900">Video Analysis</h3>
                <p class="text-gray-600 text-sm leading-relaxed">
                    Scene detection and frame-by-frame analysis
                </p>
            </div>

            <!-- Feature 5 -->
            <div class="bg-gradient-to-br from-purple-50 to-white rounded-2xl p-6 shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border border-purple-100">
                <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-2xl text-white">mic</span>
                </div>
                <h3 class="text-lg font-bold mb-2 text-gray-900">Audio Transcription</h3>
                <p class="text-gray-600 text-sm leading-relaxed">
                    OpenAI Whisper for speech-to-text
                </p>
            </div>

            <!-- Feature 6 -->
            <div class="bg-gradient-to-br from-blue-50 to-white rounded-2xl p-6 shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border border-blue-100">
                <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-2xl text-white">description</span>
                </div>
                <h3 class="text-lg font-bold mb-2 text-gray-900">Document OCR</h3>
                <p class="text-gray-600 text-sm leading-relaxed">
                    Extract text from PDFs and images
                </p>
            </div>

            <!-- Feature 7 -->
            <div class="bg-gradient-to-br from-purple-50 to-white rounded-2xl p-6 shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border border-purple-100">
                <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-2xl text-white">inventory_2</span>
                </div>
                <h3 class="text-lg font-bold mb-2 text-gray-900">Archive Extraction</h3>
                <p class="text-gray-600 text-sm leading-relaxed">
                    Auto-extract and analyze ZIP, RAR, 7Z files
                </p>
            </div>

            <!-- Feature 8 -->
            <div class="bg-gradient-to-br from-blue-50 to-white rounded-2xl p-6 shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border border-blue-100">
                <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-2xl text-white">label</span>
                </div>
                <h3 class="text-lg font-bold mb-2 text-gray-900">Smart Tags</h3>
                <p class="text-gray-600 text-sm leading-relaxed">
                    AI-generated categories and metadata
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Privacy First Section -->
<div class="py-16 bg-gradient-to-br from-gray-900 to-gray-800 text-white">
    <div class="max-w-6xl mx-auto px-8">
        <div class="text-center mb-12">
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-green-500/20 text-green-300 rounded-full text-sm font-medium mb-6">
                <span class="material-symbols-outlined text-lg">lock</span>
                Your Privacy is Sacred
            </div>
            <h2 class="text-4xl font-bold mb-4">100% Local Processing</h2>
            <p class="text-xl text-gray-300 max-w-3xl mx-auto">
                Every AI model runs on your hardware. Zero external API calls. Complete data sovereignty.
            </p>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="w-16 h-16 bg-green-500/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-3xl text-green-400">cloud_off</span>
                </div>
                <h3 class="text-xl font-bold mb-2">No Cloud</h3>
                <p class="text-gray-400">
                    Your files never leave your server. Not even for processing.
                </p>
            </div>

            <div class="text-center">
                <div class="w-16 h-16 bg-blue-500/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-3xl text-blue-400">code_off</span>
                </div>
                <h3 class="text-xl font-bold mb-2">No Tracking</h3>
                <p class="text-gray-400">
                    Zero telemetry, analytics, or third-party services.
                </p>
            </div>

            <div class="text-center">
                <div class="w-16 h-16 bg-purple-500/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-3xl text-purple-400">key</span>
                </div>
                <h3 class="text-xl font-bold mb-2">No API Keys</h3>
                <p class="text-gray-400">
                    Open-source AI models. No subscriptions required.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Technology Stack -->
<div class="py-16 bg-gradient-to-b from-gray-50 to-white">
    <div class="max-w-6xl mx-auto px-8">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-bold text-gray-900 mb-4">Built with Modern Tech</h2>
            <p class="text-xl text-gray-600">Production-ready stack for reliability and performance</p>
        </div>

        <div class="bg-gradient-to-br from-purple-600 to-blue-600 rounded-3xl p-8 shadow-2xl">
            <div class="flex flex-wrap justify-center gap-3">
                <span class="px-5 py-2.5 bg-white/20 text-white rounded-full text-sm font-medium backdrop-blur-sm hover:bg-white/30 transition-colors">Laravel 12 + Livewire 3</span>
                <span class="px-5 py-2.5 bg-white/20 text-white rounded-full text-sm font-medium backdrop-blur-sm hover:bg-white/30 transition-colors">Python FastAPI</span>
                <span class="px-5 py-2.5 bg-white/20 text-white rounded-full text-sm font-medium backdrop-blur-sm hover:bg-white/30 transition-colors">Florence-2 Vision</span>
                <span class="px-5 py-2.5 bg-white/20 text-white rounded-full text-sm font-medium backdrop-blur-sm hover:bg-white/30 transition-colors">CLIP Embeddings</span>
                <span class="px-5 py-2.5 bg-white/20 text-white rounded-full text-sm font-medium backdrop-blur-sm hover:bg-white/30 transition-colors">OpenAI Whisper</span>
                <span class="px-5 py-2.5 bg-white/20 text-white rounded-full text-sm font-medium backdrop-blur-sm hover:bg-white/30 transition-colors">PostgreSQL + pgvector</span>
                <span class="px-5 py-2.5 bg-white/20 text-white rounded-full text-sm font-medium backdrop-blur-sm hover:bg-white/30 transition-colors">Docker Compose</span>
                <span class="px-5 py-2.5 bg-white/20 text-white rounded-full text-sm font-medium backdrop-blur-sm hover:bg-white/30 transition-colors">Face Recognition</span>
                <span class="px-5 py-2.5 bg-white/20 text-white rounded-full text-sm font-medium backdrop-blur-sm hover:bg-white/30 transition-colors">PaddleOCR + Tesseract</span>
                <span class="px-5 py-2.5 bg-white/20 text-white rounded-full text-sm font-medium backdrop-blur-sm hover:bg-white/30 transition-colors">Ollama (Optional)</span>
            </div>
        </div>
    </div>
</div>

<!-- Getting Started CTA -->
<div class="py-16 bg-gradient-to-br from-purple-600 to-blue-600">
    <div class="max-w-4xl mx-auto px-8 text-center text-white">
        <h2 class="text-4xl font-bold mb-4">Ready to Get Started?</h2>
        <p class="text-xl mb-8 text-purple-100">
            Set up your own AI-powered media manager in minutes
        </p>
        
        <div class="flex flex-col sm:flex-row gap-4 justify-center mb-8">
            @auth
                <a wire:navigate href="{{ route('instant-upload') }}" class="group inline-flex items-center justify-center gap-2 px-10 py-4 bg-white text-purple-600 font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1 text-lg">
                    <span class="material-symbols-outlined text-2xl group-hover:scale-110 transition-transform">upload</span>
                    Start Uploading
                </a>
                <a wire:navigate href="{{ route('gallery') }}" class="group inline-flex items-center justify-center gap-2 px-10 py-4 bg-purple-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1 text-lg border-2 border-white/20">
                    <span class="material-symbols-outlined text-2xl group-hover:scale-110 transition-transform">photo_library</span>
                    View Gallery
                </a>
            @else
                <a wire:navigate href="{{ route('register') }}" class="group inline-flex items-center justify-center gap-2 px-10 py-4 bg-white text-purple-600 font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1 text-lg">
                    <span class="material-symbols-outlined text-2xl group-hover:scale-110 transition-transform">rocket_launch</span>
                    Create Free Account
                </a>
                <a wire:navigate href="{{ route('login') }}" class="group inline-flex items-center justify-center gap-2 px-10 py-4 bg-purple-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1 text-lg border-2 border-white/20">
                    <span class="material-symbols-outlined text-2xl group-hover:scale-110 transition-transform">login</span>
                    Sign In
                </a>
            @endauth
        </div>

        <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 text-white rounded-full text-sm backdrop-blur-sm">
            <span class="material-symbols-outlined text-lg">info</span>
            First-time setup downloads AI models (~5GB). One-time operation, cached forever.
        </div>
    </div>
</div>
</x-layouts.app>
