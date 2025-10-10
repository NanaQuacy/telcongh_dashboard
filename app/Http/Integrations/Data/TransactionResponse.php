<?php

namespace App\Http\Integrations\Data;

use Saloon\Contracts\Response;
use Saloon\Traits\Responses\HasResponseHelpers;

class TransactionResponse
{
    use HasResponseHelpers;

    public function __construct(
        protected Response $response
    ) {}

    /**
     * Get the transaction data
     */
    public function transaction(): ?array
    {
        $data = $this->response->json();
        
        return $data['transaction'] ?? $data['data']['transaction'] ?? $data;
    }

    /**
     * Get the payment data
     */
    public function payment(): ?array
    {
        $data = $this->response->json();
        
        return $data['payment'] ?? $data['data']['payment'] ?? null;
    }

    /**
     * Get the customer details data
     */
    public function customerDetails(): ?array
    {
        $data = $this->response->json();
        
        return $data['customer_details'] ?? $data['data']['customer_details'] ?? null;
    }

    /**
     * Get the transaction ID
     */
    public function transactionId(): ?int
    {
        $transaction = $this->transaction();
        
        return $transaction['id'] ?? $transaction['transaction_id'] ?? null;
    }

    /**
     * Get the payment ID
     */
    public function paymentId(): ?int
    {
        $payment = $this->payment();
        
        return $payment['id'] ?? $payment['payment_id'] ?? null;
    }

    /**
     * Get the customer service details ID
     */
    public function customerServiceDetailsId(): ?int
    {
        $customerDetails = $this->customerDetails();
        
        return $customerDetails['id'] ?? $customerDetails['customer_service_details_id'] ?? null;
    }

    /**
     * Get the transaction status
     */
    public function transactionStatus(): ?string
    {
        $transaction = $this->transaction();
        
        return $transaction['transaction_status'] ?? $transaction['status'] ?? null;
    }

    /**
     * Get the payment status
     */
    public function paymentStatus(): ?string
    {
        $payment = $this->payment();
        
        return $payment['payment_status'] ?? $payment['status'] ?? null;
    }

    /**
     * Get the selling price
     */
    public function sellingPrice(): ?float
    {
        $transaction = $this->transaction();
        
        return $transaction['selling_price'] ?? null;
    }

    /**
     * Get the cost price
     */
    public function costPrice(): ?float
    {
        $transaction = $this->transaction();
        
        return $transaction['cost_price'] ?? null;
    }

    /**
     * Get the profit
     */
    public function profit(): ?float
    {
        $transaction = $this->transaction();
        
        return $transaction['profit'] ?? null;
    }

    /**
     * Get the payment amount
     */
    public function paymentAmount(): ?float
    {
        $payment = $this->payment();
        
        return $payment['payment_amount'] ?? $payment['amount'] ?? null;
    }

    /**
     * Get the paid amount
     */
    public function paidAmount(): ?float
    {
        $payment = $this->payment();
        
        return $payment['paid_amount'] ?? null;
    }

    /**
     * Get the due amount
     */
    public function dueAmount(): ?float
    {
        $payment = $this->payment();
        
        return $payment['due_amount'] ?? null;
    }

    /**
     * Get the payment method
     */
    public function paymentMethod(): ?string
    {
        $payment = $this->payment();
        
        return $payment['payment_method'] ?? null;
    }

    /**
     * Get the customer full name
     */
    public function customerFullName(): ?string
    {
        $customerDetails = $this->customerDetails();
        
        return $customerDetails['full_name'] ?? null;
    }

    /**
     * Get the customer phone number
     */
    public function customerPhoneNumber(): ?string
    {
        $customerDetails = $this->customerDetails();
        
        return $customerDetails['phone_number'] ?? null;
    }

    /**
     * Get the customer email
     */
    public function customerEmail(): ?string
    {
        $customerDetails = $this->customerDetails();
        
        return $customerDetails['email'] ?? null;
    }

    /**
     * Get the customer location
     */
    public function customerLocation(): ?string
    {
        $customerDetails = $this->customerDetails();
        
        return $customerDetails['location'] ?? null;
    }

    /**
     * Get all data as array
     */
    public function toArray(): array
    {
        return [
            'transaction' => $this->transaction(),
            'payment' => $this->payment(),
            'customer_details' => $this->customerDetails(),
            'transaction_id' => $this->transactionId(),
            'payment_id' => $this->paymentId(),
            'customer_service_details_id' => $this->customerServiceDetailsId(),
        ];
    }

    /**
     * Check if transaction was successful
     */
    public function isSuccessful(): bool
    {
        return $this->response->successful() && 
               $this->transactionStatus() === 'completed' && 
               $this->paymentStatus() === 'completed';
    }

    /**
     * Check if payment is pending
     */
    public function isPaymentPending(): bool
    {
        return $this->paymentStatus() === 'pending';
    }

    /**
     * Check if payment is partial
     */
    public function isPaymentPartial(): bool
    {
        return $this->paymentStatus() === 'partial';
    }

    /**
     * Get the raw response
     */
    public function getResponse(): Response
    {
        return $this->response;
    }
}
