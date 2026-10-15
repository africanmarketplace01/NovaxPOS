@extends('superadmin.layout.main')
@section('title', 'Add Video Tutorial')
@section('page-title', 'Add Video Tutorial')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ route('superadmin.video-tutorials.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Back
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header py-3">
                <i class="fas fa-video me-2"></i>Tutorial Details
            </div>
            <div class="card-body">
                <form action="{{ route('superadmin.video-tutorials.store') }}" method="POST"
                      enctype="multipart/form-data">
                    @csrf
                    @include('superadmin.video-tutorials._form')
                    <hr class="my-4">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('superadmin.video-tutorials.index') }}" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save me-1"></i>Save Tutorial
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
