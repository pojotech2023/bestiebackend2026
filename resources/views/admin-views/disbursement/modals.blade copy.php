@extends('layouts.admin.app')

@section('title',translate('messages.disbursement'))

@push('css_or_js')

@endpush

@section('content')


<div class="content container-fluid">


    <!-- Bootstrap  Medium Modal -->
    <div class="modal fade" id="payment-info">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header border-bottom pb-4">
                    <button type="button" class="payment-modal-close btn-close border-0 outline-0 bg-transparent" data-dismiss="modal">
                        <i class="tio-clear"></i>
                    </button>
                    <div class="w-100 text-center">
                        <h2 class="mb-2">Payment Information</h2>
                        <div class="text-success mx-450 mx-auto">
                            You Payment has been completed. Your will  receive the amount within 7 day. Please wait till then.
                        </div>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-center mb-2">
                        <span class="mr-3">Disbursement  ID#</span> <h4 class="m-0">54564</h4>
                    </div>
                    <div class="d-flex justify-content-center">
                        <span class="mr-3">Amount</span> <h4 class="m-0">$7,000</h4>
                    </div>
                    <h3 class="text-center my-4">
                        Account Information
                    </h3>
                    <div class="mx-260 mx-auto">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="mr-3">Account Name</span> <h4 class="m-0">Jhone Doe</h4>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="mr-3">Account Number</span> <h4 class="m-0">454546546</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap  Medium Modal -->
    <div class="modal fade" id="payment-info-2">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header pb-4">
                    <button type="button" class="payment-modal-close btn-close border-0 outline-0 bg-transparent" data-dismiss="modal">
                        <i class="tio-clear"></i>
                    </button>
                    <div class="w-100 text-center">
                        <h2 class="mb-2">Payment Information</h2>
                        <div>
                            <span class="mr-2">Disbursement ID</span>
                            <strong>#4579</strong>
                        </div>
                        <div class="mt-2">
                            <span class="mr-2">Status</span>
                            <span class="badge badge-soft-primary">Pending</span>
                        </div>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="card shadow--card-2">
                        <div class="card-body">
                            <div class="d-flex flex-wrap payment-info-modal-info p-xl-4">
                                <div class="item">
                                    <h5>Restaurant Information</h5>
                                    <ul class="item-list">
                                        <li class="d-flex flex-wrap">
                                            <span class="name">Name</span>
                                            <span>:</span>
                                            <strong>Hungry Puppet</strong>
                                        </li>
                                        <li class="d-flex flex-wrap">
                                            <span class="name">Contact</span>
                                            <span>:</span>
                                            <strong>+085938754</strong>
                                        </li>
                                    </ul>
                                </div>
                                <div class="item">
                                    <h5>Owner Information</h5>
                                    <ul class="item-list">
                                        <li class="d-flex flex-wrap">
                                            <span class="name">Name</span>
                                            <span>:</span>
                                            <strong>John Doe</strong>
                                        </li>
                                        <li class="d-flex flex-wrap">
                                            <span class="name">Email</span>
                                            <span>:</span>
                                            <strong>deo@gmail.com</strong>
                                        </li>
                                    </ul>
                                </div>
                                <div class="item w-100">
                                    <h5>Account Information</h5>
                                    <ul class="item-list">
                                        <li class="d-flex flex-wrap">
                                            <span class="name">Payment Method</span>
                                            <strong>City Bank</strong>
                                        </li>
                                        <li class="d-flex flex-wrap">
                                            <span class="name">Amount</span>
                                            <strong>$7,000</strong>
                                        </li>
                                        <li class="d-flex flex-wrap">
                                            <span class="name">Account Number</span>
                                            <strong>656264</strong>
                                        </li>
                                        <li class="d-flex flex-wrap">
                                            <span class="name">Account Name</span>
                                            <strong>Jhone Doe</strong>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 btn--container justify-content-end">
                        <button type="button" class="btn btn--reset" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn--primary">Process for Payment</button>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="py-5">
        <div class="py-5"></div>
        <div class="py-5 d-flex gap-2 justify-content-center">
            <button class="btn btn--primary" type="button" data-toggle="modal" data-target="#payment-info">
                Payment Modal
            </button>
            <button class="btn btn--primary" type="button" data-toggle="modal" data-target="#payment-info-2">
                Payment Modal 2
            </button>
        </div>
        <div class="py-5"></div>
    </div>





</div>



@endsection

@push('script_2')

@endpush
