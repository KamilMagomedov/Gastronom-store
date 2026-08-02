<div style="max-width: 800px;">
    <div class="space-y-4">
        <div>
            <h3 class="text-lg font-semibold">Детали синхронизации</h3>
        </div>
        
        <div class="grid grid-cols-2 gap-4">
            <div>
                <span class="font-medium">Источник:</span>
                <span class="ml-2">{{ $record->source }}</span>
            </div>
            <div>
                <span class="font-medium">Тип сущности:</span>
                <span class="ml-2">
                    {{ match($record->entity_type) {
                        'products' => 'Товары',
                        'orders' => 'Заказы',
                        'customers' => 'Клиенты',
                        'categories' => 'Категории',
                        default => $record->entity_type,
                    } }}
                </span>
            </div>
            <div>
                <span class="font-medium">ID сущности:</span>
                <span class="ml-2">{{ $record->entity_id ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="font-medium">Операция:</span>
                <span class="ml-2">
                    {{ match($record->operation) {
                        'create' => 'Создание',
                        'update' => 'Обновление',
                        'delete' => 'Удаление',
                        'sync' => 'Синхронизация',
                        default => $record->operation,
                    } }}
                </span>
            </div>
            <div>
                <span class="font-medium">Статус:</span>
                <span class="ml-2">
                    @if($record->status === 'success')
                        <span class="text-green-600 font-medium">✓ Успешно</span>
                    @else
                        <span class="text-red-600 font-medium">✗ Ошибка</span>
                    @endif
                </span>
            </div>
            <div>
                <span class="font-medium">Дата:</span>
                <span class="ml-2">{{ $record->synced_at->format('d.m.Y H:i:s') }}</span>
            </div>
        </div>
        
        @if($record->message)
            <div>
                <span class="font-medium">Сообщение:</span>
                <div class="mt-2 p-3 bg-gray-100 rounded text-sm whitespace-pre-wrap">
                    {{ $record->message }}
                </div>
            </div>
        @endif
        
        @if($record->data)
            <div>
                <span class="font-medium">Данные синхронизации:</span>
                <div class="mt-2 p-3 bg-gray-100 rounded text-sm">
                    <pre>{{ json_encode($record->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
        @endif
    </div>
</div>
