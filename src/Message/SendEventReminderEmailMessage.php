<?php

namespace App\Message;

readonly class SendEventReminderEmailMessage
{
    public function __construct(public int $ticketId)
    {
    }
}
