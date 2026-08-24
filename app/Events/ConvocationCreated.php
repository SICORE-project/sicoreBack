<?php

namespace App\Events;


use Illuminate\Foundation\Events\Dispatchable;
use App\Models\indemnite\Convocations as Convocation;

class ConvocationCreated
{
    use Dispatchable;

    public function __construct(public Convocation $convocation) {}
}
