<?php

namespace App\Http\Controllers\Reservation;

use App\Actions\Reservation\RespondToInvitationAction;
use App\Enums\InvitationStatus;
use App\Enums\ParticipantRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reservation\RsvpRequest;
use App\Http\Resources\ReservationParticipantResource;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InvitationController extends Controller
{
    /**
     * List the reservations the authenticated user has been invited to as a guest.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $invitations = $request->user()->participations()
            ->where('role', ParticipantRole::Guest->value)
            ->with('reservation.restaurant')
            ->latest()
            ->paginate();

        return ReservationParticipantResource::collection($invitations);
    }

    public function rsvp(RsvpRequest $request, Reservation $reservation, RespondToInvitationAction $action): ReservationParticipantResource
    {
        $participant = $action->handle(
            $reservation,
            $request->user(),
            InvitationStatus::from($request->validated('status')),
        );

        return ReservationParticipantResource::make($participant);
    }
}
