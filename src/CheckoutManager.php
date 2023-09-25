<?php

declare(strict_types=1);
/**
 * Contains the Checkout Manager class.
 *
 * @copyright   Copyright (c) 2017 Attila Fulop
 * @author      Attila Fulop
 * @license     MIT
 * @since       2017-11-13
 *
 */

namespace Vanilo\Checkout;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Vanilo\Checkout\Contracts\Checkout as CheckoutContract;
use Vanilo\Checkout\Contracts\CheckoutState as CheckoutStateContract;
use Vanilo\Checkout\Contracts\CheckoutStore;
use Vanilo\Contracts\Address;
use Vanilo\Contracts\Billpayer;
use Vanilo\Contracts\CheckoutSubject;

class CheckoutManager implements CheckoutContract
{
	/** @var  CheckoutStore */
	protected $store;

	public $lock;

	public function __construct(CheckoutStore $store)
	{
		$this->store = $store;
	}

	/**
	 * @inheritDoc
	 */
	public function getCart()
	{
		return $this->store->getCart();
	}

	/**
	 * @inheritDoc
	 */
	public function setCart(CheckoutSubject $cart)
	{
		$this->store->setCart($cart);
	}

	/**
	 * @inheritdoc
	 */
	public function getState(): CheckoutStateContract
	{
		return $this->store->getState();
	}

	/**
	 * @inheritdoc
	 */
	public function setState($state)
	{
		$this->store->setState($state);
	}

	/**
	 * @inheritdoc
	 */
	public function getBillpayer(): Collection
	{
		return $this->store->getBillpayer();
	}

	/**
	 * @inheritdoc
	 */
	public function setBillpayer(Collection $address)
	{
		return $this->store->setBillpayer($billpayer);
	}

	/**
	 * @inheritdoc
	 */
	public function getShippingAddress(): Collection
	{
		return $this->store->getShippingAddress();
	}

	public function getType(): string
	{
		return $this->store->getType();
	}

	public function setType(string $type)
	{
		$this->store->setType($type);
	}

	public function getUserId()
	{
		return $this->store->getUserId();
	}

	public function setUserId(int $id)
	{
		$this->store->setUserId($id);
	}

	/**
	 * @inheritDoc
	 */
	public function setShippingAddress(Collection $address)
	{
		$this->store->setShippingAddress($address);
	}

	public function setCustomAttribute(string $key, $value): void
	{
		$this->store->setCustomAttribute($key, $value);
	}

	public function getCustomAttribute(string $key)
	{
		return $this->store->getCustomAttribute($key);
	}

	public function putCustomAttributes(array $data): void
	{
		$this->store->putCustomAttributes($data);
	}

	public function getCustomAttributes(): array
	{
		return $this->store->getCustomAttributes();
	}

	/**
	 * @inheritdoc
	 */
	public function update(array $data)
	{
		$this->store->update($data);
	}

	/**
	 * @inheritDoc
	 */
	public function total()
	{
		return $this->store->total();
	}

	public function engageLock()
	{
		$this->lock = Cache::lock('new-order', 5);
		$this->lock->block(5);
	}

	public function releaseLock()
	{
		$this->lock?->release();
	}
}
