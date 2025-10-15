<?php

namespace App\Http\Integrations\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Contracts\Body\HasBody;
use Saloon\Traits\Body\HasJsonBody;

class UpdatePermissionRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PUT;

    public function __construct(
        protected int $permissionId,
        protected string $name,
        protected string $description,
        protected ?string $token = null
    ) {}

    public function resolveEndpoint(): string
    {
        return "/permissions/{$this->permissionId}";
    }

    protected function defaultHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if ($this->token) {
            $headers['Authorization'] = 'Bearer ' . $this->token;
        }

        return $headers;
    }

    protected function defaultBody(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}
