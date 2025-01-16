@extends('layouts.admin.app')

@section('title', translate('Firebase OTP Verification'))

@push('css_or_js')

@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
            <h2 class="h1 mb-0 d-flex align-items-center gap-2">
                <img width="20" class="avatar-img" src="{{asset('public/assets/admin/img/icons/third-party.png')}}" alt="">
                <span class="page-header-title">
                    {{translate('third_party')}}
                </span>
            </h2>
        </div>
        <!-- End Page Header -->

        <!-- Inline Menu -->
        @include('admin-views.business-settings.partials._3rdparty-inline-menu')

        <div class="row gx-2 gx-lg-3">

            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">

                <div class="card">

                    <div class="card-body">
                        <form action="{{route('admin.business-settings.web-app.third-party.firebase-otp-verification-update')}}" method="post" enctype="multipart/form-data">
                            @csrf

                            <div class="row">
                                <div class="col-md-6" style="padding-top: 30px;">
                                    <?php
                                    $firebase_otp=\App\CentralLogics\Helpers::get_business_settings('firebase_otp_verification');
                                    ?>
                                    <div class="form-group">
                                        <label class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control">
                                            <span class="pr-1 d-flex align-items-center switch--label">
                                                <span class="line--limit-1">
                                                    <strong>{{translate('Firebase OTP Verification Status')}}</strong>
                                                    <i class="tio-info-outined"
                                                       data-toggle="tooltip"
                                                       data-placement="top"
                                                       title="{{ translate('If this field is active customers get the OTP through Firebase.') }}">
                                                    </i>
                                                </span>
                                            </span>
                                            <input type="checkbox" class="toggle-switch-input" name="status" {{ isset($firebase_otp) && $firebase_otp['status'] == 1 ? 'checked' : '' }}>
                                            <span class="toggle-switch-label text">
                                                <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label text-capitalize">{{translate('web_api_key')}}</label>
                                        <input type="text" value="{{$firebase_otp && env('APP_MODE')!='demo' ? $firebase_otp['web_api_key'] : ''}}" name="web_api_key" class="form-control" placeholder="">
                                    </div>
                                </div>
                            </div>
                            <div class="btn--container justify-content-end">
                                <button type="reset" class="btn btn-secondary">{{translate('clear')}}</button>
                                <button type="{{env('APP_MODE')!='demo'?'submit':'button'}}"
                                        onclick="{{env('APP_MODE')!='demo'?'':'call_demo()'}}"
                                        class="btn btn-primary">{{translate('submit')}}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('script_2')

@endpush
