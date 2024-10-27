<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group_member extends Model
{
    use HasFactory;
    protected $table = 'group_members';
    protected $primaryKey ='id';
    public $timestamps = true;
    protected $fillable = [
        'user_id',
        'group_id',
    ];

    public function user(){
        return $this->belongsTo(User::class,'user_id');
    }
    public function group(){
        return $this->belongsTo(Group::class,'group_id');
    }

}
