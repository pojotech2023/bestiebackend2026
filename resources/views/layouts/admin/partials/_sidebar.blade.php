
<?php
// COUNT(CASE WHEN order_status = "pending" AND ((created_at <> schedule_at AND ((schedule_at BETWEEN ? AND ?) OR schedule_at < ?))OR created_at = schedule_at) THEN 1 END) as pending,
// COUNT(CASE WHEN order_status NOT IN ("delivered", "failed","canceled","refund_requested","refund_request_canceled","refunded") AND delivery_man_id IS NULL AND order_type = "delivery" THEN 1 END) as searching_dm,
                $order = \App\Models\Order::Notpos()->HasSubscriptionToday()
                        ->selectRaw('COUNT(*) as total,
                        COUNT(CASE WHEN order_status = "delivered" THEN 1 END) as delivered,
                        COUNT(CASE WHEN order_status = "canceled" THEN 1 END) as canceled,
                        COUNT(CASE WHEN order_status = "failed" THEN 1 END) as failed,
                        COUNT(CASE WHEN order_status = "refunded" THEN 1 END) as refunded,
                        COUNT(CASE WHEN order_status = "refund_requested" THEN 1 END) as refund_requested,
                        COUNT(CASE WHEN order_status IN ("confirmed", "processing","handover") THEN 1 END) as processing,
                        COUNT(CASE WHEN created_at <> schedule_at AND scheduled = 1 THEN 1 END) as scheduled')
                        ->first();


                $order_sch = \App\Models\Order::Notpos()->HasSubscriptionToday()->OrderScheduledIn(30)
                        ->selectRaw('COUNT(CASE WHEN order_status = "pending" THEN 1 END) as pending,
                        COUNT(CASE WHEN order_status = "picked_up" THEN 1 END) as picked_up,
                        COUNT(CASE WHEN order_status IN ("accepted", "confirmed","processing","handover","picked_up") THEN 1 END) as ongoing,
                        COUNT(CASE WHEN delivery_man_id IS NULL  AND order_type = "delivery" AND order_status NOT IN ("delivered", "failed","canceled","refund_requested","refund_request_canceled","refunded") THEN 1 END) as searching_dm,
                        COUNT(CASE WHEN order_status = "accepted" THEN 1 END) as accepted')
                        ->first();
?>

<div id="sidebarMain" class="d-none">
    <aside
        class="js-navbar-vertical-aside navbar navbar-vertical-aside navbar-vertical navbar-vertical-fixed navbar-expand-xl navbar-bordered  ">
        <div class="navbar-vertical-container">
            <div class="navbar__brand-wrapper navbar-brand-wrapper justify-content-between">
                <!-- Logo -->
                @php($restaurant_logo = \App\Models\BusinessSetting::where(['key' => 'logo'])->first()->value)
                <a class="navbar-brand d-block p-0" href="{{ route('admin.dashboard') }}" aria-label="Front">
                    <img class="navbar-brand-logo sidebar--logo-design"
                        onerror="this.src='{{ asset('public/assets/admin/img/160x160/img2.jpg') }}'"
                        src="{{ asset('storage/business/' . $restaurant_logo) }}" alt="Logo">
                    <img class="navbar-brand-logo-mini sidebar--logo-design-2"
                        onerror="this.src='{{ asset('public/assets/admin/img/160x160/img2.jpg') }}'"
                        src="{{ asset('storage/business/' . $restaurant_logo) }}" alt="Logo">
                </a>
                <!-- End Logo -->

                <!-- Navbar Vertical Toggle -->
                <button type="button"
                    class="js-navbar-vertical-aside-toggle-invoker navbar-vertical-aside-toggle btn btn-icon btn-xs btn-ghost-dark">
                    <i class="tio-clear tio-lg"></i>
                </button>
                <!-- End Navbar Vertical Toggle -->

                <div class="navbar-nav-wrap-content-left d-none d-xl-block">
                    <!-- Navbar Vertical Toggle -->
                    <button type="button" class="js-navbar-vertical-aside-toggle-invoker close">
                        <i class="tio-first-page navbar-vertical-aside-toggle-short-align" data-toggle="tooltip"
                            data-placement="right" title="Collapse"></i>
                        <i class="tio-last-page navbar-vertical-aside-toggle-full-align"
                            data-template='<div class="tooltip d-none" role="tooltip"><div class="arrow"></div><div class="tooltip-inner"></div></div>'
                            data-toggle="tooltip" data-placement="right" title="Expand"></i>
                    </button>
                    <!-- End Navbar Vertical Toggle -->
                </div>

            </div>

            <!-- Content -->
            <div class="navbar-vertical-content bg--title" id="navbar-vertical-content">
                <!-- Search Form -->
                <form class="sidebar--search-form" autocomplete="off">
                    <input autocomplete="false" name="hidden" type="text" class="d-none">
                    <div class="search--form-group">
                        <button type="button" class="btn"><i class="tio-search"></i></button>
                        <input type="text" id="search" class="form-control form--control"
                            placeholder="{{ translate('messages.Search_Menu...') }}">
                    </div>
                </form>
                <!-- Search Form -->
                <ul class="navbar-nav navbar-nav-lg nav-tabs">
                    <!-- Dashboards -->
                    <li class="navbar-vertical-aside-has-menu {{ Request::is('admin') ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('admin.dashboard') }}"
                            title="{{ translate('messages.dashboard') }}">
                            <i class="tio-dashboard-vs nav-icon"></i>
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                {{ translate('messages.dashboard') }}
                            </span>
                        </a>
                    </li>
                    <!-- End Dashboards -->
                    @if (\App\CentralLogics\Helpers::module_permission_check('pos'))
                        <!-- POS -->
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/pos') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('admin.pos.index') }}" title="{{ translate('messages.pos') }}">
                                <i class="tio-receipt nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.pos') }}</span>
                            </a>
                        </li>
                        <!-- End POS -->
                    @endif

                    <!-- Marketing section -->
                    <li class="nav-item">
                        <small class="nav-subtitle"
                            title="{{ translate('Promotion_management') }}">{{ translate('Promotions_management') }}</small>
                        <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                    </li>
                    <!-- Campaign -->
                    @if (\App\CentralLogics\Helpers::module_permission_check('campaign'))
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('admin/campaign*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                                title="{{ translate('messages.campaigns') }}">
                                <i class="tio-notice nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.campaigns') }}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('admin/campaign*') ? 'block' : 'none' }}">

                                <li class="nav-item {{ Request::is('admin/campaign/basic/*') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.campaign.list', 'basic') }}"
                                        title="{{ translate('messages.basic_campaign') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.basic_campaign') }}</span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('admin/campaign/item/*') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.campaign.list', 'item') }}"
                                        title="{{ translate('messages.food_campaign') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.food_campaign') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif
                    <!-- End Campaign -->
                    <!-- Banner -->
                    @if (\App\CentralLogics\Helpers::module_permission_check('banner'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/banner*') && !Request::is('admin/banner/promotional-banner*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('admin.banner.add-new') }}"
                                title="{{ translate('messages.banners') }}">
                                <i class="tio-bookmark nav-icon side-nav-icon--design"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.banners') }}</span>
                            </a>
                        </li>
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/banner/promotional-banner*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('admin.banner.promotional_banner') }}"
                                title="{{ translate('messages.promotional_banner') }}">
                                <i class="tio-tabs nav-icon side-nav-icon--design"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.promotional_banner') }}</span>
                            </a>
                        </li>
                    @endif
                    <!-- End Banner -->
                    <!-- Coupon -->
                    @if (\App\CentralLogics\Helpers::module_permission_check('coupon'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/coupon*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('admin.coupon.add-new') }}"
                                title="{{ translate('messages.coupons') }}">
                                <i class="tio-ticket nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.coupons') }}</span>
                            </a>
                        </li>
                    @endif
                    <!-- End Coupon -->
                    <!-- Notification -->
                    @if (\App\CentralLogics\Helpers::module_permission_check('notification'))
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('admin/notification*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('admin.notification.add-new') }}"
                                title="{{ translate('messages.push_notification') }}">
                                <i class="tio-notifications-on nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.push_notification') }}
                                </span>
                            </a>
                        </li>
                    @endif
                    <!-- End Notification -->
                    <!-- Orders -->
                    @if (\App\CentralLogics\Helpers::module_permission_check('order'))
                        <li class="nav-item">
                            <small class="nav-subtitle">{{ translate('messages.order_management') }}</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>

                        <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/order*')  ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                                title="{{ translate('messages.orders') }}">
                                <i class="tio-file-text-outlined nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.orders') }}
                                </span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('admin/order*') && (Request::is('admin/order/subscription*') == false &&  Request::is('admin/order-cancel-reasons') ==false) ? 'block' : 'none' }}">
                                <li class="nav-item {{ Request::is('admin/order/list/all') ? 'active' : '' }} @yield('all_order')">
                                    <a class="nav-link" href="{{ route('admin.order.list', ['all']) }}"
                                        title="{{ translate('messages.all_orders') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate sidebar--badge-container">
                                            {{ translate('messages.all') }}
                                            <span class="badge badge-soft-info badge-pill ml-1">
                                                {{-- {{ \App\Models\Order::Notpos()->HasSubscriptionToday()->count() }} == --}}
                                                {{  $order->total }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('admin/order/list/scheduled') ? 'active' : '' }} @yield('scheduled')">
                                    <a class="nav-link" href="{{ route('admin.order.list', ['scheduled']) }}"
                                        title="{{ translate('messages.scheduled_orders') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate sidebar--badge-container">
                                            {{ translate('messages.scheduled') }}
                                            <span class="badge badge-soft-info badge-pill ml-1">
                                                {{-- {{ \App\Models\Order::Scheduled()->HasSubscriptionToday()->count() }} == --}}
                                                {{  $order->scheduled }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('admin/order/list/pending') ? 'active' : '' }} @yield('pending')">
                                    <a class="nav-link " href="{{ route('admin.order.list', ['pending']) }}"
                                        title="{{ translate('messages.pending_orders') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate sidebar--badge-container">
                                            {{ translate('messages.pending') }}
                                            <span class="badge badge-soft-info badge-pill ml-1">
                                                {{-- {{ \App\Models\Order::Pending()->HasSubscriptionToday()->OrderScheduledIn(30)->count() }}  == --}}
                                                {{ $order_sch->pending }}
                                            </span>
                                        </span>
                                    </a>
                                </li>

                                <li class="nav-item {{ Request::is('admin/order/list/accepted') ? 'active' : '' }} @yield('accepted')">
                                    <a class="nav-link " href="{{ route('admin.order.list', ['accepted']) }}"
                                        title="{{ translate('messages.accepted_orders') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate sidebar--badge-container">
                                            {{ translate('messages.accepted') }}
                                            <span class="badge badge-soft-success badge-pill ml-1">
                                                {{-- {{ \App\Models\Order::AccepteByDeliveryman()->HasSubscriptionToday()->OrderScheduledIn(30)->count() }} == --}}

                                                {{ $order_sch->accepted }}

                                            </span>
                                        </span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('admin/order/list/processing') ? 'active' : '' }} @yield('processing')">
                                    <a class="nav-link " href="{{ route('admin.order.list', ['processing']) }}"
                                        title="{{ translate('messages.processing_orders') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate sidebar--badge-container">
                                            {{ translate('messages.processing') }}
                                            <span class="badge badge-soft-warning badge-pill ml-1">
                                                {{-- {{ \App\Models\Order::whereIn('order_status', ['confirmed','processing','handover'])->HasSubscriptionToday()->count() }}== --}}
                                                {{ $order->processing }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                                <li
                                    class="nav-item {{ Request::is('admin/order/list/food_on_the_way') ? 'active' : '' }} @yield('picked_up')">
                                    <a class="nav-link text-capitalize"
                                        href="{{ route('admin.order.list', ['food_on_the_way']) }}"
                                        title="{{ translate('messages.foodOnTheWay_orders') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate sidebar--badge-container">
                                            {{ translate('messages.food_On_The_Way') }}
                                            <span class="badge badge-soft-warning badge-pill ml-1">
                                                {{-- {{ \App\Models\Order::FoodOnTheWay()->HasSubscriptionToday()->OrderScheduledIn(30)->count() }}== --}}
                                                {{ $order_sch->picked_up }}

                                            </span>
                                        </span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('admin/order/list/delivered') ? 'active' : '' }} @yield('delivered')">
                                    <a class="nav-link " href="{{ route('admin.order.list', ['delivered']) }}"
                                        title="{{ translate('messages.delivered_orders') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate sidebar--badge-container">
                                            {{ translate('messages.delivered') }}
                                            <span class="badge badge-soft-success badge-pill ml-1">
                                                {{-- {{ \App\Models\Order::Delivered()->HasSubscriptionToday()->Notpos()->count() }}== --}}
                                                {{ $order->delivered }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('admin/order/list/canceled') ? 'active' : '' }} @yield('canceled')">
                                    <a class="nav-link " href="{{ route('admin.order.list', ['canceled']) }}"
                                        title="{{ translate('messages.canceled_orders') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate sidebar--badge-container">
                                            {{ translate('messages.canceled') }}
                                            <span class="badge badge-soft-danger badge-pill ml-1">
                                                {{-- {{ \App\Models\Order::Canceled()->HasSubscriptionToday()->count() }}== --}}
                                                {{ $order->canceled }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('admin/order/list/failed') ? 'active' : '' }} @yield('failed')">
                                    <a class="nav-link " href="{{ route('admin.order.list', ['failed']) }}"
                                        title="{{ translate('messages.payment_failed_orders') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate sidebar--badge-container text-capitalize">
                                            {{ translate('messages.payment_failed') }}
                                            <span class="badge badge-soft-danger badge-pill ml-1">
                                                {{-- {{ \App\Models\Order::failed()->HasSubscriptionToday()->count() }}== --}}
                                                {{ $order->failed }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('admin/order/list/refunded') ? 'active' : '' }} @yield('refunded')">
                                    <a class="nav-link " href="{{ route('admin.order.list', ['refunded']) }}"
                                        title="{{ translate('messages.refunded_orders') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate sidebar--badge-container">
                                            {{ translate('messages.refunded') }}
                                            <span class="badge badge-soft-danger badge-pill ml-1">
                                                {{-- {{ \App\Models\Order::Refunded()->HasSubscriptionToday()->count() }}== --}}
                                                {{ $order->refunded }}
                                            </span>
                                        </span>
                                    </a>
                                </li>

                                <li class="nav-item {{ Request::is('admin/order/offline/payment/list*') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.order.offline_verification_list', ['all']) }}" title="{{ translate('Offline_Payments') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate sidebar--badge-container">
                                            {{ translate('messages.Offline_Payments') }}
                                            <span class="badge badge-soft-danger bg-light badge-pill ml-1">
                                                {{ \App\Models\Order::has('offline_payments')->Notpos()->count() }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                            </ul>
                        </li>


                        <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/order/subscription*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('admin.order.subscription.index') }}" title="{{ translate('messages.Subscription_orders') }} ">
                                <i class="tio-appointment nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                      {{ translate('messages.Subscription_orders') }}</span>
                            </a>
                        </li>

                        <!-- Order refund -->
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/refund/*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                                title="{{ translate('messages.Order_Refunds') }}">
                                <i class="tio-receipt nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.Order_Refunds') }}
                                </span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('admin/refund*') ? 'block' : 'none' }}">
                                <li class="nav-item {{ Request::is('admin/refund/requested') || Request::is('admin/refund/refunded') ||
                                Request::is('admin/refund/rejected') ? 'active' : '' }}">
                                    <a class="nav-link "
                                        href="{{ route('admin.refund.refund_attr', ['requested']) }}"
                                        title="{{ translate('messages.New_Refund_Requests') }} ">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate sidebar--badge-container">
                                            {{ translate('messages.New_Refund_Requests') }}
                                            <span class="badge badge-soft-danger badge-pill ml-1">
                                                {{-- {{ \App\Models\Order::Refund_requested()->count() }}= --}}
                                                {{ $order->refund_requested }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <!-- Order refund End-->


                        <!-- Order dispachment -->
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('admin/dispatch/*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                                title="{{ translate('messages.Dispatch_Management') }}">
                                <i class="tio-clock nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.Dispatch_Management') }}
                                </span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('admin/dispatch*') ? 'block' : 'none' }}">
                                <li
                                    class="nav-item {{ Request::is('admin/dispatch/list/searching_for_deliverymen') ? 'active' : '' }}">
                                    <a class="nav-link "
                                        href="{{ route('admin.dispatch.list', ['searching_for_deliverymen']) }}"
                                        title="{{ translate('messages.searching_DeliveryMan') }} {{ $order_sch->searching_dm }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">
                                            {{ translate('messages.searching_DeliveryMan') }}
                                            <span class="badge badge-soft-info badge-pill ml-1">
                                                {{-- {{ \App\Models\Order::SearchingForDeliveryman()->OrderScheduledIn(30)->count() }} --}}
                                                {{ $order_sch->searching_dm }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                                <li
                                    class="nav-item {{ Request::is('admin/dispatch/list/on_going') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.dispatch.list', ['on_going']) }}"
                                        title="{{ translate('messages.ongoing_Orders') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate sidebar--badge-container">
                                            {{ translate('messages.ongoing_Orders') }}
                                            <span class="badge badge-soft-dark bg-light badge-pill ml-1">
                                                {{-- {{ \App\Models\Order::Ongoing()->OrderScheduledIn(30)->count() }}== --}}
                                                {{ $order_sch->ongoing }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <!-- Order dispachment End-->
                    @endif
                    <!-- End Orders -->

                    <li class="nav-item">
                        <small class="nav-subtitle">{{ translate('messages.Help_&_Support') }}</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                    </li>

                    @if (\App\CentralLogics\Helpers::module_permission_check('customerList'))
                        <li  class="navbar-vertical-aside-has-menu {{ Request::is('admin/message/list') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('admin.message.list') }}"
                                title="{{ translate('messages.Chattings') }}">
                                <i class="tio-chat nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.Chattings') }}
                                </span>
                            </a>
                        </li>
                    @endif
                    <!--@if (\App\CentralLogics\Helpers::module_permission_check('contact_message'))-->
                    <!--    <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/contact/*') ? 'active' : '' }}">-->
                    <!--    <a class="js-navbar-vertical-aside-menu-link nav-link"-->
                    <!--        href="{{ route('admin.contact.list') }}"-->
                    <!--        title="{{ translate('messages.Contact_messages') }}">-->
                    <!--        <i class="tio-messages nav-icon"></i>-->
                    <!--        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">-->
                    <!--            {{ translate('messages.Contact_messages') }}-->
                    <!--        </span>-->
                    <!--    </a>-->
                    <!--    </li>-->
                    <!--@endif-->


                    <!-- Restaurant -->
                    <li class="nav-item">
                        <small class="nav-subtitle"
                            title="{{ translate('messages.restaurant_section') }}">{{ translate('messages.restaurant_management') }}</small>
                        <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                    </li>

                    @if (\App\CentralLogics\Helpers::module_permission_check('zone'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/zone*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('admin.zone.home') }}"
                                title="{{ translate('messages.zone_setup') }}">
                                <i class="tio-poi-outlined nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.zone_setup') }}</span>
                            </a>
                        </li>
                    @endif
                    @if (\App\CentralLogics\Helpers::module_permission_check('restaurant'))
                    <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/cuisine/add') ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link"
                            href="{{ route('admin.cuisine.add') }}"
                            title="{{ translate('messages.cuisine') }}">
                            <i class="tio-link nav-icon"></i>
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                {{ translate('messages.cuisine') }}
                        </a>
                    </li>

                        <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/restaurant*') && !Request::is('admin/restaurant/withdraw_list') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                                title="{{ translate('messages.restaurants') }}">
                                <i class="tio-restaurant nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.restaurants') }}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ (Request::is('admin/restaurant*') && !Request::is('admin/restaurant/withdraw_list') ||
                                stripos(Request()->fullurl(), 'pending-list', 5) ) ? 'block' : 'none' }}">
                                {{ stripos(Request()->fullurl(), 'pending-list', 5 )}}
                                <li
                                    class="navbar-vertical-aside-has-menu {{ Request::is('admin/restaurant/add') ? 'active' : '' }}">
                                    <a class="js-navbar-vertical-aside-menu-link nav-link"
                                        href="{{ route('admin.restaurant.add') }}"
                                        title="{{ translate('messages.add_restaurant') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                            {{ translate('messages.add_restaurant') }}
                                        </span>
                                    </a>
                                </li>

                                <li class="navbar-item {{ !stripos(Request()->fullurl(), 'pending-list', 5 ) && (Request::is('admin/restaurant/list') ||  Request::is('admin/restaurant/transcation/*') || Request::is('admin/restaurant/view*')) ? 'active' : '' }} ">
                                    <a class="js-navbar-vertical-aside-menu-link nav-link"
                                        href="{{ route('admin.restaurant.list') }}"
                                        title="{{ translate('messages.restaurants_list') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span
                                            class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.restaurants_list') }}</span>
                                    </a>
                                </li>

                                <li class="navbar-item {{ stripos(Request()->fullurl(), 'pending-list', 5 ) || Request::is('admin/restaurant/pending/list*') ||  Request::is('admin/restaurant/denied/list*') ? 'active' : '' }}">
                                    <a class="js-navbar-vertical-aside-menu-link nav-link"
                                        href="{{ route('admin.restaurant.pending') }}"
                                        title="{{ translate('messages.New_joining_request') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span  class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.New_joining_request') }}
                                        </span>
                                    </a>
                                </li>

                                <li
                                    class="nav-item {{ Request::is('admin/restaurant/bulk-import') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.restaurant.bulk-import') }}"
                                        title="{{ translate('messages.bulk_import') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span
                                            class="text-truncate text-capitalize">{{ translate('messages.bulk_import') }}</span>
                                    </a>
                                </li>
                                <li
                                    class="nav-item {{ Request::is('admin/restaurant/bulk-export') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.restaurant.bulk-export-index') }}"
                                        title="{{ translate('messages.bulk_export') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span
                                            class="text-truncate text-capitalize">{{ translate('messages.bulk_export') }}</span>
                                    </a>
                                </li>

                            </ul>
                        </li>


                    @endif
                <!-- End Restaurant -->
                    <li class="nav-item">
                        <small class="nav-subtitle"
                            title="{{ translate('messages.food_section') }}">{{ translate('messages.food_management') }}</small>
                        <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                    </li>

                    <!-- Category -->
                    @if (\App\CentralLogics\Helpers::module_permission_check('category'))
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('admin/category*')  ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                                title="{{ translate('messages.categories') }}">
                                <i class="tio-category nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.categories') }}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('admin/category*') ? 'block' : 'none' }}">


                                <li class="nav-item {{ Request::is('admin/category/add') ||  Request::is('admin/category/edit/*') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.category.add') }}"
                                        title="{{ translate('messages.category') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.category') }}</span>
                                    </a>
                                </li>

                                <li
                                    class="nav-item {{ Request::is('admin/category/add-sub-category') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.category.add-sub-category') }}"
                                        title="{{ translate('messages.sub_category') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.sub_category') }}</span>
                                    </a>
                                </li>

                                {{-- <li class="nav-item {{Request::is('admin/category/add-sub-sub-category')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.category.add-sub-sub-category')}}"
                                        title="add new sub sub category">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">Sub-Sub-Category</span>
                                    </a>
                                </li> --}}
                                <li class="nav-item {{ Request::is('admin/category/bulk-import') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.category.bulk-import') }}"
                                        title="{{ translate('messages.bulk_import') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span
                                            class="text-truncate text-capitalize">{{ translate('messages.bulk_import') }}</span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('admin/category/bulk-export') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.category.bulk-export-index') }}"
                                        title="{{ translate('messages.bulk_export') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span
                                            class="text-truncate text-capitalize">{{ translate('messages.bulk_export') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif
                    <!-- End Category -->

                    <!-- AddOn -->
                    @if (\App\CentralLogics\Helpers::module_permission_check('addon'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/addon*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                                title="{{ translate('messages.addons') }}">
                                <i class="tio-add-circle-outlined nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.addons') }}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('admin/addon*') ? 'block' : 'none' }}">
                                <li class="nav-item {{ Request::is('admin/addon/add-new') || Request::is('admin/addon/edit/*') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.addon.add-new') }}"
                                        title="{{ translate('messages.addon_list') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.list') }}</span>
                                    </a>
                                </li>

                                <li class="nav-item {{ Request::is('admin/addon/bulk-import') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.addon.bulk-import') }}"
                                        title="{{ translate('messages.bulk_import') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span
                                            class="text-truncate text-capitalize">{{ translate('messages.bulk_import') }}</span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('admin/addon/bulk-export') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.addon.bulk-export-index') }}"
                                        title="{{ translate('messages.bulk_export') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span
                                            class="text-truncate text-capitalize">{{ translate('messages.bulk_export') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif
                    <!-- End AddOn -->
                    <!-- Food -->
                    @if (\App\CentralLogics\Helpers::module_permission_check('food'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/food*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                                title="{{ translate('messages.foods') }}">
                                <i class="tio-fastfood nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.foods') }}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('admin/food*') ? 'block' : 'none' }}">
                                <li class="nav-item {{ Request::is('admin/food/add-new') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.food.add-new') }}"
                                        title="{{ translate('messages.add_new') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.add_new') }}</span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('admin/food/list')  ||  Request::is('admin/food/view/*') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.food.list') }}"
                                        title="{{ translate('messages.food_list') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.list') }}</span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('admin/food/reviews') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.food.reviews') }}"
                                        title="{{ translate('messages.review_list') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.review') }}</span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('admin/food/bulk-import') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.food.bulk-import') }}"
                                        title="{{ translate('messages.bulk_import') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span
                                            class="text-truncate text-capitalize">{{ translate('messages.bulk_import') }}</span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('admin/food/bulk-export') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.food.bulk-export-index') }}"
                                        title="{{ translate('messages.bulk_export') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span
                                            class="text-truncate text-capitalize">{{ translate('messages.bulk_export') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif
                    <!-- End Food -->
                    <!-- DeliveryMan -->
                    @if (\App\CentralLogics\Helpers::module_permission_check('deliveryman'))
                        <li class="nav-item">
                            <small class="nav-subtitle"
                                title="{{ translate('messages.deliveryman_section') }}">{{ translate('messages.deliveryman_management') }}</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>


                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('admin/vehicle/*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('admin.vehicle.list') }}"
                                title="{{ translate('messages.vehicles_category_setup') }}">
                                <i class="tio-car nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.vehicles_category_setup') }}
                                </span>
                            </a>
                        </li>
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('admin/shift*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('admin.shift.list') }}"
                                title="{{ translate('messages.Shift_setup') }}">
                                <i class="tio-calendar nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.Shift_setup') }}
                                </span>
                            </a>
                        </li>

                        <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/delivery-man*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                                title="{{ translate('messages.deliveryman') }}">
                                <i class="tio-running nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.deliveryman') }}</span>
                            </a>

                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{  Request::is('admin/delivery-man*') ? 'block' : 'none' }}">

                                <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/delivery-man/edit/*') || Request::is('admin/delivery-man/list') || Request::is('admin/delivery-man/preview/*')? 'active' : '' }}">
                                    <a class="js-navbar-vertical-aside-menu-link nav-link"
                                        href="{{ route('admin.delivery-man.list') }}"
                                        title="{{ translate('messages.deliveryman_list') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                            {{ translate('messages.deliveryman_list') }}
                                        </span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('admin/delivery-man/add') ? 'active' : '' }}">
                                    <a class="nav-link"
                                        href="{{ route('admin.delivery-man.add') }}"
                                        title="{{ translate('messages.add_delivery_man') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                            {{ translate('messages.add_new_delivery_man') }}
                                        </span>
                                    </a>
                                </li>

                                <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/delivery-man/pending-delivery-man-view/*') ||  Request::is('admin/delivery-man/pending/list') || Request::is('admin/delivery-man/denied/list') ? 'active' : '' }}">
                                    <a class="js-navbar-vertical-aside-menu-link nav-link"
                                        href="{{ route('admin.delivery-man.pending') }}"
                                        title="{{ translate('messages.New_joining_request') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                            {{ translate('messages.New_joining_request') }}
                                        </span>
                                    </a>
                                </li>
                                <li  class="navbar-vertical-aside-has-menu {{ Request::is('admin/delivery-man/incentive') ? 'active' : '' }}">
                                    <a class="js-navbar-vertical-aside-menu-link nav-link"
                                        href="{{  route('admin.delivery-man.incentive')}}"
                                        title=" {{ translate('messages.incentive_Requests') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                            {{ translate('messages.incentive_Requests') }}
                                        </span>
                                    </a>
                                </li>
                                <li  class="navbar-vertical-aside-has-menu {{ Request::is('admin/delivery-man/incentive-history') ? 'active' : '' }}">
                                    <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('admin.delivery-man.incentive-history') }}"
                                        title="{{ translate('messages.incentives_history') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span
                                            class="text-truncate text-capitalize">{{ translate('messages.incentives_history') }}</span>
                                    </a>
                                </li>
                                <li  class="navbar-vertical-aside-has-menu {{ Request::is('admin/delivery-man/bonus') ? 'active' : '' }}">
                                    <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('admin.delivery-man.bonus') }}"
                                        title="{{ translate('messages.bonus') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span
                                            class="text-truncate text-capitalize">{{ translate('messages.bonus') }}</span>
                                    </a>
                                </li>

                                <li  class="navbar-vertical-aside-has-menu {{ Request::is('admin/delivery-man/reviews/list') ? 'active' : '' }}">
                                <a class="js-navbar-vertical-aside-menu-link nav-link"
                                    href="{{ route('admin.delivery-man.reviews.list') }}"
                                    title="{{ translate('messages.reviews') }}">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                        {{ translate('messages.reviews') }}
                                    </span>
                                </a>
                            </li>

                            </ul>
                        </li>
                    @endif
                    <!-- End DeliveryMan -->
                    @if (\App\CentralLogics\Helpers::module_permission_check('customerList'))
                        <!-- Custommer -->
                        <li class="nav-item">
                            <small class="nav-subtitle"
                                title="{{ translate('messages.customer_section') }}">{{ translate('messages.customer_management') }}</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>


                        <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/customer/list') || Request::is('admin/customer/view*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('admin.customer.list') }}"
                                title="{{ translate('messages.Customer_List') }}">
                                <i class="tio-poi-user nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.customeres') }}
                                </span>
                            </a>
                        </li>
                        {{-- <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('admin/customer/logs') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('admin.customer.visitor_logs') }}"
                                title="{{ translate('Customer_Logs') }}">
                                <i class="tio-poi-user nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.customer_Logs') }}
                                </span>
                            </a>
                        </li> --}}

                        @if (\App\CentralLogics\Helpers::module_permission_check('customer_wallet'))
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('admin/customer/wallet*') ? 'active' : '' }}">

                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                                title="{{ translate('messages.Customer_Wallet') }}">
                                <i class="tio-wallet nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate  text-capitalize">
                                    {{ translate('messages.wallet') }}
                                </span>
                            </a>

                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('admin/customer/wallet*') ? 'block' : 'none' }}">
                                <li
                                    class="nav-item {{ Request::is('admin/customer/wallet/add-fund') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.customer.wallet.add-fund') }}"
                                        title="{{ translate('messages.add_fund') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span
                                            class="text-truncate text-capitalize">{{ translate('messages.add_fund') }}</span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('admin/customer/wallet/bonus*') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.customer.wallet.bonus.add-new') }}" title="{{ translate('messages.bonus') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate text-capitalize">{{ translate('messages.bonus') }}</span>
                                    </a>
                                </li>

                            </ul>
                        </li>
                        @endif
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('admin/customer/loyalty-point*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link  nav-link-toggle" href="javascript:"
                                title="{{ translate('messages.loyalty_point') }}">
                                <i class="tio-medal nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate  text-capitalize">
                                    {{ translate('messages.loyalty_point') }}
                                </span>
                            </a>

                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('admin/customer/loyalty-point*') ? 'block' : 'none' }}">
                                <li  class="nav-item {{ Request::is('admin/customer/loyalty-point/report*') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('admin.customer.loyalty-point.report') }}"
                                            title="{{ translate('messages.report') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span
                                                class="text-truncate text-capitalize">{{ translate('messages.report') }}</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <!--<li-->
                            <!--    class="navbar-vertical-aside-has-menu {{ Request::is('admin/customer/subscribed') ? 'active' : '' }}">-->
                            <!--    <a class="js-navbar-vertical-aside-menu-link nav-link"-->
                            <!--        href="{{ route('admin.customer.subscribed') }}"-->
                            <!--        title="{{ translate('messages.Subscribed_Emails') }}">-->
                            <!--        <i class="tio-email-outlined nav-icon"></i>-->
                            <!--        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">-->
                            <!--            {{ translate('messages.subscribed_mail_list') }}-->
                            <!--        </span>-->
                            <!--    </a>-->
                            <!--</li>-->
                        </li>
                    @endif
                    <!-- End Custommer -->

            <li class="nav-item">
    <small class="nav-subtitle" title="{{ translate('Bestie_Buddy') }}">
        {{ translate('Bestie_Buddy') }}
    </small>
    <small class="tio-more-horizontal nav-subtitle-replacer"></small>
</li>
 
<li class="navbar-vertical-aside-has-menu {{ Request::is('admin/grocery-list*') ? 'active' : '' }}">
 
    <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
       href="javascript:"
       title="{{ translate('Bestie_Buddy_List') }}">
        <i class="tio-shopping-cart nav-icon"></i>
        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
            {{ translate('Bestie_Buddy_List') }}
        </span>
    </a>
 
    <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
        style="display: {{ Request::is('admin/grocery-list*') ? 'block' : 'none' }}">
 
        {{-- All --}}
        <li class="nav-item {{ Request::is('admin/grocery-list/list') && !request('status') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.grocery-list.list') }}" title="{{ translate('All') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate sidebar--badge-container">
                    {{ translate('All') }}
                    <span class="badge badge-soft-info badge-pill ml-1">
                        {{ \App\Models\GroceryList::count() }}
                    </span>
                </span>
            </a>
        </li>
 
        {{-- Pending --}}
        <li class="nav-item {{ request('status') == 'pending' ? 'active' : '' }}">
            <a class="nav-link"
               href="{{ route('admin.grocery-list.list', ['status' => 'pending']) }}"
               title="{{ translate('Pending') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate sidebar--badge-container">
                    {{ translate('Pending') }}
                    <span class="badge badge-soft-secondary badge-pill ml-1">
                        {{ \App\Models\GroceryList::where('status', 'pending')->count() }}
                    </span>
                </span>
            </a>
        </li>
 
        {{-- Confirmed --}}
        <li class="nav-item {{ request('status') == 'confirmed' ? 'active' : '' }}">
            <a class="nav-link"
               href="{{ route('admin.grocery-list.list', ['status' => 'confirmed']) }}"
               title="{{ translate('Confirmed') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate sidebar--badge-container">
                    {{ translate('Confirmed') }}
                    <span class="badge badge-soft-success badge-pill ml-1">
                        {{ \App\Models\GroceryList::where('status', 'confirmed')->count() }}
                    </span>
                </span>
            </a>
        </li>
 
        {{-- Processing --}}
        <li class="nav-item {{ request('status') == 'processing' ? 'active' : '' }}">
            <a class="nav-link"
               href="{{ route('admin.grocery-list.list', ['status' => 'processing']) }}"
               title="{{ translate('Processing') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate sidebar--badge-container">
                    {{ translate('Processing') }}
                    <span class="badge badge-soft-warning badge-pill ml-1">
                        {{ \App\Models\GroceryList::where('status', 'processing')->count() }}
                    </span>
                </span>
            </a>
        </li>
 
        {{-- Handover --}}
        <li class="nav-item {{ request('status') == 'handover' ? 'active' : '' }}">
            <a class="nav-link"
               href="{{ route('admin.grocery-list.list', ['status' => 'handover']) }}"
               title="{{ translate('Handover') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate sidebar--badge-container">
                    {{ translate('Handover') }}
                    <span class="badge badge-soft-info badge-pill ml-1">
                        {{ \App\Models\GroceryList::where('status', 'handover')->count() }}
                    </span>
                </span>
            </a>
        </li>
 
        {{-- Out for Delivery --}}
        <li class="nav-item {{ request('status') == 'out_for_delivery' ? 'active' : '' }}">
            <a class="nav-link"
               href="{{ route('admin.grocery-list.list', ['status' => 'out_for_delivery']) }}"
               title="{{ translate('Out_for_Delivery') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate sidebar--badge-container">
                    {{ translate('Out for Delivery') }}
                    <span class="badge badge-soft-warning badge-pill ml-1">
                        {{ \App\Models\GroceryList::where('status', 'out_for_delivery')->count() }}
                    </span>
                </span>
            </a>
        </li>
 
        {{-- Delivered --}}
        <li class="nav-item {{ request('status') == 'delivered' ? 'active' : '' }}">
            <a class="nav-link"
               href="{{ route('admin.grocery-list.list', ['status' => 'delivered']) }}"
               title="{{ translate('Delivered') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate sidebar--badge-container">
                    {{ translate('Delivered') }}
                    <span class="badge badge-soft-success badge-pill ml-1">
                        {{ \App\Models\GroceryList::where('status', 'delivered')->count() }}
                    </span>
                </span>
            </a>
        </li>
 
        {{-- Cancelled --}}
        <li class="nav-item {{ request('status') == 'cancelled' ? 'active' : '' }}">
            <a class="nav-link"
               href="{{ route('admin.grocery-list.list', ['status' => 'cancelled']) }}"
               title="{{ translate('Cancelled') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate sidebar--badge-container">
                    {{ translate('Cancelled') }}
                    <span class="badge badge-soft-danger badge-pill ml-1">
                        {{ \App\Models\GroceryList::where('status', 'cancelled')->count() }}
                    </span>
                </span>
            </a>
        </li>
 
    </ul>
</li>
                    <!-- Employee-->
                    <li class="nav-item">
                        <small class="nav-subtitle"
                            title="{{ translate('messages.employee_handle') }}">{{ translate('messages.Employee_Management') }}</small>
                        <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                    </li>

                    @if (\App\CentralLogics\Helpers::module_permission_check('custom_role'))
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('admin/custom-role*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('admin.custom-role.create') }}"
                                title="{{ translate('messages.employee_Role') }}">
                                <i class="tio-incognito nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.employee_Role') }}</span>
                            </a>
                        </li>
                    @endif

                    @if (\App\CentralLogics\Helpers::module_permission_check('employee'))
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('admin/employee*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                                title="{{ translate('Employees') }}">
                                <i class="tio-user nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.employees') }}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('admin/employee*') ? 'block' : 'none' }}">
                                <li class="nav-item {{ Request::is('admin/employee/add-new') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.employee.add-new') }}"
                                        title="{{ translate('messages.add_new_Employee') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.add_new') }}</span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('admin/employee/list')  ||  Request::is('admin/employee/update/*') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.employee.list') }}"
                                        title="{{ translate('messages.Employee_list') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.list') }}</span>
                                    </a>
                                </li>

                            </ul>
                        </li>
                    @endif
                    <!-- End Employee -->

                    <!-- Report -->
                    @if (\App\CentralLogics\Helpers::module_permission_check('report'))
                        <li class="nav-item">
                            <small class="nav-subtitle"
                                title="{{ translate('messages.report_and_analytics') }}">{{ translate('messages.report_management') }}</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>

                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('admin/report/transaction-report') ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('admin.report.day-wise-report') }}"
                                title="{{ translate('messages.transaction_report') }}">
                                <span class="tio-chart-pie-1 nav-icon"></span>
                                <span class="text-truncate">{{ translate('messages.transaction_report') }}</span>
                            </a>
                        </li>

                        <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/report/expense-report') ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('admin.report.expense-report') }}" title="{{ translate('messages.expense_report') }}">
                                <span class="tio-image  nav-icon"></span>
                                <span class="text-truncate">{{ translate('messages.expense_report') }}</span>
                            </a>
                        </li>

                        <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/report/food-wise-report') ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('admin.report.food-wise-report') }}"
                                title="{{ translate('messages.food_report') }}">
                                <span class="tio-fastfood nav-icon"></span>
                                <span class="text-truncate">{{ translate('messages.food_report') }}</span>
                            </a>
                        </li>
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/report/disbursement-report') ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('admin.report.disbursement_report') }}"
                                title="{{ translate('messages.disbursement_report') }}">
                                <span class="tio-saving nav-icon"></span>
                                <span class="text-truncate">{{ translate('messages.disbursement_report') }}</span>
                            </a>
                        </li>

                        <li class="navbar-vertical-aside-has-menu  {{Request::is('admin/report/order-report') || Request::is('admin/report/campaign-order-report') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                                title="{{ translate('messages.Order_Report') }}">
                                <i class="tio-user nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.Order_Report') }}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{Request::is('admin/report/order-report') || Request::is('admin/report/campaign-order-report') ? 'block' : 'none' }}">
                                <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/report/order-report') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.report.order-report') }}" title="{{ translate('messages.order_report') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate text-capitalize">{{ translate('messages.Regular_order_report') }}</span>
                                    </a>
                                </li>
                                <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/report/campaign-order-report') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.report.campaign_order-report') }}" title="{{ translate('messages.Campaign_Order_Report') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate text-capitalize">{{ translate('messages.Campaign_Order_Report') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </li>


                        <li class="navbar-vertical-aside-has-menu   {{ Request::is('admin/report/subscription-report') || Request::is('admin/report/restaurant-report')  ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                                title="{{ translate('messages.restaurant_report') }}">
                                <span class="tio-files nav-icon"></span>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.restaurant_report') }}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('admin/report/subscription-report') || Request::is('admin/report/restaurant-report')  ? 'block' : 'none' }}">

                                <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/report/restaurant-report') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.report.restaurant-report') }}"
                                        title="{{ translate('messages.restaurant_report') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.restaurant_report') }}</span>
                                    </a>
                                </li>
                                @if (\App\CentralLogics\Helpers::subscription_check() == true  )
                                <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/report/subscription-report') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.report.subscription-report') }}"
                                        title="{{ translate('messages.Subscription_report') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.Subscription_report') }}</span>
                                    </a>
                                </li>
                                @endif
                            </ul>
                        </li>
                        <li class="navbar-vertical-aside-has-menu   {{Request::is('admin/customer/wallet/report*')  ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                                title="{{ translate('messages.Customer_Report') }}">
                                <span class="tio-poi-user nav-icon"></span>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.Customer_Report') }}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('admin/customer/wallet/report*')  ? 'block' : 'none' }}">

                                <li  class="nav-item {{ Request::is('admin/customer/wallet/report*') ? 'active' : '' }}">
                                <a class="nav-link " href="{{ route('admin.customer.wallet.report') }}"
                                    title="{{ translate('messages.Customer_Wallet_Report') }}">
                                    <span class="tio-circle nav-indicator-icon"></span>
                                    <span
                                        class="text-truncate text-capitalize">{{ translate('messages.Customer_Wallet_Report') }}</span>
                                </a>
                                </li>
                            </ul>
                        </li>
                    @endif
                    <!-- Report -->
                    <!-- Business Settings -->
                    @if (\App\CentralLogics\Helpers::module_permission_check('settings'))
                        <li class="nav-item">
                            <small class="nav-subtitle"
                                title="{{ translate('messages.business_settings') }}">{{ translate('messages.business_settings') }}</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>

                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('admin/business-settings/business-setup*')
                                ||  Request::is('admin/business-settings/refund/settings*') ||  Request::is('admin/business-settings/language*')
                            ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('admin.business-settings.business-setup') }}"
                                title="{{ translate('messages.business_setup') }}">
                                <span class="tio-settings nav-icon"></span>
                                <span class="text-truncate">{{ translate('messages.business_setup') }}</span>
                            </a>
                        </li>
                        <!-- Subscription-->
                        @if (\App\CentralLogics\Helpers::subscription_check() == true &&  \App\CentralLogics\Helpers::module_permission_check('restaurant')  )

                            <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/subscription*') ? 'active' : '' }}">
                                <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                                    title="{{ translate('messages.subscription') }}">
                                    <i class="tio-crown  nav-icon"></i>
                                    <span
                                        class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.subscription_management') }}</span>
                                </a>
                                <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                    style="display: {{ Request::is('admin/subscription*') ? 'block' : 'none' }}">
                                    <li class="nav-item {{ Request::is('admin/subscription/package/*') || Request::is('admin/subscription/search')  ||  Request::is('admin/subscription/transcation/list/*')? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('admin.subscription.package_list') }}"
                                            title="{{ translate('messages.Package_list') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">{{ translate('messages.subscription_Packages') }}</span>
                                        </a>
                                    </li>
                                    <li class="nav-item {{ Request::is('admin/subscription/list') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('admin.subscription.subscription_list') }}"
                                            title="{{ translate('messages.subscription_list') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">{{ translate('Subscriber_list') }}</span>
                                        </a>
                                    </li>
                                    </li>
                                    <li class="nav-item {{ Request::is('admin/subscription/settings') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('admin.subscription.settings') }}"
                                            title="{{ translate('messages.settings') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">{{ translate('messages.settings') }}</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endif
                        <!-- End Subscription -->

                        <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/business-settings/email-setup*') ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('admin.business-settings.email-setup',['admin','forgot-password']) }}" title="{{ translate('messages.email_template') }}">
                                <span class="tio-email nav-icon"></span>
                                <span class="text-truncate">{{ translate('messages.email_template') }}</span>
                            </a>
                        </li>

                        <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/business-settings/theme-settings*') ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('admin.business-settings.theme-settings') }}"
                                title="{{ translate('messages.theme_settings') }}">
                                <span class="tio-brush nav-icon"></span>
                                <span class="text-truncate">{{ translate('messages.theme_settings') }}</span>
                            </a>
                        </li>

                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('admin/file-manager*') ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('admin.file-manager.index') }}"
                                title="{{ translate('messages.gallery') }}">
                                <span class="tio-album nav-icon"></span>
                                <span
                                    class="text-truncate text-capitalize">{{ translate('messages.gallery') }}</span>
                            </a>
                        </li>



                    <!-- web & adpp Settings -->
                        <li  class="navbar-vertical-aside-has-menu {{ Request::is('admin/business-settings/pages*') || Request::is('admin/business-settings/social-media')  ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                                title="{{ translate('messages.Pages_&_Social_Media') }}">
                                <i class="tio-pages nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.Pages_&_Social_Media') }}
                                   </span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('admin/business-settings/pages*') || Request::is('admin/business-settings/social-media')  ? 'block' : 'none' }}">


                                    <li  class="navbar-vertical-aside-has-menu {{ Request::is('admin/business-settings/social-media') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('admin.business-settings.social-media.index') }}"
                                            title="{{ translate('messages.Social_Media') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">{{ translate('messages.Social_Media') }}</span>
                                        </a>
                                    </li>

                                    <li class="nav-item {{ Request::is('admin/business-settings/pages/terms-and-conditions') ? 'active' : '' }}">
                                        <a class="nav-link "
                                            href="{{ route('admin.business-settings.terms-and-conditions') }}"
                                            title="{{ translate('messages.terms_and_condition') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span
                                                class="text-truncate">{{ translate('messages.terms_and_condition') }}</span>
                                        </a>
                                    </li>

                                    <li  class="nav-item {{ Request::is('admin/business-settings/pages/privacy-policy') ? 'active' : '' }}">
                                        <a class="nav-link "
                                            href="{{ route('admin.business-settings.privacy-policy') }}"
                                            title="{{ translate('messages.privacy_policy') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">{{ translate('messages.privacy_policy') }}</span>
                                        </a>
                                    </li>

                                    <li  class="nav-item {{ Request::is('admin/business-settings/pages/about-us') ? 'active' : '' }}">
                                        <a class="nav-link " href="{{ route('admin.business-settings.about-us') }}"
                                            title="{{ translate('messages.about_us') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">{{ translate('messages.about_us') }}</span>
                                        </a>
                                    </li>


                                    <li class="nav-item {{ Request::is('admin/business-settings/pages/refund-policy') ? 'active' : '' }}">
                                        <a class="nav-link "
                                            href="{{ route('admin.business-settings.refund-policy') }}"
                                            title="{{ translate('messages.refund_policy') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">{{ translate('messages.refund_policy') }}</span>
                                        </a>
                                    </li>


                                    <li class="nav-item {{ Request::is('admin/business-settings/pages/shipping-policy') ? 'active' : '' }}">
                                        <a class="nav-link "
                                            href="{{ route('admin.business-settings.shipping-policy') }}"
                                            title="{{ translate('messages.shipping_policy') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">{{ translate('messages.shipping_policy') }}</span>
                                        </a>
                                    </li>

                                    <li class="nav-item {{ Request::is('admin/business-settings/pages/cancellation-policy') ? 'active' : '' }}">
                                        <a class="nav-link "
                                            href="{{ route('admin.business-settings.cancellation-policy') }}"
                                            title="{{ translate('messages.cancellation_policy') }}">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate">{{ translate('messages.cancellation_policy') }}</span>
                                        </a>
                                    </li>


                            </ul>
                        </li>
                        <li class="nav-item">
                            <small class="nav-subtitle"
                                title="{{ translate('messages.system_settings') }}">{{ translate('messages.system_settings') }}</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>

                        <li class="navbar-vertical-aside-has-menu   {{Request::is('admin/business-settings/fcm-*')|| Request::is('admin/business-settings/payment-method')  || Request::is('admin/business-settings/sms-module')|| Request::is('admin/business-settings/mail-config') || Request::is('admin/social-login/view') || Request::is('admin/business-settings/offline*')||Request::is('admin/business-settings/config*')  || Request::is('admin/business-settings/recaptcha*') || Request::is('admin/business-settings/*') ? 'active' : '' }} @yield('3rd_party')" >
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                                title="{{ translate('3rd_Party_&_Configurations') }}">
                                <span class="tio-plugin nav-icon"></span>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.3rd_Party_&_Configurations') }}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('admin/business-settings/deliveryman/join-us/*') || Request::is('admin/business-settings/restaurant/join-us/*') || Request::is('admin/business-settings/fcm-*')|| Request::is('admin/business-settings/payment-method')  || Request::is('admin/business-settings/sms-module')|| Request::is('admin/business-settings/mail-config') || Request::is('admin/social-login/view') ||Request::is('admin/business-settings/config*') || Request::is('admin/business-settings/recaptcha*') || Request::is('admin/business-settings/offline*') || Request::is('admin/login-url/login-page-setup*')? 'block' : 'none' }}  ">


                                <li class="nav-item {{ Request::is('admin/business-settings/payment-method') || Request::is('admin/business-settings/sms-module')|| Request::is('admin/business-settings/mail-config') || Request::is('admin/social-login/view') ||Request::is('admin/business-settings/config*') || Request::is('admin/business-settings/recaptcha*')  ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.business-settings.payment-method') }}"
                                        title="{{ translate('messages.3rd_Party') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.3rd_Party') }}
                                        </span>
                                    </a>
                                </li>

                                <li class="nav-item  {{ Request::is('admin/business-settings/fcm-*') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.business-settings.fcm-index') }}"
                                        title="{{ translate('messages.Firebase_Notification') }} ">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.Firebase_Notification') }}
                                        </span>
                                    </a>
                                </li>
                                <li class="nav-item  {{ Request::is('admin/login-url/login-page-setup*') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.login_url.login_url_page') }}"
                                        title="{{ translate('messages.login_page_setup') }} ">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.login_page_setup') }}
                                        </span>
                                    </a>
                                </li>
                                @if (\App\CentralLogics\Helpers::get_mail_status('offline_payment_status'))
                                <li class="nav-item {{ Request::is('admin/business-settings/offline*') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('admin.business-settings.offline') }}" title="{{ translate('messages.Offline_Payment_Setup') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.Offline_Payment_Setup') }}</span>
                                    </a>
                                </li>
                                @endif

                                <li class="nav-item @yield('reg_page')">
                                    <a class="nav-link " href="{{ route('admin.business-settings.restaurant_page_setup') }}" title="{{ translate('messages.Join_us_page_setup') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.Join_us_page_setup') }}</span>
                                    </a>
                                </li>


                            </ul>
                        </li>

                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('admin/business-settings/app-settings*') ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('admin.business-settings.app-settings') }}"
                                title="{{ translate('messages.App_&_Web_Settings') }}">
                                <span class="tio-android nav-icon"></span>
                                <span class="text-truncate">{{ translate('messages.App_&_Web_Settings') }}</span>
                            </a>
                        </li>

                    <li class="navbar-vertical-aside-has-menu   {{ Request::is('admin/react-landing-page*') ||   Request::is('admin/landing-page*')  ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                            title="{{ translate('landing_page_settings') }}">
                            <span class="tio-files nav-icon"></span>
                            <span
                                class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.landing_page_settings') }}</span>
                        </a>
                        <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                            style="display: {{ Request::is('admin/react-landing-page*') ||   Request::is('admin/landing-page*')  ? 'block' : 'none' }}">

                            <li class="nav-item {{ Request::is('admin/landing-page*') ? 'active' : '' }}">
                            <a class="nav-link"
                                href="{{ route('admin.landing_page.header') }}"
                                title="{{ translate('messages.Admin_landing_page') }} ">
                               <span class="tio-circle nav-indicator-icon"></span>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.Admin_landing_page') }}</span>
                            </a>
                        </li>

                            <li class="nav-item {{ Request::is('admin/react-landing-page*') ? 'active' : '' }}">
                            <a class="nav-link"
                                href="{{ route('admin.react_landing_page.react_header') }}"
                                title="{{ translate('messages.React_landing_page') }} ">
                               <span class="tio-circle nav-indicator-icon"></span>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.React_landing_page') }}</span>
                            </a>
                        </li>

                        </ul>
                    </li>

                        <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/business-settings/react*') ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('admin.business-settings.react-setup') }}"
                                title="{{ translate('messages.react_site') }}">
                                <span class="tio-rear-window-defrost nav-icon"></span>
                                <span class="text-truncate">{{ translate('messages.react_site') }}</span>
                            </a>
                        </li>


                        <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/business-settings/db-index') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('admin.business-settings.db-index') }}"
                                title="{{ translate('messages.clean_database') }}">
                                <i class="tio-cloud nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.clean_database') }}
                                </span>
                            </a>
                        </li>

                    <!-- End web & adpp Settings -->

                    @endif
                    <!-- End Business Settings -->


                    <!-- Business Section-->
                    <li class="nav-item">
                        <small class="nav-subtitle"
                            title="{{ translate('messages.business_section') }}">{{ translate('messages.transaction_management') }}</small>
                        <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                    </li>
                    <!-- account -->
                    @if (\App\CentralLogics\Helpers::module_permission_check('account'))
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('admin/restaurant-disbursement*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                               href="{{ route('admin.restaurant-disbursement.list', ['status' => 'all']) }}"
                               title="{{ translate('messages.restaurant_disbursement') }}">
                                <i class="tio-wallet-outlined nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.restaurant_disbursement') }}</span>
                            </a>
                        </li>
                    @endif
                @if (\App\CentralLogics\Helpers::module_permission_check('account'))
                    <li
                        class="navbar-vertical-aside-has-menu {{ Request::is('admin/dm-disbursement*') ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link"
                           href="{{ route('admin.dm-disbursement.list', ['status' => 'all']) }}"
                           title="{{ translate('messages.dm_disbursement') }}">
                            <i class="tio-saving-outlined nav-icon"></i>
                            <span
                                class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.delivery_man_disbursement') }}</span>
                        </a>
                    </li>
                @endif
                    @if (\App\CentralLogics\Helpers::module_permission_check('account'))
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('admin/account-transaction*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('admin.account-transaction.index') }}"
                                title="{{ translate('messages.collect_cash') }}">
                                <i class="tio-money nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.collect_cash') }}</span>
                            </a>
                        </li>
                    @endif
                    <!-- End account -->
                    <!-- withdraw -->
                    @if (\App\CentralLogics\Helpers::module_permission_check('withdraw_list'))
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('admin/restaurant/withdraw*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('admin.restaurant.withdraw_list') }}"
                                title="{{ translate('messages.restaurant_withdraws') }}">
                                <i class="tio-table nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.restaurant_withdraws') }}</span>
                            </a>
                        </li>
                    @endif

                    @if (\App\CentralLogics\Helpers::module_permission_check('settings'))
                        <li
                        class="navbar-vertical-aside-has-menu {{ Request::is('admin/business-settings/withdraw-method*') ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link"
                            href="{{ route('admin.business-settings.withdraw-method.list') }}"
                            title="{{ translate('messages.withdraw_method') }}">
                            <i class="tio-savings nav-icon"></i>
                            <span
                                class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.withdraw_method') }}</span>
                        </a>
                        </li>
                    @endif
                    <!-- End withdraw -->

                    <!-- provide_dm_earning -->
                    @if (\App\CentralLogics\Helpers::module_permission_check('provide_dm_earning'))
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('admin/provide-deliveryman-earnings*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('admin.provide-deliveryman-earnings.index') }}"
                                title="{{ translate('messages.DeliveryMan_Payments') }}">
                                <i class="tio-send nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.DeliveryMan_Payments') }}</span>
                            </a>
                        </li>
                    @endif
                    <!-- End provide_dm_earning -->



                    {{-- @if(1) --}}
                    <!-- Dashboards -->
                    <li class="navbar-vertical-aside-has-menu {{Request::is('admin/business-settings/system-addon')?'show active':''}}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link"
                           href="{{route('admin.business-settings.system-addon.index')}}" title="{{translate('messages.system_addons')}}">
                            <i class="tio-add-circle-outlined nav-icon"></i>
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                            {{translate('messages.system_addons')}}
                        </span>
                        </a>
                    </li>
                    <!-- End Dashboards -->
                    {{-- @endif --}}

                {{-- addons--}}
                {{-- @if(count(config('addon_admin_routes'))>0)


            {{--
                    @foreach(config('addon_admin_routes') as $routes)
                        @foreach($routes as $route)
                            <li class="navbar-vertical-aside-has-menu {{ Request::is($route['path']) ? 'show active' : '' }}">
                                <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ $route['url'] }}" title="{{ translate($route['name']) }}">
                                    <i class="tio- nav-icon"></i>
                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                        {{ translate($route['name']) }}
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    @endforeach
                @endif --}}





                @if(count(config('addon_admin_routes',[]))>0)
                <li class="nav-item">
                    <small
                        class="nav-subtitle">{{translate('messages.addon_menus')}}</small>
                    <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                </li>
                <li class="navbar-vertical-aside-has-menu {{Request::is('admin/payment/configuration/*') || Request::is('admin/sms/configuration/*')?'active':''}}">
                    <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:" >
                        <i class="tio-puzzle nav-icon"></i>
                        <span  class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{translate('Addon Menus')}}</span>
                    </a>
                    <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="display: {{Request::is('admin/payment/configuration/*') || Request::is('admin/sms/configuration/*')?'block':'none'}}">
                        @foreach(config('addon_admin_routes') as $routes)
                            @foreach($routes as $route)
                                <li class="navbar-vertical-aside-has-menu {{Request::is($route['path'])  ? 'active' :''}}">
                                    <a class="js-navbar-vertical-aside-menu-link nav-link "
                                    href="{{ $route['url'] }}" title="{{ translate($route['name']) }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate($route['name']) }}</span>
                                    </a>
                                </li>
                            @endforeach
                        @endforeach
                    </ul>
                </li>
            @endif
            <!--addon end-->




                    <!-- End web & adpp Settings -->



                    <li class="nav-item pt-100px">

                    </li>
                </ul>
            </div>
            <!-- End Content -->
        </div>
    </aside>
</div>

<div id="sidebarCompact" class="d-none">

</div>


@push('script_2')
    <script>
        $(window).on('load', function() {
            if ($(".navbar-vertical-content li.active").length) {
                $('.navbar-vertical-content').animate({
                    scrollTop: $(".navbar-vertical-content li.active").offset().top - 150
                }, 10);
            }
        });
    </script>
    <script>
        var $rows = $('#navbar-vertical-content li');
        $('#search').keyup(function() {
            var val = $.trim($(this).val()).replace(/ +/g, ' ').toLowerCase();

            $rows.show().filter(function() {
                var text = $(this).text().replace(/\s+/g, ' ').toLowerCase();
                return !~text.indexOf(val);
            }).hide();
        });
    </script>
@endpush
