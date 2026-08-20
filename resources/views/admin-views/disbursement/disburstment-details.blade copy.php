@extends('layouts.admin.app')

@section('title',translate('messages.disbursement'))

@push('css_or_js')

@endpush

@section('content')


<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon">
                <img src="{{asset('/public/assets/admin/img/report/new/disburstment.png')}}" class="w--22" alt="">
            </span>
            <span>Disbursement Details</span>
        </h1>
    </div>
    <!-- Reports -->
    
    <div class="card">
        <div class="card-header flex-wrap justify-content-between gap-3">
            <div class="left">
                <h3 class="m-0 font-bold">Disbursement #3893 <span class="badge badge-soft-warning">Processing</span> </h3>
                <span>Create at 12 July, 2022 4:30 pm</span>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="d-flex flex-wrap align-items-center mr-2">
                    <span>Total Amount</span> <span class="mx-2">:</span> <h3 class="m-0">$90,876</h3>
                </div>
                <div>
                    <select class="form-control js-select2-custom">
                        <option value="all" selected>All restaurants</option>
                        <option value="all">All restaurants</option>
                        <option value="all">All restaurants</option>
                    </select>
                </div>
                <div>
                    <select class="form-control js-select2-custom">
                        <option value="all_time">
                            All Time</option>
                        <option>
                            This Year</option>
                        <option>
                            Previous Year</option>
                        <option>
                            This Month</option>
                        <option>
                            This Week</option>
                        <option>
                            Custom</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-header border-0 py-2">
            <div class="search--button-wrapper">
                <h2 class="card-title">
                    Total Disbursements <span class="badge badge-soft-secondary ml-2" id="countItems">468</span>
                </h2>
                <form class="search-form">
                    <!-- Search -->
                    <div class="input--group input-group input-group-merge input-group-flush">
                        <input class="form-control" value="" placeholder="Search by Order ID" name="search">
                        <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                    </div>
                    <!-- End Search -->
                </form>
                <!-- Static Export Button -->
                <div class="hs-unfold ml-3">
                    <a class="js-hs-unfold-invoker btn btn-sm btn-white dropdown-toggle btn export-btn btn-outline-primary btn--primary font--sm" href="javascript:;"
                        data-hs-unfold-options='{
                            "target": "#usersExportDropdown",
                            "type": "css-animation"
                        }'>
                        <i class="tio-download-to mr-1"></i> {{translate('messages.export')}}
                    </a>
                    <div id="usersExportDropdown"
                            class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-sm-right">
                        <span class="dropdown-header">{{translate('messages.download_options')}}</span>
                        <a id="export-excel" class="dropdown-item" href="#">
                            <img class="avatar avatar-xss avatar-4by3 mr-2" src="{{ asset('public/assets/admin')}}/svg/components/excel.svg" alt="Image Description">
                            {{translate('messages.excel')}}
                        </a>
                        <a id="export-csv" class="dropdown-item" href="#">
                            <img class="avatar avatar-xss avatar-4by3 mr-2" src="{{ asset('public/assets/admin')}}/svg/components/placeholder-csv-format.svg" alt="Image Description">
                            {{translate('messages.csv')}}
                        </a>
                    </div>
                </div>
                <!-- Static Export Button -->

                <!-- Action button after check table row -->
                <button class="btn btn-danger btn-outline-danger">Cancel</button>
                <button class="btn btn-success">Complete</button>
                <!-- Action button after check table row -->

            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-thead-bordered table-align-middle card-table">
                    <thead>
                        <tr>
                            <th>
                                <label class="form-check form--check mb-14">
                                    <input type="checkbox" class="form-check-input">
                                </label>
                            </th>
                            <th>SL</th>
                            <th>Delivery Man Info</th>
                            <th>Disburse Amount</th>
                            <th>Payment method</th>
                            <th>
                                <div class="text-center">
                                    Action
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <label class="form-check form--check mb-14">
                                    <input type="checkbox" class="form-check-input">
                                </label>
                            </td>
                            <td>
                                <span class="font-weight-bold">1</span>
                            </td>
                            <td>
                                <div>Leslie Alexander</div>
                            </td>
                            <td>
                                <div class="ml-4 pl-5">
                                    <div class="pl-2">$6030.00</div>
                                </div>
                            </td>
                            <td>
                                <div>
                                    City Bank
                                </div>
                            </td>
                            <td>
                                <div class="btn--container justify-content-center">
                                    <a class="btn btn-sm btn--primary btn-outline-primary action-btn" href="javascript:" data-toggle="tooltip" title="View Details">
                                        <i class="tio-visible"></i>
                                    </a>
                                    <a class="btn btn-sm btn--danger btn-outline-danger action-btn" href="javascript:" data-toggle="tooltip" title="Cancel">
                                        <i class="tio-clear"></i>
                                    </a>
                                    <a class="btn btn-sm btn--primary btn-outline-primary action-btn" href="#" title="Edit addon" data-toggle="tooltip" title="Complete">
                                        <i class="tio-done"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label class="form-check form--check mb-14">
                                    <input type="checkbox" class="form-check-input">
                                </label>
                            </td>
                            <td>
                                <span class="font-weight-bold">1</span>
                            </td>
                            <td>
                                <div>Leslie Alexander</div>
                            </td>
                            <td>
                                <div class="ml-4 pl-5">
                                    <div class="pl-2">$6030.00</div>
                                </div>
                            </td>
                            <td>
                                <div>
                                    City Bank
                                </div>
                            </td>
                            <td>
                                <div class="btn--container justify-content-center">
                                    <a class="btn btn-sm btn--primary btn-outline-primary action-btn" href="javascript:" data-toggle="tooltip" title="View Details">
                                        <i class="tio-visible"></i>
                                    </a>
                                    <a class="btn btn-sm btn--danger btn-outline-danger action-btn" href="javascript:" data-toggle="tooltip" title="Cancel">
                                        <i class="tio-clear"></i>
                                    </a>
                                    <a class="btn btn-sm btn--primary btn-outline-primary action-btn" href="#" title="Edit addon" data-toggle="tooltip" title="Complete">
                                        <i class="tio-done"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>



@endsection

@push('script_2')

@endpush
