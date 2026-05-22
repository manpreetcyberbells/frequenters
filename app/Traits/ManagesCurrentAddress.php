<?php

namespace App\Traits;

use App\Models\CustomerAddress;

trait ManagesCurrentAddress
{
    /**
     * Agar naya address current set ho raha hai toh
     * pehle wala current address unset karo.
     * Duplicate logic tha: createCustomerAddress, updateCustomerAddress, makeCurrentAddress
     */
    protected function unsetCurrentAddressIfNeeded(int $customerId, bool $isCurrent): void
    {
        if ($isCurrent) {
            CustomerAddress::where('customer_id', $customerId)
                ->where('is_current', '1')
                ->update(['is_current' => '0']);
        }
    }
}
