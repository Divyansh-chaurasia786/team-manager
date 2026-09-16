<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskUpdate extends Model
{
    protected $fillable = ['task_id', 'message'];
    public function task() { return $this->belongsTo(Task::class); }
}
