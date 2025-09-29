<?php

namespace App\Http\Integrations\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Contracts\Body\HasBody;
use Saloon\Traits\Body\HasJsonBody;

class CreateStockBatchRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function resolveEndpoint(): string
    {
        return "/stock/batches";
    }

    protected function defaultHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    protected function defaultBody(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'box_batch_number' => $this->boxBatchNumber,
            'starting_iccid' => $this->startingIccid,
            'ending_iccid' => $this->endingIccid,
            'quantity' => $this->quantity,
            'cost' => $this->cost,
            'business_id' => $this->businessId,
        ];
    }

    public function __construct(
        public string $token,
        public string $name,
        public string $description,
        public string $boxBatchNumber,
        public string $startingIccid,
        public string $endingIccid,
        public int $quantity,
        public float $cost,
        public int $businessId
    ) {
        //
    }
}
