<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blacklist extends Model
{
    use HasFactory;

//    protected $connection = 'findchat';

    public $table = 'blacklists';

    protected $fillable = [
        'status',
        'sender_id',
        'receiver_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'receiver_id', 'firebase_uid');
    }


}
