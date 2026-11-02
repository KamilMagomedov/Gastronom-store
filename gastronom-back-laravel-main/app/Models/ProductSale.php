<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class ProductSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'total_quantity',
        'total_revenue',
    ];

    protected $casts = [
        'total_quantity' => 'integer',
        'total_revenue' => 'decimal:2',
        'updated_at' => 'datetime',
    ];

    public $timestamps = false;

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public static function updateSales(
        int $productId,
        int $quantity,
        float $revenue
    ): void {
        $revenue = number_format(
            $revenue,
            2,
            '.',
            ''
        );

        $incrementExisting = static function () use (
            $productId,
            $quantity,
            $revenue
        ): int {
            return self::query()
                ->where('product_id', $productId)
                ->update([
                    'total_quantity' => DB::raw(
                        'total_quantity + '.(int) $quantity
                    ),
                    'total_revenue' => DB::raw(
                        'total_revenue + '.$revenue
                    ),
                    'updated_at' => now(),
                ]);
        };

        if ($incrementExisting() > 0) {
            return;
        }

        try {
            self::query()->create([
                'product_id' => $productId,
                'total_quantity' => $quantity,
                'total_revenue' => $revenue,
                'updated_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            $incrementExisting();
        }
    }
}
