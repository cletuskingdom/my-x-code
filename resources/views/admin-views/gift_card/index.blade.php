@extends('layouts.admin.app')

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
            <h2 class="h1 mb-0 d-flex align-items-center gap-2">
                <img width="20" class="avatar-img" src="{{asset('public/assets/admin/img/icons/coupon.png')}}" alt="" />

                <span class="page-header-title">
                    Gift cards
                </span>
            </h2>
        </div>
        <!-- End Page Header -->

        <div class="text-right mb-5">
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#create_giftCardsBtn">
                CREATE GIFT CARDS
            </button>
        </div>
        
        <!-- Modal -->
        <div class="modal fade" id="create_giftCardsBtn" tabindex="-1" role="dialog" aria-labelledby="create_giftCardsBtnLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="create_giftCardsBtnLabel">CREATE A GIFT CARD</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <form action="{{ route('admin.gift_card.upload') }}" method="post" enctype="multipart/form-data">
                        <div class="modal-body">
                            @csrf

                            <div class="mb-3">
                                <label for="">Amount</label>
                                <input type="tel" class="form-control" name="amount" placeholder="2000" required />
                            </div>

                            <div class="mb-3">
                                <div class="form-group">
                                    <div class="form-group">
                                        <div class="">
                                            <label class="mb-0">Image</label>
                                            <small class="text-danger">* ( {{translate('ratio 3:1')}} )</small>
                                        </div>

                                        <div class="d-flex justify-content-center mt-4">
                                            <div class="upload-file">
                                                <input type="file" name="image" accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*" class="upload-file__input" id="banners" multiple required>
                                                <div class="upload-file__img_drag upload-file__img">
                                                    <img width="465" id="viewer" src="{{asset(env('APP_PUBLIC') . 'assets/admin/img/icons/upload_img2.png')}}" alt="">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 20%">Code</th>
                        <th style="width: 20%">Amount</th>
                        <th style="width: 20%">Image</th>
                        <th style="width: 20%">Available</th>
                        <th style="width: 20%">Created At</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($giftCards as $giftCard)
                        <tr>
                            <td>
                                {{ $giftCard->code }}
                            </td>
                            <td>
                                {{ number_format($giftCard->amount, 2) }}
                            </td>
                            <td>
                                <img class="img-vertical-150" src="{{asset('storage/app/' . env('APP_PUBLIC'))}}/{{ $giftCard->image }}"
                                    onerror="this.src='{{asset(env('APP_PUBLIC') . 'assets/admin/img/900x400/img1.jpg')}}'"
                                />
                            </td>
                            <td>
                                <label class="switcher">
                                    <input type="checkbox" class="switcher_input" name="status" onchange="update_status('{{ $giftCard->id }}')"
                                        data-giftCard-id="{{ $giftCard->id }}" {{ $giftCard->is_available ? 'checked' : '' }} />
                                    <span class="switcher_control"></span>
                                </label>
                            </td>
                            <td>
                                {{ $giftCard->created_at }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function update_status(giftCardId) {
            const url = `{{ route('admin.gift_card.updateStatus', ':giftCardId') }}`.replace(':giftCardId', giftCardId);
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({ giftCardId: giftCardId })
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