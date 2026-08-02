# 1C Integration Documentation

## Overview
This document describes the integration between 1C and the Laravel application for product synchronization.

## Authentication
The 1C integration uses HTTP Basic Authentication.

### Credentials
- **Username**: `1c@integration.local`
- **Password**: `1C_Secret_Password_2024`

## API Endpoints

### Base URL
```
https://your-domain.com/integration/1c/exchange
```

### 1C Exchange Protocol

#### 1. Check Authentication
```
GET /integration/1c/exchange?type=catalog&mode=checkauth
```

**Response:**
```
success
SESSION_ID
session123
```

#### 2. Initialize
```
GET /integration/1c/exchange?type=catalog&mode=init
```

**Response:**
```
zip=no
file_limit=10485760
```

#### 3. Upload File
```
POST /integration/1c/exchange?type=catalog&mode=file&filename=import.xml
```
- File content is sent in the request body
- Files are saved to `storage/app/onec/` directory

#### 4. Start Import
```
GET /integration/1c/exchange?type=catalog&mode=import&filename=import.xml
```

**Response:**
```
success
```

## XML Format

### Product Import Structure
```xml
<?xml version="1.0" encoding="UTF-8"?>
<КоммерческаяИнформация>
    <Каталог>
        <Товары>
            <Товар>
                <Ид>unique-product-id</Ид>
                <Артикул>ART-001</Артикул>
                <Наименование>Product Name</Наименование>
                <Описание>Product Description</Описание>
                <Цены>
                    <Цена>
                        <ЦенаЗаЕдиницу>1000.50</ЦенаЗаЕдиницу>
                    </Цена>
                </Цены>
                <Количество>50</Количество>
                <БазоваяЕдиница>шт</БазоваяЕдиница>
                <Картинка>path/to/image1.jpg</Картинка>
                <Картинка>path/to/image2.jpg</Картинка>
            </Товар>
        </Товары>
    </Каталог>
</КоммерческаяИнформация>
```

## Field Mapping

| 1C Field | Laravel Field | Description |
|-----------|----------------|-------------|
| Ид | external_id | Unique product identifier from 1C |
| Артикул | sku | Product article/code |
| Наименование | name | Product name |
| Описание | description | Product description |
| ЦенаЗаЕдиницу | price | Product price |
| Количество | stock_quantity | Stock quantity |
| БазоваяЕдиница | unit | Unit of measurement |
| Картинка | - | Product images (processed separately) |

## Synchronization Logs

All synchronization operations are logged to the `sync_logs` table with:
- `source`: '1C'
- `entity_type`: 'products'
- `operation`: operation type (import_start, import_complete, etc.)
- `status`: success/error
- `message`: operation details
- `data`: additional JSON data

## Error Handling

- Failed imports are logged with error details
- Images are processed asynchronously via jobs
- Duplicate products are updated instead of created

## Notes

- Products are matched by `external_id`
- If a product exists, it's updated; otherwise, a new one is created
- `in_stock` field is automatically calculated based on `stock_quantity`
- Product slugs are generated automatically from name and external_id
