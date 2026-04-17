<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111;
            margin: 22px 28px;
        }
        .top-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .top-grid td {
            vertical-align: top;
        }
        .logo {
            max-width: 155px;
            max-height: 90px;
        }
        .doc-head {
            text-align: right;
            font-size: 14px;
            font-weight: bold;
            line-height: 1.3;
        }
        .client-block {
            margin-top: 16px;
            line-height: 1.45;
            font-size: 12px;
            font-weight: 700;
        }
        .meta-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
            font-size: 10px;
        }
        .meta-grid td {
            width: 50%;
            vertical-align: top;
        }
        .meta-table {
            width: 56%;
            border-collapse: collapse;
        }
        .meta-table th,
        .meta-table td {
            border-top: 1px solid #1c1c1c;
            border-bottom: 1px solid #1c1c1c;
            border-left: 0;
            border-right: 0;
            padding: 2px 4px;
            text-align: center;
        }
        .meta-table th {
            font-weight: 700;
        }
        .title-bar {
            margin-top: 10px;
            border: 1px solid #8c8c8c;
            background: #dedede;
            text-align: center;
            font-size: 20px;
            font-weight: 700;
            line-height: 28px;
            height: 28px;
        }
        .service-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            font-size: 10px;
        }
        .service-table td {
            border: 0;
            border-bottom: 1px solid #1f1f1f;
            padding: 3px 4px;
        }
        .service-line-title {
            font-weight: 700;
            font-size: 14px;
        }
        .service-right {
            text-align: right;
            white-space: nowrap;
        }
        .desc {
            margin-top: 12px;
            margin-left: 72px;
            font-size: 10px;
            line-height: 1.35;
            width: 78%;
        }
        .two-cols {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }
        .two-cols td {
            vertical-align: top;
            width: 50%;
            padding-right: 12px;
        }
        .section-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            border-bottom: 1px solid #1c1c1c;
            line-height: 1.15;
            padding-bottom: 2px;
        }
        .terms {
            font-size: 10px;
            line-height: 1.45;
        }
        .terms strong {
            font-weight: 700;
        }
        .totals {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        .totals td {
            border-bottom: 1px solid #1c1c1c;
            padding: 3px 4px;
        }
        .totals .label {
            width: 62%;
        }
        .totals .value {
            text-align: right;
            width: 38%;
            white-space: nowrap;
        }
        .totals .strong {
            font-weight: 700;
        }
        .doc-note {
            margin-top: 7px;
            text-align: center;
            font-size: 12px;
            font-weight: 700;
        }
        .footer {
            position: fixed;
            left: 28px;
            right: 28px;
            bottom: 16px;
            font-size: 10px;
            line-height: 1.35;
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
    $proposalNumber = (string) ($proposal->number ?? '-');
    $proposalDate = !empty($proposal->proposal_date) ? \Carbon\Carbon::parse($proposal->proposal_date)->format('d M Y') : '-';
    $validUntil = !empty($proposal->valid_until) ? \Carbon\Carbon::parse($proposal->valid_until)->format('d/m/Y') : '-';
    $clientId = $proposal->client->number ?? $proposal->client->id ?? '-';
    $clientVat = $proposal->client->vat ?? $proposal->client->tax_number ?? '-';
    $baseTotal = (float) ($proposal->total_amount ?? 0);
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
            {{ $proposalNumber }}
        </td>
    </tr>
</table>

<div class="client-block">
    {{ strtoupper((string) ($proposal->client->name ?? 'CLIENTE')) }}<br>
    {{ $proposal->client->address ?? '' }}<br>
    {{ strtoupper(trim(($proposal->client->city ?? '') . ' ' . ($proposal->client->postal_code ?? ''))) }}
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
                <tr><td>1</td><td>{{ $proposalDate }}</td></tr>
            </table>
        </td>
    </tr>
</table>

<div class="title-bar">PEDIDO #</div>

<table class="service-table">
    <tr>
        <td class="service-line-title">1. SERVIÇO</td>
        <td class="service-right strong">{{ number_format($baseTotal, 2, ',', '.') }} €</td>
    </tr>
    @foreach($proposal->items as $item)
        <tr>
            <td>
                1.{{ $loop->iteration }}
                {{ $item->article->name ?? 'Serviço' }}
            </td>
            <td class="service-right">
                {{ number_format((float) ($item->quantity ?? 0), 2, ',', '.') }} Un &nbsp;&nbsp;
                {{ number_format((float) ($item->cost_price ?? 0), 2, ',', '.') }} € &nbsp;&nbsp;
                {{ number_format((float) ($item->total ?? 0), 2, ',', '.') }} €
            </td>
        </tr>
    @endforeach
</table>

<div class="desc">
    O serviço consiste na preparação e execução dos trabalhos descritos neste documento, garantindo
    uma intervenção conforme o acordado com o cliente.<br><br>
    <strong>Deslocação não incluída:</strong> O valor do serviço não abrange deslocação.
</div>

<table class="two-cols">
    <tr>
        <td>
            <div class="section-title">Termos e Condições</div>
            <div class="terms">
                <strong>Prazo Entrega</strong>&nbsp;&nbsp;30 DIAS<br>
                <strong>Condições de Pagamento:</strong><br>
                &nbsp;&nbsp;- Adjudicação&nbsp;&nbsp;50,00 %<br>
                &nbsp;&nbsp;- Conclusão&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;50,00 %<br>
                <strong>Válido até</strong>&nbsp;&nbsp;{{ $validUntil }}<br>
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
