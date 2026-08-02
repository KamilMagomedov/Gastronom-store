<div class="space-y-6 p-6 bg-gray-50 rounded-lg shadow-inner">
    <!-- Cart Info -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
        <h3 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4">Информация о корзине #{{ $cart->id }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-y-4 gap-x-6">
            <div>
                <p class="text-sm font-medium text-gray-500">ID корзины</p>
                <p class="text-lg font-semibold text-gray-900">#{{ $cart->id }}</p>
            </div>
            
            <div>
                <p class="text-sm font-medium text-gray-500">Пользователь</p>
                <p class="text-lg font-semibold text-gray-900">{{ $cart->customer?->email ?? 'Гость' }}</p>
            </div>
            
            <div>
                <p class="text-sm font-medium text-gray-500">Истекает</p>
                <p class="text-lg font-semibold {{ $cart->expires_at->isPast() ? 'text-red-600' : 'text-gray-900' }}">
                    {{ $cart->expires_at->format('d.m.Y H:i') }}
                </p>
            </div>
            
            @if($cart->session_id)
            <div class="lg:col-span-2">
                <p class="text-sm font-medium text-gray-500">ID сессии</p>
                <p class="text-sm font-mono bg-gray-100 px-2 py-1 rounded text-gray-700">{{ $cart->session_id }}</p>
            </div>
            @endif
            
            <div>
                <p class="text-sm font-medium text-gray-500">Товаров</p>
                <p class="text-lg font-semibold text-gray-900">{{ $cart->total_items }} шт.</p>
            </div>
            
            <div>
                <p class="text-sm font-medium text-gray-500">Общая сумма</p>
                <p class="text-2xl font-bold text-emerald-600">{{ number_format($cart->total_amount, 2, ',', ' ') }} ₽</p>
            </div>
        </div>
    </div>

    <!-- Cart Items Table -->
    @if($items->isNotEmpty())
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-slate-50 to-gray-100 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Товары в корзине</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                Товар
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                Артикул
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                Категория
                            </th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                Кол-во
                            </th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                Цена
                            </th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                Сумма
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-50">
                        @foreach($items as $item)
                            <tr class="hover:bg-slate-50 transition-colors duration-200">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-blue-600 hover:text-blue-800 transition-colors">
                                        <a href="{{ route('filament.admin.resources.products.edit', $item->product) }}" target="_blank" class="hover:underline">
                                            {{ $item->product->name }}
                                        </a>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200">
                                        {{ $item->product->sku }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    @if($item->product->category)
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200">
                                            {{ $item->product->category->name }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-indigo-100 text-indigo-800 font-semibold text-sm border border-indigo-200">
                                        {{ $item->quantity }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-medium text-gray-900">
                                    {{ number_format($item->price, 2, ',', ' ') }} ₽
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        {{ number_format($item->total, 2, ',', ' ') }} ₽
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gradient-to-r from-emerald-50 to-teal-50 border-t-2 border-emerald-200">
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-right">
                                <span class="text-lg font-bold text-gray-700">Итого:</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-2xl font-bold text-emerald-700">
                                    {{ number_format($cart->total_amount, 2, ',', ' ') }} ₽
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @else
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-slate-50 to-gray-100 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Товары в корзине</h3>
            </div>
            <div class="text-center py-16 px-6">
                <h3 class="text-xl font-semibold text-gray-700 mb-3">Корзина пуста</h3>
                <p class="text-gray-500 max-w-md mx-auto">В этой корзине пока нет товаров. Когда пользователь добавит товары, они появятся здесь.</p>
            </div>
        </div>
    @endif
</div>
