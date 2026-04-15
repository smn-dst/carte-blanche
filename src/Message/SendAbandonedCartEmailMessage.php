<?php

namespace App\Message;

readonly class SendAbandonedCartEmailMessage
{
    public function __construct(public int $cartId)
    {
    }
}
