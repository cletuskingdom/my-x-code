@extends('layouts.admin.app')

@section('title', translate('Business Settings'))
@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
            <h2 class="h1 mb-0 d-flex align-items-center gap-2">
                <img width="20" class="avatar-img" src="{{asset(env('APP_PUBLIC') . 'assets/admin/img/icons/business_setup2.png')}}" alt="">
                <span class="page-header-title">
                    Ads Banner -
                    <span style="font-size: 10px;" class="ml-1 py-1 px-2 rounded-circle bg-secondary text-light">
                        {{ $adbanners->count() }}
                    </span>
                </span>
            </h2>
        </div>
        <!-- End Page Header -->

        <div class="row">
            <div class="col-md-6">
                <div id="bannerCarousel" class="carousel slide mt-5" data-ride="carousel">
                    <div class="carousel-inner">
                        @foreach($adbanners as $key => $banner)
                            <div class="carousel-item {{ $key == 0 ? 'active' : '' }}">
                                <img src="{{ asset('storage/app' . env('APP_PUBLIC') . $banner->image_path) }}" class="d-block w-100 rounded" alt="Banner {{ $key + 1 }}">
                            </div>
                        @endforeach
                    </div>
        
                    <a class="carousel-control-prev" href="#bannerCarousel" role="button" data-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="sr-only">Previous</span>
                    </a>
                    <a class="carousel-control-next" href="#bannerCarousel" role="button" data-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="sr-only">Next</span>
                    </a>
                </div>
            </div>

            <div class="col-md-6">
                <form action="{{ route('admin.business-settings.ads-banner.upload') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <div class="form-group">
                            <div class="d-flex align-items-center justify-content-center gap-1">
                                <label class="mb-0">Upload your ADS Banners</label>
                                <small class="text-danger">* ( {{translate('ratio 3:1')}} )</small>
                            </div>
                            <div class="d-flex justify-content-center mt-4">
                                <div class="upload-file">
                                    <input type="file" name="banners[]" accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*" class="upload-file__input" id="banners" multiple required>
                                    <div class="upload-file__img_drag upload-file__img">
                                        <img width="465" id="viewer" src="{{asset(env('APP_PUBLIC') . 'assets/admin/img/icons/upload_img2.png')}}" alt="">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-right">
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="pt-5 d-flex justify-content-center">
            <table class="table table-striped table-hover w-auto">
                <thead>
                    <tr>
                        <th>S/N</th>
                        <th>IMAGE URL</th>
                        <th>STATUS</th>
                        <th>CREATED ON</th>
                        <th>ACTION</th>
                    </tr>
                </thead>
                
                <tbody>
                    @foreach($adbanners as $banner)
                        <tr>
                            <th scope="row">{{ $banner->id }}</th>
                            <td>
                                <img src="{{ asset('storage/app/' . env('APP_PUBLIC') . $banner->image_path) }}" alt="" class="w-50 img-fluid">
                            </td>

                            <td>
                                <div class="form-control d-flex justify-content-between align-items-center gap-3">
                                    <div>
                                        <label class="text-dark mb-0">
                                            <i class="tio-info-outined"
                                                data-toggle="tooltip"
                                                data-placement="top"
                                                title="{{ translate('When this option is enabled, this banner would not display on the users dashboard.') }}">
                                            </i>
                                        </label>
                                    </div>

                                    <label class="switcher">
                                        <input class="switcher_input" type="checkbox" name="status" onchange="update_ads_status('{{ $banner->id }}')"
                                               data-banner-id="{{ $banner->id }}" {{ $banner->status == 0 ? '' : 'checked' }} />
                                        <span class="switcher_control"></span>
                                    </label>
                                </div>
                            </td>

                            <td>{{ $banner->created_at }}</td>
                            <td>
                                <div class="d-flex justify-content-center gap-2">
                                    <button type="button" class="btn btn-outline-danger btn-sm delete square-btn"
                                        onclick="delete_ads_banner('{{ $banner['id'] }}','{{ translate('Want to delete this banner?')}}')"
                                    >
                                        <i class="tio-delete"></i>
                                    </button>

                                    <form action="{{ route('admin.business-settings.ads-banner.delete', $banner->id) }}"
                                        method="post" id="ads_b_anner-{{ $banner['id'] }}">
                                        @csrf
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function delete_ads_banner(id, message) {
            Swal.fire({
                title: '{{translate("Are you sure?")}}',
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: '{{translate("No")}}',
                confirmButtonText: '{{translate("Yes")}}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $('#ads_b_anner-'+id).submit()
                }
            })
        }

        function update_ads_status(adsBannerID) {
            const url = `{{ route('admin.business-settings.ads-banner.updateStatus', ':adsBannerID') }}`.replace(':adsBannerID', adsBannerID);
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({ adsBannerID: adsBannerID })
            })
            .then(response => response.json())
            .then(data => {
                // if (data == 1) {
                //     alert('Banner status updated successfully');
                // } else {
                //     alert('Failed to update banner status');
                // }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
    </script>
@endsection

