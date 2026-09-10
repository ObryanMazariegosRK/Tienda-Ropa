<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AuctionDeclineModel extends Model
{
    protected $table = 'auction_declines';
    protected $fillable = ['auction_id', 'user_id'];
}