<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BannerGroupModel extends Model
{
    protected $table = 'banner_groups';
    protected $fillable = ['name', 'type', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function media()
    {
        return $this->hasMany(BannerModel::class, 'banner_group_id')->orderBy('display_order');
    }
}