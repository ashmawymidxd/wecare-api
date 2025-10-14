<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InquiryNote extends Model
{
    use HasFactory;

    protected $fillable = ['inquirie_id', 'note', 'note_date' , 'note_time'];

    protected $dates = ['note_date'];

    /**
     * Get the inquiry that owns the timeline
     */
    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class, 'inquirie_id');
    }
}
