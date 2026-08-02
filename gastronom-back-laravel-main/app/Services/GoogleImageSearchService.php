<?php

namespace App\Services;

use App\Models\Product;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleImageSearchService
{
    protected string $apiKey;

    public function __construct(?string $key = null)
    {
        $this->apiKey = $key ?? config('services.serp_api.api_key');
    }

    public function searchAndDownloadImage(int $productId, string $productName): void
    {
        try {
            $product = Product::findOrFail($productId);

            $imageUrls = $this->searchGoogleImages($productName);

            if (empty($imageUrls)) {
                Log::warning("No image found for product: {$productName} (ID: {$productId})");

                return;
            }

            foreach ($imageUrls as $imageUrl) {
                $this->downloadAndAddImage($product, $imageUrl);
            }
        } catch (Exception $e) {
            Log::error("Error processing image for product {$productId}: ".$e->getMessage());

            return;
        }
    }

    private function searchGoogleImages(string $productName): array
    {
        try {
            $cleanQuery = $productName.' на белом фоне изолированный white background isolated';

            $response = Http::timeout(10)
                ->retry(2, 100)
                ->get('https://serpapi.com/search.json', [
                    'q' => $cleanQuery,
                    'engine' => 'google_images',
                    'api_key' => $this->apiKey,
                    'google_domain' => 'google.com',
                ])
                ->throw();

            $data = $response->json();

            return collect($data['images_results'] ?? [])
                ->take(5)
                ->pluck('original')
                ->toArray();

        } catch (ConnectionException $e) {
            Log::error('SerpApi Connection Error: '.$e->getMessage());
        } catch (RequestException $e) {
            Log::error('SerpApi API Error: '.$e->getCode().' - '.$e->getMessage());
        } catch (\Exception $e) {
            Log::error('SerpApi Unexpected Error: '.$e->getMessage());
        }

        return [];
    }

    private function downloadAndAddImage(Product $product, string $imageUrl): ?string
    {
        try {
            $imageContents = file_get_contents($imageUrl);

            if (empty($imageContents)) {
                Log::error("Failed to download image from: {$imageUrl}");

                return null;
            }

            $extension = $this->getImageExtension($imageUrl, $imageContents);
            $filename = Str::slug($product->name).'-'.time().'.'.$extension;

            $media = $product->addMediaFromString($imageContents)
                ->usingFileName($filename)
                ->toMediaCollection('images');

            Log::info("Successfully added image for product {$product->id}: {$media->getUrl()}");

            return $media->getUrl();

        } catch (Exception $e) {
            Log::error("Error adding image to product {$product->id}: ".$e->getMessage());

            return null;
        }
    }

    private function getImageExtension(string $url, string $contents): string
    {
        // Проверяем URL на расширение
        $urlExtension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
        if (in_array(strtolower($urlExtension), ['jpg', 'jpeg', 'png', 'webp'])) {
            return strtolower($urlExtension);
        }

        // Проверяем по сигнатуре файла
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_buffer($finfo, $contents);
        finfo_close($finfo);

        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg'
        };
    }
}
