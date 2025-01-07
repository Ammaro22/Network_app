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
        'change',
        'file_old_name',
        'file_new_name',
        'user_name',
        'date_checkin',
        'file_old_id'
    ];

    public function file()
    {
        return $this->belongsTo(File::class, 'file_id');
    }

    public function fileold()
    {
        return $this->belongsTo(Fileold::class, 'file_old_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
