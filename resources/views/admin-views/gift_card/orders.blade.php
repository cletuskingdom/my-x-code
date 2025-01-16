@extends('layouts.admin.app')

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
            <h2 class="h1 mb-0 d-flex align-items-center gap-2">
                <img width="20" class="avatar-img" src="{{asset('public/assets/admin/img/icons/coupon.png')}}" alt="" />

                <span class="page-header-title">
                    Gift Card Orders
                </span>
            </h2>
        </div>
        <!-- End Page Header -->

        <div class="table-responsive pt-5">
            <h3></h3>
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 20%">Gift card code</th>
                        <th style="width: 20%">From</th>
                        <th style="width: 10%">To</th>
                        <th style="width: 10%">Status</th>
                        <th style="width: 20%">Created At</th>
                        <th style="width: 20%">Used At</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($giftCardOrders as $giftCardOrder)
                        <tr>
                            <td>
                                {{ $giftCardOrder->giftCard->code }}
                            </td>
                            <td>
                                {{ $giftCardOrder->fromUser->email }}
                            </td>
                            <td>
                                {{ $giftCardOrder->toUser->email }}
                            </td>
                            <td>
                                {{ $giftCardOrder->is_redeemed ? 'USED' : 'NOT USED' }}
                            </td>
                            <td>
                                {{ $giftCardOrder->created_at }}
                            </td>
                            <td>
                                {{$giftCardOrder->is_redeemed ? $giftCardOrder->updated_at : '' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection