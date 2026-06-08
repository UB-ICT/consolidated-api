<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    App\Providers\FirestoreServiceProvider::class,
    LdapRecord\Laravel\LdapServiceProvider::class,
    Modules\Auth\Providers\AuthServiceProvider::class,
    Spatie\Permission\PermissionServiceProvider::class,
];
