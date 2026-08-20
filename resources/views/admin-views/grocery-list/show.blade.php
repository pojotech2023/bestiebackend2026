@extends('layouts.admin.app')

@section('title', translate('Bestie Buddy Order #') . $groceryList->id)

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .charge-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px dashed #eee; }
        .charge-row:last-child { border-bottom: none; font-weight: 700; font-size: 1.05rem; }
        .badge-charges-confirmed { background: #d4edda; color: #155724; }
        .badge-charges-pending   { background: #fff3cd; color: #856404; }
        /* Custom delivery charge toggle */
        #custom-delivery-wrap { background: #f8f9fa; border: 1px dashed #ced4da; border-radius: 6px; padding: 8px 10px; margin-top: 8px; }
        #custom-delivery-wrap label { font-size: 12px; color: #6c757d; margin-bottom: 3px; }
        .custom-delivery-badge { font-size: 10px; background: #cce5ff; color: #004085; border-radius: 3px; padding: 1px 5px; margin-left: 4px; vertical-align: middle; }

        /* FIX: Assign Delivery Man button -> bright blue */
        .btn-assign-dm {
            background-color: #2196f3 !important;
            border-color: #2196f3 !important;
            color: #fff !important;
        }
        .btn-assign-dm:hover {
            background-color: #1976d2 !important;
            border-color: #1976d2 !important;
            color: #fff !important;
        }

        .dm-online-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: #28a745; display: inline-block; margin-right: 4px;
        }
    </style>
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm">
                <h1 class="page-header-title">
                    <span class="page-header-icon"><i class="tio-shopping-basket"></i></span>
                    Bestie Buddy Order
                    <span class="badge badge-soft-dark ml-2">#{{ $groceryList->id }}</span>
                </h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-no-gutter">
                        <li class="breadcrumb-item"><a href="{{ route('admin.grocery-list.list') }}">Bestie Buddy</a></li>
                        <li class="breadcrumb-item active">Order #{{ $groceryList->id }}</li>
                    </ol>
                </nav>
            </div>
            <div class="col-sm-auto">
                <a href="{{ route('admin.grocery-list.edit', $groceryList->id) }}" class="btn btn-sm btn--warning">
                    <i class="tio-edit mr-1"></i> Edit Order
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- ── Left Column ─────────────────────────────────────────────────── -->
        <div class="col-lg-8">

            <!-- Customer & Order Details -->
            <div class="card mb-3">
                <div class="card-header"><h5 class="card-header-title">Order Information</h5></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row mb-0">
                                <dt class="col-sm-5 text-muted">Customer</dt>
                                <dd class="col-sm-7">
                                    @if($groceryList->user)
                                        <a href="{{ route('admin.customer.view', $groceryList->user_id) }}">
                                            {{ $groceryList->user->f_name ?? 'N/A' }} {{ $groceryList->user->l_name ?? '' }}
                                        </a>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </dd>
                                <dt class="col-sm-5 text-muted">Email</dt>
                                <dd class="col-sm-7">{{ $groceryList->user->email ?? 'N/A' }}</dd>
                                <dt class="col-sm-5 text-muted">Phone</dt>
                                <dd class="col-sm-7">{{ $groceryList->user->phone ?? 'N/A' }}</dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="row mb-0">
                                <dt class="col-sm-5 text-muted">Status</dt>
                                <dd class="col-sm-7">
                                    @php
                                        $sc = [
                                            'processing'       => 'warning',
                                            'assigned'         => 'info',
                                            'out_for_delivery' => 'primary',
                                            'completed'        => 'success',
                                            'cancelled'        => 'danger',
                                        ];
                                        // FIX: display labels (completed -> Delivered), DB value unchanged
                                        $statusLabels = [
                                            'processing'       => 'Processing',
                                            'assigned'         => 'Assigned',
                                            'out_for_delivery' => 'Out for delivery',
                                            'completed'        => 'Delivered',
                                            'cancelled'        => 'Cancelled',
                                        ];
                                    @endphp
                                    <span class="badge badge-soft-{{ $sc[$groceryList->status] ?? 'secondary' }}">
                                        {{ $statusLabels[$groceryList->status] ?? ucfirst(str_replace('_', ' ', $groceryList->status)) }}
                                    </span>
                                </dd>
                                <dt class="col-sm-5 text-muted">Date</dt>
                                <dd class="col-sm-7">{{ $groceryList->created_at->format('d M Y H:i') }}</dd>
                                @if($groceryList->assigned_at)
                                <dt class="col-sm-5 text-muted">Assigned At</dt>
                                <dd class="col-sm-7">{{ \Carbon\Carbon::parse($groceryList->assigned_at)->format('d M Y H:i') }}</dd>
                                @endif
                                @if($groceryList->completed_at)
                                <dt class="col-sm-5 text-muted">Completed At</dt>
                                <dd class="col-sm-7">{{ \Carbon\Carbon::parse($groceryList->completed_at)->format('d M Y H:i') }}</dd>
                                @endif
                            </dl>
                        </div>
                    </div>
                    @if($groceryList->description)
                        <hr>
                        <p class="mb-0"><strong>Note:</strong> {{ $groceryList->description }}</p>
                    @endif
                </div>
            </div>

            <!-- Delivery Address -->
            @if($groceryList->address || $groceryList->latitude)
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-header-title"><i class="tio-map-outlined mr-1"></i> Delivery Address</h5>
                </div>
                <div class="card-body">
                    @if($groceryList->address)
                        <p class="mb-1">{{ $groceryList->address }}</p>
                    @endif
                    <small class="text-muted">
                        @if($groceryList->house) House: {{ $groceryList->house }} &nbsp;|&nbsp; @endif
                        @if($groceryList->street_number) Street: {{ $groceryList->street_number }} &nbsp;|&nbsp; @endif
                        @if($groceryList->floor) Floor: {{ $groceryList->floor }} @endif
                    </small>
                    @if($groceryList->latitude && $groceryList->longitude)
                        <br>
                        <a href="https://maps.google.com/?q={{ $groceryList->latitude }},{{ $groceryList->longitude }}"
                           target="_blank" class="btn btn-xs btn-outline-info mt-2">
                            <i class="tio-map mr-1"></i> View on Map
                        </a>
                    @endif
                </div>
            </div>
            @endif

            <!-- Items List -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-header-title">
                        <i class="tio-list mr-1"></i> Items ({{ $groceryList->items->count() }})
                    </h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-borderless mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>Item Name</th>
                                <th>Quantity</th>
                                <th>Unit</th>
                                <th>Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($groceryList->items as $i => $item)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $item->item_name }}</td>
                                <td>{{ $item->quantity ?? '—' }}</td>
                                <td>{{ $item->unit ?? '—' }}</td>
                                <td>{{ $item->count ?? '—' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">No items added.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Reference Images -->
            @if($groceryList->image_records->count())
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-header-title">
                        <i class="tio-photo mr-1"></i> Reference Images ({{ $groceryList->image_records->count() }})
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($groceryList->image_records->sortBy('sort_order') as $img)
                        <div class="col-4 col-md-3 mb-3">
                            <a href="{{ $img->file_url }}" target="_blank">
                                <img src="{{ $img->file_url }}"
                                     class="img-fluid rounded"
                                     style="height:100px;width:100%;object-fit:cover;"
                                     alt="{{ $img->original_name ?? 'Reference Image' }}"
                                     onerror="this.src='{{ asset('public/assets/admin/img/160x160/img2.jpg') }}'">
                            </a>
                            @if($img->original_name)
                                <small class="text-muted d-block text-center mt-1"
                                       style="font-size:10px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                    {{ $img->original_name }}
                                </small>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

        </div>

        <!-- ── Right Column: Charges + Delivery Man ──────────────────────────── -->
        <div class="col-lg-4">

            <!-- Order Summary / Charges -->
            <div class="card mb-3">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-header-title mb-0"><i class="tio-money mr-1"></i> Order Summary</h5>
                    @php
                        $summarySc = [
                            'processing'       => 'warning',
                            'assigned'         => 'info',
                            'out_for_delivery' => 'primary',
                            'completed'        => 'success',
                            'cancelled'        => 'danger',
                        ];
                    @endphp
                    <span class="badge badge-soft-{{ $summarySc[$groceryList->status] ?? 'secondary' }}">
                        {{ $statusLabels[$groceryList->status] ?? ucfirst(str_replace('_', ' ', $groceryList->status)) }}
                    </span>
                </div>
                <div class="card-body">
                    <div id="charges-display">
                        <div class="charge-row">
                            <span class="text-muted">Items Cost</span>
                            <span>₹<span id="d-items">{{ number_format($groceryList->items_cost ?? 0, 0) }}</span></span>
                        </div>
                        <div class="charge-row">
                            <span class="text-muted">
                                Delivery Charge
                                {{--
                                    FIX: Show correct label based on:
                                    1. custom_delivery_charge set on order  → "Custom ₹XX"
                                    2. global type = fixed                  → "Fixed ₹30"
                                    3. global type = percentage             → "5%"
                                    FIX: whole numbers only, no decimals
                                --}}
                                @if($groceryList->custom_delivery_charge !== null)
                                    <span class="custom-delivery-badge">Custom ₹{{ number_format($groceryList->custom_delivery_charge, 0) }}</span>
                                @elseif($settings->delivery_charge_type === 'fixed')
                                    <small class="text-muted">(Fixed ₹{{ number_format($settings->delivery_charge_amount, 0) }})</small>
                                @else
                                    <small class="text-muted">({{ round($settings->delivery_charge_percentage) }}%)</small>
                                @endif
                            </span>
                            <span>₹<span id="d-delivery">{{ number_format($groceryList->delivery_charge ?? 0, 0) }}</span></span>
                        </div>
                        <div class="charge-row">
                            <span class="text-muted">
                                Service Charge
                                <small class="text-muted">({{ round($settings->service_charge_percentage) }}%)</small>
                            </span>
                            <span>₹<span id="d-service">{{ number_format($groceryList->service_charge ?? 0, 0) }}</span></span>
                        </div>
                        <div class="charge-row">
                            <span class="text-muted">
                                GST
                                <small class="text-muted">({{ round($settings->gst_percentage) }}%)</small>
                            </span>
                            <span>₹<span id="d-gst">{{ number_format($groceryList->gst_amount ?? 0, 0) }}</span></span>
                        </div>
                        <div class="charge-row">
                            <span>Total</span>
                            <span>₹<span id="d-total">{{ number_format($groceryList->total_amount ?? 0, 0) }}</span></span>
                        </div>
                    </div>

                    @if(!$groceryList->is_price_confirmed)
                    <hr>
                    <form id="chargesForm">
                        @csrf
                        {{-- Items Cost --}}
                        <div class="form-group mb-2">
                            <label class="form-label">
                                Items Cost (₹)
                                <small class="text-muted">— other charges auto-calculated</small>
                            </label>
                            <input type="number" id="items_cost_input" name="items_cost"
                                   step="1" min="0"
                                   value="{{ $groceryList->items_cost ? (int)$groceryList->items_cost : '' }}"
                                   class="form-control form-control-sm"
                                   placeholder="Enter items cost">
                        </div>

                        {{-- Custom Delivery Charge toggle --}}
                        <div class="form-group mb-2">
                            <div class="d-flex align-items-center" style="gap:6px;">
                                <input type="checkbox" id="use-custom-delivery"
                                       {{ $groceryList->custom_delivery_charge !== null ? 'checked' : '' }}>
                                <label for="use-custom-delivery" class="mb-0 small" style="cursor:pointer;">
                                    Set custom delivery charge for this order
                                </label>
                            </div>
                            <div id="custom-delivery-wrap" style="display:{{ $groceryList->custom_delivery_charge !== null ? 'block' : 'none' }};">
                                <label>Custom Delivery Charge (₹)
                                    {{-- FIX: Show correct global fallback label --}}
                                    @if($settings->delivery_charge_type === 'fixed')
                                        <small class="text-muted">— overrides global Fixed ₹{{ number_format($settings->delivery_charge_amount, 0) }}</small>
                                    @else
                                        <small class="text-muted">— overrides global {{ round($settings->delivery_charge_percentage) }}%</small>
                                    @endif
                                </label>
                                <input type="number" id="custom_delivery_input" name="custom_delivery_charge"
                                       step="1" min="0"
                                       value="{{ $groceryList->custom_delivery_charge !== null ? (int)$groceryList->custom_delivery_charge : '' }}"
                                       class="form-control form-control-sm"
                                       placeholder="e.g. 50">
                                <small class="text-muted d-block mt-1">
                                    Leave empty to revert to global setting.
                                </small>
                            </div>
                        </div>

                        <div class="d-flex" style="gap:6px;">
                            <button type="button" class="btn btn-sm btn-outline-secondary flex-fill"
                                    onclick="previewCharges()">
                                <i class="tio-refresh mr-1"></i> Preview
                            </button>
                            <button type="button" class="btn btn-sm btn--primary flex-fill"
                                    onclick="saveCharges(false)">
                                Save
                            </button>
                            <button type="button" class="btn btn-sm btn-success flex-fill"
                                    onclick="saveCharges(true)">
                                Confirm
                            </button>
                        </div>
                    </form>
                    @else
                        <p class="text-success mt-2 mb-0 small">
                            <i class="tio-checkmark-circle mr-1"></i>
                            Confirmed
                            @if($groceryList->price_confirmed_at)
                                {{ \Carbon\Carbon::parse($groceryList->price_confirmed_at)->format('d M Y H:i') }}
                            @endif
                        </p>
                    @endif
                </div>
            </div>

            <!-- Delivery Man -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-header-title"><i class="tio-user mr-1"></i> Delivery Man</h5>
                </div>
                <div class="card-body">
                    @if($groceryList->delivery_man)
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar avatar-circle mr-2"
                                 style="background:linear-gradient(135deg,#11998e,#38ef7d);
                                        display:flex;align-items:center;justify-content:center;
                                        color:#fff;font-weight:700;font-size:14px;
                                        width:40px;height:40px;border-radius:50%;">
                                {{ strtoupper(substr($groceryList->delivery_man->f_name ?? 'D', 0, 1)) }}
                            </div>
                            <div>
                                <strong>
                                    {{ $groceryList->delivery_man->f_name }}
                                    {{ $groceryList->delivery_man->l_name }}
                                </strong>
                                <div class="text-muted small">{{ $groceryList->delivery_man->phone }}</div>
                                @if(isset($groceryList->delivery_man->current_orders))
                                    <div class="text-muted small">
                                        Active orders: {{ $groceryList->delivery_man->current_orders }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @else
                        <p class="text-muted small mb-3">No delivery man assigned yet.</p>
                    @endif

                    {{-- FIX: bright blue button --}}
                    <button class="btn btn-sm btn-assign-dm w-100" onclick="loadDeliveryMen()">
                        <i class="tio-user-add mr-1"></i>
                        {{ $groceryList->delivery_man ? 'Reassign' : 'Assign' }} Delivery Man
                    </button>

                    <div id="dm-list" class="mt-3" style="display:none;">
                        <div class="input-group input-group-sm mb-2">
                            <input type="text" id="dm-search" class="form-control"
                                   placeholder="Search delivery man...">
                        </div>
                        <div id="dm-items" style="max-height:250px;overflow-y:auto;"></div>
                    </div>
                </div>
            </div>

            <!-- Status Update -->
            <div class="card mb-3">
                <div class="card-header"><h5 class="card-header-title">Update Status</h5></div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.grocery-list.status') }}">
                        <input type="hidden" name="id" value="{{ $groceryList->id }}">
                        <div class="input-group input-group-sm">
                            <select name="status" class="form-control">
                                @foreach(['processing','assigned','out_for_delivery','completed','cancelled'] as $s)
                                    <option value="{{ $s }}" {{ $groceryList->status === $s ? 'selected' : '' }}>
                                        {{ $statusLabels[$s] ?? ucfirst(str_replace('_', ' ', $s)) }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="input-group-append">
                                <button type="submit" class="btn btn--primary btn-sm">Update</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('script')
<script>
const orderId    = {{ $groceryList->id }};
const chargesUrl = "{{ route('admin.grocery-list.update-charges', $groceryList->id) }}";
const dmUrl      = "{{ route('admin.grocery-list.available-delivery-men', $groceryList->id) }}";
const assignBase = "{{ url('admin/grocery-list/assign-delivery-man') }}";
const csrf       = document.querySelector('meta[name=csrf-token]').content;

// ── FIX: Pass global settings to JS for correct preview calculation ───────────
const globalDeliveryType       = "{{ $settings->delivery_charge_type }}";
const globalDeliveryAmount     = {{ (float) $settings->delivery_charge_amount }};
const globalDeliveryPercentage = {{ (float) $settings->delivery_charge_percentage }};
const globalServicePct         = {{ (float) $settings->service_charge_percentage }};
const globalGstPct             = {{ (float) $settings->gst_percentage }};

// ── Custom delivery charge toggle ─────────────────────────────────────────────
document.getElementById('use-custom-delivery')?.addEventListener('change', function () {
    const wrap  = document.getElementById('custom-delivery-wrap');
    const input = document.getElementById('custom_delivery_input');
    wrap.style.display = this.checked ? 'block' : 'none';
    if (!this.checked) input.value = '';
});

// ── Build payload (items_cost + optional custom_delivery_charge) ──────────────
function buildPayload(confirm) {
    const cost      = parseFloat(document.getElementById('items_cost_input').value) || 0;
    const useCustom = document.getElementById('use-custom-delivery')?.checked;
    const customVal = document.getElementById('custom_delivery_input')?.value ?? '';

    const payload = { items_cost: cost, confirm };
    if (useCustom && customVal !== '') {
        payload.custom_delivery_charge = parseFloat(customVal);
    } else {
        payload.custom_delivery_charge = '';
    }
    return payload;
}

function previewCharges() {
    const cost = parseFloat(document.getElementById('items_cost_input').value) || 0;
    if (cost <= 0) { toastr.warning('Please enter a valid items cost.'); return; }

    fetch(chargesUrl, {
        method : 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        body   : JSON.stringify(buildPayload(false)),
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) { toastr.error(data.error); return; }
        updateDisplay(data);
    })
    .catch(() => toastr.error('Preview failed. Please try again.'));
}

function saveCharges(confirm) {
    const cost = parseFloat(document.getElementById('items_cost_input').value) || 0;
    if (cost <= 0) { toastr.warning('Please enter a valid items cost.'); return; }

    fetch(chargesUrl, {
        method : 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        body   : JSON.stringify(buildPayload(confirm)),
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) { toastr.error(data.error); return; }
        updateDisplay(data);

        if (confirm) {
            toastr.success('Charges confirmed! Customer notified.');
            setTimeout(() => location.reload(), 1500);
        } else {
            toastr.success('Charges saved successfully.');
        }
    })
    .catch(() => toastr.error('Save failed. Please try again.'));
}

// ── Display as integer (no decimals) ─────────────────────────────────────────
function updateDisplay(data) {
    document.getElementById('d-items').textContent    = Math.round(data.items_cost);
    document.getElementById('d-delivery').textContent = Math.round(data.delivery_charge);
    document.getElementById('d-service').textContent  = Math.round(data.service_charge);
    document.getElementById('d-gst').textContent      = Math.round(data.gst_amount);
    document.getElementById('d-total').textContent    = Math.round(data.total_amount);
}

// ── Delivery man list ─────────────────────────────────────────────────────────
let allDMs = [];

function loadDeliveryMen() {
    const list = document.getElementById('dm-list');
    const isHidden = list.style.display === 'none' || list.style.display === '';
    list.style.display = isHidden ? 'block' : 'none';
    if (!isHidden || allDMs.length) return;

    document.getElementById('dm-items').innerHTML =
        '<p class="text-muted small text-center py-2"><i class="tio-refresh tio-lg"></i> Loading...</p>';

    fetch(dmUrl, { headers: { Accept: 'application/json' } })
        .then(r => r.json())
        .then(data => {
            // FIX: only keep ONLINE delivery men.
            // Backend should already filter, but we double-filter here as a safety net.
            // Adjust the field name below to match whatever your API returns
            // (e.g. dm.active, dm.is_online, dm.status === 'online').
            allDMs = (data.delivery_men || []).filter(dm => {
                if (typeof dm.active !== 'undefined') return Number(dm.active) === 1;
                if (typeof dm.is_online !== 'undefined') return Boolean(dm.is_online);
                if (typeof dm.status !== 'undefined') return dm.status === 'online' || dm.status === 'active';
                return true; // fallback: if no online flag exists, show all (backend filter required)
            });
            renderDMs(allDMs);
        })
        .catch(() => {
            document.getElementById('dm-items').innerHTML =
                '<p class="text-danger small text-center py-2">Failed to load delivery men.</p>';
        });
}

function renderDMs(dms) {
    const container = document.getElementById('dm-items');
    if (!dms.length) {
        container.innerHTML = '<p class="text-muted small text-center py-2">No online delivery men available.</p>';
        return;
    }
    container.innerHTML = dms.map(dm => `
        <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
            <div>
                <strong><span class="dm-online-dot"></span>${dm.name}</strong>
                <div class="text-muted small">${dm.phone} &bull; ${dm.current_orders} active orders</div>
            </div>
            <button class="btn btn-xs btn-assign-dm" onclick="assignDM(${dm.id})">Assign</button>
        </div>
    `).join('');
}

document.getElementById('dm-search')?.addEventListener('input', function () {
    const q = this.value.toLowerCase();
    renderDMs(allDMs.filter(d =>
        d.name.toLowerCase().includes(q) || d.phone.includes(q)
    ));
});

function assignDM(dmId) {
    if (!confirm('Assign this delivery man to the order?')) return;
    fetch(`${assignBase}/${orderId}/${dmId}`, { headers: { Accept: 'application/json' } })
    .then(r => r.json())
    .then(data => {
        toastr.success(data.message || 'Assigned successfully.');
        setTimeout(() => location.reload(), 1200);
    })
    .catch(() => toastr.error('Assignment failed. Please try again.'));
}
</script>
@endpush