<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'file_path',
        'file_name',
        'type' // 'contract' or 'payment_proof'
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
}
