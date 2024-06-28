<?php

namespace App\Listeners;

use App\Events\VerifyRegisterAdmin;
use App\Mail\EmailRegisterAdmin;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendEmailVerifyAdmin
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
     * @param  \App\Events\VerifyRegisterAdmin  $event
     * @return void
     */
    public function handle(VerifyRegisterAdmin $event)
    {
        $admin = $event->admin;
    
        // Send verification email with verification token
        Mail::to($admin->email)->send(new EmailRegisterAdmin ($admin));
    }
}
