@extends('layouts.admin.app')

@section('title', translate('Add new addon'))

@push('css_or_js')

@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
            <h2 class="h1 mb-0 d-flex align-items-center gap-2">
                <img width="20" class="avatar-img" src="{{asset('public/assets/admin/img/icons/attribute.png')}}" alt="">
                <span class="page-header-title">
                    {{translate('Add_New_Addon')}}
                </span>
            </h2>
        </div>
        <!-- End Page Header -->


        <div class="row g-3">
            <div class="col-12">
                <div class="mt-3">
                    <div class="card">
                        <div class="card-top px-card pt-4">
                            <div class="d-flex flex-column flex-md-row flex-wrap gap-3 justify-content-md-between align-items-md-center">
                                <h5 class="d-flex align-items-center gap-2">
                                    {{translate('Addon_Table')}}
                                    <span class="badge badge-soft-dark rounded-50 fz-12">{{ $addons->total() }}</span>
                                </h5>

                                <div class="d-flex flex-wrap justify-content-md-end gap-3">
                                    <form action="{{url()->current()}}" method="GET">
                                        <div class="input-group">
                                            <input id="datatableSearch_" type="search" name="search" class="form-control" placeholder="{{translate('Search by Addon name')}}" aria-label="Search" value="{{$search}}" required="" autocomplete="off">
                                            <div class="input-group-append">
                                                <button type="submit" class="btn btn-primary"> {{translate('Search')}}</button>
                                            </div>
                                        </div>
                                    </form>
                                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#adAddondModal">
                                        <i class="tio-add"></i>
                                        {{translate('Add_Addon')}}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="py-4">
                            <div class="table-responsive datatable-custom">
                                <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>{{translate('SL')}}</th>
                                            <th>{{translate('name')}}</th>
                                            <th>{{translate('price')}}</th>
                                            <th>{{translate('image')}}</th>
                                            <th>{{translate('category')}}</th>
                                            <th class="text-center">{{translate('tax')}} (%)</th>
                                            <th class="text-center">{{translate('status')}}</th>
                                            <th class="text-center">{{translate('action')}}</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                    @foreach($addons as $key=>$addon)
                                        <tr>
                                            <td>{{$addons->firstitem()+$key}}</td>
                                            <td>
                                                <div>
                                                    {{$addon['name']}}
                                                </div>
                                            </td>
                                            <td>{{ Helpers::set_symbol($addon['price']) }}</td>
                                            <td><img src="{{$addon['image']}}" alt="" width="80"></td>
                                            <td>{{is_null($addon->category)?'':$addon->category->name}}</td>
                                            <td class="text-center">{{ $addon['tax'] }}</td>

                                            <td class="text-center">
                                                <div>
                                                    <label class="switcher">
                                                        <input id="{{ $addon['id']}}" class="switcher_input" 
                                                            type="checkbox" {{ $addon['status'] == 1 ? 'checked' : '' }} 
                                                            data-url="{{ route('admin.addon.status', $addon['id']) }} " 
                                                            onchange="change_addon_status(this)"
                                                        >
                                                        <span class="switcher_control"></span>
                                                    </label>
                                                </div>
                                            </td>

                                            <td>
                                                <div class="d-flex justify-content-center gap-2">
                                                    <a class="btn btn-outline-info btn-sm edit square-btn"
                                                        href="{{route('admin.addon.edit',[$addon['id']])}}"><i class="tio-edit"></i></a>
                                                    <button class="btn btn-outline-danger btn-sm delete square-btn" type="button"
                                                        onclick="form_alert('addon-{{$addon['id']}}','{{translate('Want to delete this addon')}} ?')"><i class="tio-delete"></i></button>
                                                </div>
                                                <form action="{{route('admin.addon.delete',[$addon['id']])}}"
                                                        method="post" id="addon-{{$addon['id']}}">
                                                    @csrf @method('delete')
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="table-responsive mt-4 px-3">
                                <div class="d-flex justify-content-lg-end">
                                    <!-- Pagination -->
                                    {!! $addons->links() !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="adAddondModal" tabindex="-1" role="dialog" aria-labelledby="adAddondModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-body">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <form action="{{route('admin.addon.store')}}" method="post" enctype="multipart/form-data">
                            @csrf
                            @php($data = Helpers::get_business_settings('language'))
                            @php($default_lang = Helpers::get_default_language())

                            @if ($data && array_key_exists('code', $data[0]))
                            <ul class="nav nav-tabs w-fit-content mb-4">
                                @foreach ($data as $lang)
                                    <li class="nav-item">
                                        <a class="nav-link lang_link {{ $lang['default'] == true ? 'active' : '' }}" href="#"
                                        id="{{ $lang['code'] }}-link">{{ Helpers::get_language_name($lang['code']) . '(' . strtoupper($lang['code']) . ')' }}</a>
                                    </li>
                                @endforeach
                            </ul>
                            <div class="row">
                                <div class="col-md-6">
                                    @foreach ($data as $lang)
                                        <div class="form-group {{ $lang['default'] == false ? 'd-none' : '' }} lang_form" id="{{ $lang['code'] }}-form">
                                            <label class="input-label" for="exampleFormControlInput1">{{ translate('name') }} ({{ strtoupper($lang['code']) }})</label>
                                            <input type="text" name="name[]" class="form-control" placeholder="{{translate('New addon')}}"
                                                {{$lang['status'] == true ? 'required':''}} maxlength="255"
                                                @if($lang['status'] == true) oninvalid="document.getElementById('{{$lang['code']}}-link').click()" @endif>
                                        </div>
                                        <input type="hidden" name="lang[]" value="{{ $lang['code'] }}">
                                    @endforeach
                                </div> 
                                    @else
                                        <div class="col-sm-6 mb-4">
                                            <div class="form-group lang_form" id="{{ $default_lang }}-form">
                                                <label class="input-label" for="exampleFormControlInput1">{{ translate('name') }} ({{ strtoupper($default_lang) }})</label>
                                                <input type="text" name="name[]" class="form-control" maxlength="255" placeholder="{{ translate('New addon') }}" required>
                                            </div>
                                            <input type="hidden" name="lang[]" value="{{ $default_lang }}">
                                            
                                            <input name="position" value="0" style="display: none">
                                        </div>
                                        @endif
                                        <div class="col-sm-6">
                                            <div class="form-group lang_form" id="{{ $default_lang }}-form">
                                                <label class="input-label" for="exampleFormControlInput1">{{ translate('category') }} ({{ strtoupper($default_lang) }})</label>
                                                <select name="category" class="form-control">
                                                    <option value="" selected disabled>Select Category</option>
                                                    @foreach($categories as $category)
                                                    <option value="{{$category->id}}">{{ucwords($category->name)}}</option>
                                                    @endforeach
                                                    
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 from_part_2 mb-4">
                                            <label class="input-label" for="exampleFormControlInput1">{{translate('price')}}</label>
                                            <input type="number" min="0" name="price" step="any" class="form-control"
                                                placeholder="100" required
                                                oninvalid="document.getElementById('en-link').click()">
                                        </div>
                                        <div class="col-sm-6 from_part_2 mb-4">
                                            <label class="input-label" for="exampleFormControlInput1">{{translate('tax')}} (%)</label>
                                            <input type="number" min="0" name="tax" step="any" class="form-control"
                                                   placeholder="5" required
                                                   value="7.5"
                                                   oninvalid="document.getElementById('en-link').click()">
                                        </div>

                                        {{-- <div class="col-sm-6 from_part_2 mb-4">
                                            <label class="input-label">Addon Photo</label>
                                            <input type="file" class="form-control" name="image" oninput="document.getElementById('addon-image').src = window.URL.createObjectURL(this.files[0])">

                                            
                                        </div>

                                        <div class="col-sm-6">
                                            <img src="{{asset('public/assets/admin/img/400x400/img2.jpg')}}" width="100" alt="Addon-Image" id="addon-image">
                                        </div> --}}

                                        <div class="col-12">
                                            <label class="input-label">Description</label>
                                            <textarea name="description" class="form-control" rows="4">{{old('description')}}</textarea>
                                        </div>
                                        <div class="col-12 mt-3">
                                            <div class="d-flex justify-content-end gap-3">
                                                <button type="reset" class="btn btn-secondary">{{translate('reset')}}</button>
                                                <button type="submit" class="btn btn-primary">{{translate('submit')}}</button>
                                            </div>
                                        </div>
                                
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

@endsection

@push('script_2')
    <script>
        $(".lang_link").click(function(e){
            e.preventDefault();
            $(".lang_link").removeClass('active');
            $(".lang_form").addClass('d-none');
            $(this).addClass('active');

            let form_id = this.id;
            let lang = form_id.split("-")[0];
            console.log(lang);
            $("#"+lang+"-form").removeClass('d-none');
            if(lang == '{{$default_lang}}')
            {
                $(".from_part_2").removeClass('d-none');
            }
            else
            {
                $(".from_part_2").addClass('d-none');
            }
        });
    </script>

@endpush
