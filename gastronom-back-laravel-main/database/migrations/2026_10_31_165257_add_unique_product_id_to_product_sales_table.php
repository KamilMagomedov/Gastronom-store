<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('product_sales')
            ->select('product_id')
            ->selectRaw('SUM(total_quantity) as total_quantity')
            ->selectRaw('SUM(total_revenue) as total_revenue')
            ->selectRaw('MAX(updated_at) as updated_at')
            ->groupBy('product_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $rowIds = DB::table('product_sales')
                ->where('product_id', $duplicate->product_id)
                ->orderBy('id')
                ->pluck('id');

            $keepId = $rowIds->shift();

            DB::table('product_sales')
                ->where('id', $keepId)
                ->update([
                    'total_quantity' => (int) $duplicate->total_quantity,
                    'total_revenue' => $duplicate->total_revenue,
                    'updated_at' => $duplicate->updated_at ?? now(),
                ]);

            if ($rowIds->isNotEmpty()) {
                DB::table('product_sales')
                    ->whereIn('id', $rowIds)
                    ->delete();
            }
        }

        Schema::table('product_sales', function (Blueprint $table) {
            $table->unique('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('product_sales', function (Blueprint $table) {
            $table->dropUnique(['product_id']);
        });
    }
};