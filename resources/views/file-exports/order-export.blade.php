<div class="row">
    <div class="col-lg-12 text-center "><h1 >{{ translate($data['status'] ?? 'messages.All') }} {{ translate('messages.Order_List') }}</h1></div>
    <div class="col-lg-12">



    <table>
        <thead>
            <tr>
                <th>{{ translate('filter_criteria') }} -</th>
                <th></th>
                <th></th>
                <th>
                    {{ translate('order_status' )}} : {{ translate($data['order_status'] ?? $data['status']) }}
                    @if ($data['search'])
                    <br>
                    {{ translate('search_bar_content' )}} : {{ $data['search'] }}
                    @endif
                    @if ($data['zones'])
                    <br>
                    {{ translate('zones' )}} : {{ $data['zones'] }}
                    @endif
                    @if ($data['restaurant'])
                    <br>
                    {{ translate('restaurant' )}} : {{ $data['restaurant'] }}
                    @endif
                    @if ($data['type'])
                    <br>
                    {{ translate('order_type' )}} : {{ translate($data['type']) }}
                    @endif
                    @if ($data['from'])
                    <br>
                    {{ translate('from' )}} : {{ $data['from']?Carbon\Carbon::parse($data['from'])->format('d M Y'):'' }}
                    @endif
                    @if ($data['to'])
                    <br>
                    {{ translate('to' )}} : {{ $data['to']?Carbon\Carbon::parse($data['to'])->format('d M Y'):'' }}
                    @endif

                </th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
            </tr>
            <tr>
                <th>{{ translate('messages.sl') }}</th>
                <th>{{ translate('messages.Order_Id') }}</th>
                <th>{{ translate('messages.Order_Date') }}</th>
                <th>{{ translate('messages.Customer_Name') }}</th>
                <th>{{ translate('messages.Restaurant_Name') }}</th>
                <th>{{ translate('messages.Food_Price') }}</th>
                <th>{{ translate('messages.Food_Discount') }}</th>
                <th>{{ translate('messages.Coupon_Discount') }}</th>
                <th>{{ translate('messages.Discounted_Amount') }}</th>
                <th>{{ translate('messages.Tax') }}</th>
                <th>{{ translate('messages.Delivery_Charge') }}</th>
                <th>{{ translate('messages.Admin_Commission') }}</th>
                <th>{{ translate('messages.Commission_Type') }}</th>
                <th>{{ translate('messages.Total_Amount') }}</th>
                <th>{{ translate('messages.Payment_Status') }}</th>
                <th>{{ translate('messages.Order_Status') }}</th>
                <th>{{ translate('messages.Order_Type') }}</th>
            </tr>
        </thead>
        <tbody>
        @foreach($data['orders'] as $key => $order)
            @php
                $isAdmin = auth('admin')->check();
                if ($isAdmin) {
                    $order_food_price = $order['order_amount'] - $order['dm_tips'] - $order['total_tax_amount'] - $order['delivery_charge'] + $order['coupon_discount_amount'] + $order['restaurant_discount_amount'];
                    $order_food_discount = $order->details->sum('discount_on_food');
                    $order_discounted_amount = $order['coupon_discount_amount'] + $order['restaurant_discount_amount'];
                    $order_total_amount = $order['order_amount'];
                } else {
                    $order_food_price = 0;
                    $order_food_discount = 0;
                    foreach ($order->details as $detail) {
                        $food_item = $detail->food ?? $detail->campaign;
                        if ($food_item) {
                            $item_price = $food_item->price;
                        } else {
                            $food_arr = json_decode($detail->food_details, true);
                            $item_price = isset($food_arr['price']) ? $food_arr['price'] : ($detail['price'] - $detail['commission_amount']);
                        }

                        $item_discount = 0;
                        if ($detail['discount_on_food'] > 0) {
                            if ($food_item) {
                                $item_discount = \App\CentralLogics\Helpers::product_discount_calculate($food_item, $item_price, $order->restaurant);
                            } else {
                                $item_discount = $detail['discount_on_food'] - \App\CentralLogics\Helpers::calculate_commission($detail['discount_on_food'], $order->restaurant);
                            }
                        }
                        // Subtract discount directly from food price for restaurant
                        $order_food_price += ($item_price - $item_discount) * $detail['quantity'];
                    }
                    $order_food_discount = 0;
                    $order_discounted_amount = $order['coupon_discount_amount'];
                    $order_total_amount = $order['order_amount'] - $order['total_commission_amount'];
                }
            @endphp
            <tr>
                <td>{{ $key+1 }}</td>
                <td>{{ $order->id }}</td>
                <td>{{ \App\CentralLogics\Helpers::time_date_format($order->created_at) }}</td>

                <td>
                    @if ($order->customer)
                        {{ $order->customer['f_name'] . ' ' . $order->customer['l_name'] }}
                    @else
                        {{ translate('not_found') }}
                    @endif
                </td>
                <td>
                    @if($order->restaurant)
                        {{$order->restaurant->name}}
                    @else
                        {{ translate('messages.not_found') }}
                    @endif
                </td>
                <td>{{ \App\CentralLogics\Helpers::format_currency($order_food_price) }}</td>
                <td>{{ \App\CentralLogics\Helpers::format_currency($order_food_discount) }}</td>
                <td>{{ \App\CentralLogics\Helpers::format_currency($order['coupon_discount_amount']) }}</td>
                <td>{{ \App\CentralLogics\Helpers::format_currency($order_discounted_amount) }}</td>
                <td>{{ \App\CentralLogics\Helpers::format_currency($order['total_tax_amount']) }}</td>
                <td>{{ \App\CentralLogics\Helpers::format_currency($order->delivery_charge) }}</td>
                <td>
                    @php
                        $admin_commission_value = 0;
                        $restaurant = $order->restaurant;
                        $restaurant_logic = $restaurant ? $restaurant->admin_commission_logic : null;
                        $global_logic = \App\Models\BusinessSetting::where('key', 'admin_commission_logic')->first()?->value ?? 'new';
                        $logic = $restaurant_logic ?: $global_logic;

                        if ($order->transaction) {
                            $admin_commission_value = $order->transaction->admin_commission;
                        } else {
                            if ($logic == 'old') {
                                $commission_pct = 0;
                                if ($restaurant) {
                                    $commission_pct = $restaurant->comission ?? \App\Models\BusinessSetting::where('key','admin_commission')->first()?->value ?? 0;
                                }
                                $food_base = $order['order_amount'] - $order['additional_charge'] - $order['delivery_charge'] - $order['total_tax_amount'] - $order['dm_tips'] + $order['coupon_discount_amount'] + $order['restaurant_discount_amount'];
                                $admin_commission_value = ($food_base * $commission_pct) / 100;
                            } else {
                                $admin_commission_value = $order->total_commission_amount;
                            }
                        }
                        $commission_type = $logic == 'old' ? translate('Food Commission') : translate('Admin Charge');
                    @endphp
                    {{ \App\CentralLogics\Helpers::format_currency($admin_commission_value) }}
                </td>
                <td>{{ $commission_type }}</td>
                <td>{{ \App\CentralLogics\Helpers::format_currency($order_total_amount) }}</td>
                <td>{{ translate($order->payment_status) }}</td>
                <td>{{ translate($order->order_status) }}</td>
                <td>{{ translate($order->order_type) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
