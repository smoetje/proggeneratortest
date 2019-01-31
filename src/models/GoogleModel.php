<?php

namespace Smoetje\Proggenerator\models;

use Illuminate\Database\Eloquent\Model;

class GoogleModel extends Model
{
    protected $table = 'googletables';
    protected $fillable = [
        'id',
        'editie_jaar',
        'googlesheet_id',
        'googlesheet_status'
    ];

}
