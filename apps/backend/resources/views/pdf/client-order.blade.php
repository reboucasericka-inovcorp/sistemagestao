<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9.5px;
            color: #111;
            margin: 16px 22px;
        }
        .top-grid { width: 100%; border-collapse: collapse; }
        .top-grid td { vertical-align: top; }
        .logo { max-width: 145px; max-height: 70px; }
        .doc-head { text-align: right; font-size: 13px; font-weight: bold; line-height: 1.15; }
        .client-block { margin-top: 10px; line-height: 1.3; font-size: 10px; font-weight: 700; }
        .meta-grid { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 9px; }
        .meta-grid td { width: 50%; vertical-align: top; }
        .meta-table { width: 56%; border-collapse: collapse; }
        .meta-table th, .meta-table td {
            border-top: 1px solid #1c1c1c;
            border-bottom: 1px solid #1c1c1c;
            border-left: 0;
            border-right: 0;
            padding: 2px 3px;
            text-align: center;
        }
        .title-bar {
            margin-top: 8px;
            border: 1px solid #8c8c8c;
            background: #dedede;
            text-align: center;
            font-size: 10px;
            font-weight: 700;
            line-height: 1.2;
            padding: 2px 0;
        }
        .service-table { width: 100%; border-collapse: collapse; margin-top: 4px; font-size: 9px; }
        .service-table th {
            border-bottom: 1px solid #1f1f1f;
            text-align: left;
            padding: 3px 4px;
            font-weight: 700;
        }
        .service-table td { border: 0; border-bottom: 1px solid #1f1f1f; padding: 3px 4px; }
        .service-right { text-align: right; white-space: nowrap; }
        .two-cols { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .two-cols td { vertical-align: top; width: 50%; padding-right: 12px; }
        .section-title {
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 3px;
            border-bottom: 1px solid #1c1c1c;
            line-height: 1.2;
            padding-bottom: 1px;
        }
        .terms { font-size: 9px; line-height: 1.25; }
        .terms strong { font-weight: 700; }
        .totals { width: 100%; border-collapse: collapse; font-size: 9px; }
        .totals td { border-bottom: 1px solid #1c1c1c; padding: 2px 3px; }
        .totals .label { width: 62%; }
        .totals .value { text-align: right; width: 38%; white-space: nowrap; }
        .totals .strong { font-weight: 700; }
        .doc-note { margin-top: 4px; text-align: center; font-size: 10px; font-weight: 700; }
        .footer {
            position: fixed;
            left: 22px;
            right: 22px;
            bottom: 12px;
            font-size: 9px;
            line-height: 1.25;
        }
    </style>
</head>
<body>
@php
    $logoPath = null;
    if (!empty($company->logo_path)) {
        $resolvedPath = public_path('storage/' . ltrim($company->logo_path, '/'));
        if (file_exists($resolvedPath)) {
            $logoPath = $resolvedPath;
        }
    }
    $orderNumber = (string) ($order->number ?? '-');
    $orderDate = !empty($order->order_date) ? \Carbon\Carbon::parse($order->order_date)->format('d M Y') : '-';
    $clientId = $order->client->number ?? $order->client->id ?? '-';
    $clientVat = $order->client->vat ?? $order->client->tax_number ?? '-';
    $baseTotal = (float) ($order->total_amount ?? 0);
    $vatRate = 23.00;
    $vatValue = $baseTotal * ($vatRate / 100);
    $grandTotal = $baseTotal + $vatValue;
@endphp

<table class="top-grid">
    <tr>
        <td>
            @if($logoPath)
                <img src="{{ $logoPath }}" class="logo" alt="Logo">
            @else
                <div style="font-weight:700;font-size:20px;">{{ $company->name ?? 'EMPRESA' }}</div>
            @endif
        </td>
        <td class="doc-head">
            ORÇAMENTO<br>
            {{ $orderNumber }}
        </td>
    </tr>
</table>

<div class="client-block">
    {{ strtoupper((string) ($order->client->name ?? 'CLIENTE')) }}<br>
    {{ $order->client->address ?? '' }}<br>
    {{ strtoupper(trim(($order->client->city ?? '') . ' ' . ($order->client->postal_code ?? ''))) }}
</div>

<table class="meta-grid">
    <tr>
        <td>
            <table class="meta-table">
                <tr><th>Cliente N.º</th><th>Contribuinte</th></tr>
                <tr><td>{{ $clientId }}</td><td>{{ $clientVat }}</td></tr>
            </table>
        </td>
        <td style="text-align:right;">
            <table class="meta-table" style="margin-left:auto;">
                <tr><th>Edição</th><th>de</th></tr>
                <tr><td>1</td><td>{{ $orderDate }}</td></tr>
            </table>
        </td>
    </tr>
</table>

<div class="title-bar">PEDIDO #</div>

<table class="service-table">
    <thead>
    <tr>
        <th style="width:45%;">Descrição</th>
        <th class="service-right" style="width:14%;">Quantidade</th>
        <th class="service-right" style="width:14%;">Desconto</th>
        <th class="service-right" style="width:13%;">Preço</th>
        <th class="service-right" style="width:14%;">Total</th>
    </tr>
    </thead>
    <tbody>
    @foreach($order->items as $item)
        <tr>
            <td>
                1.{{ $loop->iteration }}
                {{ $item->article->name ?? 'Serviço' }}
                @if(!empty($item->supplier?->name))
                    - {{ $item->supplier->name }}
                @endif
            </td>
            <td class="service-right">{{ number_format((float) ($item->quantity ?? 0), 2, ',', '.') }}</td>
            <td class="service-right">0,00 €</td>
            <td class="service-right">{{ number_format((float) ($item->cost_price ?? 0), 2, ',', '.') }} €</td>
            <td class="service-right">{{ number_format((float) ($item->total ?? 0), 2, ',', '.') }} €</td>
        </tr>
    @endforeach
    </tbody>
</table>

<table class="two-cols">
    <tr>
        <td>
            <div class="section-title">Termos e Condições</div>
            <div class="terms">
                <strong>Prazo Entrega</strong>&nbsp;&nbsp;30 DIAS<br>
                <strong>Condições de Pagamento:</strong><br>
                &nbsp;&nbsp;- Adjudicação&nbsp;&nbsp;50,00 %<br>
                &nbsp;&nbsp;- Conclusão&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;50,00 %<br>
                <strong>Válido até</strong>&nbsp;&nbsp;{{ !empty($order->order_date) ? \Carbon\Carbon::parse($order->order_date)->addDays(30)->format('d/m/Y') : '-' }}<br>
                <strong>* Valor sem IVA incluído</strong>
            </div>
        </td>
        <td>
            <table class="totals">
                <tr><td class="label">Subtotal</td><td class="value">{{ number_format($baseTotal, 2, ',', '.') }} €</td></tr>
                <tr><td class="label">Desconto Linha</td><td class="value">0,00 €</td></tr>
                <tr><td class="label">Desconto Geral</td><td class="value">0,00 €</td></tr>
                <tr><td class="label">Total sem IVA</td><td class="value">{{ number_format($baseTotal, 2, ',', '.') }} €</td></tr>
                <tr><td class="label">IVA {{ number_format($vatRate, 2, ',', '.') }} %</td><td class="value">{{ number_format($vatValue, 2, ',', '.') }} €</td></tr>
                <tr><td class="label strong">Total com IVA</td><td class="value strong">{{ number_format($grandTotal, 2, ',', '.') }} €</td></tr>
            </table>
            <div class="doc-note">Este documento não serve de fatura</div>
        </td>
    </tr>
</table>

<div class="footer">
    Contactos<br>
    {{ $company->address ?? '-' }}<br>
    @if(!empty($company->phone))T {{ $company->phone }} @endif
    @if(!empty($company->mobile))&nbsp;&nbsp;M {{ $company->mobile }}@endif<br>
    @if(!empty($company->email)){{ $company->email }}<br>@endif
    @if(!empty($company->website)){{ $company->website }}@endif
</div>
</body>
</html>
