<?php

use App\Support\Facades\Trigger;

Trigger::on('asteriskcdrdb.cel', 'write,update,delete', function ($event) {
    ray($event)->showApp();
});
