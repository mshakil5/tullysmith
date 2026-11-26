<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AttendanceSetting extends Model {
 protected $guarded=[];
 protected $casts=['settings'=>'array'];
 public static function values(): array {
  return array_replace(['start'=>null,'finish'=>null,'days'=>[1,2,3,4,5],'clock_in'=>false,'clock_out'=>false,'before'=>10,'repeat'=>30,'max_hours'=>12],static::find(1)?->settings ?? []);
 }
}
