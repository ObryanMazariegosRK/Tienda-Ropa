<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BannerModel extends Model
{
    protected $table = 'banners';

    protected $fillable = ['banner_group_id', 'media_url', 'display_order'];

    public function group()
    {
        return $this->belongsTo(BannerGroupModel::class, 'banner_group_id');
    }
}