<?php

namespace App\Services\Connect;

use App\Models\Connect\ConnectCustomer;

class ConnectOptOutService
{
    public const STOP_KEYWORDS  = ['STOP', 'UNSUBSCRIBE', 'CANCEL', 'STOPALL', 'END', 'QUIT'];
    public const START_KEYWORDS = ['START', 'UNSTOP'];

    public function isStop(string $message): bool
    {
        return in_array(strtoupper(trim($message)), self::STOP_KEYWORDS, true);
    }

    public function isStart(string $message): bool
    {
        return in_array(strtoupper(trim($message)), self::START_KEYWORDS, true);
    }

    public function optOut(ConnectCustomer $customer): void
    {
        $customer->update([
            'opted_out'    => true,
            'opted_out_at' => now(),
        ]);
    }

    public function optIn(ConnectCustomer $customer): void
    {
        $customer->update([
            'opted_out'    => false,
            'opted_out_at' => null,
        ]);
    }
}
