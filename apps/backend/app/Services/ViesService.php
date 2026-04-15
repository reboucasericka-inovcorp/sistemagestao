<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class ViesService
{
    /**
     * @return array{name: string|null, address: string|null, valid: bool}
     */
    public function validateNif(string $nif): array
    {
        try {
            [$countryCode, $vatNumber] = $this->normalizeVatInput($nif);
            if ($vatNumber === '') {
                return $this->invalidResult();
            }

            $response = Http::timeout(8)
                ->retry(2, 150)
                ->accept('text/xml')
                ->withHeaders([
                    'Content-Type' => 'text/xml; charset=utf-8',
                    'SOAPAction' => '""',
                ])
                ->withBody($this->buildSoapEnvelope($countryCode, $vatNumber), 'text/xml')
                ->post('https://ec.europa.eu/taxation_customs/vies/services/checkVatService');

            if (! $response->successful()) {
                return $this->invalidResult();
            }

            return $this->parseSoapResponse($response->body());
        } catch (Throwable) {
            return $this->invalidResult();
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function normalizeVatInput(string $nif): array
    {
        $sanitized = strtoupper(preg_replace('/[^A-Z0-9]/', '', $nif) ?? '');

        if ($sanitized === '') {
            return ['PT', ''];
        }

        if (preg_match('/^[A-Z]{2}/', $sanitized) === 1) {
            return [substr($sanitized, 0, 2), substr($sanitized, 2)];
        }

        return ['PT', $sanitized];
    }

    private function buildSoapEnvelope(string $countryCode, string $vatNumber): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <checkVat xmlns="urn:ec.europa.eu:taxud:vies:services:checkVat:types">
      <countryCode>{$countryCode}</countryCode>
      <vatNumber>{$vatNumber}</vatNumber>
    </checkVat>
  </soap:Body>
</soap:Envelope>
XML;
    }

    /**
     * @return array{name: string|null, address: string|null, valid: bool}
     */
    private function parseSoapResponse(string $soapBody): array
    {
        $xml = @simplexml_load_string($soapBody);
        if ($xml === false) {
            return $this->invalidResult();
        }

        $xml->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $xml->registerXPathNamespace('vies', 'urn:ec.europa.eu:taxud:vies:services:checkVat:types');

        $validNode = $xml->xpath('//vies:checkVatResponse/vies:valid');
        $nameNode = $xml->xpath('//vies:checkVatResponse/vies:name');
        $addressNode = $xml->xpath('//vies:checkVatResponse/vies:address');

        $valid = isset($validNode[0]) && strtolower(trim((string) $validNode[0])) === 'true';
        $name = isset($nameNode[0]) ? $this->normalizeField((string) $nameNode[0]) : null;
        $address = isset($addressNode[0]) ? $this->normalizeField((string) $addressNode[0]) : null;

        return [
            'name' => $name,
            'address' => $address,
            'valid' => $valid,
        ];
    }

    /**
     * @return array{name: string|null, address: string|null, valid: bool}
     */
    private function invalidResult(): array
    {
        return [
            'name' => null,
            'address' => null,
            'valid' => false,
        ];
    }

    private function normalizeField(string $value): ?string
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $value) ?? '');
        if ($normalized === '' || $normalized === '---') {
            return null;
        }

        return $normalized;
    }
}
