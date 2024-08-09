<?php

use App\Models\DeliveryMan;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// في ملف `routes/channels.php`
Broadcast::channel('my-event', function () {
    return true;
});


Broadcast::channel('my-event-customer', function () {
    return true;
});











