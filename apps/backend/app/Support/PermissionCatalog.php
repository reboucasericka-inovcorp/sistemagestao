<?php

namespace App\Support;

class PermissionCatalog
{
    /**
     * @return list<string>
     */
    public static function modules(): array
    {
        return [
            'entities',
            'contacts',
            'countries',
            'vat',
            'contact-functions',
            'calendar-types',
            'calendar-actions',
            'calendar-events',
            'articles',
            'company',
            'logs',
            'users',
            'roles',
            'digital-files',
            'bank-accounts',
            'customer-accounts',
            'supplier-invoices',
            'proposals',
            'client-orders',
            'supplier-orders',
            'work-orders',
        ];
    }

    /**
     * @return list<string>
     */
    public static function actions(): array
    {
        return ['read', 'create', 'update', 'delete'];
    }

}
