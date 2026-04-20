<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<style>
@page {
    margin: 20px;
}

body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 9.5px;
    color: #000;
    margin: 0;
}

.head-grid {
    width: 100%;
    border-collapse: collapse;
}

.head-grid td {
    vertical-align: top;
}

.head-right {
    text-align: right;
    font-size: 11px;
    font-weight: bold;
    line-height: 1.15;
}

.logo {
    max-height: 70px;
}

.client {
    margin-top: 10px;
    font-size: 10px;
    font-weight: bold;
    line-height: 1.3;
}

.meta-wrap {
    width: 100%;
    margin-top: 8px;
    border-collapse: collapse;
}

.meta-wrap > tbody > tr > td {
    width: 50%;
    vertical-align: top;
    padding: 0;
}

.meta-inner {
    width: 100%;
    border-collapse: collapse;
}

.meta-inner th,
.meta-inner td {
    border-top: 1px solid #000;
    border-bottom: 1px solid #000;
    text-align: center;
    padding: 2px 3px;
}

.meta-inner-right {
    margin-left: auto;
}

.title-bar {
    margin-top: 8px;
    border: 1px solid #999;
    background: #e5e5e5;
    text-align: center;
    font-size: 9px;
    font-weight: bold;
    padding: 2px 0;
    line-height: 1.2;
}

.table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 4px;
}

.table thead {
    display: table-header-group;
}

.table tbody {
    display: table-row-group;
}

.table tbody tr {
    page-break-inside: avoid;
}

.table th {
    border-bottom: 1px solid #000;
    text-align: left;
    padding: 3px 4px;
    font-size: 8.5px;
    font-weight: bold;
}

.table td {
    border-bottom: 1px solid #000;
    padding: 3px 4px;
    font-size: 8.5px;
}

.right {
    text-align: right;
}

.desc {
    margin-top: 6px;
    font-size: 8.5px;
    line-height: 1.25;
}

.two-cols {
    width: 100%;
    margin-top: 10px;
    border-collapse: collapse;
}

.two-cols td {
    width: 50%;
    vertical-align: top;
    padding: 0 6px 0 0;
}

.two-cols td:last-child {
    padding: 0 0 0 6px;
}

.totals-block {
    page-break-inside: avoid;
}

.section-title {
    font-size: 12px;
    font-weight: bold;
    border-bottom: 2px solid #000;
    margin-bottom: 4px;
    padding-bottom: 2px;
}

.terms {
    font-size: 9px;
    line-height: 1.32;
}

.terms-sep {
    border-bottom: 1px solid #000;
    margin-top: 6px;
    height: 1px;
}

.totals {
    width: 100%;
    border-collapse: collapse;
}

.totals td {
    border-bottom: 1px solid #000;
    padding: 2px 4px;
    font-size: 9.5px;
}

.totals .label {
    width: 55%;
}

.totals .percent {
    width: 15%;
    text-align: center;
}

.totals .value {
    width: 30%;
    text-align: right;
}

.totals .total-final td {
    font-weight: bold;
    font-size: 10px;
    border-top: 1px solid #000;
}

.totals .total-final .label,
.totals .total-final .percent {
    background-color: #e4e4e4;
}

.totals .total-final .value {
    background-color: #d8d8d8;
}

.bold {
    font-weight: bold;
}

.doc-note {
    margin-top: 6px;
    margin-bottom: 4px;
    text-align: center;
    font-size: 9px;
    font-weight: bold;
}

.mb-sep {
    border-top: 1px solid #000;
    margin: 8px 0 6px;
}

.mb-box {
    width: 100%;
    border-collapse: collapse;
    page-break-inside: avoid;
}

.mb-box td {
    vertical-align: top;
    padding: 0 0 0 0;
}

.mb-logo {
    width: 55px;
    height: auto;
    display: block;
}

.mb-data {
    font-size: 9.5px;
    line-height: 1.35;
    padding-left: 6px;
}

.mb-fallback {
    font-size: 8px;
    font-weight: bold;
    line-height: 1.15;
}

