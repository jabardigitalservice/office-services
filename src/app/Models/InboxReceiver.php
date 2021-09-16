<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InboxReceiver extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = "inbox_receiver";
}
