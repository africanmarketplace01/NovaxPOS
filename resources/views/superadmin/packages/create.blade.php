@extends('superadmin.layout.main')
@section('title', 'Create Package')
@section('page-title', 'Create Package')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ route('superadmin.packages.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Back
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header py-3"><i class="fas fa-box-open me-2"></i>Package Details</div>
            <div class="card-body">
                <form action="{{ route('superadmin.packages.store') }}" method="POST">
                    @csrf
                    @include('superadmin.packages._form')
                    <hr class="my-4">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('superadmin.packages.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save me-1"></i>Create Package
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
