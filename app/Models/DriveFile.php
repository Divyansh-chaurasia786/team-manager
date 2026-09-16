<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriveFile extends Model
{
    protected $fillable = ['uploaded_by', 'original_name', 'drive_file_id', 'drive_url', 'file_type', 'upload_date'];
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }
}
