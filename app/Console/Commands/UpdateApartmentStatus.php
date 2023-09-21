<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Payment;
use App\Services\Billing\PaymentGateway;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class UpdateApartmentStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apartment:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update apartment status if bookings are expired';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $now = now();
        $bookings = Booking::whereDate('departure_date', '<', $now)->get();
        foreach ($bookings as $booking){
            $booking->setApartmentAvailable();
        }

        return CommandAlias::SUCCESS;
    }
}
