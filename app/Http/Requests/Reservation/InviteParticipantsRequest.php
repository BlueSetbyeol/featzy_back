<?php

namespace App\Http\Requests\Reservation;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InviteParticipantsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only the organizer may invite; enforced by the can:invite,reservation
        // middleware on the route.
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_ids' => ['array', 'required_without:friend_group_id'],
            // Scope the existence check past soft-deleted accounts so a trashed
            // user id can't be invited into a (wasted) participant slot.
            'user_ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'friend_group_id' => [
                'integer',
                'required_without:user_ids',
                // Scope to the inviter's own groups so foreign groups 422 rather
                // than leaking their existence.
                Rule::exists('friend_groups', 'id')->where('owner_id', $this->user()->id),
            ],
        ];
    }
}
