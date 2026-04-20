<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('image-processing.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
