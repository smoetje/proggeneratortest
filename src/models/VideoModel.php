<?php

namespace Smoetje\Proggenerator\models;

use Illuminate\Database\Eloquent\Model;

class VideoModel extends Model
{
    protected $table = 'videos';
    protected $guarded = ['id', 'update_at', 'created_at'];
    protected $fillable = [
        'url',
        'groepsnaam_fk',
        'group_id',
        'sheet_index'
    ];

    protected $hidden = ['id', 'groepscode_fk', 'group_id', 'created_at', 'updated_at'];

    public function programmatie()
    {
        return $this->belongsTo('App\ProgrammatieModel');
    }
}
