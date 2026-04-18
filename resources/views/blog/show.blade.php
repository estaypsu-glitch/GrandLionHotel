@extends('layouts.app')

@section('title', $post['title'])

@section('content')
    <section class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="{{ route('blog.index') }}" class="ta-chip text-decoration-none">
            <i class="bi bi-arrow-left-short"></i>
            Back to blog
        </a>
        <div class="d-flex flex-wrap gap-2">
            <span class="ta-chip">{{ $post['category'] }}</span>
            <span class="ta-chip">{{ $post['read_time'] }}</span>
            <span class="ta-chip">{{ $post['date'] }}</span>
        </div>
    </section>

    <article class="soft-card overflow-hidden mb-4">
        <img src="{{ $post['image'] }}" alt="{{ $post['title'] }}" class="w-100 object-cover" style="height: 440px;">
        <div class="p-4 p-lg-5">
            <p class="ta-eyebrow mb-2">By {{ $post['author']['name'] }} &middot; {{ $post['author']['role'] }}</p>
            <h1 class="mb-3">{{ $post['title'] }}</h1>
            <p class="lead text-secondary mb-0">{{ $post['intro'] }}</p>
        </div>
    </article>

    <section class="soft-card p-4 p-lg-5 mb-4">
        <p class="ta-eyebrow mb-2">Quick Takeaways</p>
        <h2 class="h3 mb-3">What To Remember</h2>
        <ul class="mb-0 text-secondary">
            @foreach($post['highlights'] as $highlight)
                <li class="mb-2">{{ $highlight }}</li>
            @endforeach
        </ul>
    </section>

    @foreach($post['sections'] as $section)
        <section class="soft-card p-4 p-lg-5 mb-3">
            <h2 class="h3 mb-2">{{ $section['heading'] }}</h2>
            <p class="text-secondary mb-0">{{ $section['body'] }}</p>
        </section>
    @endforeach

    <section class="soft-card p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <p class="ta-eyebrow mb-1">Filed Under</p>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($post['tags'] as $tag)
                        <span class="ta-chip">{{ $tag }}</span>
                    @endforeach
                </div>
            </div>
            <a href="{{ route('rooms.search') }}" class="btn btn-ta">Find rooms</a>
        </div>
    </section>

    <section>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h3 mb-0">Related Articles</h2>
            <a href="{{ route('blog.index') }}" class="btn btn-sm btn-ta-outline">View all posts</a>
        </div>
        <div class="row g-3 mb-4">
            @foreach($related as $item)
                <div class="col-md-6">
                    <article class="soft-card h-100 result-card overflow-hidden">
                        <img src="{{ $item['image'] }}" alt="{{ $item['title'] }}" class="w-100 object-cover" style="height: 190px;">
                        <div class="p-4 d-flex flex-column h-100">
                            <p class="ta-eyebrow mb-2">{{ $item['category'] }} &middot; {{ $item['read_time'] }}</p>
                            <h3 class="h4 mb-2">{{ $item['title'] }}</h3>
                            <p class="text-secondary mb-3">{{ \Illuminate\Support\Str::limit($item['excerpt'], 120) }}</p>
                            <a href="{{ route('blog.show', $item['slug']) }}" class="btn btn-ta mt-auto">Read article</a>
                        </div>
                    </article>
                </div>
            @endforeach
        </div>
    </section>

    <section class="soft-card p-4 p-lg-5 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
            <p class="ta-eyebrow mb-1">Ready To Plan Your Stay?</p>
            <h2 class="mb-1">Apply these tips on your next booking.</h2>
            <p class="text-secondary mb-0">Compare available rooms and confirm your reservation in minutes.</p>
        </div>
        <a href="{{ route('rooms.search') }}" class="btn btn-ta">Start booking</a>
    </section>
@endsection
