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
            <span>Restaurant Disbursement</span>
        </h1>
        <ul class="nav nav-tabs mb-4 border-0 pt-2">
            <li class="nav-item">
                <a class="nav-link active" href="#" >All</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">Pending</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">Processing</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">Completed</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">Partially Completed</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">Canceled</a>
            </li>
        </ul>
    </div>
    <!-- Reports -->
    <div class="d-flex flex-column gap-2">
        <div class="card">
            <div class="card-header border-0 flex-wrap justify-content-between gap-4">
                <div class="left">
                    <h3 class="m-0 font-bold">Disbursement #3893 <span class="badge badge-soft-success">Processing</span> </h3>
                    <span>Create at 12 July, 2022 4:30 pm</span>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div class="d-flex flex-wrap align-items-center mr-2">
                        <span>Total Amount</span> <span class="mx-2">:</span> <h3 class="m-0">$90,876</h3>
                    </div>
                    <div>
                        <a href="#" class="btn btn--primary">View Details</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header border-0 flex-wrap justify-content-between gap-4">
                <div class="left">
                    <h3 class="m-0 font-bold">Disbursement #3893 <span class="badge badge-soft-warning">Processing</span> </h3>
                    <span>Create at 12 July, 2022 4:30 pm</span>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div class="d-flex flex-wrap align-items-center mr-2">
                        <span>Total Amount</span> <span class="mx-2">:</span> <h3 class="m-0">$90,876</h3>
                    </div>
                    <div>
                        <a href="#" class="btn btn--primary">View Details</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header border-0 flex-wrap justify-content-between gap-4">
                <div class="left">
                    <h3 class="m-0 font-bold">Disbursement #3893 <span class="badge badge-soft-info">Processing</span> </h3>
                    <span>Create at 12 July, 2022 4:30 pm</span>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div class="d-flex flex-wrap align-items-center mr-2">
                        <span>Total Amount</span> <span class="mx-2">:</span> <h3 class="m-0">$90,876</h3>
                    </div>
                    <div>
                        <a href="#" class="btn btn--primary">View Details</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header border-0 flex-wrap justify-content-between gap-4">
                <div class="left">
                    <h3 class="m-0 font-bold">Disbursement #3893 <span class="badge badge-soft-danger">Processing</span> </h3>
                    <span>Create at 12 July, 2022 4:30 pm</span>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div class="d-flex flex-wrap align-items-center mr-2">
                        <span>Total Amount</span> <span class="mx-2">:</span> <h3 class="m-0">$90,876</h3>
                    </div>
                    <div>
                        <a href="#" class="btn btn--primary">View Details</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header border-0 flex-wrap justify-content-between gap-4">
                <div class="left">
                    <h3 class="m-0 font-bold">Disbursement #3893 <span class="badge badge-soft-secondary">Processing</span> </h3>
                    <span>Create at 12 July, 2022 4:30 pm</span>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div class="d-flex flex-wrap align-items-center mr-2">
                        <span>Total Amount</span> <span class="mx-2">:</span> <h3 class="m-0">$90,876</h3>
                    </div>
                    <div>
                        <a href="#" class="btn btn--primary">View Details</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>



@endsection

@push('script_2')

@endpush
