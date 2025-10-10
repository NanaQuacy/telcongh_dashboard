<?php

namespace App\Http\Integrations\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Contracts\Body\HasBody;
use Saloon\Traits\Body\HasMultipartBody;
use Saloon\Data\MultipartValue;

class TestCustomerServiceRequest extends Request implements HasBody
{
    use HasMultipartBody;

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
        $multipart = [];

        // Use actual form data instead of hardcoded values
        $fields = [
            'full_name', 'phone_number', 'location',
            'NOK_name', 'NOK_phone', 'Business_id',
            'email', 'Alternate_phone_number', 'SIM_serial_number',
            'Remarks', 'Reason_for_Action', 'Ticket_Number',
            'Status', 'Handled_by'
        ];

        foreach ($fields as $field) {
            if (!empty($this->data[$field])) {
                $multipart[] = new MultipartValue($field, (string) $this->data[$field]);
            }
        }

        // Add boolean fields
        $booleanFields = [
            'MyMTNApp_Activation_Status',
            'MomoApp_Activation_Status',
            'ADS_Activation_Status',
            'RGT_Activation_Status',
            'is_active'
        ];

        foreach ($booleanFields as $field) {
            if (isset($this->data[$field])) {
                $multipart[] = new MultipartValue($field, $this->data[$field] ? '1' : '0');
            }
        }

        \Log::info('Test multipart body with real data', [
            'count' => count($multipart),
            'items' => array_map(fn($item) => ['name' => $item->name, 'value' => $item->value, 'filename' => $item->filename], $multipart),
            'endpoint' => $this->resolveEndpoint(),
            'headers' => $this->defaultHeaders(),
            'token_preview' => substr($this->token, 0, 10) . '...',
            'data_keys' => array_keys($this->data)
        ]);

        return $multipart;
    }
}
