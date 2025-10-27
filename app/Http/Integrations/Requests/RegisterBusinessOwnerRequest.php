<?php

namespace App\Http\Integrations\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Contracts\Body\HasBody;
use Saloon\Traits\Body\HasJsonBody;

class RegisterBusinessOwnerRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected string $name,
        protected string $phone,
        protected string $email,
        protected string $password,
        protected string $passwordConfirmation,
        protected string $businessName,
        protected string $businessAddress,
        protected string $businessPhone,
        protected string $businessEmail,
        protected ?string $businessWebsite = null,
        protected ?string $businessDescription = null
    ) {}

    public function resolveEndpoint(): string
    {
        return '/register-business-owner';
    }

    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    protected function defaultBody(): array
    {
        $payload = [
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'password' => $this->password,
            'password_confirmation' => $this->passwordConfirmation,
            'business_name' => $this->businessName,
            'business_address' => $this->businessAddress,
            'business_phone' => $this->businessPhone,
            'business_email' => $this->businessEmail,
        ];

        // Add optional fields only if they are provided
        if ($this->businessWebsite !== null) {
            $payload['business_website'] = $this->businessWebsite;
        }

        if ($this->businessDescription !== null) {
            $payload['business_description'] = $this->businessDescription;
        }

        return $payload;
    }

    // Getter methods for accessing the data
    public function getName(): string
    {
        return $this->name;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getPasswordConfirmation(): string
    {
        return $this->passwordConfirmation;
    }

    public function getBusinessName(): string
    {
        return $this->businessName;
    }

    public function getBusinessAddress(): string
    {
        return $this->businessAddress;
    }

    public function getBusinessPhone(): string
    {
        return $this->businessPhone;
    }

    public function getBusinessEmail(): string
    {
        return $this->businessEmail;
    }

    public function getBusinessWebsite(): ?string
    {
        return $this->businessWebsite;
    }

    public function getBusinessDescription(): ?string
    {
        return $this->businessDescription;
    }

    // Validation method
    public function validatePayload(): array
    {
        $errors = [];

        if (empty($this->name)) {
            $errors['name'] = 'Name is required';
        }

        if (empty($this->phone)) {
            $errors['phone'] = 'Phone is required';
        }

        if (empty($this->email) || !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Valid email is required';
        }

        if (empty($this->password)) {
            $errors['password'] = 'Password is required';
        }

        if ($this->password !== $this->passwordConfirmation) {
            $errors['password_confirmation'] = 'Password confirmation does not match';
        }

        if (empty($this->businessName)) {
            $errors['business_name'] = 'Business name is required';
        }

        if (empty($this->businessAddress)) {
            $errors['business_address'] = 'Business address is required';
        }

        if (empty($this->businessPhone)) {
            $errors['business_phone'] = 'Business phone is required';
        }

        if (empty($this->businessEmail) || !filter_var($this->businessEmail, FILTER_VALIDATE_EMAIL)) {
            $errors['business_email'] = 'Valid business email is required';
        }

        if ($this->businessWebsite && !filter_var($this->businessWebsite, FILTER_VALIDATE_URL)) {
            $errors['business_website'] = 'Valid business website URL is required';
        }

        return $errors;
    }

    // Helper method to create from array
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? '',
            phone: $data['phone'] ?? '',
            email: $data['email'] ?? '',
            password: $data['password'] ?? '',
            passwordConfirmation: $data['password_confirmation'] ?? '',
            businessName: $data['business_name'] ?? '',
            businessAddress: $data['business_address'] ?? '',
            businessPhone: $data['business_phone'] ?? '',
            businessEmail: $data['business_email'] ?? '',
            businessWebsite: $data['business_website'] ?? null,
            businessDescription: $data['business_description'] ?? null
        );
    }
}
