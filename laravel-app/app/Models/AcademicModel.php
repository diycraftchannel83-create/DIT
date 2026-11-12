<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class AcademicModel extends Model
{
    protected $connection = 'academic';

    public $timestamps = false;

    protected $guarded = [];
}
