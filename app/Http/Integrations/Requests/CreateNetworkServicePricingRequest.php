<?php

namespace App\Http\Integrations\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Contracts\Body\HasBody;
use Saloon\Traits\Body\HasJsonBody;

class CreateNetworkServicePricingRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function resolveEndpoint(): string
    {
        return "/network-service-pricings";
    }

    protected function defaultHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    protected function defaultBody(): array
    {
        return [
            'network_service_id' => $this->networkServiceId,
            'business_id' => $this->businessId,
            'cost_price' => $this->costPrice,
            'selling_price' => $this->sellingPrice,
        ];
    }

    public function __construct(
        public string $token,
        public int $networkServiceId,
        public int $businessId,
        public float $costPrice,
        public float $sellingPrice
    ) {
        //
    }
}
