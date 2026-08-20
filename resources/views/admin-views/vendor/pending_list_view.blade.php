@extends('layouts.admin.app')

@section('title', translate('messages.New_joining_request'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')

    <div class="content container-fluid">
        <div class="card card-from-sm">
            <div class="card-body">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                        <div class="page--header-title">
                            <h1 class="page-header-title"> {{ translate('Restaurant_Details') }} </h1>
                            <p class="page-header-text"> {{ translate('Requested_to_join_at') }}
                                {{ \App\CentralLogics\Helpers::time_date_format($restaurant->created_at) }}</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{route('admin.restaurant.edit',[$restaurant->id])}}" class="btn btn-primary"><i class="tio-open-in-new"></i>
                                {{ translate('Edit_Information') }}</a>
                            @if($restaurant->vendor->status === null)
                                <a  onclick="request_alert('{{route('admin.restaurant.application',[$restaurant['id'],0])}}','{{translate('messages.you_want_to_reject_this_application')}}')"
                                href="javascript:"  class="btn btn--danger font-regular"> {{ translate('Reject') }} </a>
                            @endif

                            @if($restaurant->vendor->status === null || $restaurant->vendor->status == 0 )
                                <a  onclick="request_alert('{{route('admin.restaurant.application',[$restaurant['id'],1])}}','{{translate('messages.you_want_to_approve_this_application')}}')"
                                href="javascript:" class="btn btn-success"> {{ translate('Approve') }} </a>
                            @endif
                        </div>
                    </div>
                </div>
                <!-- End Page Header -->
                <!-- Banner -->
                <section class="shop-details-banner">
                    <div class="card">
                        <div class="card-body px-0 pt-0">
                            <img src="{{ asset('storage/app/public/restaurant/cover/' . $restaurant->cover_photo) }}"
                                onerror="this.src='{{ asset('public/assets/admin/img/900x400/img1.jpg') }}'"
                                class="shop-details-banner-img" alt="">
                            <div class="shop-details-banner-content">
                                <div class="shop-details-banner-content-thumbnail">
                                    <img src="{{ asset('storage/app/public/restaurant/' . $restaurant->logo) }}"
                                        onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'"
                                        class="thumbnail" alt="">
                                    <h3 class="mt-4 pt-3 mb-4 d-sm-none">{{ $restaurant->name }}</h3>
                                </div>
                                <div class="shop-details-banner-content-content">
                                    <h3 class="mt-sm-4 pt-sm-3 mb-4 d-none d-sm-block">{{ $restaurant->name }}</h3>
                                    <div class="shop-details-model">
                                        <div class="shop-details-model-item">
                                            <img src="{{ asset('/public/assets/admin/new-img/icon-1.png') }}"
                                                alt="">
                                            <div class="shop-details-model-item-content">
                                                <h6> {{ translate('Business_Model') }} </h6>
                                                @if ($restaurant->restaurant_model == 'commission')
                                                    <div>{{ translate('Commission_Base') }}</div>
                                                @elseif($restaurant->restaurant_model == 'none')
                                                    <div>{{ translate('Not_chosen') }}</div>
                                                @else
                                                    <div>{{ translate('Subscription_Base') }}</div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="shop-details-model-item">
                                            <img src="{{ asset('/public/assets/admin/new-img/icon-2.png') }}"
                                                alt="">
                                            <div class="shop-details-model-item-content">
                                                <h6> {{ translate('VAT/TAX') }} </h6>
                                                <div> {{ $restaurant->tax }} %</div>
                                            </div>
                                        </div>
                                        <div class="shop-details-model-item">
                                            <img src="{{ asset('/public/assets/admin/new-img/icon-4.png') }}"
                                                alt="">
                                            <div class="shop-details-model-item-content">
                                                <h6> {{ translate('Address') }} </h6>
                                                <div>{{ $restaurant->address }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card mt-3">
                        <div class="card-header justify-content-between align-items-center">
                            <label class="input-label text-capitalize d-inline-flex align-items-center m-0">
                                <span class="line--limit-1"><img src="{{ asset('/public/assets/admin/img/company.png') }}"
                                        alt=""> {{ translate('Registration_Information') }} </span>
                                <span data-toggle="tooltip" data-placement="right"
                                    data-original-title="{{ translate('See_the_general_information_a_restaurant_provides_during_registration.') }}"
                                    class="input-label-secondary">
                                    <img src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                        alt="info"></span>
                            </label>
                        </div>
                        <div class="card-body">
                            <div class="__registration-information">
                                <div class="item">
                                    <h5> {{ translate('General_Information') }}</h5>
                                    <ul>
                                        <li>
                                            <span class="left"> {{ translate('Restaurant_Name') }} </span>
                                            <span class="right">{{ $restaurant->name }}</span>
                                        </li>
                                        <li>
                                            <span class="left">{{ translate('Zone') }} </span>
                                            <span class="right">{{ $restaurant->zone->name }}</span>
                                        </li>


                                        <?php
                                            $delivery_time_start = explode('-', $restaurant->delivery_time)[0] ?? 10;
                                            $delivery_time_end = explode('-', $restaurant->delivery_time)[1] ?? 30;
                                            $delivery_time_type = explode('-', $restaurant->delivery_time)[2] ?? 'min';
                                        ?>
                                        <li>
                                            <span class="left"> {{ translate('Delivery_Time_Within') }} </span>
                                            <span class="right">{{ $delivery_time_start }}-{{ $delivery_time_end }}
                                                {{ $delivery_time_type }}</span>
                                        </li>
                                    </ul>
                                </div>
                                <div class="item" style="--width:74px">
                                    <h5> {{ translate('Owner_Information') }} </h5>
                                    <ul>
                                        <li>
                                            <span class="left"> {{ translate('First_Name') }}</span>
                                            <span class="right">{{ $restaurant->vendor->f_name }}</span>
                                        </li>
                                        <li>
                                            <span class="left"> {{ translate('Last_Name') }}</span>
                                            <span class="right">{{ $restaurant->vendor->l_name }}</span>
                                        </li>
                                        <li>
                                            <span class="left">{{ translate('Phone') }}</span>
                                            <span class="right">{{ $restaurant->phone }}</span>
                                        </li>
                                    </ul>
                                </div>
                                <div class="item" style="--width:74px">
                                    <h5>{{ translate('Login_Information') }}</h5>
                                    <ul>
                                        <li>
                                            <span class="left">{{ translate('Email') }}</span>
                                            <span class="right">{{ $restaurant->email }}</span>
                                        </li>
                                        <li>
                                            <span class="left">{{ translate('Password') }} </span>
                                            <span class="right">*************</span>
                                        </li>
                                    </ul>
                                </div>
                                <div class="item">
                                    <h5>{{ translate('Cuisines') }} </h5>
                                    <div>
                                        @foreach ($restaurant->cuisine as $key => $cuisine)
                                            {{ $cuisine->name }} ,
                                        @endforeach
                                        {{-- {{ $restaurant->cuisine }} --}}
                                    </div>
                                </div>
                            </div>
                        </div>


                        @if($restaurant->additional_data  &&  count(json_decode($restaurant->additional_data, true)) > 0 )

                        <div class="card-header justify-content-between align-items-center">
                            <label class="input-label text-capitalize d-inline-flex align-items-center m-0">
                                <span class="line--limit-1"><img src="{{ asset('/public/assets/admin/img/company.png') }}"
                                        alt=""> {{ translate('Additional_Information') }} </span>
                                <span data-toggle="tooltip" data-placement="right"
                                    data-original-title="{{ translate('View_the_additional_information_restaurant_has_provided_during_registration.') }}"
                                    class="input-label-secondary">
                                    <img src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                        alt="info"></span>
                            </label>
                        </div>
                        <div class="card-body">
                            <div class="__registration-information">
                                <div class="item">
                                    <ul>

                                        @foreach (json_decode($restaurant->additional_data, true) as $key => $item)
                                                @if (is_array($item))

                                                @foreach ($item as $k =>  $data)
                                                <li>
                                                    <span class="left"> {{ $k == 0 ? translate($key)  : ''}} </span>
                                                    <span class="right">{{   $data }} </span>
                                                </li>
                                                @endforeach
                                                @else
                                                <li>
                                                    <span class="left"> {{ translate($key) }} </span>
                                                    <span class="right">{{  $item ?? translate('messages.N/A')}} </span>
                                                </li>

                                                @endif
                                        @endforeach

                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="card-header justify-content-between align-items-center">
                            <label class="input-label text-capitalize d-inline-flex align-items-center m-0">
                                <span class="line--limit-1"><i class="tio-file-text-outlined"></i>
                                    {{ translate('Documents') }} </span>
                                <span data-toggle="tooltip" data-placement="right"
                                    data-original-title="{{ translate('Additional_Documents') }}"
                                    class="input-label-secondary">
                                    <img src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                        alt="info"></span>
                            </label>
                        </div>
                        <div class="card-body">
                            <h5 class="mb-3"> {{ translate('Attachments') }} </h5>
                                <div class="d-flex flex-wrap gap-4 align-items-start">
                                    @if($restaurant->additional_documents  &&  count(json_decode($restaurant->additional_documents, true)) > 0 )
                                @foreach (json_decode($restaurant->additional_documents, true) as $key => $item)

                                    @foreach ($item as $file)
                                        <?php
                                        $path_info = pathinfo('storage/app/public/additional_documents/' . $file);
                                        $f_date = $path_info['extension'];
                                        ?>

                                        @if (in_array($f_date, ['pdf', 'doc', 'docs', 'docx' ]))
                                                @if ($f_date == 'pdf')
                                                <div class="attachment-card min-w-260">
                                                    <label  for="">{{ translate($key) }}</label>
                                                    <a href="{{ asset('storage/app/public/additional_documents/'.$file) }}" target="_blank" rel="noopener noreferrer">
                                                        <div class="img ">


                                                            <iframe src="https://docs.google.com/gview?url={{ asset('storage/app/public/additional_documents/' . $file) }}&embedded=true"></iframe>


                                                        </div>
                                                            </a>

                                                        <a href="{{ asset('storage/app/public/additional_documents/' . $file) }}"
                                                            download class="download-icon mt-3">
                                                            <img src="{{ asset('/public/assets/admin/new-img/download-icon.svg') }}"
                                                                alt="">
                                                        </a>
                                                        <a href="#" class="pdf-info">
                                                            <img  src="{{ asset('/public/assets/admin/new-img/pdf.png') }}"
                                                                alt="">
                                                            <div class="w-0 flex-grow-1">
                                                                <h6 class="title">{{ translate('Click_To_View_The_file.pdf') }}
                                                                </h6>

                                                            </div>
                                                        </a>
                                                    </div>
                                                @else
                                                    <div class="attachment-card  min-w-260">
                                                        <label  for="">{{ translate($key) }}</label>
                                                        <a href="{{ asset('storage/app/public/additional_documents/'.$file) }}" target="_blank" rel="noopener noreferrer">
                                                        <div class="img">

                                                                <iframe src="https://docs.google.com/gview?url={{ asset('storage/app/public/additional_documents/' . $file) }}&embedded=true"></iframe>

                                                        </div>
                                                            </a>
                                                        <a href="{{ asset('storage/app/public/additional_documents/' . $file) }}"
                                                            download class="download-icon mt-3">
                                                            <img  src="{{ asset('/public/assets/admin/new-img/download-icon.svg') }}"
                                                                alt="">
                                                        </a>
                                                        <a href="#" class="pdf-info">
                                                            <img src="{{ asset('/public/assets/admin/new-img/doc.png') }}"
                                                                alt="">
                                                            <div class="w-0 flex-grow-1">
                                                                <h6 class="title">{{ translate('Click_To_View_The_file.doc') }}
                                                                </h6>

                                                            </div>
                                                        </a>
                                                    </div>
                                                @endif
                                        @endif
                                    @endforeach
                                @endforeach
                                @endif
                                </div>
                            @endif
                            <br>
                            <br>

                            @if($restaurant->additional_documents  &&  count(json_decode($restaurant->additional_documents, true)) > 0 )
                            <h5 class="mb-3"> {{ translate('Images') }} </h5>

                            <div class="d-flex flex-wrap gap-4 align-items-start">
                                @foreach (json_decode($restaurant->additional_documents, true) as $key => $item)
                                @foreach ($item as $file)

                                <?php
                                    $path_info = pathinfo('storage/app/public/additional_documents/' . $file);
                                    $f_date = $path_info['extension'];
                                    ?>
                                    @if (in_array($f_date, ['jpg', 'jpeg', 'png']))
                                    <div class="attachment-card max-w-360">
                                            <label  for="">{{ translate($key) }}</label>
                                            <a href="{{ asset('storage/app/public/additional_documents/' . $file) }}"
                                                download class="download-icon mt-3">
                                                <img  src="{{ asset('/public/assets/admin/new-img/download-icon.svg') }}"
                                                    alt="">
                                            </a>
                                            <img src="{{ asset('storage/app/public/additional_documents/' . $file) }}"

                                                class="aspect-615-350 cursor-pointer mw-100 object--cover" alt="">
                                            </div>
                                            @endif
                                            @endforeach
                                @endforeach
                            </div>
                            @endif

                        </div>
                    </div>
                </section>
                <!-- Banner -->

       
        </div>
    </div>

@endsection

@push('script_2')
    <script>
        function request_alert(url, message) {
            Swal.fire({
                title: "{{ translate('messages.are_you_sure_?') }}",
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: "{{ translate('messages.no') }}",
                confirmButtonText: "{{ translate('messages.yes') }}",
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    location.href = url;
                }
            })
        }
    </script>
@endpush
