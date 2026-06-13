<?php

namespace App\Http\Controllers\Reservation;

use App\Actions\Reservation\InviteParticipantsAction;
use App\Data\Reservation\InviteParticipantsData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reservation\InviteParticipantsRequest;
use App\Http\Resources\ReservationParticipantResource;
use App\Models\Reservation;
use App\Models\ReservationParticipant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ParticipantController extends Controller
{
    public function store(InviteParticipantsRequest $request, Reservation $reservation, InviteParticipantsAction $action): JsonResponse
    {
        $participants = $action->handle(
            $reservation,
            InviteParticipantsData::from($request->validated()),
            $request->user(),
        );

        return ReservationParticipantResource::collection($participants)
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function destroy(Reservation $reservation, ReservationParticipant $participant): Response
    {
        $participant->delete();

        return response()->noContent();
    }
}
