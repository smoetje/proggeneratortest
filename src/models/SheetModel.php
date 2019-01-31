<?php

namespace Smoetje\Proggenerator\models;

use Illuminate\Database\Eloquent\Model;

class SheetModel extends Model
{
    protected $table = 'sheets';
    protected $fillable = [
        'id',
        'editie_jaar',
        'sheet_kind', // afhankelijk van sheet-kind, kan het juiste object worden geinstanieerd (googlesheet, excel of local)
        'sheet_url',
        'sheet_status'
    ];
}
