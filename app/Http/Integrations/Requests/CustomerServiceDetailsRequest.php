<?php

namespace App\Http\Integrations\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Contracts\Body\HasBody;
use Saloon\Traits\Body\HasJsonBody;

class CustomerServiceDetailsRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected array $data,
        protected string $token
    ) {}

    public function resolveEndpoint(): string
    {
        return '/customer-service-details';
    }

    protected function defaultHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
        ];
    }

    protected function defaultBody(): array
    {
        $jsonData = [];

        // ✅ Text fields
        $fields = [
            'full_name', 'phone_number', 'location',
            'NOK_name', 'NOK_phone', 'Business_id',
            'email', 'Alternate_phone_number', 'SIM_serial_number',
            'Remarks', 'Reason_for_Action', 'Ticket_Number',
            'Status', 'Handled_by'
        ];

        foreach ($fields as $field) {
            if (!empty($this->data[$field])) {
                $jsonData[$field] = (string) $this->data[$field];
            }
        }

        // ✅ Boolean flags
        $booleanFields = [
            'MyMTNApp_Activation_Status',
            'MomoApp_Activation_Status',
            'ADS_Activation_Status',
            'RGT_Activation_Status',
            'is_active'
        ];

        foreach ($booleanFields as $field) {
            if (isset($this->data[$field])) {
                $jsonData[$field] = $this->data[$field];
            }
        }

        \Log::info('JSON body created', [
            'data_keys' => array_keys($this->data),
            'json_keys' => array_keys($jsonData),
            'json_data' => $jsonData
        ]);

        return $jsonData;
    }
}

