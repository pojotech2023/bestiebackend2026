@extends('layouts.admin.app')

@section('title',translate('messages.update_notification'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title text-capitalize">
                        <div class="card-header-icon d-inline-flex mr-2 img">
                            <img src="{{ asset('public/assets/admin/img/bell.png')}}" alt="public">
                        </div>
                        <span>{{translate('messages.notification_update')}}</span>
                    </h1>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{route('admin.notification.update',[$notification['id']])}}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="input-label">{{translate('messages.title')}}</label>
                                <input id="notification_title" type="text" value="{{$notification['title']}}" name="notification_title" class="form-control" required maxlength="191">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="input-label">{{translate('messages.zone')}}</label>
                                <select id="zone" name="zone" class="form-control js-select2-custom">
                                    <option value="all" {{isset($notification->zone_id)?'':'selected'}}>{{translate('messages.all_zone')}}</option>
                                    @foreach(\App\Models\Zone::orderBy('name')->get(['id','name']) as $z)
                                        <option value="{{$z['id']}}" {{$notification->zone_id==$z['id']?'selected':''}}>{{$z['name']}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="input-label">{{translate('messages.send_to')}}</label>
                                <select name="tergat" class="form-control" id="tergat" required>
                                    <option value="customer" {{$notification->tergat=='customer'?'selected':''}}>{{translate('messages.customer')}}</option>
                                    <option value="deliveryman" {{$notification->tergat=='deliveryman'?'selected':''}}>{{translate('messages.deliveryman')}}</option>
                                    <option value="restaurant" {{$notification->tergat=='restaurant'?'selected':''}}>{{translate('messages.restaurant')}}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">&nbsp;</label>
                                <center class="mb-3">
                                    <img class="initial-30" id="viewer"
                                        src="{{ asset('storage/notification')}}/{{$notification['image']}}"
                                        onerror="this.src='{{ asset('public/assets/admin/img/900x400/img1.jpg')}}'"
                                        alt="image"/>
                                </center>
                                <label>{{translate('messages.notification_banner')}}</label><small class="text-danger">* ( {{translate('messages.ratio_3:1')}} )</small>
                                <div class="custom-file">
                                    <input type="file" name="image" id="customFileEg1" class="custom-file-input"
                                        accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                    <label class="custom-file-label" for="customFileEg1">{{translate('messages.choose_file')}}</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label class="input-label">{{translate('messages.description')}}</label>
                                <textarea id="description" name="description" class="form-control h--md-200px" required>{{$notification['description']}}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="input-label">{{translate('messages.schedule_date')}}</label>
                                <input type="text" id="scheduled_date" name="scheduled_date"
                                    class="form-control"
                                    value="{{ $notification->scheduled_at ? \Carbon\Carbon::parse($notification->scheduled_at)->format('Y-m-d') : '' }}"
                                    placeholder="Select date" required readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="input-label">{{translate('messages.schedule_time')}}</label>
                                <input type="text" id="scheduled_time" name="scheduled_time"
                                    class="form-control"
                                    value="{{ $notification->scheduled_at ? \Carbon\Carbon::parse($notification->scheduled_at)->format('H:i') : '' }}"
                                    placeholder="Select time" required readonly>
                                {{-- hidden field - controller expects scheduled_at --}}
                                <input type="hidden" id="scheduled_at" name="scheduled_at"
                                    value="{{ $notification->scheduled_at ? \Carbon\Carbon::parse($notification->scheduled_at)->format('Y-m-d\TH:i') : '' }}">
                            </div>
                        </div>
                    </div>

                    <div class="btn--container justify-content-end mb-0">
                        <button id="reset_btn" type="button" class="btn btn--reset">{{translate('messages.reset')}}</button>
                        <button type="submit" class="btn btn--primary">{{translate('send_again')}}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
<script>
    // ✅ Date picker for edit
    var editDatePicker = flatpickr("#scheduled_date", {
        dateFormat: "Y-m-d",
        disableMobile: true,
        defaultDate: "{{ $notification->scheduled_at ? \Carbon\Carbon::parse($notification->scheduled_at)->format('Y-m-d') : '' }}",
        onChange: function(selectedDates, dateStr) {
            updateHiddenScheduledAt();
        }
    });

    // ✅ Time picker for edit - scroll style
    var editTimePicker = flatpickr("#scheduled_time", {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
        time_24hr: false,
        minuteIncrement: 1,
        disableMobile: true,
        defaultDate: "{{ $notification->scheduled_at ? \Carbon\Carbon::parse($notification->scheduled_at)->format('H:i') : '07:00' }}",
        onChange: function(selectedDates, dateStr) {
            updateHiddenScheduledAt();
        }
    });

    // Combine date + time into hidden scheduled_at field (controller uses Y-m-d\TH:i)
    function updateHiddenScheduledAt() {
        var dateVal = document.getElementById('scheduled_date').value;
        var timeVal = document.getElementById('scheduled_time').value;
        if (dateVal && timeVal) {
            document.getElementById('scheduled_at').value = dateVal + 'T' + timeVal;
        }
    }

    function readURL(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) { $('#viewer').attr('src', e.target.result); }
            reader.readAsDataURL(input.files[0]);
        }
    }
    $("#customFileEg1").change(function () { readURL(this); });

    $('#reset_btn').click(function () {
        $('#notification_title').val("{{$notification['title']}}");
        $('#zone').val("{{$notification->zone_id ?? 'all'}}").trigger('change');
        $('#tergat').val("{{$notification->tergat}}").trigger('change');
        $('#viewer').attr('src', "{{ asset('storage/notification')}}/{{$notification['image']}}");
        $('#customFileEg1').val(null);
        $('#description').val("{{addslashes($notification['description'])}}");
        editDatePicker.setDate("{{ $notification->scheduled_at ? \Carbon\Carbon::parse($notification->scheduled_at)->format('Y-m-d') : '' }}");
        editTimePicker.setDate("{{ $notification->scheduled_at ? \Carbon\Carbon::parse($notification->scheduled_at)->format('H:i') : '07:00' }}");
        $('#scheduled_at').val("{{ $notification->scheduled_at ? \Carbon\Carbon::parse($notification->scheduled_at)->format('Y-m-d\TH:i') : '' }}");
    });

    $(document).on('ready', function () {
        $('.js-select2-custom').each(function () {
            $.HSCore.components.HSSelect2.init($(this));
        });
    });
</script>
@endpush