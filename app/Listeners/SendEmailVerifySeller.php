<?php

namespace App\Listeners;

use App\Events\VerifyRegisterSeller;
use App\Mail\EmailRegisterSeller;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendEmailVerifySeller
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\VerifyRegisterSeller  $event
     * @return void
     */
   
    public function handle(VerifyRegisterSeller $event)
    {
        $seller = $event->seller;
    
        // Send verification email with verification token
        Mail::to($seller->email)->send(new EmailRegisterSeller($seller));
    }
}
