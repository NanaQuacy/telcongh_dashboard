<?php

namespace App\Http\Integrations\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Contracts\Body\HasBody;
use Saloon\Traits\Body\HasJsonBody;

class RegisterRequest extends Request implements HasBody
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
        return '/register';
    }

    /**
     * Default body data
     */
    protected function defaultBody(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'password_confirmation' => $this->passwordConfirmation,
            'phone' => $this->phone,
            'business_code' => $this->business_code,
        ];
    }

    /**
     * Create a new register request instance
     */
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $passwordConfirmation,
        public ?string $phone = null,
        public string $business_code
    ) {
        //
    }
}
