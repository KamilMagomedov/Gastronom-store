<?php

namespace Tests\Feature\Api\V1;

use App\Enums\StaticPageType;
use App\Models\StaticPage;
use Database\Seeders\StaticPageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaticPageControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(StaticPageSeeder::class);
    }

    public function test_terms_page_returns_success_response(): void
    {
        $response = $this->getJson('/api/v1/pages/'.StaticPageType::TERMS->value);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'title',
                    'last_updated',
                    'sections' => [
                        '*' => [
                            'number',
                            'title',
                            'content',
                        ],
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'Условия использования',
                ],
            ]);
    }

    public function test_privacy_page_returns_success_response(): void
    {
        $response = $this->getJson('/api/v1/pages/privacy');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'title',
                    'last_updated',
                    'sections' => [
                        '*' => [
                            'number',
                            'title',
                            'content',
                        ],
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'Политика конфиденциальности',
                ],
            ]);
    }

    public function test_terms_page_contains_required_sections(): void
    {
        $response = $this->getJson('/api/v1/pages/terms');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(5, $data['sections']);

        $sectionTitles = array_column($data['sections'], 'title');
        $this->assertContains('Общие положения', $sectionTitles);
        $this->assertContains('Регистрация аккаунта', $sectionTitles);
        $this->assertContains('Доставка и оплата', $sectionTitles);
        $this->assertContains('Возврат товаров', $sectionTitles);
        $this->assertContains('Конфиденциальность', $sectionTitles);
    }

    public function test_privacy_page_contains_required_sections(): void
    {
        $response = $this->getJson('/api/v1/pages/privacy');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(7, $data['sections']);

        $sectionTitles = array_column($data['sections'], 'title');
        $this->assertContains('Сбор информации', $sectionTitles);
        $this->assertContains('Использование данных', $sectionTitles);
        $this->assertContains('Защита данных', $sectionTitles);
        $this->assertContains('Передача данных третьим лицам', $sectionTitles);
        $this->assertContains('Cookies и технологии отслеживания', $sectionTitles);
        $this->assertContains('Права пользователя', $sectionTitles);
        $this->assertContains('Хранение данных', $sectionTitles);
    }

    public function test_terms_page_contains_requirements_for_registration_section(): void
    {
        $response = $this->getJson('/api/v1/pages/terms');

        $response->assertStatus(200);
        $data = $response->json('data');

        $registrationSection = collect($data['sections'])->firstWhere('title', 'Регистрация аккаунта');

        $this->assertNotNull($registrationSection);
        $this->assertArrayHasKey('requirements', $registrationSection);
        $this->assertCount(2, $registrationSection['requirements']);
        $this->assertContains('Вам должно быть не менее 18 лет.', $registrationSection['requirements']);
        $this->assertContains('Вы обязаны предоставить достоверную информацию.', $registrationSection['requirements']);
    }

    public function test_terms_page_contains_important_note_for_delivery_section(): void
    {
        $response = $this->getJson('/api/v1/pages/terms');

        $response->assertStatus(200);
        $data = $response->json('data');

        $deliverySection = collect($data['sections'])->firstWhere('title', 'Доставка и оплата');

        $this->assertNotNull($deliverySection);
        $this->assertArrayHasKey('important_note', $deliverySection);
        $this->assertEquals(
            'Важно: Оплата производится через безопасный шлюз. Мы не храним полные данные ваших банковских карт.',
            $deliverySection['important_note']
        );
    }

    public function test_sections_are_ordered_by_number(): void
    {
        $response = $this->getJson('/api/v1/pages/terms');

        $response->assertStatus(200);
        $data = $response->json('data');

        $sectionNumbers = array_column($data['sections'], 'number');
        $sortedNumbers = $sectionNumbers;
        sort($sortedNumbers);

        $this->assertEquals($sortedNumbers, $sectionNumbers, 'Sections should be ordered by number');
    }

    public function test_inactive_page_returns_404(): void
    {
        $inactivePage = StaticPage::factory()->create([
            'slug' => 'inactive-test',
            'title' => 'Неактивная страница',
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/v1/pages/inactive-test');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Не найдено',
            ]);
    }

    public function test_invalid_page_slug_returns_404(): void
    {
        $response = $this->getJson('/api/v1/pages/invalid');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Не найдено',
            ]);
    }

    public function test_nonexistent_page_returns_404(): void
    {
        $response = $this->getJson('/api/v1/pages/nonexistent');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Не найдено',
            ]);
    }

    public function test_api_response_contains_correct_structure(): void
    {
        $response = $this->getJson('/api/v1/pages/terms');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ])
            ->assertJsonPath('success', true);
    }
}
