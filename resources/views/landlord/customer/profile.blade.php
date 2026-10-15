@extends('landlord.marketplace.layout')
@section('title', 'Profile')

@section('content')
<div class="container py-4">
  <div class="acct-shell">
    @include('landlord.customer._nav', ['active' => 'profile'])
    <div class="acct-main">
      <h4 class="fw-bold mb-3">Profile</h4>

      @if(session('success'))<div class="mp-flash mp-flash-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>@endif
      @if($errors->any())<div class="mp-flash mp-flash-error"><i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}</div>@endif

      <form action="{{ route('customer.profile.update') }}" method="POST" class="bg-white rounded-3 p-4" style="border:1px solid var(--mp-border)">
        @csrf @method('PUT')
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold" style="font-size:var(--text-sm)">Full name *</label>
            <input type="text" name="name" class="form-control" value="{{ old('name',$c->name) }}" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold" style="font-size:var(--text-sm)">Email</label>
            <input type="email" class="form-control" value="{{ $c->email }}" disabled>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold" style="font-size:var(--text-sm)">Phone</label>
            <input type="text" name="phone" class="form-control" value="{{ old('phone',$c->phone) }}">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold" style="font-size:var(--text-sm)">City</label>
            <input type="text" name="city" class="form-control" value="{{ old('city',$c->city) }}">
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold" style="font-size:var(--text-sm)">Address</label>
            <textarea name="address" class="form-control" rows="2">{{ old('address',$c->address) }}</textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold" style="font-size:var(--text-sm)">Country</label>
            <input type="text" name="country" class="form-control" value="{{ old('country',$c->country) }}">
          </div>
        </div>
        <hr class="my-4">
        <div class="fw-semibold mb-2" style="font-size:var(--text-sm)">Change password <span class="text-muted" style="font-weight:400;font-size:var(--text-xs)">(leave blank to keep current)</span></div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold" style="font-size:var(--text-sm)">New password</label>
            <input type="password" name="new_password" class="form-control" minlength="6">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold" style="font-size:var(--text-sm)">Confirm new password</label>
            <input type="password" name="new_password_confirmation" class="form-control" minlength="6">
          </div>
        </div>
        <button type="submit" class="btn btn-primary mt-4"><i class="fas fa-save me-1"></i> Save changes</button>
      </form>
    </div>
  </div>
</div>
@endsection
