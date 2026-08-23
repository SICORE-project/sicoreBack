<?php

namespace App\Observers;

use App\Events\ConvocationCreated;
use App\Models\indemnite\Convocations;

class ConvocationObserver
{
    /**
     * Handle the Convocations "created" event.
     */
    public function created(Convocations $convocation): void
    {
        event(new ConvocationCreated($convocation));
    }

    /**
     * Handle the Convocations "updated" event.
     */
    public function updated(Convocations $convocation): void
    {
        //
    }

    /**
     * Handle the Convocations "deleted" event.
     */
    public function deleted(Convocations $convocation): void
    {
        //
    }

    /**
     * Handle the Convocations "restored" event.
     */
    public function restored(Convocations $convocations): void
    {
        //
    }

    /**
     * Handle the Convocations "force deleted" event.
     */
    public function forceDeleted(Convocations $convocations): void
    {
        //
    }
}
