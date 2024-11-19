<?php

namespace App\Models;

use App\Traits\Imageable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory,Imageable;
    protected $table = 'files';
    protected $primaryKey ='id';
    public $timestamps = true;
    protected $fillable = [
        'name',
        'path',
        'state'
    ];
    public function file_group(){
        return $this->hasmany(File_group::class,'file_id');
    }
    public function check()
    {
        return $this->hasMany(Check::class, 'file_id');
    }

    public function change()
    {
        return $this->hasMany(Change::class, 'file_id');
    }


}
