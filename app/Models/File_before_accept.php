<?php

namespace App\Models;

use App\Traits\Imageable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File_before_accept extends Model
{
    use HasFactory,Imageable;
    protected $table = 'file_before_accepts';
    protected $primaryKey ='id';
    public $timestamps = true;
    protected $fillable = [
        'request_id',
        'name',
        'path',
        'state'
    ];

    public function request(){
        return $this->belongsTo(Request::class,'request_id');
    }
}
