<?php

namespace App\Http\Integrations\Data;

use Saloon\Http\Response;

class SimVerificationResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly bool $isValid,
        public readonly ?string $simSerialNumber = null,
        public readonly ?string $networkName = null,
        public readonly ?string $networkCode = null,
        public readonly ?string $status = null,
        public readonly ?string $message = null,
        public readonly ?bool $isAvailable = null,
        public readonly ?string $reason = null,
        public readonly ?array $errors = null
    ) {
        //
    }

    /**
     * Create a SIM verification response from Saloon response
     */
    public static function fromResponse(Response $response): self
    {
        $data = $response->json();

        return new self(
            success: $response->successful() && ($data['status'] === 'success' || $data['success'] ?? true),
            isValid: $data['data']['is_valid'] ?? $data['is_valid'] ?? false,
            simSerialNumber: $data['data']['serial_number'] ?? $data['data']['sim_serial_number'] ?? $data['sim_serial_number'] ?? null,
            networkName: $data['data']['network_name'] ?? $data['network_name'] ?? null,
            networkCode: $data['data']['network_code'] ?? $data['network_code'] ?? null,
            status: $data['status'] ?? $data['data']['status'] ?? null,
            message: $data['message'] ?? null,
            isAvailable: $data['data']['is_available'] ?? $data['is_available'] ?? null,
            reason: $data['data']['reason'] ?? $data['reason'] ?? null,
            errors: $data['errors'] ?? null
        );
    }

    /**
     * Check if the verification request was successful
     */
    public function isSuccessful(): bool
    {
        return $this->success;
    }

    /**
     * Check if the SIM card is valid
     */
    public function isValid(): bool
    {
        return $this->isValid;
    }

    /**
     * Get the SIM serial number
     */
    public function getSimSerialNumber(): ?string
    {
        return $this->simSerialNumber;
    }

    /**
     * Get the network name
     */
    public function getNetworkName(): ?string
    {
        return $this->networkName;
    }

    /**
     * Get the network code
     */
    public function getNetworkCode(): ?string
    {
        return $this->networkCode;
    }

    /**
     * Get the SIM status
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * Get the response message
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Get error messages
     */
    public function getErrors(): ?array
    {
        return $this->errors;
    }

    /**
     * Check if the SIM is available
     */
    public function isAvailable(): ?bool
    {
        return $this->isAvailable;
    }

    /**
     * Get the verification reason
     */
    public function getReason(): ?string
    {
        return $this->reason;
    }

    /**
     * Get the verification result message for display
     */
    public function getDisplayMessage(): string
    {
        if (!$this->success) {
            return $this->message ?? 'Verification failed';
        }

        // If we have a specific reason from the API, use it
        if ($this->reason) {
            return $this->reason;
        }

        if ($this->isValid) {
            $networkInfo = $this->networkName ? " ({$this->networkName})" : '';
            return "SIM card verified successfully{$networkInfo}";
        }

        return $this->message ?? 'Invalid SIM card serial number';
    }
}
