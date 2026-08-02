<?php

namespace App\Services\StaticPage\Strategies;

use App\Models\StaticPage;
use App\Models\StaticPageSection;
use App\Models\StaticPageSectionRequirement;
use App\Services\StaticPage\StaticPageStrategyInterface;

class StaticStrategy implements StaticPageStrategyInterface
{
    private array $pages = [
        'terms' => [
            'title' => 'Условия использования',
            'last_updated' => '15 октября 2023',
            'sections' => [
                [
                    'number' => 1,
                    'title' => 'Общие положения',
                    'content' => 'Добро пожаловать в наше приложение для доставки свежих продуктов. Настоящие Условия регулируют использование вами нашего сервиса, включая все функциональные возможности и интерфейсы. Мы оставляем за собой право вносить изменения в данные условия в любое время без предварительного уведомления.',
                ],
                [
                    'number' => 2,
                    'title' => 'Регистрация аккаунта',
                    'content' => 'Для использования полного функционала приложения необходимо создать учетную запись. Вы несете ответственность за конфиденциальность данных вашего аккаунта.',
                    'requirements' => [
                        'Вам должно быть не менее 18 лет.',
                        'Вы обязаны предоставить достоверную информацию.',
                    ],
                ],
                [
                    'number' => 3,
                    'title' => 'Доставка и оплата',
                    'content' => 'Мы стремимся доставлять заказы в указанные сроки, однако время доставки может варьироваться в зависимости от загруженности и погодных условий.',
                    'important_note' => 'Важно: Оплата производится через безопасный шлюз. Мы не храним полные данные ваших банковских карт.',
                ],
                [
                    'number' => 4,
                    'title' => 'Возврат товаров',
                    'content' => 'Если качество доставленных продуктов вас не устраивает, вы можете оформить возврат в течение 24 часов с момента получения заказа. Пожалуйста, свяжитесь с нашей службой поддержки через раздел "Помощь".',
                ],
                [
                    'number' => 5,
                    'title' => 'Конфиденциальность',
                    'content' => 'Мы уважаем вашу приватность. Сбор и обработка персональных данных осуществляется в строгом соответствии с нашей Политикой конфиденциальности.',
                ],
            ],
        ],
        'privacy' => [
            'title' => 'Политика конфиденциальности',
            'last_updated' => '15 октября 2023',
            'sections' => [
                [
                    'number' => 1,
                    'title' => 'Сбор информации',
                    'content' => 'Мы собираем персональные данные, которые вы предоставляете при регистрации и использовании нашего приложения. Это включает имя, контактную информацию, адрес доставки и данные о заказах.',
                ],
                [
                    'number' => 2,
                    'title' => 'Использование данных',
                    'content' => 'Ваши данные используются для обработки заказов, улучшения качества сервиса, предоставления персонализированных предложений и связи с вами по вопросам обслуживания.',
                ],
                [
                    'number' => 3,
                    'title' => 'Защита данных',
                    'content' => 'Мы применяем современные методы защиты данных для обеспечения безопасности вашей информации. Доступ к данным имеет только уполномоченный персонал.',
                ],
                [
                    'number' => 4,
                    'title' => 'Передача данных третьим лицам',
                    'content' => 'Мы не продаем вашу информацию третьим лицам. Данные могут передаваться партнерам (службы доставки, платежные системы) исключительно для выполнения заказов.',
                ],
                [
                    'number' => 5,
                    'title' => 'Cookies и технологии отслеживания',
                    'content' => 'Мы используем cookies для улучшения пользовательского опыта, аналитики и безопасности приложения. Вы можете управлять настройками cookies в вашем браузере.',
                ],
                [
                    'number' => 6,
                    'title' => 'Права пользователя',
                    'content' => 'Вы имеете право на доступ, исправление, удаление своих персональных данных и ограничение их обработки. Для этого свяжитесь с нашей службой поддержки.',
                ],
                [
                    'number' => 7,
                    'title' => 'Хранение данных',
                    'content' => 'Мы храним ваши данные только столько, сколько необходимо для предоставления услуг и соблюдения законодательных требований.',
                ],
            ],
        ],
    ];

    public function getPage(string $slug): ?StaticPage
    {
        if (! $this->exists($slug)) {
            return null;
        }

        $pageData = $this->pages[$slug];

        $staticPage = new StaticPage([
            'slug' => $slug,
            'title' => $pageData['title'],
            'is_active' => true,
        ]);

        $staticPage->setRelation('sections', $this->createSections($pageData['sections'] ?? []));

        return $staticPage;
    }

    private function createSections(array $sectionsData): \Illuminate\Support\Collection
    {
        return collect($sectionsData)->map(function ($sectionData) {
            $section = new StaticPageSection([
                'number' => $sectionData['number'],
                'title' => $sectionData['title'],
                'content' => $sectionData['content'],
                'important_note' => $sectionData['important_note'] ?? null,
            ]);

            if (isset($sectionData['requirements'])) {
                $requirements = collect($sectionData['requirements'])->map(function ($requirement, $index) {
                    return new StaticPageSectionRequirement([
                        'requirement' => $requirement,
                        'order' => $index + 1,
                    ]);
                });
                $section->setRelation('requirements', $requirements);
            }

            return $section;
        });
    }

    public function exists(string $slug): bool
    {
        return isset($this->pages[$slug]);
    }
}
