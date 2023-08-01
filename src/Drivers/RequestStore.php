<?php

declare(strict_types=1);
/**
 * Contains the RequestStore class.
 *
 * @copyright   Copyright (c) 2017 Attila Fulop
 * @author      Attila Fulop
 * @license     MIT
 * @since       2017-11-23
 *
 */

namespace Vanilo\Checkout\Drivers;

use App\Models\Admin\Address as AdminAddress;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Vanilo\Checkout\Contracts\CheckoutStore;
use Vanilo\Checkout\Traits\EmulatesFillAttributes;
use Vanilo\Checkout\Traits\HasCart;
use Vanilo\Checkout\Traits\HasCheckoutState;

/**
 * Stores & fetches checkout data across http requests.
 * This is a simple and lightweight and variant for
 * cases when having volatile checkout data is ✔
 */
class RequestStore implements CheckoutStore
{
	use HasCheckoutState;
	use HasCart;
	use EmulatesFillAttributes;

	protected $state;

	protected Collection $billpayer;
	protected Collection $shippingAddress;

	/** @var array */
	protected $customData = [];

	protected string $type = 'checkout';
	protected $user_id = null;

	public function __construct($config)
	{
		$this->billpayer = collect();
		$this->shippingAddress = collect();
	}

	/**
	 * @inheritdoc
	 */
	public function update(array $data)
	{
		if (Arr::has($data, 'user_id')) {
			$this->setUserId(Arr::get($data, 'user_id'));
		}

		$shippingAddrId = Arr::get($data, 'shippingAddress.id');
		$billingAddrId = Arr::get($data, 'billpayer.id');

		$shippingAddress = [];
		$billingAddress = [];

		$this->type = Arr::get($data, 'type', 'checkout');

		if ($shippingAddrId == 'new-address') {
			$shippingAddress = $data['shippingAddress'];
		} else {
			$shippingAddress = AdminAddress::find($shippingAddrId)->toArray();
		}

		$this->updateShippingAddress($shippingAddress ?? []);

		if ($billingAddrId == 'use-shipping-data') {
			$billingAddress['id']			= $billingAddrId;
			$billingAddress['firstname'] 	= $shippingAddress['firstname'];
			$billingAddress['lastname'] 	= $shippingAddress['lastname'];
			$billingAddress['email'] 		= $shippingAddress['email'];
			$billingAddress['phone'] 		= $shippingAddress['phone'];
			$billingAddress['address'] 		= $shippingAddress['address'];
			$billingAddress['city'] 		= $shippingAddress['city'];
			$billingAddress['postalcode'] 	= $shippingAddress['postalcode'];
			$billingAddress['country_id'] 	= $shippingAddress['country_id'];

			if (Arr::get($data, 'billpayer.shipping-nif')) {
				$billingAddress['nif'] = Arr::get($data, 'billpayer.shipping-nif');
			}
		} else if ($billingAddrId == 'new-address') {
			$billingAddress = $data['billpayer'];
			$billingAddress['email'] = $shippingAddress['email'];
		} else if ($billingAddrId == 'fatura-simplificada') {
			$billingAddress = [];
			$billingAddress['id']			= $billingAddrId;
			$billingAddress['country_id'] 	= $shippingAddress['country_id'];
		} else {
			$billingAddressDB = AdminAddress::find($billingAddrId);

			$billingAddress['id']			= $billingAddrId;
			$billingAddress['firstname'] 	= $billingAddressDB->firstname;
			$billingAddress['lastname'] 	= $billingAddressDB->lastname;
			$billingAddress['email'] 		= $billingAddressDB->email;
			$billingAddress['phone'] 		= $billingAddressDB->phone;
			$billingAddress['nif'] 			= $billingAddressDB->nif;
			$billingAddress['address'] 		= $billingAddressDB->address;
			$billingAddress['city'] 		= $billingAddressDB->city;
			$billingAddress['postalcode'] 	= $billingAddressDB->postalcode;
			$billingAddress['country_id'] 	= $billingAddressDB->country_id;
		}

		$this->updateBillpayer($billingAddress);
	}

	/**
	 * @inheritdoc
	 */
	public function total()
	{
		return $this->cart->total();
	}

	/**
	 * @inheritdoc
	 */
	public function getBillpayer(): Collection
	{
		return $this->billpayer;
	}

	/**
	 * @inheritdoc
	 */
	public function setBillpayer(Collection $billpayer)
	{
		$this->billpayer = $billpayer;
	}

	/**
	 * @inheritdoc
	 */
	public function getShippingAddress(): Collection
	{
		return $this->shippingAddress;
	}

	/**
	 * @inheritdoc
	 */
	public function setShippingAddress(Collection $address)
	{
		return $this->shippingAddress = $address;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function setType(string $type)
	{
		$this->type = $type;
	}

	public function getUserId()
	{
		return $this->user_id;
	}

	public function setUserId(int $id)
	{
		$this->user_id = $id;
	}

	public function setCustomAttribute(string $key, $value): void
	{
		Arr::set($this->customData, $key, $value);
	}

	public function getCustomAttribute(string $key)
	{
		return Arr::get($this->customData, $key);
	}

	public function putCustomAttributes(array $data): void
	{
		$this->customData = $data;
	}

	public function getCustomAttributes(): array
	{
		return $this->customData;
	}

	/**
	 * @inheritdoc
	 */
	protected function updateBillpayer($data)
	{
		$this->fill($this->billpayer, $data);
	}

	/**
	 * @inheritdoc
	 */
	protected function updateShippingAddress($data)
	{
		$this->fill($this->shippingAddress, $data);
	}

	private function fill($target, array $attributes)
	{
		if (method_exists($target, 'fill')) {
			$target->fill($attributes);
		} else {
			$this->fillAttributes($target, $attributes);
		}
	}
}
