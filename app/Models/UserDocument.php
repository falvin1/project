<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDocument extends Model
{
    use HasFactory;
    protected $fillable = [       
    'user_id',
    'file_name',
    'drive_file_id',
    'content', 
    'pdf_path'];
}
