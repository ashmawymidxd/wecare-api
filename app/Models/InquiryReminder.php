<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InquiryReminder extends Model
{
    use HasFactory;

    protected $fillable = ['inquirie_id', 'note', 'reminder_type' , 'reminder_date'];

    protected $dates = ['reminder_date'];

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class, 'inquirie_id');
    }


}
