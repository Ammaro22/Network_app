<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Change extends Model
{
    use HasFactory;
    protected $table = 'changes';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'file_id',
        'old_value',
        'new_value',
        'field_name',
        'user_id',
        'user_name'
    ];
    public function file()
    {
        return $this->belongsTo(File::class, 'file_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
