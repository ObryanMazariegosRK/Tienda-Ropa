<?php
namespace Database\Factories;

use App\Domain\Enum\AuctionStatus;
use App\Domain\Enum\ProductSaleType;
use App\Domain\Enum\ProductStatus;
use App\Models\AuctionModel;
use App\Models\Category;
use App\Models\ProductModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = ProductModel::class;

    // Cache estático para no repetir la query 200 veces durante el seeding
    protected static ?array $leafCategoryIds = null;

    protected static function leafCategoryIds(): array
    {
        if (self::$leafCategoryIds === null) {
            self::$leafCategoryIds = Category::whereNotIn('id', function ($query) {
                $query->select('parent_category_id')
                    ->from('categories')
                    ->whereNotNull('parent_category_id');
            })
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();
        }

        return self::$leafCategoryIds;
    }

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);
        $price = fake()->randomFloat(2, 50, 800);

        return [
            'category_id'  => fake()->randomElement(self::leafCategoryIds()),
            'name'         => ucfirst($name),
            'slug'         => Str::slug($name) . '-' . fake()->unique()->numberBetween(1000, 9999),
            'description'  => fake()->paragraphs(2, true),
            'price'        => $price,
            'offer_price'  => null,
            'sale_type'    => ProductSaleType::DIRECT,
            'status'       => ProductStatus::AVAILABLE,
        ];
    }

    public function onOffer(): static
    {
        return $this->state(function (array $attributes) {
            $price = $attributes['price'];
            return [
                'sale_type'   => ProductSaleType::DIRECT,
                'offer_price' => round($price * fake()->randomFloat(2, 0.5, 0.9), 2),
            ];
        });
    }

    public function auction(): static
    {
        return $this->state(fn (array $attributes) => [
            'sale_type'   => ProductSaleType::AUCTION,
            'offer_price' => null,
        ])->afterCreating(function (ProductModel $product) {
            $startingPrice = $product->price;
            $startDate = fake()->dateTimeBetween('-2 days', 'now');
            $endDate = fake()->dateTimeBetween('+1 day', '+2 weeks');

            AuctionModel::create([
                'product_id'             => $product->id,
                'starting_price'         => $startingPrice,
                'current_price'          => $startingPrice,
                'min_increment'          => round($startingPrice * 0.05, 2),
                'start_date'             => $startDate,
                'end_date'               => $endDate,
                'status'                 => AuctionStatus::ACTIVE,
                'current_winner_user_id' => null,
                'winner_user_id'         => null,
                'order_id'               => null,
            ]);
        });
    }
}