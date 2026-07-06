<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class LmsModel extends Model
{
    protected $guarded = ['id'];
}
