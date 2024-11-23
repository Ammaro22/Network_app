<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;
    protected $table = 'groups';
    protected $primaryKey ='id';
    public $timestamps = true;
    protected $fillable = [
        'user_id',
        'name'

    ];
    public function user(){
        return $this->belongsTo(User::class,'user_id');
    }
    public function group_member(){
        return $this->hasmany(Group_member::class,'group_id');
    }
    public function file_group(){
        return $this->hasmany(File_group::class,'group_id');
    }
    public function request(){
        return $this->hasmany(Request::class,'group_id');
    }
    public function file_old(){
        return $this->hasmany(Fileold::class,'group_id');
    }

}
