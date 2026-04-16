<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            margin: 30px;
        }

        .header {
            margin-bottom: 25px;
        }

        .company-name {
            font-size: 22px;
            font-weight: bold;
        }
        .company-logo {
            max-height: 56px;
            margin-bottom: 10px;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin-top: 25px;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 6px;
        }

        th {
            background: #f5f5f5;
        }

        .right {
            text-align: right;
        }

        .total-box {
            margin-top: 20px;
            text-align: right;
            font-size: 16px;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="header">
    @if(!empty($company->logo))
        <div>
            <img src="{{ $company->logo }}" alt="Logo" class="company-logo">
        </div>
    @endif
    <div class="company-name">{{ $company->name }}</div>
    <div>{{ $company->address }}</div>
    <div>{{ $company->postal_code }} {{ $company->city }}</div>
</div>

<div class="section-title">Encomenda {{ $order->number }}</div>

<p>
<strong>Cliente:</strong> {{ $order->client->name ?? '' }}<br>
<strong>Data:</strong> {{ $order->order_date }}<br>
<strong>Estado:</strong> {{ $order->status }}
</p>

<table>
    <thead>
        <tr>
            <th style="width: 35%;">Artigo</th>
            <th style="width: 20%;">Fornecedor</th>
            <th style="width: 10%;">Qtd</th>
            <th style="width: 15%;">Preço</th>
            <th style="width: 20%;">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($order->items as $item)
        <tr>
            <td>{{ $item->article->name ?? '' }}</td>
            <td>{{ $item->supplier->name ?? '' }}</td>
            <td class="right">{{ $item->quantity }}</td>
            <td class="right">{{ number_format((float) $item->cost_price, 2, ',', '.') }}</td>
            <td class="right">{{ number_format((float) $item->total, 2, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="total-box">
    Total: {{ number_format((float) $order->total_amount, 2, ',', '.') }} €
</div>

</body>
</html>
