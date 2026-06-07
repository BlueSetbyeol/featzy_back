<?php

namespace App\Http\Requests\Reservation;

use App\Enums\InvitationStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RsvpRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The invited guest is authorized by the can:rsvp,reservation middleware.
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in([InvitationStatus::Accepted->value, InvitationStatus::Declined->value]),
            ],
        ];
    }
}
