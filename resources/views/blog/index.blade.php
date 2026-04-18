@extends('layouts.app')

@section('title', 'Blog')

@section('content')
    <section class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <p class="ta-eyebrow mb-1">Editorial</p>
            <h1 class="mb-1">Hotel Journal</h1>
            <p class="text-secondary mb-0">Practical guides, travel strategy, and modern hospitality insights from The Grand Lion team.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('rooms.search') }}" class="btn btn-ta-outline">Find rooms</a>
            <a href="{{ route('about') }}" class="btn btn-ta">Our story</a>
        </div>
    </section>

    @if($featured)
        <section class="soft-card overflow-hidden mb-4">
            <div class="row g-0">
                <div class="col-lg-7 p-4 p-lg-5 d-flex flex-column justify-content-center">
                    <p class="ta-eyebrow mb-2">Featured Article</p>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="ta-chip">{{ $featured['category'] }}</span>
                        <span class="ta-chip">{{ $featured['date'] }}</span>
                        <span class="ta-chip">{{ $featured['read_time'] }}</span>
                    </div>
                    <h2 class="display-6 mb-3">{{ $featured['title'] }}</h2>
                    <p class="text-secondary mb-4">{{ $featured['excerpt'] }}</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('blog.show', $featured['slug']) }}" class="btn btn-ta">Read featured article</a>
                        <a href="{{ route('rooms.search') }}" class="btn btn-ta-outline">Book a stay</a>
                    </div>
                </div>
                <div class="col-lg-5">
                    <img src="{{ $featured['image'] }}" alt="{{ $featured['title'] }}" class="w-100 h-100 object-cover" style="min-height: 320px;">
                </div>
            </div>
        </section>
    @endif

    @if($topics->isNotEmpty())
        <section class="mb-4">
            <p class="ta-eyebrow mb-2">Topics</p>
            <div class="d-flex flex-wrap gap-2">
                @foreach($topics as $topic)
                    <span class="ta-chip">{{ $topic }}</span>
                @endforeach
            </div>
        </section>
    @endif

    <section class="mb-3">
        <p class="ta-eyebrow mb-1">Latest Posts</p>
        <h2 class="mb-0">Recent Reads</h2>
    </section>

    <section class="row g-4 mb-4">
        @forelse($latest as $post)
            <div class="col-md-6 col-xl-4">
                <article class="soft-card h-100 result-card overflow-hidden">
                    <img src="{{ $post['image'] }}" alt="{{ $post['title'] }}" class="w-100 object-cover" style="height: 220px;">
                    <div class="p-3 p-lg-4 d-flex flex-column h-100">
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <span class="ta-chip">{{ $post['category'] }}</span>
                            <span class="ta-chip">{{ $post['read_time'] }}</span>
                        </div>
                        <p class="ta-eyebrow mb-2">{{ $post['date'] }}</p>
                        <h3 class="h4 mb-2">{{ $post['title'] }}</h3>
                        <p class="text-secondary mb-3">{{ \Illuminate\Support\Str::limit($post['excerpt'], 125) }}</p>
                        <div class="d-flex flex-wrap gap-2 mb-4">
                            @foreach($post['tags'] as $tag)
                                <span class="badge text-bg-light border">{{ $tag }}</span>
                            @endforeach
                        </div>
                        <a href="{{ route('blog.show', $post['slug']) }}" class="btn btn-ta mt-auto">Read article</a>
                    </div>
                </article>
            </div>
        @empty
            <div class="col-12">
                <div class="soft-card p-4 text-secondary">More blog articles are being prepared. Please check back soon.</div>
            </div>
        @endforelse
    </section>

    <section class="soft-card p-4 p-lg-5 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
            <p class="ta-eyebrow mb-1">Planning A Trip?</p>
            <h2 class="mb-1">Turn reading into your next stay.</h2>
            <p class="text-secondary mb-0">Use these guides, then compare room options that match your travel style.</p>
        </div>
        <a href="{{ route('rooms.search') }}" class="btn btn-ta">Start booking</a>
    </section>
@endsection
