<?php

namespace App\Services\Contacts;

use App\Models\ContactModel;
use Illuminate\Support\Facades\DB;

class ContactService
{
    public function create(array $data): ContactModel
    {
        $payload = $this->normalize($data);

        return DB::transaction(static fn (): ContactModel => ContactModel::create($payload));
    }

    public function update(ContactModel $contact, array $data): ContactModel
    {
        $payload = $this->normalize($data);

        return DB::transaction(function () use ($contact, $payload): ContactModel {
            $contact->update($payload);

            return $contact->refresh();
        });
    }

    private function normalize(array $data): array
    {
        $payload = $data;

        if (array_key_exists('email', $payload)) {
            $payload['email'] = $payload['email']
                ? strtolower((string) $payload['email'])
                : null;
        }

        return $payload;
    }
}
