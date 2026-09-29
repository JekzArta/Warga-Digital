<?php

use App\Models\User;
use App\Services\ScopeAuthorizer;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private Channel Chat Bebas RT: Hanya warga & pengurus RT terkait yang boleh subscribe
Broadcast::channel('chat.rt.{rtId}', function (User $user, int $rtId) {
    return ScopeAuthorizer::canAccess($user, 'rt', $rtId);
});

// Private Channel Chat Bebas RW: Warga dalam naungan RW terkait yang boleh subscribe
Broadcast::channel('chat.rw.{rwId}', function (User $user, int $rwId) {
    return ScopeAuthorizer::canAccess($user, 'rw', $rwId);
});
