<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SourceNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_id',
        'note',
        'added_by',
        'date_added'
    ];

    protected $casts = [
        'date_added' => 'datetime',
    ];

    public function source()
    {
        return $this->belongsTo(Source::class);
    }

    public function employees()
    {
        return $this->belongsTo(Employee::class, 'added_by');
    }
}
