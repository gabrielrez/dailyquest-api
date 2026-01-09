<?php

namespace App\Http\Controllers;

use App\Http\Traits\Respondable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use Respondable, AuthorizesRequests;
}
