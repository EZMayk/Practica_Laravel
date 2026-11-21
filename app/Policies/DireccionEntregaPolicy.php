<?php

namespace App\Policies;

use App\Models\DireccionEntrega;
use App\Models\User;

class DireccionEntregaPolicy
{
    public function view(User $user, DireccionEntrega $direccionEntrega): bool
    {
        return $direccionEntrega->user_id === $user->id;
    }
}
