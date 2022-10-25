<?php

declare(strict_types=1);
/**
 * Contains the CheckoutDataFactory interface.
 *
 * @copyright   Copyright (c) 2017 Attila Fulop
 * @author      Attila Fulop
 * @license     MIT
 * @since       2017-11-26
 *
 */

namespace Vanilo\Checkout\Contracts;

interface CheckoutDataFactory
{
    public function createBillpayer(): ?Array;

    public function createShippingAddress(): Array;
}
