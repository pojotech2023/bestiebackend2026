@extends('layouts.admin.app')

@section('title', translate('Bestie Buddy Charge Settings'))

@php
    // Round to whole number, no decimals
    function cleanNum($val) {
        if ($val === null || $val === '') return $val;
        return (int) round((float) $val);
    }
@endphp

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm">
                <h1 class="page-header-title">
                    <span class="page-header-icon"><i class="tio-settings"></i></span>
                    Bestie Buddy — Charge Settings
                </h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-no-gutter">
                        <li class="breadcrumb-item"><a href="{{ route('admin.grocery-list.list') }}">Bestie Buddy</a></li>
                        <li class="breadcrumb-item active">Settings</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-7">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h5 class="card-header-title mb-0">
                        <i class="tio-percent mr-1"></i> Charge Configuration
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-4">
                        These charges are applied once the delivery man enters the <strong>Items Cost</strong>
                        for a Bestie Buddy order. Charges are calculated automatically and shown to the customer.
                    </p>

                    <form method="POST" action="{{ route('admin.grocery-list.settings.update') }}" id="settingsForm">
                        @csrf

                        <!-- ── Delivery Charge ───────────────────────────── -->
                        <div class="form-group row align-items-start">
                            <label class="col-sm-5 col-form-label font-weight-600 pt-2">
                                <i class="tio-bicycle mr-1 text-info"></i> Delivery Charge
                            </label>
                            <div class="col-sm-7">

                                {{-- Type toggle --}}
                                <div class="btn-group btn-group-sm w-100 mb-2" role="group" id="delivery-type-toggle">
                                    <button type="button"
                                            class="btn {{ $settings->delivery_charge_type === 'percentage' ? 'btn-info' : 'btn-outline-info' }}"
                                            onclick="setDeliveryType('percentage')"
                                            id="btn-percentage">
                                        <i class="tio-percent mr-1"></i> Percentage
                                    </button>
                                    <button type="button"
                                            class="btn {{ $settings->delivery_charge_type === 'fixed' ? 'btn-info' : 'btn-outline-info' }}"
                                            onclick="setDeliveryType('fixed')"
                                            id="btn-fixed">
                                        <i class="tio-indian-rupee mr-1"></i> Fixed Amount
                                    </button>
                                </div>

                                {{-- Hidden type field --}}
                                <input type="hidden" name="delivery_charge_type" id="delivery_charge_type"
                                       value="{{ old('delivery_charge_type', $settings->delivery_charge_type) }}">

                                {{-- Percentage input --}}
                                <div id="delivery-percentage-wrap"
                                     style="{{ $settings->delivery_charge_type === 'fixed' ? 'display:none;' : '' }}">
                                    <div class="input-group">
                                        <input type="number" name="delivery_charge_percentage"
                                               id="delivery_charge_percentage"
                                               class="form-control @error('delivery_charge_percentage') is-invalid @enderror"
                                               value="{{ old('delivery_charge_percentage', cleanNum($settings->delivery_charge_percentage)) }}"
                                               step="1" min="0" max="100"
                                               oninput="livePreview()">
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    @error('delivery_charge_percentage')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">% of Items Cost</small>
                                </div>

                                {{-- Fixed amount input --}}
                                <div id="delivery-fixed-wrap"
                                     style="{{ $settings->delivery_charge_type !== 'fixed' ? 'display:none;' : '' }}">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">₹</span>
                                        </div>
                                        <input type="number" name="delivery_charge_amount"
                                               id="delivery_charge_amount"
                                               class="form-control @error('delivery_charge_amount') is-invalid @enderror"
                                               value="{{ old('delivery_charge_amount', cleanNum($settings->delivery_charge_amount)) }}"
                                               step="1" min="0"
                                               oninput="livePreview()">
                                    </div>
                                    @error('delivery_charge_amount')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Flat amount — same for every order</small>
                                </div>

                            </div>
                        </div>

                        <!-- ── Service Charge ────────────────────────────── -->
                        <div class="form-group row align-items-center">
                            <label class="col-sm-5 col-form-label font-weight-600">
                                <i class="tio-shopping-cart mr-1 text-warning"></i> Service Charge
                            </label>
                            <div class="col-sm-7">
                                <div class="input-group">
                                    <input type="number" name="service_charge_percentage"
                                           id="service_charge_percentage"
                                           class="form-control @error('service_charge_percentage') is-invalid @enderror"
                                           value="{{ old('service_charge_percentage', cleanNum($settings->service_charge_percentage)) }}"
                                           step="1" min="0" max="100" required
                                           oninput="livePreview()">
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                @error('service_charge_percentage')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Applied on Items Cost</small>
                            </div>
                        </div>

                        <!-- ── GST ───────────────────────────────────────── -->
                        <div class="form-group row align-items-center">
                            <label class="col-sm-5 col-form-label font-weight-600">
                                <i class="tio-receipt mr-1 text-success"></i> Tax (GST)
                            </label>
                            <div class="col-sm-7">
                                <div class="input-group">
                                    <input type="number" name="gst_percentage"
                                           id="gst_percentage"
                                           class="form-control @error('gst_percentage') is-invalid @enderror"
                                           value="{{ old('gst_percentage', cleanNum($settings->gst_percentage)) }}"
                                           step="1" min="0" max="100" required
                                           oninput="livePreview()">
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                @error('gst_percentage')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Applied on (Items + Delivery + Service)</small>
                            </div>
                        </div>

                        <hr>

                        <!-- ── Live Preview ──────────────────────────────── -->
                        <div class="card bg-light border-0 mb-4">
                            <div class="card-body py-3">
                                <p class="font-weight-600 mb-2">
                                    Live Preview
                                    <small class="text-muted">(sample ₹500 order)</small>
                                </p>
                                <div id="preview-output" class="small"></div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn--primary w-100">
                            <i class="tio-save mr-1"></i> Save Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
