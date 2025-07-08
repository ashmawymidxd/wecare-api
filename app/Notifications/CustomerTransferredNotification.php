<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Employee;

class CustomerTransferredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $fromEmployee;
    public $toEmployee;
    public $transferredCount;

    /**
     * Create a new notification instance.
     */
    public function __construct(Employee $fromEmployee, Employee $toEmployee, int $transferredCount)
    {
        $this->fromEmployee = $fromEmployee;
        $this->toEmployee = $toEmployee;
        $this->transferredCount = $transferredCount;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database']; // You can adjust channels as needed
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'from_employee_id' => $this->fromEmployee->id,
            'from_employee_name' => $this->fromEmployee->name,
            'to_employee_id' => $this->toEmployee->id,
            'to_employee_name' => $this->toEmployee->name,
            'transferred_count' => $this->transferredCount,
            'message' => "{$this->transferredCount} customers transferred from {$this->fromEmployee->name} to {$this->toEmployee->name}",
            'type' => 'customer.transferred'
        ];
    }
}
