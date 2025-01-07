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
        'group_id'
    ];

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }
    public function change(){
        return $this->hasmany(Change::class,'file_old_id');
    }
}
