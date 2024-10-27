<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class type extends Model
{
    use HasFactory;
   protected $table = 'types';
    protected $primaryKey ='id';
    public $timestamps = true;
    protected $fillable = [
        'type_name',
    ];

    public function user(){
        return $this->hasmany(User::class,'type_id');
    }
}
