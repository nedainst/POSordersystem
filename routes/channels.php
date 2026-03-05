<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Admin kitchen/cashier channel — requires auth
Broadcast::channel('admin.orders', function ($user) {
    return $user !== null;
});

// Customer order tracking — public channel (no auth needed)
// Uses public channel: order.{orderId}
