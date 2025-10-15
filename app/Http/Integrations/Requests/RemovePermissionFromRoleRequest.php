<?php

namespace App\Http\Integrations\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Contracts\Body\HasBody;
use Saloon\Traits\Body\HasJsonBody;

class RemovePermissionFromRoleRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected int $permissionId,
        protected int $roleId,
        protected ?string $token = null
    ) {}

    public function resolveEndpoint(): string
    {
        return "/permissions/remove-from-role";
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
            'permission_id' => $this->permissionId,
            'role_id' => $this->roleId,
        ];
    }
}
