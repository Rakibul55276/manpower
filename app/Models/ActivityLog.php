<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'action', 'subject'];
    public function user() { return $this->belongsTo(User::class); }
    public static function record($action, $subject) { return static::create(['user_id' => auth()->id(), 'action' => $action, 'subject' => $subject]); }
}
