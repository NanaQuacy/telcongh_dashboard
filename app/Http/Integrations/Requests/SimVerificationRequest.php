<?php

namespace App\Http\Integrations\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Contracts\Body\HasBody;
use Saloon\Traits\Body\HasJsonBody;

class SimVerificationRequest extends Request implements HasBody
{
    use HasJsonBody;

    /**
     * The HTTP method of the request
     */
    protected Method $method = Method::POST;

    /**
     * The endpoint of the request
     */
    public function resolveEndpoint(): string
    {
        return '/stock/verify-serial-number';
    }

    /**
     * Default headers for the request
     */
    protected function defaultHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Default body data
     */
    protected function defaultBody(): array
    {
        return [
            'serial_numbers'=> $this->simSerialNumber,
            'business_id' => $this->businessId,
        ];
    }

    /**
     * Create a new SIM verification request instance
     */
    public function __construct(
        public string $token,
        public string $simSerialNumber,
        public int $businessId
    ) {
        //
    }
}