// ── Toggle delivery charge type ───────────────────────────────────────────────
function setDeliveryType(type) {
    document.getElementById('delivery_charge_type').value = type;

    const pctWrap   = document.getElementById('delivery-percentage-wrap');
    const fixedWrap = document.getElementById('delivery-fixed-wrap');
    const btnPct    = document.getElementById('btn-percentage');
    const btnFixed  = document.getElementById('btn-fixed');

    if (type === 'fixed') {
        pctWrap.style.display   = 'none';
        fixedWrap.style.display = '';
        btnFixed.className  = 'btn btn-info';
        btnPct.className    = 'btn btn-outline-info';
        // Remove required from % input so form submits fine
        document.getElementById('delivery_charge_percentage').removeAttribute('required');
    } else {
        pctWrap.style.display   = '';
        fixedWrap.style.display = 'none';
        btnPct.className    = 'btn btn-info';
        btnFixed.className  = 'btn btn-outline-info';
        document.getElementById('delivery_charge_percentage').setAttribute('required', 'required');
    }
    livePreview();
}

// ── Live Preview ──────────────────────────────────────────────────────────────
function livePreview() {
    const type    = document.getElementById('delivery_charge_type').value;
    const service = Math.round(parseFloat(document.getElementById('service_charge_percentage').value) || 0);
    const gst     = Math.round(parseFloat(document.getElementById('gst_percentage').value) || 0);
    const sample  = 500;

    let deliveryCharge = 0;
    let deliveryLabel  = '';

    if (type === 'fixed') {
        deliveryCharge = Math.round(parseFloat(document.getElementById('delivery_charge_amount').value) || 0);
        deliveryLabel  = `Fixed ₹${deliveryCharge}`;
    } else {
        const pct      = Math.round(parseFloat(document.getElementById('delivery_charge_percentage').value) || 0);
        deliveryCharge = Math.round(sample * pct / 100);
        deliveryLabel  = `${pct}%`;
    }

    const serviceCharge = Math.round(sample * service / 100);
    const subtotal       = sample + deliveryCharge + serviceCharge;
    const gstAmount       = Math.round(subtotal * gst / 100);
    const total            = subtotal + gstAmount;

    const fmt = v => '₹' + Math.round(v).toLocaleString('en-IN');

    document.getElementById('preview-output').innerHTML = `
        <div class="d-flex justify-content-between">
            <span class="text-muted">Items Cost</span><span>${fmt(sample)}</span>
        </div>
        <div class="d-flex justify-content-between">
            <span class="text-muted">Delivery (${deliveryLabel})</span><span>${fmt(deliveryCharge)}</span>
        </div>
        <div class="d-flex justify-content-between">
            <span class="text-muted">Service (${service}%)</span><span>${fmt(serviceCharge)}</span>
        </div>
        <div class="d-flex justify-content-between">
            <span class="text-muted">GST (${gst}%)</span><span>${fmt(gstAmount)}</span>
        </div>
        <div class="d-flex justify-content-between font-weight-700 border-top mt-1 pt-1">
            <span>Total</span><span>${fmt(total)}</span>
        </div>
    `;
}

// Init on load
livePreview();
</script>
@endpush