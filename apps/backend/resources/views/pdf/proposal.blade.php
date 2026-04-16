<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            margin: 40px;
            color: #333;
        }

        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            border-bottom: 2px solid #eee;
            padding-bottom: 15px;
        }

        .company-info {
            text-align: right;
        }

        .company-name {
            font-size: 20px;
            font-weight: bold;
        }

        .logo {
            max-height: 60px;
        }

        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin-top: 25px;
            margin-bottom: 10px;
            color: #444;
        }

        .client-box {
            border: 1px solid #ddd;
            padding: 12px;
            margin-bottom: 20px;
            background: #fafafa;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 8px;
        }

        th {
            background: #f2f2f2;
            font-weight: bold;
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

        .footer {
            margin-top: 40px;
            font-size: 11px;
            color: #666;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }

        .signature {
            margin-top: 40px;
        }
    </style>
</head>
<body>

@php
    $companyLogoPath = null;
    if (!empty($company->logo_path)) {
        $resolvedPath = public_path('storage/' . ltrim($company->logo_path, '/'));
        if (file_exists($resolvedPath)) {
            $companyLogoPath = $resolvedPath;
        }
    }
@endphp

<div class="header">
    <div>
        @if($companyLogoPath)
            <img src="{{ $companyLogoPath }}" class="logo" alt="Logo">
        @endif
    </div>

    <div class="company-info">
        <div class="company-name">{{ $company->name }}</div>
        <div>{{ $company->address }}</div>
        <div>{{ $company->postal_code }} {{ $company->city }}</div>
        <div>{{ $company->phone ?? '' }}</div>
        <div>{{ $company->email ?? '' }}</div>
    </div>
</div>

<div class="section-title">PROPOSTA COMERCIAL</div>

<div class="client-box">
    <strong>{{ $proposal->client->name ?? '' }}</strong><br>
    {{ $proposal->client->address ?? '' }}<br>
    {{ $proposal->client->postal_code ?? '' }} {{ $proposal->client->city ?? '' }}<br>
    {{ $proposal->client->phone ?? '' }}
</div>

<p>
<strong>Data:</strong> {{ $proposal->proposal_date }}<br>
<strong>Validade até:</strong> {{ $proposal->valid_until }}<br>
<strong>Referência:</strong> {{ $proposal->number }}
</p>

<p>
Prezado(a) {{ $proposal->client->contact_name ?? 'Cliente' }},<br><br>
Segue a nossa proposta comercial conforme solicitado.
Estamos à disposição para qualquer esclarecimento adicional.
</p>

<table>
    <thead>
        <tr>
            <th style="width: 40%;">Produto</th>
            <th style="width: 10%;">Qtd</th>
            <th style="width: 15%;">Preço UN</th>
            <th style="width: 15%;">Desconto</th>
            <th style="width: 10%;">IVA</th>
            <th style="width: 20%;">Montante</th>
        </tr>
    </thead>
    <tbody>
        @foreach($proposal->items as $item)
        <tr>
            <td>{{ $item->article->name ?? '' }}</td>
            <td class="right">{{ $item->quantity }}</td>
            <td class="right">{{ number_format($item->cost_price, 2, ',', '.') }} €</td>
            <td class="right">{{ $item->discount ?? 0 }}%</td>
            <td class="right">{{ $item->vat ?? 0 }}%</td>
            <td class="right">{{ number_format($item->total, 2, ',', '.') }} €</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="total-box">
    Total Geral: {{ number_format($proposal->total_amount, 2, ',', '.') }} €
</div>

<div class="signature">
    <p>Atenciosamente,</p>
    <strong>{{ $company->contact_person ?? 'Responsável Comercial' }}</strong><br>
    {{ $company->email ?? '' }}
</div>

<div class="footer">
    * Impostos incluídos no preço final.<br>
    * Condições de pagamento: 50% no aceite e 50% após entrega.<br>
    * Prazo de entrega: 15 dias úteis após confirmação.<br>
</div>

</body>
</html>
