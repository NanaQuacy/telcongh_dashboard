<?php

namespace App\Http\Integrations\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class BusinessRequest extends Request
{
    /**
     * 
     * The HTTP method of the request
     */
    protected Method $method = Method::GET;

    /**
     * The endpoint of the request
     */
    public function resolveEndpoint(): string
    {
        return '/my-businesses';
    }

    /**
     * Default query parameters
     */
    protected function defaultQuery(): array
    {
        return [
            'user_id' => $this->userId,
        ];
    }

    /**
     * Default headers
     */
    protected function defaultHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
        ];
    }

    /**
     * Create a new business request instance
     */
    public function __construct(
        public string $userId,
        public string $token
    ) {
        //
    }
}
