<?php

namespace App\Http\Integrations\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Contracts\Body\HasBody;
use Saloon\Traits\Body\HasJsonBody;

class LoginRequest extends Request implements HasBody
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
        return '/login';
    }

    /**
     * Default body data
     */
    protected function defaultBody(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
            'remember' => $this->remember ?? false,
        ];
    }

    /**
     * Create a new login request instance
     */
    public function __construct(
        public string $email,
        public string $password,
        public ?bool $remember = false
    ) {
        //
    }
}
