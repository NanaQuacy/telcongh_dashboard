<?php

namespace App\Http\Integrations\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class DeletePermissionRequest extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        protected int $permissionId,
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
