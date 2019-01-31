<?php

namespace Smoetje\Proggenerator\models;

use Illuminate\Database\Eloquent\Model;

class GroupModel extends Model
{
    protected $table = 'groups';
    // protected $guarded = ['id', 'update_at', 'created_at'];
    protected $guarded = ['id'];
    protected $fillable = [
                            'groepsnaam',
                            'groepscode',
                            'genre',
                            'subgenre',
                            'omschrijvingkort',
                            'omschrijvinglang',
                            'url'
                          ];

    protected $hidden = ['created_at', 'updated_at'];

    // 1 group kan meerdere malen tijdens de feesten worden geprogrammeerd
    // group = 1
    // programmatie = many
    public function programmatie(){
        return $this->hasMany('App\ProgrammatieModel', 'group_id', 'id');
    }

    // 1 group heeft veel foto's
    // Group = 1
    // Fotos = many
    public function foto(){
        return $this->hasMany('App\FotoModel', 'groepscode_fk', 'groepscode');
    }

    // 1 group heeft veel foto's
    // Group = 1
    // Fotos = many
    public function video(){
        return $this->hasMany('App\VideoModel', 'groepscode_fk', 'groepscode');
    }


}
