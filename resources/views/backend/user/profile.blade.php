@extends('backend.layout.main')
@section('content')

<x-success-message key="message3" />
<x-error-message key="message2" />
<x-error-message key="message1" />
<x-error-message key="not_permitted" />

<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{__('db.Update User Profile')}}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                        <form action="{{ route('user.profileUpdate', Auth::id()) }}" method="post">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>{{__('db.UserName')}} *</strong> </label>
                                        <input type="text" name="name" value="{{$lims_user_data->name}}" required class="form-control" />
                                    @if($errors->has('name'))
                                    <span>
                                       <strong>{{ $errors->first('name') }}</strong>
                                    </span>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <label>{{__('db.Email')}} *</strong> </label>
                                    <input type="email" name="email" value="{{$lims_user_data->email}}" required class="form-control">
                                    @if($errors->has('email'))
                                    <span>
                                       <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <label>{{__('db.Phone Number')}} *</strong> </label>
                                    <input type="text" name="phone" value="{{$lims_user_data->phone}}" required class="form-control" />
                                </div>
                                <div class="form-group">
                                    <label>{{__('db.Company Name')}}</strong> </label>
                                    <input type="text" name="company_name" value="{{$lims_user_data->company_name}}" class="form-control" />
                                </div>
                                <div class="form-group">
                                    <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
                                </div>
                            </div>
                        </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{__('db.Change Password')}}</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('user.password', Auth::id()) }}" method="post">
                            @csrf
                            @method('put')
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>{{__('db.Current Password')}} *</strong> </label>
                                        <input type="password" name="current_pass" required class="form-control" />
                                </div>
                                <div class="form-group">
                                    <label>{{__('db.New Password')}} *</strong> </label>
                                    <input type="password" name="new_pass" required class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>{{__('db.Confirm Password')}} *</strong> </label>
                                    <input type="password" name="confirm_pass" id="confirm_pass" required class="form-control">
                                </div>
                                <div class="form-group">
                                    <div class="registrationFormAlert" id="divCheckPasswordMatch">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
                                </div>
                            </div>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── 3.15 Manager Override PIN ────────────────────────────────────────────── --}}
    @if(Auth::id() == $lims_user_data->id || Auth::user()->role_id <= 2)
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white d-flex align-items-center">
                        <i class="dripicons-lock mr-2"></i>
                        <strong>Manager Override PIN (3.15)</strong>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">Set a 4–6 digit PIN that allows you to approve one-time discount overrides at the POS when a cashier requests escalation.</p>
                        @if(session('message3') && str_contains(session('message3'), 'PIN'))
                            <div class="alert alert-success">{{ session('message3') }}</div>
                        @endif
                        <form action="{{ route('user.setOverridePin', $lims_user_data->id) }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label>New Override PIN <small class="text-muted">(4–6 digits)</small></label>
                                <input type="password" name="override_pin" class="form-control" maxlength="6" pattern="\d{4,6}" placeholder="4–6 digit PIN" required>
                            </div>
                            <div class="form-group">
                                <label>Confirm PIN</label>
                                <input type="password" name="override_pin_confirmation" class="form-control" maxlength="6" pattern="\d{4,6}" placeholder="Repeat PIN" required>
                            </div>
                            @error('override_pin') <div class="alert alert-danger">{{ $message }}</div> @enderror
                            <button type="submit" class="btn btn-dark">
                                <i class="dripicons-lock"></i> Set Override PIN
                            </button>
                            @if($lims_user_data->override_pin)
                                <span class="text-success ml-2"><i class="dripicons-checkmark"></i> PIN is currently set</span>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</section>


@endsection

@push('scripts')
<script type="text/javascript">
    $("ul#setting").siblings('a').attr('aria-expanded','true');
    $("ul#setting").addClass("show");
    $("ul#setting #user-menu").addClass("active");



    $('#confirm_pass').on('input', function(){

        if($('input[name="new_pass"]').val() != $('input[name="confirm_pass"]').val())
            $("#divCheckPasswordMatch").html("Password doesn't match!");
        else
            $("#divCheckPasswordMatch").html("Password matches!");

    });
</script>
@endpush
