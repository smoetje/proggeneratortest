<?php

namespace Smoetje\Proggenerator\models;

use Illuminate\Database\Eloquent\Model;

class LocatieModel extends Model
{
    protected $table = 'locaties';
    protected $fillable = [
        'id',
        'name'
    ];

    protected $guarded = ['updated_at', 'created_at'];

    protected $hidden = ['id', 'updated_at', 'created_at'];

    // Op 1 locatie kunnen er veel programmaties zijn...
    public function programmatie(){
        return $this->hasMany('App\ProgrammatieModel', 'locatie_id', 'id');
    }
}
