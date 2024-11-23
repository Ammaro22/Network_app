<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fileold extends Model
{
    use HasFactory;
    protected $table = 'file_olds';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'name',
        'path',
    ];
    public function file_group()
    {
        return $this->hasMany(File_group::class, 'file_id');
    }
}
