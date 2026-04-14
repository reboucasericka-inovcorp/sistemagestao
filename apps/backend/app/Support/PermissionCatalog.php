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
        ];
    }

    /**
     * @return list<string>
     */
    public static function actions(): array
    {
        return ['view', 'create', 'read', 'update', 'delete'];
    }

}
