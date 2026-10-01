<?php

namespace Tests\Unit\Mail\Fixtures;

class MailableDeTestEnFile extends \Illuminate\Mail\Mailable implements \Illuminate\Contracts\Queue\ShouldQueue
{
    public function build()
    {
        return $this->subject('Rappel')->html('<p>Bonjour</p>');
    }
}
