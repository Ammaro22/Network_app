<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileModification extends Model
{
    protected $fillable = ['file_id', 'user_id', 'changes'];
}
