<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Comprovativo de Pagamento</title>
</head>
<body>
@php
    $company = \App\Models\Settings\CompanyModel::query()->first();
    $logoUrl = null;

    if ($company && !empty($company->logo_path)) {
        $logoUrl = url(\Illuminate\Support\Facades\Storage::url((string) $company->logo_path));
    }
@endphp
<p>Estimado(a) Fornecedor,</p>

<p>
    Enviamos em anexo o comprovativo de pagamento da fatura "{{ $invoice->number }}".
</p>

<p>
    Qualquer questão, entre em contacto connosco.
</p>

<p>
    Cumprimentos,
    <br>
    @if($logoUrl)
        <img src="{{ $logoUrl }}" alt="Logo" style="max-height: 48px; width: auto; display: block; margin-top: 8px;">
    @else
        {{ $company->name ?? config('app.name') }}
    @endif
</p>
</body>
</html>
