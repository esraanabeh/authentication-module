<?php

namespace Modules\Authentication\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;


class Verification extends Model
{
    use HasFactory;
    protected $fillable = [
        'pin_code',
        'user_id',
        'type',
        'is_used'

    ];

    public function user()
    {
        return  $this->belongsTo(User::class, 'user_id', 'id');
    }

}
