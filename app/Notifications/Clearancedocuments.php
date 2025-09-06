<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class Clearancedocuments extends Notification implements ShouldQueue
{
    use Queueable;

    public $contract;

    public function __construct(Contract $contract)
    {
        $this->contract = $contract;
    }

    public function via($notifiable)
    {
        return ['database']; // You can add other channels like mail, slack, etc.
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => 'Reminder: Clearance documents (Contract #'.$this->contract->contract_number.') for client '.$this->contract->customer->name.' are still pending. Please upload them to proceed',
            'contract_id' => $this->contract->id,
            'action_url' => '/contracts/'.$this->contract->id,
            'type'=>'info',
        ];
    }

}
