<!DOCTYPE html>
<html>

<head>
    <title>Invoice</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .container-flex {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #808080;
        }

        /* .right-content {
            text-align: right;
        } */
    </style>
</head>

<body>
    <div  style="display: flex; justify-content: space-between; border-bottom: 1px solid #808080;">
        <div class="invoice-details">
            <b>Organization:</b>
            <img src="{{ $organization?->fetchFirstMedia()?->file_url ?? '' }}" alt="Organization"
                style="width: 100px;">
            <br> Name: {{ $organization['name'] ?? '' }}
            <br> Address: {{ $organization['address'] ?? '' }}
            <br> Phone: {{ $organization['phone'] ?? '' }}
            <br> Email: {{ $organization['email'] ?? '' }}
            <br>
            <br>
        </div>
        <div class="right-content">
            <b>Invoice Code</b>: {{ $invoiceData['code'] }}
            <br>
            <b>Date Issued</b>: {{ $invoiceData['issued_date'] }}
            <br>
            <b>Due Date</b>: {{ $invoiceData['created_date'] }}
            <br>
            <br>
        </div>
    </div>
    <br>
    <div class="container-flex">
        <p>
            <b>Invoice To <br />
            </b>{{ $invoiceData['customer']['name'] }}
            <br />{{ $invoiceData['customer']['address'] }}
            <br />{{ $invoiceData['customer']['contact_number'] }}
            <br />{{ $invoiceData['customer']['email'] }}
            <br />
        </p>
        <img src="{{ $invoiceData['qr_code']}}" alt="QR Code" style="width: 100px;">
    </div>
    <table>
        <tr>
            <th>Item Description</th>
            <th>Cost</th>
            <th>Quantity</th>
            <th>Hours</th>
            <th>Price</th>
        </tr> @foreach ($invoiceData['items'] as $item) <tr>
            <td>{{ $item['name'] }}</td>
            <td>{{ $item['price'] }}</td>
            <td>{{ $item['pivot']['cost'] }}</td>
            <td>{{ $item['pivot']['hours'] }}</td>
            <td>{{ $item['price'] * $item['pivot']['cost'] * $item['pivot']['hours'] }}</td>
        </tr> @endforeach
    </table>
    <div class='container-flex'  style="display: flex; justify-content: space-between; border-bottom: 1px solid #808080;">
        <p>
            <b>Saleperson: <br />
            </b>{{ $invoiceData['sale_person'] }}
            <br />
        </p>
        <div class="right-content">
            <p>
                <b>Tax:</b> {{ $invoiceData['tax'] }}% <br />
            </p>
            <p>
                <b>Total:</b> {{ $invoiceData['total'] }}
                <br />
            </p>
        </div>
    </div>
    <p>
        <b>Note: <br />
        </b>{{ $invoiceData['note'] }}
    </p>
</body>

</html>