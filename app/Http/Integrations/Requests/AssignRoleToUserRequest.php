<?php

namespace App\Http\Integrations\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Contracts\Body\HasBody;
use Saloon\Traits\Body\HasJsonBody;

class AssignRoleToUserRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected int $roleId,
        protected int $userId,
        protected ?string $token = null
    ) {}

    public function resolveEndpoint(): string
    {
        return "/roles/assign-to-user";
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
            'role_id' => $this->roleId,
            'user_id' => $this->userId,
        ];
    }
}
