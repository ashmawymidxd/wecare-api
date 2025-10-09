<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InquiriesTimeLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'stepOne',
        'stepTwo', // Note: Fixed typo from 'stepTow' to 'stepTwo'
        'stepThree',
        'inquirie_id'
    ];

    /**
     * Get the inquiry that owns the timeline
     */
    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class, 'inquirie_id');
    }
}