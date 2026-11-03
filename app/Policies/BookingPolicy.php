<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        return $user->active && ($user->isAdmin() || (int) $booking->assigned_to === (int) $user->id);
    }

    public function update(User $user, Booking $booking): bool
    {
        return $this->view($user, $booking);
    }

    public function invoice(User $user, Booking $booking): bool
    {
        return $booking->source !== 'tour_request' && $this->view($user, $booking) && $user->mayInvoice();
    }

    public function payment(User $user, Booking $booking): bool
    {
        return $this->view($user, $booking) && $user->mayVerifyPayments();
    }
}
