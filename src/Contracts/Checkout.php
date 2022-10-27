<?php

declare(strict_types=1);
/**
 * Contains the Checkout interface.
 *
 * @copyright   Copyright (c) 2017 Attila Fulop
 * @author      Attila Fulop
 * @license     MIT
 * @since       2017-11-13
 *
 */

namespace Vanilo\Checkout\Contracts;

use Illuminate\Support\Collection;
use Vanilo\Contracts\CheckoutSubject;

interface Checkout
{
    /**
     * Returns the cart
     *
     * @return CheckoutSubject|null
     */
    public function getCart();

    /**
     * Set the cart for the checkout
     *
     * @param CheckoutSubject $cart
     */
    public function setCart(CheckoutSubject $cart);

    /**
     * Returns the state of the checkout
     *
     * @return CheckoutState
     */
    public function getState(): CheckoutState;

    /**
     * Sets the state of the checkout
     *
     * @param CheckoutState|string $state
     */
    public function setState($state);

	/**
	 * Returns the bill payer details
	 *
	 * @return Collection
	 */
    public function getBillpayer(): Collection;

    /**
     * Sets the bill payer details
     *
     * @param Collection $data
     */
    public function setBillpayer(Collection $data);

    /**
     * Returns the shipping address
     *
     * @return Collection
     */
    public function getShippingAddress(): Collection;

    /**
     * Sets the shipping address
     *
     * @param Collection $data
     */
    public function setShippingAddress(Collection $data);

    public function setCustomAttribute(string $key, $value): void;

    public function getCustomAttribute(string $key);

    public function putCustomAttributes(array $data): void;

    public function getCustomAttributes(): array;

    /**
     * Update checkout data with an array of attributes
     *
     * @deprecated
     *
     * @param array $data
     */
    public function update(array $data);

    /**
     * Returns the grand total of the checkout
     *
     * @return float
     */
    public function total();
}
