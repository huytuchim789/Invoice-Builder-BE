<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>

<head>
    <title></title>
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
    </style>
</head>

<body>
<div>
    <div>
        <div>
            <b>Organization:</b>
            <br>
            Name: {{ $organization['name'] }}
            <br>
            Address: {{ $organization['address'] }}
            <br>
            Phone: {{ $organization['phone'] }}
            <br>
            Email: {{ $organization['email'] }}
        </div>
        <br>
        <p><b>Invoice To<br /></b>{{ $invoiceData['customer']['name'] }}<br />{{ $invoiceData['customer']['address'] }}<br />{{ $invoiceData['customer']['contact_number'] }}<br />{{ $invoiceData['customer']['email'] }}<br />
        </p>
        <table>
            <tr>
                <th>Item Description</th>
                <th>Cost</th>
                <th>Quantity</th>
                <th>Hours</th>
                <th>Price</th>
            </tr>
            @foreach ($invoiceData['items'] as $item)
                <tr>
                    <td>{{ $item['name'] }}</td>
                    <td>{{ $item['price'] }}</td>
                    <td>{{ $item['pivot']['cost'] }}</td>
                    <td>{{ $item['pivot']['hours'] }}</td>
                    <td>{{ $item['price'] * $item['pivot']['cost'] * $item['pivot']['hours'] }}</td>
                </tr>
            @endforeach
        </table>
        <img src="{{ $invoiceData['qr_code'] }}" alt="QR Code" style="width: 100px;">
        <p><b>Saleperson:<br /></b>{{ $invoiceData['sale_person'] }}<br /></p>
        <p>Sub Total {{ $invoiceData['total'] }}<br /></p>
        <p>Tax {{ $invoiceData['tax'] }}%<br /></p>
        <p>Total {{ $invoiceData['total'] }}<br /></p>
        <p><b>Note:<br /></b>{{ $invoiceData['note'] }}</p>
    </div>
</div>
</body>

</html>
