<?php

namespace Smoetje\Proggenerator\models;

use Illuminate\Database\Eloquent\Model;

class ProgrammatieModel extends Model
{
    protected $table = 'programmaties';
    //protected $guarded = ['id', 'update_at', 'created_at'];
    protected $guarded = ['id'];
    protected $fillable = [
                            'dagnr',
                            'datum',
                            'uur',
                            'chronologie',
                            'locatie_id',
                            'groepscode_fk',
                            'group_id'
                          ];
    public $timestamps = true;
    protected $hidden = ['id', 'locatie_id', 'created_at', 'updated_at'];

    // 1 group kan meerdere malen tijdens de feesten worden geprogrammeerd
    // group = 1
    // programmatie = many
    public function groups(){
        return $this->belongsTo('App\GroupModel');
    }

    // Op 1 locatie kunnen er veel programmaties zijn...
    public function locatie(){
        return $this->belongsTo('App\LocatieModel');
    }

}
