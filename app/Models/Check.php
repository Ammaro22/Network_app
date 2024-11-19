<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Check extends Model
{
    use HasFactory;

    protected $table = 'checks';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'file_id',
        'type_check',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function file()
    {
        return $this->belongsTo(File::class, 'file_id');
    }
}
