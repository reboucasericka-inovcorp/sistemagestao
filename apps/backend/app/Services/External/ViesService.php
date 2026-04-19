<?php

namespace App\Services\External;

use SoapClient;
use Throwable;

class ViesService
{
    private const WSDL_URL = 'http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl';

    /**
     * @return array{valid: bool, name: string|null, address: string|null}
     */
    public function validate(string $countryCode, string $vatNumber): array
    {
        $countryCode = strtoupper(trim($countryCode));
        $vatNumber = preg_replace('/\D+/', '', $vatNumber) ?? '';

        if ($countryCode === '' || $vatNumber === '') {
            return $this->invalidResult();
        }

        try {
            /** @var SoapClient $client */
            $client = new SoapClient(self::WSDL_URL, [
                'connection_timeout' => 10,
                'exceptions' => true,
                'trace' => false,
                'cache_wsdl' => WSDL_CACHE_BOTH,
            ]);

            /** @var object{valid?: bool, name?: string, address?: string} $response */
            $response = $client->checkVat([
                'countryCode' => $countryCode,
                'vatNumber' => $vatNumber,
            ]);

            return [
                'valid' => (bool) ($response->valid ?? false),
                'name' => $this->normalizeField($response->name ?? null),
                'address' => $this->normalizeField($response->address ?? null),
            ];
        } catch (Throwable) {
            return $this->invalidResult();
        }
    }

    /**
     * @return array{valid: bool, name: string|null, address: string|null}
     */
    private function invalidResult(): array
    {
        return [
            'valid' => false,
            'name' => null,
            'address' => null,
        ];
    }

    private function normalizeField(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim(preg_replace('/\s+/', ' ', $value) ?? '');

        if ($normalized === '' || $normalized === '---') {
            return null;
        }

        return $normalized;
    }
}

