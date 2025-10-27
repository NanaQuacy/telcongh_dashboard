<?php

namespace App\Http\Integrations\Data;

class TransactionItemResponse
{
    public function __construct(
        public readonly int $id,
        public readonly int $businessId,
        public readonly int $userId,
        public readonly ?int $customerServiceDetailsId,
        public readonly ?int $paymentId,
        public readonly string $transactionStatus,
        public readonly ?string $paymentStatus,
        public readonly float $sellingPrice,
        public readonly float $costPrice,
        public readonly float $profit,
        public readonly ?string $paymentMethod,
        public readonly ?float $paymentAmount,
        public readonly ?float $paidAmount,
        public readonly ?float $dueAmount,
        public readonly ?string $customerName,
        public readonly ?string $customerPhone,
        public readonly ?string $customerEmail,
        public readonly ?string $serviceName,
        public readonly ?string $networkName,
        public readonly string $createdAt,
        public readonly string $updatedAt
    ) {}

    public static function fromArray(array $data): self
    {
        // Extract transaction data
        $transaction = $data['transaction'] ?? $data;
        $payment = $data['payment'] ?? null;
        $customerDetails = $data['customer_details'] ?? null;
        $service = $data['service'] ?? null;
        $network = $data['network'] ?? null;

        return new self(
            id: $transaction['id'] ?? $data['id'] ?? 0,
            businessId: $transaction['business_id'] ?? $data['business_id'] ?? 0,
            userId: $transaction['user_id'] ?? $data['user_id'] ?? 0,
            customerServiceDetailsId: $transaction['customer_service_details_id'] ?? $data['customer_service_details_id'] ?? null,
            paymentId: $transaction['payment_id'] ?? $data['payment_id'] ?? null,
            transactionStatus: $transaction['transaction_status'] ?? $transaction['status'] ?? 'pending',
            paymentStatus: $payment['payment_status'] ?? $payment['status'] ?? null,
            sellingPrice: (float) ($transaction['selling_price'] ?? 0),
            costPrice: (float) ($transaction['cost_price'] ?? 0),
            profit: (float) ($transaction['profit'] ?? 0),
            paymentMethod: $payment['payment_method'] ?? null,
            paymentAmount: $payment ? (float) ($payment['payment_amount'] ?? $payment['amount'] ?? 0) : null,
            paidAmount: $payment ? (float) ($payment['paid_amount'] ?? 0) : null,
            dueAmount: $payment ? (float) ($payment['due_amount'] ?? 0) : null,
            customerName: $customerDetails['full_name'] ?? $customerDetails['name'] ?? null,
            customerPhone: $customerDetails['phone_number'] ?? $customerDetails['phone'] ?? null,
            customerEmail: $customerDetails['email'] ?? null,
            serviceName: $service['name'] ?? null,
            networkName: $network['name'] ?? null,
            createdAt: $transaction['created_at'] ?? $data['created_at'] ?? '',
            updatedAt: $transaction['updated_at'] ?? $data['updated_at'] ?? ''
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getBusinessId(): int
    {
        return $this->businessId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getCustomerServiceDetailsId(): ?int
    {
        return $this->customerServiceDetailsId;
    }

    public function getPaymentId(): ?int
    {
        return $this->paymentId;
    }

    public function getTransactionStatus(): string
    {
        return $this->transactionStatus;
    }

    public function getPaymentStatus(): ?string
    {
        return $this->paymentStatus;
    }

    public function getSellingPrice(): float
    {
        return $this->sellingPrice;
    }

    public function getCostPrice(): float
    {
        return $this->costPrice;
    }

    public function getProfit(): float
    {
        return $this->profit;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function getPaymentAmount(): ?float
    {
        return $this->paymentAmount;
    }

    public function getPaidAmount(): ?float
    {
        return $this->paidAmount;
    }

    public function getDueAmount(): ?float
    {
        return $this->dueAmount;
    }

    public function getCustomerName(): ?string
    {
        return $this->customerName;
    }

    public function getCustomerPhone(): ?string
    {
        return $this->customerPhone;
    }

    public function getCustomerEmail(): ?string
    {
        return $this->customerEmail;
    }

    public function getServiceName(): ?string
    {
        return $this->serviceName;
    }

    public function getNetworkName(): ?string
    {
        return $this->networkName;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function isCompleted(): bool
    {
        return $this->transactionStatus === 'completed';
    }

    public function isPending(): bool
    {
        return $this->transactionStatus === 'pending';
    }

    public function isInProgress(): bool
    {
        return $this->transactionStatus === 'in_progress';
    }

    public function isCancelled(): bool
    {
        return $this->transactionStatus === 'cancelled';
    }

    public function isRefunded(): bool
    {
        return $this->transactionStatus === 'refunded';
    }

    public function isPaymentCompleted(): bool
    {
        return $this->paymentStatus === 'completed';
    }

    public function isPaymentPending(): bool
    {
        return $this->paymentStatus === 'pending';
    }

    public function isPaymentPartial(): bool
    {
        return $this->paymentStatus === 'partial';
    }

    public function getFormattedSellingPrice(): string
    {
        return '₵' . number_format($this->sellingPrice, 2);
    }

    public function getFormattedCostPrice(): string
    {
        return '₵' . number_format($this->costPrice, 2);
    }

    public function getFormattedProfit(): string
    {
        return '₵' . number_format($this->profit, 2);
    }

    public function getFormattedPaymentAmount(): string
    {
        return $this->paymentAmount ? '₵' . number_format($this->paymentAmount, 2) : 'N/A';
    }

    public function getFormattedPaidAmount(): string
    {
        return $this->paidAmount ? '₵' . number_format($this->paidAmount, 2) : 'N/A';
    }

    public function getFormattedDueAmount(): string
    {
        return $this->dueAmount ? '₵' . number_format($this->dueAmount, 2) : 'N/A';
    }

    public function getStatusBadgeColor(): string
    {
        return match($this->transactionStatus) {
            'completed' => 'bg-green-100 text-green-800',
            'pending' => 'bg-yellow-100 text-yellow-800',
            'in_progress' => 'bg-blue-100 text-blue-800',
            'cancelled' => 'bg-red-100 text-red-800',
            'refunded' => 'bg-purple-100 text-purple-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    public function getPaymentStatusBadgeColor(): string
    {
        return match($this->paymentStatus) {
            'completed' => 'bg-green-100 text-green-800',
            'pending' => 'bg-yellow-100 text-yellow-800',
            'partial' => 'bg-orange-100 text-orange-800',
            'failed' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }
}
