@extends('layouts.admin.app')

@section('title', translate('Bestie_Buddy_List'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title">
                        <span class="page-header-icon"><i class="tio-shopping-basket"></i></span>
                        {{ translate('Bestie_Buddy_List') }}
                        <span class="badge badge-soft-dark ml-2">{{ $groceryLists->total() }}</span>
                    </h1>
                </div>
                <div class="col-sm-auto">
                    <a href="{{ route('admin.grocery-list.settings') }}" class="btn btn--primary btn-sm">
                        <i class="tio-settings mr-1"></i> {{ translate('Charge Settings') }}
                    </a>
                </div>
            </div>
        </div>
        <!-- End Page Header -->

        <div class="card">
            <!-- Header -->
            <div class="card-header border-0 py-3">
                <div class="row align-items-center gy-2">
                    <!-- Search -->
                    <div class="col-12 col-md">
                        <form class="d-flex" method="GET" action="{{ route('admin.grocery-list.list') }}">
                            <input type="hidden" name="status" value="{{ $status }}">
                            <div class="input-group input-group-merge input-group-flush">
                                <input type="search" name="search" class="form-control form-control-prepended"
                                       value="{{ $search }}"
                                       placeholder="{{ translate('Ex:_Search_by_customer') }}">
                                <div class="input-group-prepend">
                                    <div class="input-group-text"><i class="tio-search"></i></div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn--secondary ml-2">
                                {{ translate('messages.search') }}
                            </button>
                            @if($search || $status)
                                <a href="{{ route('admin.grocery-list.list') }}" class="btn btn--primary ml-2">
                                    {{ translate('messages.reset') }}
                                </a>
                            @endif
                        </form>
                    </div>

                    <!-- Status Filter -->
                    <div class="col-auto">
                        <div class="d-flex gap-2 flex-wrap">
                            @foreach(['all' => 'All', 'processing' => 'Processing', 'assigned' => 'Assigned', 'completed' => 'Delivered', 'cancelled' => 'Cancelled'] as $val => $label)
                                <a href="{{ route('admin.grocery-list.list', array_merge(request()->query(), ['status' => $val === 'all' ? '' : $val])) }}"
                                   class="btn btn-sm {{ ($status == ($val === 'all' ? '' : $val)) ? 'btn--primary' : 'btn-outline-secondary' }}">
                                    {{ $label }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <!-- Export -->
                    <div class="col-auto">
                        <div class="hs-unfold">
                            <a class="js-hs-unfold-invoker btn btn-sm btn-white" href="javascript:;"
                               data-hs-unfold-options='{"target":"#usersExportDropdown","type":"css-animation"}'>
                                <i class="tio-download-to mr-1"></i> {{ translate('messages.export') }}
                            </a>
                            <div id="usersExportDropdown"
                                 class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-sm-right">
                                <span class="dropdown-header">{{ translate('messages.download_options') }}</span>
                                <a class="dropdown-item"
                                   href="{{ route('admin.grocery-list.export', ['type' => 'csv', request()->getQueryString()]) }}">
                                    <img class="avatar avatar-xss avatar-4by3 mr-2"
                                         src="{{ asset('assets/admin') }}/svg/components/placeholder-csv-format.svg">
                                    .{{ translate('messages.csv') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Header -->

            <!-- Table -->
            <div class="table-responsive datatable-custom">
                <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('messages.sl') }}</th>
                            <th>{{ translate('messages.customer_name') }}</th>
                            <th>{{ translate('messages.email') }}</th>
                            <th>{{ translate('messages.title') }}</th>
                            <th>{{ translate('messages.status') }}</th>
                            <th class="text-right">{{ translate('Total') }}</th>
                            <th>{{ translate('messages.date') }}</th>
                            <th class="text-center">{{ translate('messages.actions') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                    @php
                        // FIX: unified status label mapping so "completed" shows as "Delivered"
                        // everywhere (list page + show page), same as show.blade.php
                        $statusClasses = [
                            'processing'       => 'badge-soft-warning',
                            'assigned'         => 'badge-soft-info',
                            'out_for_delivery' => 'badge-soft-primary',
                            'completed'        => 'badge-soft-success',
                            'cancelled'        => 'badge-soft-danger',
                        ];
                        $statusLabels = [
                            'processing'       => 'Processing',
                            'assigned'         => 'Assigned',
                            'out_for_delivery' => 'Out for delivery',
                            'completed'        => 'Delivered',
                            'cancelled'        => 'Cancelled',
                        ];
                    @endphp
                    @forelse($groceryLists as $key => $list)
                        <tr>
                            <td>{{ $key + $groceryLists->firstItem() }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-circle mr-2"
                                         style="background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:12px;width:32px;height:32px;">
                                        {{ substr($list->user->f_name ?? 'U', 0, 1) }}
                                    </div>
                                    <a href="{{ route('admin.customer.view', [$list->user_id]) }}"
                                       class="text--title text-hover font-weight-600">
                                        {{ $list->user->f_name ?? 'N/A' }} {{ $list->user->l_name ?? '' }}
                                    </a>
                                </div>
                            </td>
                            <td>{{ $list->user->email ?? 'N/A' }}</td>
                            <td>
                                @if($list->title)
                                    <span title="{{ $list->title }}">{{ Str::limit($list->title, 30) }}</span>
                                @else
                                    <span class="badge badge-soft-secondary">N/A</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $statusClasses[$list->status] ?? 'badge-soft-secondary' }}">
                                    {{ $statusLabels[$list->status] ?? ucfirst(str_replace('_', ' ', $list->status)) }}
                                </span>
                            </td>
                            <td class="text-right">
                                @if($list->charges_confirmed)
                                    <strong>₹{{ number_format($list->total_amount, 2) }}</strong>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $list->created_at->format('d M Y H:i') }}</td>
                            <td class="text-center">
                                <a class="btn btn-sm btn--warning btn-outline-warning"
                                   href="{{ route('admin.grocery-list.show', [$list->id]) }}"
                                   title="{{ translate('messages.view') }}">
                                    <i class="tio-visible-outlined"></i>
                                </a>
                            <a href="{{ route('admin.grocery-list.edit', $list->id) }}"
                            class="btn btn-sm btn--warning">
                            <i class="tio-edit mr-1"></i> Edit Order
                            </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="empty--data">
                                    <img src="{{ asset('/assets/admin/img/empty.png') }}" alt="empty">
                                    <h5>{{ translate('no_data_found') }}</h5>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <!-- End Table -->

            <div class="page-area px-4 pb-3">
                <div class="d-flex align-items-center justify-content-end">
                    {{ $groceryLists->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection