<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>


    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        body {
            background-color: #FCFCFC;
            font-family: 'Inter', sans-serif;
            font-size: 10px;
            padding: 0;
            margin: 0;
        }

        .invoice-wrapper {
            padding: 35px 20px 80px;
            width: 595px;
            margin: 0 auto;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 27px;
        }

        .logo {
            height: 20px;
            margin-bottom: 10px;
        }

        .logo img {
            height: 20px;
        }

        .invoice-header .title {
            font-size: 18px;
            margin: 0;
            margin-bottom: 8px;
        }

        .invoice-header .date {
            opacity: 0.8;
        }

        .invoice-header .right {
            text-align: right;
        }

        .invoice-body {
            border-radius: 12px;
            border: 1px solid #D7DAE0;
            background: #ffffff;
            flex-grow: 1;
        }

        .invoice-body-top {
            padding: 22px 15px;
            display: flex;
            font-weight: 500;
            column-gap: 30px;
            border-bottom: 1px solid #D7DAE0;
        }

        .invoice-body-top h6 {
            font-size: 16px;
            margin: 0;
            color: #1455AC;
        }

        .invoice-body-top .subtxt {
            margin-bottom: 4px;
            font-weight: 400;
            font-size: 9px;
            opacity: 0.9;
        }

        .ml-auto {
            margin-left: auto;
        }

        .invoice-body-bottom {
            padding: 9px 12px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table tr td,
        .table tr th {
            border-bottom: 1px solid #D6EBFF;
            padding: 13px;
            text-align: start;
            vertical-align: top;
        }

        .table tr th {
            border-top: 1px solid #D6EBFF;
            background: #F5FBFF;
            text-transform: uppercase;
            font-size: 9px;
            font-weight: 500;
        }

        .table .name {
            font-weight: 500;
            margin-bottom: 4px;
        }

        .text-right {
            text-align: right;
        }

        .info {
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-top: 8px;
        }

        .info li {
            list-style: none;
        }

        .mt-10px {
            margin-top: 10px;
        }

        .text-12 {
            font-size: 12px;
            font-weight: 700;
            color: #222222;
        }

        .border-0 {
            border: none !important;
        }

        .mr-100px {
            margin-right: 100px;
        }

        footer {
            border-top: 0.5px solid #EBEDF2;
            background: #F2F4F7;
            display: flex;
            justify-content: center;
            align-items: center;
            justify-content: center;
            column-gap: 60px;
            padding: 15px;
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            box-sizing: border-box
        }

        footer a {
            color: #000000;
            text-decoration: none;
        }
    </style>


</head>

<body>

    <!-- Invoice -->
    <div class="invoice-wrapper">
        <div class="invoice-header">
            <div class="left">
                <h4 class="title">Disbursement Invoice</h4>
                <div class="date">15 July, 2023 8:30 am</div>
            </div>
            <div class="right">
                <div class="logo">
                    <img src="{{asset('/public/assets/admin/img/logo2.png')}}" alt="logo">
                </div>
                <div>Business address City, State, IN - 000 000</div>
            </div>
        </div>
        <div class="invoice-body">
            <div class="invoice-body-top">
                <div>
                    <div class="subtxt">Disbursement ID</div>
                    <div>41546564564</div>
                </div>
                <div>
                    <div class="subtxt">Created At</div>
                    <div>12 July, 2023 8:30 am</div>
                </div>
                <div class="ml-auto">
                    <div class="subtxt">Total Amount (USD)</div>
                    <h6>$4,950.00</h6>
                </div>
            </div>
            <div class="invoice-body-bottom">
                <table class="table">
                    <thead>
                        <tr>
                            <th>SL</th>
                            <th>Restaurant Info</th>
                            <th>Payment Method</th>
                            <th>
                                <div class="text-right"> Amount</div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="mt-10px">
                                    01
                                </div>
                            </td>
                            <td>
                                <div class="name mt-10px">Hungry Puppets</div>
                            </td>
                            <td>
                                <div class="name">Bank</div>
                                <ul class="info">
                                    <li>
                                        <span>Bank Name</span>
                                        <span>:</span>
                                        <span class="name">City Bank</span>
                                    </li>
                                    <li>
                                        <span>Account Name</span>
                                        <span>:</span>
                                        <span class="name">Jhone Doe</span>
                                    </li>
                                    <li>
                                        <span>Account Number</span>
                                        <span>:</span>
                                        <span class="name">3214554545</span>
                                    </li>
                                    <li>
                                        <span>Branch Name</span>
                                        <span>:</span>
                                        <span class="name">abc Branch</span>
                                    </li>
                                </ul>
                            </td>
                            <td>
                                <div class="mt-10px text-right">
                                    $4,950.00
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="mt-10px">
                                    02
                                </div>
                            </td>
                            <td>
                                <div class="name mt-10px">Hungry Puppets</div>
                            </td>
                            <td>
                                <div class="name">6Cash</div>
                                <ul class="info">
                                    <li>
                                        <span>Bank Name</span>
                                        <span>:</span>
                                        <span class="name">City Bank</span>
                                    </li>
                                    <li>
                                        <span>Account Name</span>
                                        <span>:</span>
                                        <span class="name">Jhone Doe</span>
                                    </li>
                                </ul>
                            </td>
                            <td>
                                <div class="mt-10px text-right">
                                    $4,950.00
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="mt-10px">
                                    03
                                </div>
                            </td>
                            <td>
                                <div class="name mt-10px">Hungry Puppets</div>
                            </td>
                            <td>
                                <div class="name">7Cash</div>
                                <ul class="info">
                                    <li>
                                        <span>Account Name</span>
                                        <span>:</span>
                                        <span class="name">Jhone Doe</span>
                                    </li>
                                    <li>
                                        <span>Account Number</span>
                                        <span>:</span>
                                        <span class="name">3214554545</span>
                                    </li>
                                </ul>
                            </td>
                            <td>
                                <div class="mt-10px text-right">
                                    $4,950.00
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="border-0"></td>
                            <td class="border-0"></td>
                            <td class="border-0">
                                <div class="text-right text-12 mr-100px">
                                    Total
                                </div>
                            </td>
                            <td class="border-0">
                                <div class="text-right text-12">
                                    $4,950.00
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <br>
                <br>
            </div>
        </div>
        <footer>
            <a href="www.stackfood.inc">www.stackfood.inc</a>
            <a href="tel:+91 00000 00000">+91 00000 00000</a>
            <a href="mailto:hello@email.com">hello@email.com</a>
        </footer>
    </div>
    <!-- Invoice -->


</body>

</html>