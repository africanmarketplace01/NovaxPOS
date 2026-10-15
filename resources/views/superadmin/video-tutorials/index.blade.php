@extends('superadmin.layout.main')
@section('title', 'Video Tutorials')
@section('page-title', 'Video Tutorials')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-semibold">Video Tutorials</h5>
    <a href="{{ route('superadmin.video-tutorials.create') }}" class="btn btn-primary btn-sm">
        <i class="fas fa-plus me-1"></i>Add Tutorial
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Filters --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-5">
                <input type="text" name="search" value="{{ request('search') }}"
                       class="form-control form-control-sm" placeholder="Search title or category…">
            </div>
            <div class="col-sm-4">
                <select name="category" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-outline-primary flex-fill">
                    <i class="fas fa-search me-1"></i>Filter
                </button>
                <a href="{{ route('superadmin.video-tutorials.index') }}" class="btn btn-sm btn-outline-secondary flex-fill">
                    Reset
                </a>
            </div>
        </form>
    </div>
</div>

@if($tutorials->isEmpty())
    <div class="card">
        <div class="card-body text-center py-5 text-muted">
            No tutorials found.
            <a href="{{ route('superadmin.video-tutorials.create') }}">Add the first one</a>.
        </div>
    </div>
@else
    <div class="row g-3" id="tutorials-grid">
        @foreach($tutorials as $tut)
        <div class="col-xl-4 col-md-6" data-id="{{ $tut->id }}">
            <div class="card h-100 {{ !$tut->is_published ? 'opacity-60' : '' }}">
                {{-- Thumbnail --}}
                <div class="position-relative" style="padding-top:56.25%; background:#000; overflow:hidden; border-radius:.375rem .375rem 0 0;">
                    <img src="{{ $tut->thumbnail_url }}"
                         alt="{{ $tut->title }}"
                         style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;opacity:.85;"
                         onerror="this.src='{{ asset('images/video-placeholder.svg') }}'">
                    {{-- Type badge --}}
                    <span class="position-absolute top-0 start-0 m-2 badge
                        {{ $tut->video_type === 'youtube' ? 'bg-danger' : ($tut->video_type === 'vimeo' ? 'bg-info' : 'bg-dark') }}">
                        <i class="fab fa-{{ $tut->video_type === 'upload' ? 'file-video' : $tut->video_type }} me-1"></i>
                        {{ ucfirst($tut->video_type) }}
                    </span>
                    {{-- Compression status --}}
                    @if($tut->compression_status === 'pending' || $tut->compression_status === 'processing')
                    <span class="position-absolute top-0 end-0 m-2 badge bg-warning text-dark">
                        <i class="fas fa-spinner fa-spin me-1"></i>Compressing…
                    </span>
                    @elseif($tut->compression_status === 'failed')
                    <span class="position-absolute top-0 end-0 m-2 badge bg-danger" title="Compression failed — original file is used">
                        <i class="fas fa-exclamation-triangle me-1"></i>Raw file
                    </span>
                    @endif
                    {{-- Duration --}}
                    @if($tut->duration)
                    <span class="position-absolute bottom-0 end-0 m-2 badge bg-dark bg-opacity-75">
                        <i class="fas fa-clock me-1"></i>{{ $tut->duration }}
                    </span>
                    @endif
                    {{-- Play overlay --}}
                    <div class="position-absolute top-50 start-50 translate-middle">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:44px;height:44px;background:rgba(255,255,255,.85);">
                            <i class="fas fa-play text-dark" style="margin-left:3px;"></i>
                        </div>
                    </div>
                </div>
                <div class="card-body pb-2">
                    <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
                        <h6 class="mb-0 fw-semibold" style="font-size:.9rem;line-height:1.3;">{{ $tut->title }}</h6>
                        <span class="badge flex-shrink-0 {{ $tut->is_published ? 'bg-success' : 'bg-secondary' }}">
                            {{ $tut->is_published ? 'Live' : 'Draft' }}
                        </span>
                    </div>
                    <span class="badge bg-light text-dark border" style="font-size:.7rem;">{{ $tut->category }}</span>
                    @if($tut->description)
                        <p class="text-muted mt-2 mb-0" style="font-size:.8rem;line-height:1.4;">
                            {{ Str::limit($tut->description, 80) }}
                        </p>
                    @endif
                </div>
                <div class="card-footer d-flex gap-2 pt-2">
                    {{-- Toggle publish --}}
                    <form action="{{ route('superadmin.video-tutorials.toggle', $tut->id) }}" method="POST" class="d-inline">
                        @csrf @method('PATCH')
                        <button class="btn btn-sm {{ $tut->is_published ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                title="{{ $tut->is_published ? 'Unpublish' : 'Publish' }}">
                            <i class="fas fa-{{ $tut->is_published ? 'eye-slash' : 'eye' }}"></i>
                        </button>
                    </form>
                    <a href="{{ route('superadmin.video-tutorials.edit', $tut->id) }}"
                       class="btn btn-sm btn-outline-primary flex-fill">
                        <i class="fas fa-edit me-1"></i>Edit
                    </a>
                    <form action="{{ route('superadmin.video-tutorials.destroy', $tut->id) }}" method="POST"
                          onsubmit="return confirm('Delete this tutorial?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-3">
        {{ $tutorials->links() }}
    </div>
@endif

<div class="mt-3 d-flex align-items-center gap-2">
    <a href="{{ route('tutorials') }}" target="_blank" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-external-link-alt me-1"></i>Preview Public Page
    </a>
    <span class="text-muted small">This is what tenants see</span>
</div>

@endsection
