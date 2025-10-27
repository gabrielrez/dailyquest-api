<?php

namespace App\Http\Interfaces;

use App\Models\User;

interface ReportInterface
{
    public function generate(User $for): array;
}
