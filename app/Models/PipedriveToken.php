<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PipedriveToken extends Model
{
    protected $fillable = [
        'company_id', 'access_token', 'refresh_token', 'expires_at'
    ];
}
