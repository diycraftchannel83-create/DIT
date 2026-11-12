<?php

namespace App\Models;

class AcademicUser extends AcademicModel
{
    protected $table = 'users';

    protected $hidden = ['password_hash'];
}
