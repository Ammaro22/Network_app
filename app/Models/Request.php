<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    use HasFactory;
    protected $table = 'requests';
    protected $primaryKey ='id';
    public $timestamps = true;
    protected $fillable = [
        'group_id',
    ];

    public function group(){
        return $this->belongsTo(Group::class,'group_id');
    }
    public function file_befor(){
        return $this->hasmany(File_before_accept::class,'request_id');
    }
}
