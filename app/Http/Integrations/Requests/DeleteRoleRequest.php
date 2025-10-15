<?php

namespace App\Http\Integrations\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class DeleteRoleRequest extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        protected int $roleId,
        protected ?string $token = null
    ) {}

    public function resolveEndpoint(): string
    {
        return "/roles/{$this->roleId}";
    }

    protected function defaultHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
        ];

        if ($this->token) {
            $headers['Authorization'] = 'Bearer ' . $this->token;
        }

        return $headers;
    }

    protected function defaultQuery(): array
    {
        return [];
    }
}
