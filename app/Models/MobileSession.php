<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MobileSession extends Model
{
    protected $fillable = ['session_id','connected_account_id','session_token','expires_at'];
    public function account() {
        return $this->belongsTo(ConnectedAccount::class, 'connected_account_id');
    }
}
