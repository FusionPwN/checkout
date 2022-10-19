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
use Vanilo\Checkout\Contracts\CheckoutDataFactory;
use Vanilo\Checkout\Contracts\CheckoutStore;
use Vanilo\Checkout\Traits\EmulatesFillAttributes;
use Vanilo\Checkout\Traits\HasCart;
use Vanilo\Checkout\Traits\HasCheckoutState;
use Vanilo\Contracts\Address;
use Vanilo\Contracts\Billpayer;

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

    /** @var  Billpayer */
    protected $billpayer;

    /** @var  Address */
    protected $shippingAddress;

    /** @var  CheckoutDataFactory */
    protected $dataFactory;

    /** @var array */
    protected $customData = [];

    public function __construct($config, CheckoutDataFactory $dataFactory)
    {
        $this->dataFactory = $dataFactory;
        $this->billpayer = $dataFactory->createBillpayer();
        $this->shippingAddress = $dataFactory->createShippingAddress();
    }

    /**
     * @inheritdoc
     */
    public function update(array $data)
    {
		$shippingAddrId = Arr::get($data, 'shippingAddress.id');
		$billingAddrId = Arr::get($data, 'billpayer.id');

		$shippingAddress = [];
		$billingAddress = [];

		if ($shippingAddrId == 'new-address') {
			$shippingAddress = $data['shippingAddress'];
		} else {
			$shippingAddress = AdminAddress::find($shippingAddrId)->toArray();
		}

		$this->updateShippingAddress($shippingAddress ?? []);

		if ($billingAddrId == 'use-shipping-data') {
			$billingAddress['firstname'] 				= $shippingAddress['firstname'];
			$billingAddress['lastname'] 				= $shippingAddress['lastname'];
			$billingAddress['email'] 					= $shippingAddress['email'];
			$billingAddress['phone'] 					= $shippingAddress['phone'];
			$billingAddress['address'] 					= [];
			$billingAddress['address']['address'] 		= $shippingAddress['address'];
			$billingAddress['address']['city'] 			= $shippingAddress['city'];
			$billingAddress['address']['postalcode'] 	= $shippingAddress['postalcode'];
			$billingAddress['address']['country_id'] 	= $shippingAddress['country_id'];
			if (Arr::get($data, 'billpayer.shipping-nif')) {
				$billingAddress['nif'] = Arr::get($data, 'billpayer.shipping-nif');
			}
		} else if ($billingAddrId == 'new-address') {
			$billingAddress = $data['billpayer'];
		} else if ($billingAddrId == 'fatura-simplificada') {
			$billingAddress = [];
			$billingAddress['address'] = [];
			$billingAddress['address']['country_id'] = $shippingAddress['country_id'];
		} else {
			$billingAddressDB = AdminAddress::find($billingAddrId);

			$billingAddress['firstname'] 				= $billingAddressDB->firstname;
			$billingAddress['lastname'] 				= $billingAddressDB->lastname;
			$billingAddress['email'] 					= $billingAddressDB->email;
			$billingAddress['phone'] 					= $billingAddressDB->phone;
			$billingAddress['nif'] 						= $billingAddressDB->nif;
			$billingAddress['address'] 					= [];
			$billingAddress['address']['address'] 		= $billingAddressDB->address;
			$billingAddress['address']['city'] 			= $billingAddressDB->city;
			$billingAddress['address']['postalcode'] 	= $billingAddressDB->postalcode;
			$billingAddress['address']['country_id'] 	= $billingAddressDB->country_id;
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
    public function getBillpayer(): Billpayer
    {
        return $this->billpayer;
    }

    /**
     * @inheritdoc
     */
    public function setBillpayer(Billpayer $billpayer)
    {
        $this->billpayer = $billpayer;
    }

    /**
     * @inheritdoc
     */
    public function getShippingAddress(): Address
    {
        return $this->shippingAddress;
    }

    /**
     * @inheritdoc
     */
    public function setShippingAddress(Address $address)
    {
        return $this->shippingAddress = $address;
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
        $this->fill($this->billpayer, Arr::except($data, 'address'));
        $this->fill($this->billpayer->address, $data['address']);
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

    private function getShipToName()
    {
        if ($this->billpayer->isOrganization()) {
            return sprintf(
                '%s (%s)',
                $this->billpayer->getCompanyName(),
                $this->billpayer->getFullName()
            );
        }

        return $this->billpayer->getName();
    }
}
