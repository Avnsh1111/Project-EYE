<?php

return [
    App\Providers\AppServiceProvider::class,
    ...class_exists(\Laravel\Telescope\TelescopeServiceProvider::class) 
        ? [App\Providers\TelescopeServiceProvider::class] 
        : [],
];