.footer {
    position: fixed;
    bottom: 22px;
    left: 22px;
    right: 110px;
    font-size: 9px;
    line-height: 1.35;
}

.footer-title {
    font-weight: bold;
    margin-bottom: 2px;
}
</style>
</head>

<body>

@php
    $logoPath = null;
    if (!empty($company?->logo_path)) {
        $resolvedPath = public_path('storage/' . ltrim((string) $company->logo_path, '/'));
        if (file_exists($resolvedPath)) {
            $logoPath = $resolvedPath;
        }
    }

    $baseTotal = (float) ($proposal->total_amount ?? 0);
    $vatValue = 0.0;
    $ratesSeen = [];

    foreach ($proposal->items as $pdfItem) {
        $rate = (float) ($pdfItem->article?->vat?->rate ?? 23);
        $ratesSeen[(string) $rate] = $rate;
        $lineEx = (float) ($pdfItem->total ?? 0);
        $vatValue += $lineEx * ($rate / 100);
    }

    $ratesList = array_values($ratesSeen);
    sort($ratesList);
    $grandTotal = $baseTotal + $vatValue;

    if (count($ratesList) === 1) {
        $vatRatePercent = number_format($ratesList[0], 2, ',', '.').' %';
    } elseif (count($ratesList) === 0) {
        $vatRatePercent = number_format(23.0, 2, ',', '.').' %';
    } else {
        $vatRatePercent = '—';
    }

    $proposalDateFormatted = \Carbon\Carbon::parse($proposal->proposal_date)->format('d/m/Y');
    $validUntilFormatted = $proposal->valid_until
        ? \Carbon\Carbon::parse($proposal->valid_until)->format('d/m/Y')
        : '';

    $multibancoValor = $grandTotal > 0 ? $grandTotal / 2 : 0;
    $mbImagePath = public_path('images/mb.png');
    $mbImageExists = file_exists($mbImagePath);

    $companyAddressLine = trim((string) ($company->address ?? ''));
    $companyPostalCity = trim(
        trim((string) ($company->postal_code ?? '')).' '.trim((string) ($company->city ?? ''))
    );
@endphp

<table class="head-grid">
    <tr>
        <td style="width:60%;">
            @if($logoPath && file_exists($logoPath))
                <img src="{{ $logoPath }}" class="logo" alt="">
            @endif
        </td>
        <td class="head-right" style="width:40%;">
            ORÇAMENTO<br>
            {{ $proposal->number }}
        </td>
    </tr>
</table>

<div class="client">
    {{ strtoupper((string) ($proposal->client->name ?? '')) }}<br>
    {{ $proposal->client->address ?? '' }}<br>
    {{ strtoupper(trim((string) (($proposal->client->postal_code ?? '').' '.($proposal->client->city ?? '')))) }}
</div>

