<?php

namespace App\Models;

use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\Exceptions\PaymentException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

class Acquirer extends Model
{
    /** @use HasFactory<\Database\Factories\AcquirerFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'config',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function scopeSorted($query)
    {
        return $query->orderBy('sort_order');
    }

    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function gateway(): PaymentGateway
    {
        $className = $this->resolveGatewayClass();

        if (! class_exists($className)) {
            throw new PaymentException("Gateway class '{$className}' not found for acquirer '{$this->code}'");
        }

        return App::make($className, ['config' => $this->config ?? []]);
    }

    private function resolveGatewayClass(): string
    {
        $map = [
            'sberbank' => \App\Services\Payment\Gateways\SberbankGateway::class,
            'tinkoff' => \App\Services\Payment\Gateways\TinkoffGateway::class,
            'vtb' => \App\Services\Payment\Gateways\VtbGateway::class,
            'alfa' => \App\Services\Payment\Gateways\AlfaGateway::class,
            'psb' => \App\Services\Payment\Gateways\PsbGateway::class,
            'gazprom' => \App\Services\Payment\Gateways\GazpromGateway::class,
        ];

        return $map[$this->code] ?? throw new PaymentException("Unknown acquirer code: '{$this->code}'");
    }
}
