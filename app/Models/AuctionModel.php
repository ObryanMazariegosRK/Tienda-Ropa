<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AuctionModel extends Model
{
    protected $table = 'auctions';
    protected $fillable = ['product_id', 'starting_price', 'current_price', 'min_increment', 'start_date', 'end_date', 'status', 'current_winner_user_id', 'winner_user_id', 'order_id'];

    public function bids()
    {
        return $this->hasMany(AuctionBidModel::class, 'auction_id')->orderByDesc('created_at');
    }

    public function product()
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }
}