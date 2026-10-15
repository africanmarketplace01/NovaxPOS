@extends('landlord.layouts.public')

@section('title', 'Video tutorials — ' . $siteN)
@section('description', 'Learn how to use ' . $siteN . ' with short step-by-step video tutorials.')

@section('content')
    @include('landlord.partials.page-hero', [
        'eyebrow' => 'Tutorials',
        'title' => 'Learn ' . $siteN . ' step by step',
        'lead' => 'Short video tutorials to get you up and running fast, from setup to advanced features.',
        'center' => true,
    ])

    <section class="section">
        <div class="container">
            @if ($tutorials->isEmpty())
                <div class="empty">
                    <i class="fas fa-video-slash" aria-hidden="true"></i>
                    <p>No tutorials available yet. Check back soon.</p>
                </div>
            @else
                <div class="toolbar">
                    <div class="chips" role="group" aria-label="Filter by category">
                        <button type="button" class="chip" data-cat="all" aria-pressed="true">All <span class="muted" style="margin-left:.35rem">{{ $tutorials->flatten()->count() }}</span></button>
                        @foreach ($tutorials->keys() as $category)
                            <button type="button" class="chip" data-cat="{{ Str::slug($category) }}" aria-pressed="false">{{ $category }}</button>
                        @endforeach
                    </div>
                    <div class="searchbar">
                        <label for="tutSearch" class="sr-only">Search tutorials</label>
                        <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                        <input type="search" id="tutSearch" class="input" placeholder="Search tutorials…" autocomplete="off">
                    </div>
                </div>

                @foreach ($tutorials as $category => $items)
                    <section class="tut-group" data-cat="{{ Str::slug($category) }}" aria-labelledby="cat-{{ Str::slug($category) }}">
                        <h2 id="cat-{{ Str::slug($category) }}">{{ $category }}</h2>
                        <div class="grid grid--3">
                            @foreach ($items as $tut)
                                <button type="button" class="card card--hover tut-card" data-video
                                        data-title="{{ $tut->title }}" data-type="{{ $tut->video_type }}"
                                        data-embed="{{ $tut->embed_url }}" data-file="{{ $tut->video_public_url ?? '' }}"
                                        data-search="{{ Str::lower($tut->title . ' ' . ($tut->description ?? '')) }}">
                                    <span class="tut-card__thumb">
                                        <img src="{{ $tut->thumbnail_url }}" alt="" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/video-placeholder.svg') }}'">
                                        <span class="tut-card__play" aria-hidden="true"><i class="fas fa-play"></i></span>
                                        @if ($tut->duration)<span class="tut-card__dur">{{ $tut->duration }}</span>@endif
                                    </span>
                                    <span class="tut-card__body">
                                        <span class="tut-card__cat">{{ $tut->category }}</span>
                                        <span class="tut-card__title" style="display:block">{{ $tut->title }}</span>
                                        @if ($tut->description)<span class="tut-card__desc" style="display:block">{{ Str::limit($tut->description, 90) }}</span>@endif
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </section>
                @endforeach

                <div class="empty" id="noResults" hidden>
                    <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                    <p>No tutorials match your search.</p>
                </div>
            @endif
        </div>
    </section>

    <div class="modal" id="videoModal" hidden>
        <div class="modal__box" role="dialog" aria-modal="true" aria-labelledby="videoTitle">
            <div class="modal__head">
                <h2 id="videoTitle">Video tutorial</h2>
                <button type="button" class="modal__close" data-video-close aria-label="Close video"><i class="fas fa-xmark" aria-hidden="true"></i></button>
            </div>
            <div class="modal__player" id="videoPlayer"></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var modal = document.getElementById('videoModal');
            var player = document.getElementById('videoPlayer');
            var lastTrigger = null;

            function openVideo(card) {
                lastTrigger = card;
                document.getElementById('videoTitle').textContent = card.dataset.title;
                player.textContent = '';
                var el;
                if (card.dataset.type === 'upload' && card.dataset.file) {
                    el = document.createElement('video');
                    el.controls = true; el.autoplay = true; el.playsInline = true;
                    el.src = card.dataset.file;
                } else if (card.dataset.embed) {
                    el = document.createElement('iframe');
                    el.src = card.dataset.embed;
                    el.allow = 'autoplay; encrypted-media; picture-in-picture';
                    el.allowFullscreen = true;
                    el.title = card.dataset.title;
                }
                if (el) player.appendChild(el);
                modal.hidden = false;
                document.body.style.overflow = 'hidden';
                modal.querySelector('[data-video-close]').focus();
            }

            function closeVideo() {
                player.textContent = ''; // stops playback
                modal.hidden = true;
                document.body.style.overflow = '';
                if (lastTrigger) lastTrigger.focus();
            }

            document.querySelectorAll('[data-video]').forEach(function (card) {
                card.addEventListener('click', function () { openVideo(card); });
            });
            modal.querySelector('[data-video-close]').addEventListener('click', closeVideo);
            modal.addEventListener('click', function (e) { if (e.target === modal) closeVideo(); });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) closeVideo(); });

            // Category filter + search
            var chips = document.querySelectorAll('.chip[data-cat]');
            var search = document.getElementById('tutSearch');
            function filter() {
                var active = document.querySelector('.chip[aria-pressed="true"]');
                var cat = active ? active.dataset.cat : 'all';
                var q = (search ? search.value : '').toLowerCase().trim();
                var visible = 0;
                document.querySelectorAll('.tut-group').forEach(function (group) {
                    var groupVisible = false;
                    group.querySelectorAll('[data-video]').forEach(function (card) {
                        var show = (cat === 'all' || group.dataset.cat === cat) && (!q || card.dataset.search.indexOf(q) !== -1);
                        card.hidden = !show;
                        if (show) { groupVisible = true; visible++; }
                    });
                    group.hidden = !groupVisible;
                });
                var none = document.getElementById('noResults');
                if (none) none.hidden = visible > 0;
            }
            chips.forEach(function (chip) {
                chip.addEventListener('click', function () {
                    chips.forEach(function (c) { c.setAttribute('aria-pressed', c === chip ? 'true' : 'false'); });
                    filter();
                });
            });
            if (search) search.addEventListener('input', filter);
        })();
    </script>
@endpush
