<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AuctionBidModel extends Model
{
    protected $table = 'auction_bids';
    protected $fillable = ['auction_id', 'user_id', 'amount'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id'); 
    }

    public function auction()
    {
        return $this->belongsTo(AuctionModel::class, 'auction_id');
    }
}