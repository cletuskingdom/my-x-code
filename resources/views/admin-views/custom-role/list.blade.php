@extends('layouts.admin.app')

@section('title', translate('Employee Roles'))

@push('css_or_js')
    <!-- Custom styles for this page -->
    <link href="{{asset('public/assets/back-end')}}/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
        <h2 class="h1 mb-0 d-flex align-items-center gap-2">
            <img width="20" class="avatar-img" src="{{asset('public/assets/admin/img/icons/employee.png')}}" alt="">
            <span class="page-header-title">
                {{translate('employee_Roles')}}
            </span>
        </h2>
    </div>
    <!-- End Page Header -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-top px-card pt-4">
                    <div class="d-flex flex-column flex-md-row flex-wrap gap-3 justify-content-md-between align-items-md-center">
                        <h5 class="d-flex gap-2">
                            {{translate('employee_table')}}
                            <span class="badge badge-soft-dark rounded-50 fz-12">{{$roles->total()}}</span>
                        </h5>

                        <div class="d-flex flex-wrap justify-content-md-end gap-3">
                            <form action="{{url()->current()}}" method="GET">
                                <div class="input-group">
                                    <input id="datatableSearch_" type="search" name="search" class="form-control" placeholder="{{translate('Search by name, email or phone')}}" aria-label="Search" value="" required="" autocomplete="off">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-primary">{{translate('Search')}}</button>
                                    </div>
                                </div>
                            </form>
                            <div>
                                <a href="{{route('admin.custom-role.create')}}" class="btn btn-outline-primary text-nowrap">Add Role <i class="tio-add"></i></a>
                            </div>
                            <div>
                                <button type="button" class="btn btn-outline-primary text-nowrap" data-toggle="dropdown" aria-expanded="false">
                                    <i class="tio-download-to"></i>
                                    Export
                                    <i class="tio-chevron-down"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-right">
                                    <li>
                                        <a type="submit" class="dropdown-item d-flex align-items-center gap-2" href="{{route('admin.employee.excel-export')}}">
                                            <img width="14" src="{{asset('public/assets/admin/img/icons/excel.png')}}" alt="">
                                            {{ translate('Excel') }}
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="py-3">
                    <div class="table-responsive">
                        <table id="datatable" class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                            <thead class="thead-light">
                                <tr>
                                    <th>{{translate('SL')}}</th>
                                    <th>{{translate('Title')}}</th>
                                    <th>{{translate('Permissions')}}</th>
                                    <th class="text-center">{{translate('action')}}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($roles as $key=>$role)
                            
                                <tr>
                                    <td>{{$key + 1}}</td>
                                    <td class="text-capitalize">
                                        {{$role->name}}
                                    </td>
                                    
                                    <td>
                                        
                                        @php
                                            $modules = explode(',', trim($role->module_access, '[]'));
                                        @endphp

                                        @foreach($modules as $index=>$module)
                                        <span class="bg-secondary text-white rounded-right rounded-left px-2 mr-2">{{str_replace('_', ' ', str_replace('"', '', $module))}} </span> @if(($index+1)%2==0) <br> @endif
                                        @endforeach
                                        
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-2">
                                            <a href="{{route('admin.custom-role.update',[$role['id']])}}"
                                            class="btn btn-outline-info btn-sm square-btn"
                                            title="{{translate('Edit')}}">
                                                <i class="tio-edit"></i>
                                            </a>
                                            <a onclick="form_alert('role-{{$role->id}}', '{{translate('want_to_delete_this_role?')}}')"
                                               class="btn btn-outline-danger btn-sm delete square-btn"
                                               title="{{translate('delete')}}">
                                                <i class="tio-delete"></i>
                                            </a>
                                        </div>
                                        {{-- <form action="{{route('admin.employee.delete')}}" method="post" id="employee-{{$e->id}}">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="id" value="{{$e->id}}">
                                        </form> --}}
                                    </td>
                                </tr>
                                
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="table-responsive mt-4 px-3">
                        <div class="d-flex justify-content-lg-end">
                            <!-- Pagination -->
                            {{$roles->links()}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
    <!-- Page level plugins -->
    <script src="{{asset('public/assets/back-end')}}/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="{{asset('public/assets/back-end')}}/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <!-- Page level custom scripts -->
    <script>
        // Call the dataTables jQuery plugin
        $(document).ready(function () {
            $('#dataTable').DataTable();
        });
    </script>
@endpush