<table class="meta-wrap">
    <tr>
        <td>
            <table class="meta-inner">
                <tr>
                    <th>Cliente Nº</th>
                    <th>Contribuinte</th>
                </tr>
                <tr>
                    <td>{{ $proposal->client->number }}</td>
                    <td>{{ $proposal->client->vat ?? $proposal->client->tax_number ?? '' }}</td>
                </tr>
            </table>
        </td>
        <td style="text-align:right;">
            <table class="meta-inner meta-inner-right">
                <tr>
                    <th>Edição</th>
                    <th>de</th>
                </tr>
                <tr>
                    <td>1</td>
                    <td>{{ $proposalDateFormatted }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<div class="title-bar">PEDIDO #</div>

<table class="table">
    <thead>
        <tr>
            <th style="width:42%;">Descrição</th>
            <th class="right" style="width:14%;">Quantidade</th>
            <th class="right" style="width:14%;">Desconto</th>
            <th class="right" style="width:15%;">Preço</th>
            <th class="right" style="width:15%;">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($proposal->items as $item)
        @php
            $ref = trim((string) ($item->article->reference ?? ''));
            $lineLabel = $ref !== '' ? $ref.' | '.$item->article->name : $item->article->name;
        @endphp
        <tr>
            <td>{{ $lineLabel }}</td>
            <td class="right">{{ number_format((float) $item->quantity, 2, ',', '.') }} Un</td>
            <td class="right">{{ number_format(0, 2, ',', '.') }} %</td>
            <td class="right">{{ number_format((float) $item->cost_price, 2, ',', '.') }} €</td>
            <td class="right">{{ number_format((float) $item->total, 2, ',', '.') }} €</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="desc">
    O serviço consiste na preparação e execução conforme descrito acima.<br>
    <strong>Deslocação não incluída:</strong> O valor do serviço não abrange deslocação, salvo acordo expresso em proposta.
</div>

<table class="two-cols">
    <tr>
        <td>
            <div class="section-title">Termos e Condições</div>
            <div class="terms">
                <strong>Prazo Entrega</strong> 30 DIAS<br><br>

                <strong>Condições de Pagamento:</strong><br>
                - Adjudicação 50,00 %<br>
                - Conclusão 50,00 %<br><br>

                <strong>Válido até:</strong> {{ $validUntilFormatted }}<br><br>

                <strong>* Valor sem IVA incluído</strong><br><br>

                IBAN PT50 0000 0000 0000 0000 0000 0 (Millennium BCP)<br>
                IBAN PT50 0000 0000 0000 0000 0000 0 (Bankinter)

                <div class="terms-sep"></div>
            </div>
        </td>
        <td>
            <div class="totals-block">
                <table class="totals">
                    <tr>
                        <td class="label">Subtotal</td>
                        <td class="percent"></td>
                        <td class="value">{{ number_format($baseTotal, 2, ',', '.') }} €</td>
                    </tr>
                    <tr>
                        <td class="label">Desconto Linha</td>
                        <td class="percent"></td>
                        <td class="value">{{ number_format(0, 2, ',', '.') }} €</td>
                    </tr>
                    <tr>
                        <td class="label">Desconto Geral</td>
                        <td class="percent">{{ number_format(0, 2, ',', '.') }} %</td>
                        <td class="value">{{ number_format(0, 2, ',', '.') }} €</td>
                    </tr>
                    <tr>
                        <td class="label bold">Total sem IVA</td>
                        <td class="percent"></td>
                        <td class="value bold">{{ number_format($baseTotal, 2, ',', '.') }} €</td>
                    </tr>
                    <tr>
                        <td class="label">IVA</td>
                        <td class="percent">{{ $vatRatePercent }}</td>
                        <td class="value">{{ number_format($vatValue, 2, ',', '.') }} €</td>
                    </tr>
                    <tr class="total-final">
                        <td class="label">Total com IVA</td>
                        <td class="percent"></td>
                        <td class="value">{{ number_format($grandTotal, 2, ',', '.') }} €</td>
                    </tr>
                </table>

                <div class="doc-note">Este documento não serve de fatura</div>

                <div class="mb-sep"></div>

                <table class="mb-box">
                    <tr>
                        <td style="width:60px;">
                            @if($mbImageExists)
                                <img src="{{ $mbImagePath }}" class="mb-logo" alt="Multibanco">
                            @else
                                <span class="mb-fallback">MB<br>MULTIBANCO</span>
                            @endif
                        </td>
                        <td class="mb-data">
                            Entidade&nbsp;&nbsp;&nbsp;&nbsp;11 473<br>
                            Referência&nbsp;&nbsp;969 006 950<br>
                            Valor&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;50,00 % {{ number_format($multibancoValor, 2, ',', '.') }} €
                        </td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
</table>

<div class="footer">
    <div class="footer-title">Contactos</div>
    {{ $companyAddressLine }}<br>
    @if($companyPostalCity !== '')
        {{ $companyPostalCity }}<br>
    @endif
    {{ $company->email ?? '' }}<br>
    {{ $company->phone ?? '' }}
</div>

</body>
</html>
