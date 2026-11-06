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
            <span>Disbursement Report</span>
        </h1>
        <ul class="nav nav-tabs mb-4 border-0 pt-2">
            <li class="nav-item">
                <a class="nav-link active" href="#" >Restaurant Debursements</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">Delivery Man Debursements</a>
            </li>
        </ul>
    </div>
    <!-- Reports -->
    <div class="disbursement-report mb-20">
        <div class="__card-3 rebursement-item">
            <img src="{{ asset('assets/admin/img/report/new/trx1.png')}}" class="icon" alt="report/new">
            <h3 class="title text-008958">$2,000
            </h3>
            <h6 class="subtitle">Pending Disbursements</h6>
            <div class="info-icon" data-toggle="tooltip" data-placement="top" data-original-title="When the order is successfully delivered full order amount goes to this section.">
                <img src="{{ asset('assets/admin/img/report/new/info1.png')}}" alt="report/new">
            </div>
        </div>
        <div class="__card-3 rebursement-item">
            <img src="{{ asset('assets/admin/img/report/new/trx2.png')}}" class="icon" alt="report/new">
            <h3 class="title text-006AB4">$2,000
            </h3>
            <h6 class="subtitle">Processing Transactions</h6>
            <div class="info-icon" data-toggle="tooltip" data-placement="top" data-original-title="When the order is successfully delivered full order amount goes to this section.">
                <img src="{{ asset('assets/admin/img/report/new/info2.png')}}" alt="report/new">
            </div>
        </div>
        <div class="__card-3 rebursement-item">
            <img src="{{ asset('assets/admin/img/report/new/trx5.png')}}" class="icon" alt="report/new">
            <h3 class="title text-FF7E0D">$2,000
            </h3>
            <h6 class="subtitle">Completed Disbursements</h6>
            <div class="info-icon" data-toggle="tooltip" data-placement="top" data-original-title="When the order is successfully delivered full order amount goes to this section.">
                <img src="{{ asset('assets/admin/img/report/new/info5.png')}}" alt="report/new">
            </div>
        </div>
        <div class="__card-3 rebursement-item">
            <img src="{{ asset('assets/admin/img/report/new/trx4.png')}}" class="icon" alt="report/new">
            <h3 class="title text-008790">$2,000
            </h3>
            <h6 class="subtitle">Partially Completed Disbursements</h6>
            <div class="info-icon" data-toggle="tooltip" data-placement="top" data-original-title="When the order is successfully delivered full order amount goes to this section.">
                <img src="{{ asset('assets/admin/img/report/new/info4.png')}}" alt="report/new">
            </div>
        </div>
        <div class="__card-3 rebursement-item">
            <img src="{{ asset('assets/admin/img/report/new/trx3.png')}}" class="icon" alt="report/new">
            <h3 class="title text-FF5A54">$2,000
            </h3>
            <h6 class="subtitle">Canceled Transactions</h6>
            <div class="info-icon" data-toggle="tooltip" data-placement="top" data-original-title="When the order is successfully delivered full order amount goes to this section.">
                <img src="{{ asset('assets/admin/img/report/new/info3.png')}}" alt="report/new">
            </div>
        </div>
    </div>

    <div class="card mb-20">
        <div class="card-body">
            <h4 class="">Search Data</h4>
            <form>
                <div class="row g-3">
                    <div class="col-sm-6 col-md-3">
                        <select class="form-control js-select2-custom">
                            <option value="all">All Zones</option>
                            <option value="8">
                                ABCD
                            </option>
                            <option value="2">
                                Dhaka
                            </option>
                            <option value="1">
                                Farmgate
                            </option>
                            <option value="4">
                                Gazipur
                            </option>
                            <option value="3">
                                India
                            </option>
                        </select>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <select class="form-control js-select2-custom">
                            <option value="all" selected>All restaurants</option>
                            <option value="all">All restaurants</option>
                            <option value="all">All restaurants</option>
                        </select>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <select class="form-control js-select2-custom">
                            <option value="all_time">
                                All Status</option>
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
                    <div class="col-sm-6 col-md-3">
                        <select class="form-control js-select2-custom">
                            <option value="all_time">
                                All Payment Method</option>
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
                    <div class="col-sm-6 col-md-3">
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
                    <div class="col-sm-6 col-md-3 ml-auto">
                        <button type="submit" class="btn btn--primary btn-block btn-xl-inline-block ml-auto">Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-0 py-2">
            <div class="search--button-wrapper">
                <h3 class="card-title">
                    Total Disbursements <span class="badge badge-soft-secondary ml-2" id="countItems">468</span>
                </h3>
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
                            <img class="avatar avatar-xss avatar-4by3 mr-2" src="{{ asset('assets/admin')}}/svg/components/excel.svg" alt="Image Description">
                            {{translate('messages.excel')}}
                        </a>
                        <a id="export-csv" class="dropdown-item" href="#">
                            <img class="avatar avatar-xss avatar-4by3 mr-2" src="{{ asset('assets/admin')}}/svg/components/placeholder-csv-format.svg" alt="Image Description">
                            {{translate('messages.csv')}}
                        </a>
                    </div>
                </div>
                <!-- Static Export Button -->
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
                            <th>ID</th>
                            <th>Created At</th>
                            <th>Disburse Amount</th>
                            <th>Payment method</th>
                            <th>Status</th>
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
                                <span class="font-weight-bold">#4654</span>
                            </td>
                            <td>
                                <span>12 July, 2022  12:00 pm</span>
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
                                <div class="badge badge-soft-success">
                                    Completed
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
                                <span class="font-weight-bold">#4654</span>
                            </td>
                            <td>
                                <span>12 July, 2022  12:00 pm</span>
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
                                <div class="badge badge-soft-danger">
                                    Completed
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
                                <span class="font-weight-bold">#4654</span>
                            </td>
                            <td>
                                <span>12 July, 2022  12:00 pm</span>
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
                                <div class="badge badge-soft-info">
                                    Completed
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
                                <span class="font-weight-bold">#4654</span>
                            </td>
                            <td>
                                <span>12 July, 2022  12:00 pm</span>
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
                                <div class="badge badge-soft-primary">
                                    Completed
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
