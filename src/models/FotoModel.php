<?php

namespace Smoetje\Proggenerator\models;

use Illuminate\Database\Eloquent\Model;

class FotoModel extends Model
{
    protected $table = 'fotos';
    protected $guarded = ['id', 'update_at', 'created_at'];
//    protected $fillable = [
//                            'url',
//                            'height',
//                            'groepsnaam_fk',
//                            'group_id'
//                          ];
//
//    protected $hidden = ['id', 'groepscode_fk', 'group_id', 'created_at', 'updated_at'];

    // 1 group heeft veel foto's
    // Group = 1
    // Fotos = many
    public function group()
    {
        return $this->belongsTo('App\GroupModel');
    }
}
