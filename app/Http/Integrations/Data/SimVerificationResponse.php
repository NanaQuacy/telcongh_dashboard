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
        public readonly ?array $errors = null,
        public readonly ?int $stockItemId = null,
        public readonly ?int $stockBatchId = null,
        public readonly ?string $stockBatchName = null,
        public readonly ?int $networkId = null,
        public readonly ?int $businessId = null,
        public readonly ?string $businessName = null,
        public readonly ?bool $isActive = null,
        public readonly ?bool $isSold = null
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
            errors: $data['errors'] ?? null,
            stockItemId: $data['data']['stockitem_id'] ?? null,
            stockBatchId: $data['data']['stock_batch_id'] ?? null,
            stockBatchName: $data['data']['stock_batch_name'] ?? null,
            networkId: $data['data']['network_id'] ?? null,
            businessId: $data['data']['business_id'] ?? null,
            businessName: $data['data']['business_name'] ?? null,
            isActive: $data['data']['is_active'] ?? null,
            isSold: $data['data']['is_sold'] ?? null
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
     * Get the stock item ID
     */
    public function getStockItemId(): ?int
    {
        return $this->stockItemId;
    }

    /**
     * Get the stock batch ID
     */
    public function getStockBatchId(): ?int
    {
        return $this->stockBatchId;
    }

    /**
     * Get the stock batch name
     */
    public function getStockBatchName(): ?string
    {
        return $this->stockBatchName;
    }

    /**
     * Get the network ID
     */
    public function getNetworkId(): ?int
    {
        return $this->networkId;
    }

    /**
     * Get the business ID
     */
    public function getBusinessId(): ?int
    {
        return $this->businessId;
    }

    /**
     * Get the business name
     */
    public function getBusinessName(): ?string
    {
        return $this->businessName;
    }

    /**
     * Check if the SIM is active
     */
    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    /**
     * Check if the SIM is sold
     */
    public function getIsSold(): ?bool
    {
        return $this->isSold;
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
