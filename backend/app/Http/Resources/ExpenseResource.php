<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount_original' => $this->amount_original,
            'currency' => $this->currency,
            'exchange_rate' => $this->exchange_rate,
            'amount_brl' => $this->amount_brl,
            'status' => $this->status,
            'failure_reason' => $this->failure_reason,
            'converted_at' => $this->converted_at,
            'created_at' => $this->created_at,
        ];
    }
}
