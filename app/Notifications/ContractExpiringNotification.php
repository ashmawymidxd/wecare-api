<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $contractNumber,
        public string $customerName,
        public int $daysUntilExpiry,
        public string $expiryDate
    ) {}

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'Contract Expiring Soon',
            'message' => "Contract {$this->contractNumber} with {$this->customerName} expires in {$this->daysUntilExpiry} days (on {$this->expiryDate}).",
            'contract_number' => $this->contractNumber,
            'expiry_date' => $this->expiryDate,
            'action_url' => "/contracts/{$this->contractNumber}",
            'type'=>'info'
        ];
    }
}
