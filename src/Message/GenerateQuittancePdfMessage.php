<?php

namespace App\Message;

class GenerateQuittancePdfMessage
{
    public function __construct(
        private int $paiementId
    ) {
    }

    public function getPaiementId(): int
    {
        return $this->paiementId;
    }
}
