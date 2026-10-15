@extends('superadmin.layout.main')
@section('title', 'Edit Tutorial')
@section('page-title', 'Edit Tutorial')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ route('superadmin.video-tutorials.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Back
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <span><i class="fas fa-video me-2"></i>Edit Tutorial</span>
                <form action="{{ route('superadmin.video-tutorials.destroy', $videoTutorial->id) }}" method="POST"
                      onsubmit="return confirm('Delete this tutorial permanently?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="fas fa-trash me-1"></i>Delete
                    </button>
                </form>
            </div>
            <div class="card-body">
                <form action="{{ route('superadmin.video-tutorials.update', $videoTutorial->id) }}" method="POST"
                      enctype="multipart/form-data">
                    @csrf @method('PUT')
                    @include('superadmin.video-tutorials._form')
                    <hr class="my-4">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('superadmin.video-tutorials.index') }}" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save me-1"></i>Update Tutorial
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
