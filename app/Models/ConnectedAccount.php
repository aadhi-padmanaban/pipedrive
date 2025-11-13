<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConnectedAccount extends Model
{
    protected $fillable = ['stripe_user_id','access_token','refresh_token','scope','livemode','raw_response'];
}
