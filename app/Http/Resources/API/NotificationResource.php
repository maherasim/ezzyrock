<?php

namespace App\Http\Resources\API;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $image = '';
        $data = is_array($this->data) ? $this->data : [];
        $bookingId = $data['id'] ?? $data['booking_id'] ?? null;

        if (!empty($bookingId) && ($booking = Booking::find($bookingId))) {
            $user = User::where('id',$booking->customer_id)->first();
            if (!empty($user)) {
                $image = $user->login_type != null ? $user->social_image: getSingleMedia($user, 'profile_image',null);
            }
        }

        return [
            'id' => $this->id,
            'read_at' => $this->read_at,
            'profile_image'     => $image,
            'created_at' => timeAgoFormate($this->created_at),
            'data' => $data,
        ];
    }
}
