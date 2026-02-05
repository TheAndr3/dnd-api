<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JoinCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'invitation_code' => 'required|string',
            'character_id' => 'required|exists:characters,id',
        ];
    }
}
