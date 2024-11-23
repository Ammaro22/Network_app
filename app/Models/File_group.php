<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File_group extends Model
{
    use HasFactory;
    protected $table = 'file_groups';
    protected $primaryKey ='id';
    public $timestamps = true;
    protected $fillable = [
        'file_id',
        'group_id'
    ];

    public function file(){
        return $this->belongsTo(File::class,'file_id');
    }

    public function group(){
        return $this->belongsTo(Group::class,'group_id');
    }

}
