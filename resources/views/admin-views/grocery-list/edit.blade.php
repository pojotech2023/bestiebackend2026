@extends('layouts.admin.app')

@section('title', translate('Edit Bestie Buddy Order #') . $groceryList->id)

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .item-row { background: #fff; border: 1px solid #e7eaf3; border-radius: 6px; padding: 10px 12px; margin-bottom: 8px; }
        .item-row:hover { border-color: #bac2ce; }
        .item-row.row-invalid { border-color: #e63757; background: #fff5f6; }
        .btn-remove-item { color: #e63757; background: none; border: none; cursor: pointer; padding: 0 4px; font-size: 18px; line-height: 1; }
        .btn-remove-item:hover { color: #c0392b; }
        .image-preview-wrap { position: relative; display: inline-block; margin: 4px; }
        .image-preview-wrap img { width: 90px; height: 90px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd; }
        .image-preview-wrap .remove-img { position: absolute; top: -6px; right: -6px; background: #e63757; color: #fff;
            border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer;
            display: flex; align-items: center; justify-content: center; }
        #image-previews { display: flex; flex-wrap: wrap; margin-top: 8px; }
        .existing-img-wrap { position: relative; display: inline-block; margin: 4px; }
        .existing-img-wrap img { width: 90px; height: 90px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd; }
        .existing-img-wrap .remove-existing { position: absolute; top: -6px; right: -6px; background: #e63757; color: #fff;
            border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer;
            display: flex; align-items: center; justify-content: center; z-index: 10; }
        .existing-img-wrap.marked-delete img { opacity: 0.35; border-color: #e63757; }
        .existing-img-wrap.marked-delete .remove-existing { background: #6c757d; }
        #items-container .item-row input,
        #items-container .item-row select,
        #new-items-container .item-row input,
        #new-items-container .item-row select { font-size: 13px; }

        .item-row .row > [class*="col-"],
        .item-labels-header > [class*="col-"] {
            padding-left: 6px;
            padding-right: 6px;
        }


        /* ── Custom delivery charge toggle ── */
        .delivery-toggle-wrap label { cursor: pointer; }
        #custom-delivery-row { transition: all .2s; }
        .custom-delivery-hint { font-size: 11px; color: #8c98a4; margin-top: 4px; }
        .charge-badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .charge-badge.global  { background: #e8f7ee; color: #00b074; }
        .charge-badge.custom  { background: #fff3e0; color: #e86900; }
    </style>
@endpush

@section('content')
<div class="content container-fluid">

    {{-- Page Header --}}
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm">
                <h1 class="page-header-title">
                    <span class="page-header-icon"><i class="tio-edit"></i></span>
                    Edit Bestie Buddy Order
                    <span class="badge badge-soft-dark ml-2">#{{ $groceryList->id }}</span>
                </h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-no-gutter">
                        <li class="breadcrumb-item"><a href="{{ route('admin.grocery-list.list') }}">Bestie Buddy</a></li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.grocery-list.show', $groceryList->id) }}">Order #{{ $groceryList->id }}</a>
                        </li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </nav>
            </div>
            <div class="col-sm-auto">
                <a href="{{ route('admin.grocery-list.show', $groceryList->id) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="tio-arrow-backward mr-1"></i> Back to Order
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }} <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <form action="{{ route('admin.grocery-list.update', $groceryList->id) }}" method="POST" enctype="multipart/form-data" id="editForm">
        @csrf
        @method('PUT')

        {{-- Hidden: deleted image IDs --}}
        <div id="deleted-images-container"></div>

        <div class="row">

            {{-- ── Left Column ──────────────────────────────────────────────── --}}
            <div class="col-lg-8">

                {{-- Basic Info --}}
                <div class="card mb-3">
                    <div class="card-header"><h5 class="card-header-title">Order Information</h5></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Customer</label>
                                    <input type="text" class="form-control form-control-sm"
                                           value="{{ ($groceryList->user->f_name ?? '') . ' ' . ($groceryList->user->l_name ?? '') }}"
                                           readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-control form-control-sm">
                                        @php
                                            $statusLabels = [
                                                'processing'       => 'Processing',
                                                'assigned'         => 'Assigned',
                                                'out_for_delivery' => 'Out for delivery',
                                                'completed'        => 'Delivered',
                                                'cancelled'        => 'Cancelled',
                                            ];
                                        @endphp
                                        @foreach(['processing','assigned','out_for_delivery','completed','cancelled'] as $s)
                                            <option value="{{ $s }}" {{ $groceryList->status === $s ? 'selected' : '' }}>
                                                {{ $statusLabels[$s] ?? ucfirst(str_replace('_', ' ', $s)) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-2">
                            <label class="form-label">Note / Description</label>
                            <textarea name="description" class="form-control form-control-sm" rows="2"
                                      placeholder="Optional note...">{{ old('description', $groceryList->description) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Items --}}
                <div class="card mb-3">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-header-title mb-0">
                            <i class="tio-list mr-1"></i> Items
                        </h5>
                        <button type="button" class="btn btn-sm btn--primary" onclick="addItemRow()">
                            <i class="tio-add mr-1"></i> Add Item
                        </button>
                    </div>
                    <div class="card-body">

                        {{-- Column labels --}}
                        <div class="row mb-1 px-1 d-none d-md-flex item-labels-header" style="font-size:12px;color:#8c98a4;font-weight:600;">
                            <div class="col-md-4">ITEM NAME <span class="text-danger">*</span></div>
                            <div class="col-md-2">QUANTITY <span class="text-danger">*</span></div>
                            <div class="col-md-2">UNIT <span class="text-danger">*</span></div>
                            <div class="col-md-2">COUNT <span class="text-danger">*</span></div>
                            <div class="col-md-2 text-center">REMOVE</div>
                        </div>

                        {{--
                            FIX: Unit list — shared by existing rows (server-rendered)
                            and new rows (JS-rendered). Keep both lists in sync.
                            Add/remove options here if client needs more units.
                        --}}
                        @php
                            $unitOptions = ['kg', 'g', 'liters', 'ml', 'pcs', 'dozen', 'pack', 'box', 'bunch', 'bag'];

                            // Strip trailing zeros from numeric strings: "250.00" -> "250", "2.50" -> "2.5"
                            if (!function_exists('bb_fmt_num')) {
                                function bb_fmt_num($v) {
                                    if ($v === null || $v === '') return '';
                                    if (!is_numeric($v)) return $v;
                                    $s = rtrim(rtrim(number_format((float)$v, 2, '.', ''), '0'), '.');
                                    return $s === '' ? '0' : $s;
                                }
                            }
                        @endphp

                        <div id="items-container">
                            @forelse($groceryList->items as $item)
                            <div class="item-row" data-item-id="{{ $item->id }}">
                                <div class="row align-items-center">
                                    <div class="col-12 col-md-4 mb-2 mb-md-0">
                                        <input type="text"
                                               name="items[{{ $item->id }}][item_name]"
                                               value="{{ old('items.'.$item->id.'.item_name', $item->item_name) }}"
                                               class="form-control form-control-sm"
                                               placeholder="Item name" required>
                                        <input type="hidden" name="items[{{ $item->id }}][id]" value="{{ $item->id }}">
                                    </div>
                                    <div class="col-4 col-md-2">
                                        <input type="text"
                                               name="items[{{ $item->id }}][quantity]"
                                               value="{{ old('items.'.$item->id.'.quantity', bb_fmt_num($item->quantity)) }}"
                                               class="form-control form-control-sm item-quantity-field"
                                               placeholder="e.g. 2.5">
                                    </div>
                                    <div class="col-4 col-md-2">
                                        {{-- FIX: unit dropdown instead of free text --}}
                                        <select name="items[{{ $item->id }}][unit]" class="form-control form-control-sm item-unit-field">
                                            <option value="">-- Unit --</option>
                                            @foreach($unitOptions as $u)
                                                <option value="{{ $u }}"
                                                    {{ old('items.'.$item->id.'.unit', $item->unit) === $u ? 'selected' : '' }}>
                                                    {{ ucfirst($u) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-2 col-md-2">
                                        <input type="text"
                                               name="items[{{ $item->id }}][count]"
                                               value="{{ old('items.'.$item->id.'.count', bb_fmt_num($item->count)) }}"
                                               class="form-control form-control-sm item-count-field"
                                               placeholder="e.g. 1">
                                    </div>
                                    <div class="col-2 col-md-2 text-center">
                                        <button type="button" class="btn-remove-item" onclick="removeItemRow(this)" title="Remove">
                                            &times;
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @empty
                            @endforelse
                        </div>

                        {{-- New items appended here by JS --}}
                        <div id="new-items-container"></div>

                        <p id="no-items-msg" class="text-muted small mt-2 mb-0" style="display:none;">
                            No items yet. Click <strong>Add Item</strong> to begin.
                        </p>

                    </div>
                </div>

                {{-- Images --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-header-title">
                            <i class="tio-photo mr-1"></i> Reference Images
                            <small class="text-muted font-weight-normal">(optional · multiple allowed)</small>
                        </h5>
                    </div>
                    <div class="card-body">

                        @if($groceryList->image_records->count())
                        <p class="small text-muted mb-1">Existing images — click <strong>&times;</strong> to remove:</p>
                        <div id="existing-images" class="d-flex flex-wrap mb-3">
                            @foreach($groceryList->image_records->sortBy('sort_order') as $img)
                            <div class="existing-img-wrap" id="eimg-{{ $img->id }}">
                                <img src="{{ $img->file_url }}"
                                     alt="{{ $img->original_name ?? 'Image' }}"
                                     onerror="this.src='{{ asset('public/assets/admin/img/160x160/img2.jpg') }}'">
                                <div class="remove-existing" onclick="markDeleteImage({{ $img->id }})" title="Remove">
                                    &times;
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif

                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="new_images" name="new_images[]"
                                   accept="image/*" multiple onchange="previewNewImages(this)">
                            <label class="custom-file-label" for="new_images">Choose images...</label>
                        </div>
                        <div id="image-previews"></div>
                        <small class="text-muted d-block mt-1">Accepted: JPG, PNG, WEBP. Max 2MB each.</small>
                    </div>
                </div>

            </div>

            {{-- ── Right Column ─────────────────────────────────────────────── --}}
            <div class="col-lg-4">

                {{-- Delivery Address --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-header-title"><i class="tio-map-outlined mr-1"></i> Delivery Address</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-2">
                            <label class="form-label small">Full Address</label>
                            <textarea name="address" class="form-control form-control-sm" rows="2"
                                      placeholder="Street address...">{{ old('address', $groceryList->address) }}</textarea>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group mb-2">
                                    <label class="form-label small">House / Flat</label>
                                    <input type="text" name="house" class="form-control form-control-sm"
                                           value="{{ old('house', $groceryList->house) }}" placeholder="Flat 3B">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group mb-2">
                                    <label class="form-label small">Street No.</label>
                                    <input type="text" name="street_number" class="form-control form-control-sm"
                                           value="{{ old('street_number', $groceryList->street_number) }}" placeholder="12">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group mb-0">
                                    <label class="form-label small">Floor</label>
                                    <input type="text" name="floor" class="form-control form-control-sm"
                                           value="{{ old('floor', $groceryList->floor) }}" placeholder="3rd Floor">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── Pricing card ──────────────────────────────── --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-header-title"><i class="tio-money mr-1"></i> Pricing</h5>
                    </div>
                    <div class="card-body">

                        {{-- Items Cost --}}
                        <div class="form-group mb-3">
                            <label class="form-label small">Items Cost (₹)</label>
                            <input type="number" name="items_cost" id="items_cost" step="1" min="0"
                                   class="form-control form-control-sm"
                                   value="{{ old('items_cost', $groceryList->items_cost !== null ? (int)$groceryList->items_cost : '') }}"
                                   placeholder="0"
                                   oninput="updateDeliveryPreview()">
                            <small class="text-muted">Other charges auto-calculated on save.</small>
                        </div>

                        {{-- ── Custom Delivery Charge ──────────────────── --}}
                        <div class="border rounded p-2 mb-2" style="background:#f8f9fa;">
                            <div class="delivery-toggle-wrap d-flex align-items-center justify-content-between mb-1">
                                <label class="form-label small mb-0 font-weight-600" for="use_custom_delivery">
                                    <i class="tio-bike mr-1 text-info"></i> Delivery Charge
                                </label>

                                {{-- Current mode badge --}}
                                @if($groceryList->custom_delivery_charge !== null)
                                    <span class="charge-badge custom" id="delivery-mode-badge">
                                        Custom ₹{{ number_format($groceryList->custom_delivery_charge, 0) }}
                                    </span>
                                @else
                                    <span class="charge-badge global" id="delivery-mode-badge">
                                        Global %
                                    </span>
                                @endif
                            </div>

                            {{-- Toggle --}}
                            <div class="custom-control custom-switch mb-2">
                                <input type="checkbox" class="custom-control-input" id="use_custom_delivery"
                                       onchange="toggleCustomDelivery(this)"
                                       {{ $groceryList->custom_delivery_charge !== null ? 'checked' : '' }}>
                                <label class="custom-control-label small" for="use_custom_delivery">
                                    Set custom amount for this order only
                                </label>
                            </div>

                            {{-- Custom amount row (shown/hidden by toggle) --}}
                            <div id="custom-delivery-row" style="{{ $groceryList->custom_delivery_charge !== null ? '' : 'display:none;' }}">
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">₹</span>
                                    </div>
                                    <input type="number" name="custom_delivery_charge"
                                           id="custom_delivery_charge"
                                           class="form-control form-control-sm"
                                           step="1" min="0"
                                           value="{{ old('custom_delivery_charge', $groceryList->custom_delivery_charge !== null ? (int)$groceryList->custom_delivery_charge : '') }}"
                                           placeholder="e.g. 30"
                                           oninput="updateDeliveryPreview()">
                                </div>
                                <p class="custom-delivery-hint mt-1 mb-0">
                                    <i class="tio-info-outined"></i>
                                    This flat amount replaces the global % charge for this order only.
                                    It will <strong>not</strong> be added on top — it replaces it.
                                </p>
                            </div>

                            {{-- Live preview --}}
                            <div id="delivery-preview" class="mt-2" style="font-size:12px; color:#677788;"></div>
                        </div>

                        @if($groceryList->is_price_confirmed)
                            <div class="alert alert-success py-1 px-2 small mb-0 mt-2">
                                <i class="tio-checkmark-circle mr-1"></i> Price already confirmed — charges locked.
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Save --}}
                <div class="card mb-3">
                    <div class="card-body">
                        <button type="submit" class="btn btn--primary btn-block">
                            <i class="tio-save mr-1"></i> Save Changes
                        </button>
                        <a href="{{ route('admin.grocery-list.show', $groceryList->id) }}"
                           class="btn btn-outline-secondary btn-block mt-2">
                            Cancel
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </form>

</div>
@endsection

@push('script')
<script>
// ── Unit options (must match the PHP $unitOptions array above) ───────────────
const unitOptions = ['kg', 'g', 'liters', 'ml', 'pcs', 'dozen', 'pack', 'box', 'bunch', 'bag'];

function buildUnitSelectHtml(name) {
    let opts = `<option value="">-- Unit --</option>`;
    unitOptions.forEach(u => {
        opts += `<option value="${u}">${u.charAt(0).toUpperCase() + u.slice(1)}</option>`;
    });
    return `<select name="${name}" class="form-control form-control-sm">${opts}</select>`;
}

// ── Toast helper (uses toastr if available on the page, else falls back to alert) ──
function bbToast(message, type) {
    type = type || 'error';
    if (typeof toastr !== 'undefined') {
        toastr[type](message);
    } else if (typeof iziToast !== 'undefined') {
        iziToast[type === 'error' ? 'error' : 'success']({ message: message, position: 'topRight' });
    } else {
        alert(message);
    }
}

// ── Strip trailing zeros from a numeric string: "250.00" -> "250", "2.50" -> "2.5" ──
function bbFormatNum(val) {
    if (val === null || val === undefined) return '';
    val = String(val).trim();
    if (val === '' || isNaN(val)) return val;
    let n = parseFloat(val);
    if (isNaN(n)) return val;
    let s = n.toFixed(2).replace(/0+$/, '').replace(/\.$/, '');
    return s === '' ? '0' : s;
}

// Strip leading/trailing zero formatting as the user types out of the field (on blur)
function bbAttachNumFormatter(el) {
    el.addEventListener('blur', function () {
        if (this.value.trim() !== '') {
            this.value = bbFormatNum(this.value);
        }
    });
}

function bbInitNumFormatters(scope) {
    (scope || document).querySelectorAll('.item-quantity-field, .item-count-field').forEach(bbAttachNumFormatter);
}

// ── New item row counter ──────────────────────────────────────────────────────
let newItemIndex = -1;

function addItemRow() {
    const idx = newItemIndex--;
    const html = `
        <div class="item-row" data-item-id="${idx}">
            <div class="row align-items-center">
                <div class="col-12 col-md-4 mb-2 mb-md-0">
                    <input type="text"
                           name="new_items[${Math.abs(idx)}][item_name]"
                           class="form-control form-control-sm"
                           placeholder="Item name" required>
                </div>
                <div class="col-4 col-md-2">
                    <input type="text"
                           name="new_items[${Math.abs(idx)}][quantity]"
                           class="form-control form-control-sm item-quantity-field"
                           placeholder="e.g. 2.5">
                </div>
                <div class="col-4 col-md-2">
                    ${buildUnitSelectHtml(`new_items[${Math.abs(idx)}][unit]`).replace('class="form-control form-control-sm"', 'class="form-control form-control-sm item-unit-field"')}
                </div>
                <div class="col-2 col-md-2">
                    <input type="text"
                           name="new_items[${Math.abs(idx)}][count]"
                           class="form-control form-control-sm item-count-field"
                           placeholder="e.g. 1">
                </div>
                <div class="col-2 col-md-2 text-center">
                    <button type="button" class="btn-remove-item" onclick="removeItemRow(this)">&times;</button>
                </div>
            </div>
        </div>`;
    const wrap = document.getElementById('new-items-container');
    wrap.insertAdjacentHTML('beforeend', html);
    bbInitNumFormatters(wrap);
    toggleNoItemsMsg();
}

const deletedItemIds = new Set();

function removeItemRow(btn) {
    const row    = btn.closest('.item-row');
    const itemId = parseInt(row.dataset.itemId);
    if (itemId > 0) {
        deletedItemIds.add(itemId);
        updateDeletedItemsInput();
    }
    row.remove();
    toggleNoItemsMsg();
}

function updateDeletedItemsInput() {
    document.querySelectorAll('.deleted-item-input').forEach(el => el.remove());
    deletedItemIds.forEach(id => {
        const inp = document.createElement('input');
        inp.type  = 'hidden';
        inp.name  = 'deleted_items[]';
        inp.value = id;
        inp.className = 'deleted-item-input';
        document.getElementById('editForm').appendChild(inp);
    });
}

function toggleNoItemsMsg() {
    const total = document.querySelectorAll('#items-container .item-row, #new-items-container .item-row').length;
    document.getElementById('no-items-msg').style.display = total === 0 ? 'block' : 'none';
}

// ── Custom delivery charge toggle ─────────────────────────────────────────────
function toggleCustomDelivery(checkbox) {
    const row   = document.getElementById('custom-delivery-row');
    const input = document.getElementById('custom_delivery_charge');
    const badge = document.getElementById('delivery-mode-badge');

    if (checkbox.checked) {
        row.style.display = '';
        input.required    = false;
        badge.className   = 'charge-badge custom';
        badge.textContent = 'Custom ₹';
    } else {
        row.style.display = 'none';
        input.value       = '';
        input.required    = false;
        badge.className   = 'charge-badge global';
        badge.textContent = 'Global %';
    }
    updateDeliveryPreview();
}

// Live preview of delivery charge
function updateDeliveryPreview() {
    const previewEl  = document.getElementById('delivery-preview');
    const itemsCost  = parseFloat(document.getElementById('items_cost').value) || 0;
    const useCustom  = document.getElementById('use_custom_delivery').checked;
    const customAmt  = parseFloat(document.getElementById('custom_delivery_charge').value);

    if (!itemsCost) { previewEl.innerHTML = ''; return; }

    if (useCustom && !isNaN(customAmt)) {
        previewEl.innerHTML =
            `<i class="tio-info-outined"></i> Delivery charge for this order: <strong>₹${Math.round(customAmt)}</strong>
             <span class="text-warning">(custom override)</span>`;

        document.getElementById('delivery-mode-badge').textContent = `Custom ₹${Math.round(customAmt)}`;
    } else if (!useCustom) {
        previewEl.innerHTML =
            `<i class="tio-info-outined"></i> Delivery charge calculated from global % settings on save.`;
    } else {
        previewEl.innerHTML = '';
    }
}

// ── Image handling ────────────────────────────────────────────────────────────
const deletedImageIds = new Set();

function markDeleteImage(imgId) {
    const wrap = document.getElementById('eimg-' + imgId);
    if (!wrap) return;

    if (deletedImageIds.has(imgId)) {
        deletedImageIds.delete(imgId);
        wrap.classList.remove('marked-delete');
        wrap.querySelector('.remove-existing').textContent = '×';
        wrap.querySelector('.remove-existing').title       = 'Remove';
    } else {
        deletedImageIds.add(imgId);
        wrap.classList.add('marked-delete');
        wrap.querySelector('.remove-existing').textContent = '↩';
        wrap.querySelector('.remove-existing').title       = 'Undo remove';
    }
    syncDeletedImagesInputs();
}

function syncDeletedImagesInputs() {
    const container = document.getElementById('deleted-images-container');
    container.innerHTML = '';
    deletedImageIds.forEach(id => {
        container.insertAdjacentHTML('beforeend',
            `<input type="hidden" name="deleted_images[]" value="${id}">`);
    });
}

const newFilesList = new DataTransfer();

function previewNewImages(input) {
    const preview = document.getElementById('image-previews');
    preview.innerHTML = '';
    newFilesList.items.clear();

    Array.from(input.files).forEach((file, idx) => {
        newFilesList.items.add(file);
        const reader  = new FileReader();
        reader.onload = e => {
            preview.insertAdjacentHTML('beforeend', `
                <div class="image-preview-wrap" id="nprev-${idx}">
                    <img src="${e.target.result}" alt="${file.name}">
                    <div class="remove-img" onclick="removeNewPreview(${idx}, '${file.name}')">&times;</div>
                </div>`);
        };
        reader.readAsDataURL(file);
    });
}

function removeNewPreview(idx, fileName) {
    document.getElementById('nprev-' + idx)?.remove();
    const dt = new DataTransfer();
    Array.from(newFilesList.files).forEach(f => { if (f.name !== fileName) dt.items.add(f); });
    document.getElementById('new_images').files = dt.files;
}

// ── Validation on submit: every item row must have quantity, unit AND count ──
function validateItemsBeforeSubmit(e) {
    document.querySelectorAll('.item-row.row-invalid').forEach(r => r.classList.remove('row-invalid'));

    const rows = document.querySelectorAll('#items-container .item-row, #new-items-container .item-row');

    if (rows.length === 0) {
        bbToast('Please add at least one item.', 'error');
        e.preventDefault();
        return false;
    }

    let firstInvalidEl = null;
    const errors = []; // collects which field types are missing, in order found

    rows.forEach(row => {
        const nameEl = row.querySelector('input[name*="[item_name]"]');
        const qtyEl  = row.querySelector('.item-quantity-field');
        const unitEl = row.querySelector('.item-unit-field');
        const cntEl  = row.querySelector('.item-count-field');

        const nameVal = nameEl ? nameEl.value.trim() : '';
        const qtyVal  = qtyEl  ? qtyEl.value.trim()  : '';
        const unitVal = unitEl ? unitEl.value.trim() : '';
        const cntVal  = cntEl  ? cntEl.value.trim()  : '';

        let rowInvalid = false;

        if (!nameVal) {
            rowInvalid = true;
            if (!firstInvalidEl) firstInvalidEl = nameEl;
        }
        if (!qtyVal) {
            rowInvalid = true;
            if (!errors.includes('quantity')) errors.push('quantity');
            if (!firstInvalidEl) firstInvalidEl = qtyEl;
        }
        if (!unitVal) {
            rowInvalid = true;
            if (!errors.includes('unit')) errors.push('unit');
            if (!firstInvalidEl) firstInvalidEl = unitEl;
        }
        if (!cntVal) {
            rowInvalid = true;
            if (!errors.includes('count')) errors.push('count');
            if (!firstInvalidEl) firstInvalidEl = cntEl;
        }

        if (rowInvalid) row.classList.add('row-invalid');
    });

    if (errors.length || document.querySelector('.item-row.row-invalid')) {
        if (errors.includes('quantity')) bbToast('Please fill quantity', 'error');
        if (errors.includes('unit'))     bbToast('Please fill unit', 'error');
        if (errors.includes('count'))    bbToast('Please fill count', 'error');

        if (firstInvalidEl) {
            firstInvalidEl.focus();
            firstInvalidEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        e.preventDefault();
        return false;
    }

    return true;
}

document.getElementById('editForm').addEventListener('submit', validateItemsBeforeSubmit);

// Init
bbInitNumFormatters(document);
toggleNoItemsMsg();
updateDeliveryPreview();
</script>
@endpush