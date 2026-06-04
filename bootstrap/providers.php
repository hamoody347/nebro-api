<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\TenancyServiceProvider::class,
    // TelescopeServiceProvider is registered conditionally in AppServiceProvider::register() (local env only)
];
