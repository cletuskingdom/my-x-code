@extends('layouts.admin.app')

@section('title', translate('Product List'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
            <h2 class="h1 mb-0 d-flex align-items-center gap-2">
                <img width="20" class="avatar-img" src="{{asset('public/assets/admin/img/icons/product.png')}}" alt="">
                <span class="page-header-title">
                    @if ($category_name === 'all')                    
                        {{ translate('All_Product_List') }}
                    @else
                        Category list for "{{ $category_name }}"
                    @endif
                </span>
            </h2>
            <span class="badge badge-soft-dark rounded-50 fz-14">{{ $products->total() }}</span>
        </div>
        <div>
            <a href="{{ route('admin.product.list') }}"
                class="btn btn-sm px-4 btn-{{ Request::is('admin/product/list') ? 'primary' : 'secondary' }} mb-2"    
            >
                All products
            </a>
            
            @foreach ($category_list as $category)
                <a href="{{ route('admin.product.getProductsByCategory', $category->id) }}"
                    class="btn btn-sm px-4 btn-{{ $category->id == $categoryId ? 'primary' : 'secondary' }} mb-2"    
                >
                    {{ $category->name }}
                </a>
            @endforeach
        </div>
        <!-- End Page Header -->


        <div class="row g-2">
            <div class="col-12">
                <!-- Card -->
                <div class="card">
                    <!-- Header -->
                    <div class="card-top px-card pt-4">
                        <div class="row justify-content-between align-items-center gy-2">
                            <div class="col-lg-4">
                                <form action="{{url()->current()}}" method="GET">
                                    <div class="input-group">
                                        <input id="datatableSearch_" type="search" name="search" class="form-control" placeholder="{{translate('search_by_product_name')}}" aria-label="Search" value="{{$search}}" required="" autocomplete="off">
                                        <div class="input-group-append">
                                            <button type="submit" class="btn btn-primary">{{translate('Search')}}</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="col-lg-8">
                                <div class="d-flex gap-3 justify-content-end text-nowrap flex-wrap">
                                    <div>
                                        <button type="button" class="btn btn-outline-primary" data-toggle="dropdown" aria-expanded="false">
                                            <i class="tio-download-to"></i>
                                            Export
                                            <i class="tio-chevron-down"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-right">
                                            <li>
                                                <a type="submit" class="dropdown-item d-flex align-items-center gap-2" href="{{route('admin.product.excel-import', ['search' => $search])}}">
                                                    <img width="14" src="{{asset('public/assets/admin/img/icons/excel.png')}}" alt="">
                                                    {{translate('Excel')}}
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                    <a href="{{route('admin.product.add-new')}}" class="btn btn-primary">
                                        <i class="tio-add"></i> {{translate('add_New_Product')}}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End Header -->

                    <div class="py-4">
                        <div class="table-responsive datatable-custom">
                            <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                                <thead class="thead-light">
                                <tr>
                                    <th>{{translate('SL')}}</th>
                                    <th>{{translate('product_name')}}</th>
                                    <th>{{translate('selling_price')}}</th>
                                    <th class="text-center">{{translate('total_sale')}}</th>
                                    <th>Tax (%)</th>
                                    <th>{{translate('status')}}</th>
                                    <th>Group</th>
                                    <th class="text-center">{{translate('action')}}</th>
                                </tr>
                                </thead>

                                <tbody id="set-rows">
                                    @foreach($products as $key => $product)
                                        <tr>
                                            <td>{{ $products->firstitem() + $key }}</td>
                                            <td>
                                                <div class="media align-items-center gap-3">
                                                    <div class="avatar">
                                                        <img src="{{ asset('storage/app/public/product') }}/{{$product['image']}}" class="rounded img-fit"
                                                            onerror="this.src='{{asset('public/assets/admin/img/160x160/img2.jpg')}}'">
                                                    </div>
                                                    <div class="media-body">
                                                        <a class="text-dark" href="{{ route('admin.product.view', [$product['id']]) }}">
                                                            {{ Str::limit($product['name'], 30) }}
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ \App\CentralLogics\Helpers::set_symbol($product['price']) }}</td>
                                            <td class="text-center">{{ $product->total_sales ?? 0 }}</td> <!-- Display total sales or 0 -->
                                            <td>{{ $product['tax'] }}</td>
                                            <td>
                                                <div>
                                                    <label class="switcher">
                                                        <input id="{{ $product['id'] }}" class="switcher_input" type="checkbox" 
                                                            {{ $product['status'] == 1 ? 'checked' : '' }} 
                                                            data-url="{{ route('admin.product.status', [$product['id'], 0]) }}" 
                                                            onchange="status_change(this)">
                                                        <span class="switcher_control"></span>
                                                    </label>
                                                </div>
                                            </td>
                                            <td>{{ $product->group->name }}</td>
                                            <td>
                                                <div class="d-flex justify-content-center gap-2">
                                                    <?php
                                                        $positions = json_decode($product->category_ids, true);
                                                        $position = $positions[0]['position'];
                                                        $categories_product = $product->whereJsonContains('category_ids', ['id' => $positions[0]['id']])->get();
                                                        $count_ = 1;
                                                        $count_2 = 1;
                                                        $count_3 = 1;
                                                    ?>

                                                    <select name="category_ids" class="form-control" onchange="updateCategory(this, {{ $product['id'] }})">
                                                        @foreach ($categories_product as $category_product)
                                                            <option {{ (int)$count_++ == (int)$position ? 'selected' : '' }} value="{{ '[{' . '"id": "' . $positions[0]['id'] . '", "position": "' . $count_2++ . '"}]' }}">
                                                                {{ $count_3++ }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <script>
                                                        function updateCategory(selectElement, productId) {
                                                            var selectedCategory = selectElement.value;
                                                            var url = "{{ route('admin.product.update_position', ':id') }}".replace(':id', productId);

                                                            $.ajaxSetup({
                                                                headers: {
                                                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                                                }
                                                            });

                                                            $.ajax({
                                                                url: url,
                                                                type: 'POST',
                                                                data: {
                                                                    category_ids: selectedCategory,
                                                                    product_id: productId,
                                                                },
                                                                success: function(response) {
                                                                    if (response.success) {
                                                                        alert('Category updated successfully');
                                                                    } else {
                                                                        alert('Failed to update category');
                                                                    }
                                                                },
                                                                error: function() {
                                                                    alert('Error updating category');
                                                                }
                                                            });
                                                        }
                                                    </script>

                                                    <a class="btn btn-outline-info btn-sm edit square-btn"
                                                    href="{{ route('admin.product.edit', [$product['id']]) }}"><i class="tio-edit"></i></a>
                                                    
                                                    <button type="button" class="btn btn-outline-danger btn-sm delete square-btn"
                                                            onclick="form_alert('product-{{$product['id']}}','{{ translate('Want to delete this item ?') }}')">
                                                        <i class="tio-delete"></i>
                                                    </button>
                                                </div>
                                                <form action="{{ route('admin.product.delete', [$product['id']]) }}" method="post" id="product-{{ $product['id'] }}">
                                                    @csrf @method('delete')
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                    
                                    <!-- Pagination Links -->
                                    {{ $products->links() }}                                    
                                </tbody>
                            </table>
                        </div>

                        <div class="table-responsive mt-4 px-3">
                            <div class="d-flex justify-content-lg-end">
                                <!-- Pagination -->
                                {!! $products->links() !!}
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Card -->
            </div>
        </div>
    </div>

@endsection

@push('script_2')
    <script>
        $('#search-form').on('submit', function () {
            var formData = new FormData(this);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{route('admin.product.search')}}',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                beforeSend: function () {
                    $('#loading').show();
                },
                success: function (data) {
                    $('#set-rows').html(data.view);
                    $('.page-area').hide();
                },
                complete: function () {
                    $('#loading').hide();
                },
            });
        });
    </script>
@endpush
