@extends('layouts.app')

@section('content')
<!-- Hero Section -->
<div style="text-align: center; padding: 4rem 2rem; max-width: 900px; margin: 0 auto;">
    <div style="font-size: 5rem; margin-bottom: 2rem;">📸</div>
    <h1 style="font-size: 3rem; font-weight: 500; margin-bottom: 1rem; color: #202124; font-family: 'Google Sans', sans-serif;">
        Welcome to Avinash-EYE
    </h1>
    <p style="font-size: 1.25rem; color: var(--secondary-color); max-width: 600px; margin: 0 auto 3rem; line-height: 1.6;">
        AI-powered photo analysis and semantic search, running completely locally with no external API calls
    </p>

    <!-- CTA Buttons -->
    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
        <a href="{{ route('upload') }}" class="btn btn-primary" style="font-size: 1rem; padding: 0.875rem 2rem;">
            <span class="material-symbols-outlined" style="font-size: 1.25rem;">upload</span>
            Upload photos
        </a>
        <a href="{{ route('gallery') }}" class="btn btn-secondary" style="font-size: 1rem; padding: 0.875rem 2rem;">
            <span class="material-symbols-outlined" style="font-size: 1.25rem;">photo_library</span>
            View gallery
        </a>
    </div>
</div>

<!-- Features Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin: 3rem 0;">
    <div class="card" style="text-align: center; transition: var(--transition); cursor: default;">
        <div style="width: 56px; height: 56px; background: #e8f0fe; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
            <span class="material-symbols-outlined" style="font-size: 2rem; color: var(--primary-color);">image</span>
        </div>
        <h3 style="font-size: 1.125rem; font-weight: 500; margin-bottom: 0.75rem; color: #202124;">
            Image Analysis
        </h3>
        <p style="color: var(--secondary-color); font-size: 0.875rem; line-height: 1.6;">
            Upload photos and get detailed AI-generated descriptions using BLIP captioning model
        </p>
    </div>

    <div class="card" style="text-align: center; transition: var(--transition); cursor: default;">
        <div style="width: 56px; height: 56px; background: #e8f0fe; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
            <span class="material-symbols-outlined" style="font-size: 2rem; color: var(--primary-color);">search</span>
        </div>
        <h3 style="font-size: 1.125rem; font-weight: 500; margin-bottom: 0.75rem; color: #202124;">
            Semantic Search
        </h3>
        <p style="color: var(--secondary-color); font-size: 0.875rem; line-height: 1.6;">
            Search photos using natural language with CLIP embeddings and vector similarity
        </p>
    </div>

    <div class="card" style="text-align: center; transition: var(--transition); cursor: default;">
        <div style="width: 56px; height: 56px; background: #e8f0fe; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
            <span class="material-symbols-outlined" style="font-size: 2rem; color: var(--primary-color);">lock</span>
        </div>
        <h3 style="font-size: 1.125rem; font-weight: 500; margin-bottom: 0.75rem; color: #202124;">
            100% Local
        </h3>
        <p style="color: var(--secondary-color); font-size: 0.875rem; line-height: 1.6;">
            All AI models run locally with no external API calls for complete privacy
        </p>
    </div>

    <div class="card" style="text-align: center; transition: var(--transition); cursor: default;">
        <div style="width: 56px; height: 56px; background: #e8f0fe; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
            <span class="material-symbols-outlined" style="font-size: 2rem; color: var(--primary-color);">face</span>
        </div>
        <h3 style="font-size: 1.125rem; font-weight: 500; margin-bottom: 0.75rem; color: #202124;">
            Face Detection
        </h3>
        <p style="color: var(--secondary-color); font-size: 0.875rem; line-height: 1.6;">
            Automatically detect and count faces in your photos for better organization
        </p>
    </div>

    <div class="card" style="text-align: center; transition: var(--transition); cursor: default;">
        <div style="width: 56px; height: 56px; background: #e8f0fe; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
            <span class="material-symbols-outlined" style="font-size: 2rem; color: var(--primary-color);">label</span>
        </div>
        <h3 style="font-size: 1.125rem; font-weight: 500; margin-bottom: 0.75rem; color: #202124;">
            Smart Tags
        </h3>
        <p style="color: var(--secondary-color); font-size: 0.875rem; line-height: 1.6;">
            Automatic meta tag generation for easy filtering and organization
        </p>
    </div>

    <div class="card" style="text-align: center; transition: var(--transition); cursor: default;">
        <div style="width: 56px; height: 56px; background: #e8f0fe; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
            <span class="material-symbols-outlined" style="font-size: 2rem; color: var(--primary-color);">speed</span>
        </div>
        <h3 style="font-size: 1.125rem; font-weight: 500; margin-bottom: 0.75rem; color: #202124;">
            Fast Search
        </h3>
        <p style="color: var(--secondary-color); font-size: 0.875rem; line-height: 1.6;">
            Lightning-fast vector similarity search powered by PostgreSQL pgvector
        </p>
    </div>

    <div class="card" style="text-align: center; transition: var(--transition); cursor: default;">
        <div style="width: 56px; height: 56px; background: #e8f0fe; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
            <span class="material-symbols-outlined" style="font-size: 2rem; color: var(--primary-color);">category</span>
        </div>
        <h3 style="font-size: 1.125rem; font-weight: 500; margin-bottom: 0.75rem; color: #202124;">
            Smart Collections
        </h3>
        <p style="color: var(--secondary-color); font-size: 0.875rem; line-height: 1.6;">
            Automatically organize photos by AI-detected categories and face groups
        </p>
    </div>
</div>

<!-- Technology Stack -->
<div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; color: white; margin-top: 3rem;">
    <h3 style="font-size: 1.25rem; font-weight: 500; margin-bottom: 1.5rem; text-align: center;">
        Technology Stack
    </h3>
    <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 0.75rem;">
        <span class="tag" style="background: rgba(255,255,255,0.2); color: white; cursor: default;">Laravel 11 + Livewire 3</span>
        <span class="tag" style="background: rgba(255,255,255,0.2); color: white; cursor: default;">Python FastAPI</span>
        <span class="tag" style="background: rgba(255,255,255,0.2); color: white; cursor: default;">BLIP Captioning</span>
        <span class="tag" style="background: rgba(255,255,255,0.2); color: white; cursor: default;">CLIP Embeddings</span>
        <span class="tag" style="background: rgba(255,255,255,0.2); color: white; cursor: default;">PostgreSQL + pgvector</span>
        <span class="tag" style="background: rgba(255,255,255,0.2); color: white; cursor: default;">Docker Compose</span>
        <span class="tag" style="background: rgba(255,255,255,0.2); color: white; cursor: default;">Face Recognition</span>
        <span class="tag" style="background: rgba(255,255,255,0.2); color: white; cursor: default;">Ollama (Optional)</span>
    </div>
</div>

<!-- Info Banner -->
<div class="alert alert-info" style="margin-top: 2rem;">
    <span class="material-symbols-outlined">info</span>
    <div>
        <strong>First-time setup</strong>
        <div style="font-size: 0.875rem; margin-top: 0.25rem;">
            The first time you run the system, it will download AI models (~2GB). This is a one-time operation and models will be cached for future use.
        </div>
    </div>
</div>
@endsection
