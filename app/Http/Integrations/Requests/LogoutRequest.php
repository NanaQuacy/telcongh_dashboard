<?php

namespace App\Http\Integrations\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Contracts\Body\HasBody;
use Saloon\Traits\Body\HasJsonBody;

class LogoutRequest extends Request implements HasBody
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
        return '/logout';
    }

    /**
     * Default body data
     */
    protected function defaultBody(): array
    {
        return [
            'token' => $this->token,
        ];
    }

    /**
     * Create a new logout request instance
     */
    public function __construct(
        public string $token
    ) {
        //
    }
}
