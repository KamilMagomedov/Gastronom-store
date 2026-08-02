<?php

use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\NewPasswordController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\ResendOTPController;
use App\Http\Controllers\Api\V1\Auth\VerifyOtpController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\FrequentlyPurchasedController;
use App\Http\Controllers\Api\V1\HomeCategoriesController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PopularProductsController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProductReviewController;
use App\Http\Controllers\Api\V1\ProductSearchController;
use App\Http\Controllers\Api\V1\SettingsController;
use App\Http\Controllers\Api\V1\StaticPageController;
use App\Http\Controllers\Api\V1\SupportController;
use App\Http\Middleware\OptionalSanctumMiddleware;
use Illuminate\Support\Facades\Route;

/**
 * @apiDefine NotFoundError
 *            404 Not Found
 *
 * @apiError (NotFoundError) {String} message Error description
 */

/**
 * @apiDefine AuthirizationError
 *            401 Unauthorized
 *
 * @apiHeader {String="Bearer :token"} Authorization Replace <code>:token</code> with supplied Auth Token
 *
 * @apiError (AuthirizationError) {String} message Error description
 */

/**
 * @apiDefine ValidationError
 *            422 Unprocessable Entity
 *
 * @apiError (ValidationError) {String} message Error description
 * @apiError (ValidationError) {Object} errors Failed rules
 * @apiError (ValidationError) {String[]} [errors.:field] Specific <code>:field</code> errors
 */

/**
 * @apiDefine ThrottleError
 *            429 Too Many Requests
 *
 * @apiError (ThrottleError) {String} message Throttle message
 */
Route::prefix('v1')
    ->name('api.v1.')
    ->group(function () {
        Route::prefix('auth')
            ->name('auth.')
            ->group(function () {
                /**
                 * @api {post} /v1/auth/register Register new account
                 *
                 * @apiGroup Account
                 *
                 * @apiBody {String} name Name
                 * @apiBody {String} email Email
                 * @apiBody {String} password Password
                 * @apiBody {String} password_confirmation Password confirmation
                 *
                 * @apiSuccessExample {json} Success-Response:
                 *    HTTP/1.1 200 OK
                 * {
                 * "data": {
                 * "token": "3|HM8NGnReV2ll55WpigXWUPEowYOw0DAgIPQN3sY515aa42f1",
                 * "customer": {
                 * "id": 3,
                 * "name": "John Doe",
                 * "email": "john_doe2@example.com"
                 * }
                 * },
                 * "success": true
                 * }
                 *
                 * @apiUse ThrottleError
                 * @apiUse ValidationError
                 */
                Route::post('register', RegisterController::class)
                    ->name('register')
                    ->middleware('throttle:7,1');

                /**
                 * @api {post} /v1/auth/login Login
                 *
                 * @apiGroup Account
                 *
                 * @apiBody {String} email Email
                 * @apiBody {String} password Password
                 *
                 * @apiUse ThrottleError
                 * @apiUse ValidationError
                 *
                 * @apiSuccessExample {json} Success-Response:
                 *   HTTP/1.1 200 OK
                 * {
                 * "data": {
                 * "token": "4|9sblOPujO9CfW342VFERXBMgfVL5Ze4MD3LC262xbc2783ea",
                 * "customer": {
                 * "name": "John Doe",
                 * "email": "john@example.com",
                 * "email_verified": true
                 * }
                 * },
                 * "success": true
                 * }
                 *
                 * @apiUse ThrottleError
                 * @apiUse ValidationError
                 */
                Route::post('login', LoginController::class)
                    ->name('login')
                    ->middleware('throttle:15,1');

                /**
                 * @api {post} /v1/auth/verify Verify account by OTP
                 *
                 * @apiGroup Account
                 *
                 * @apiBody {String} name Name
                 * @apiBody {String} email Email
                 *
                 * @apiUse ThrottleError
                 * @apiUse ValidationError
                 *
                 * @apiSuccessExample {json} Success-Response:
                 *  HTTP/1.1 200 OK
                 * {
                 * "data": {
                 * "message": "Email verified successfully",
                 * "token": {
                 * "status": true,
                 * "token": "9233",
                 * "message": "OTP generated"
                 * },
                 * "customer": {
                 * "name": "John Doe",
                 * "email": "john@example.com",
                 * "email_verified": true
                 * }
                 * },
                 * "success": true
                 * }
                 *
                 * @apiUse ThrottleError
                 * @apiUse ValidationError
                 */
                Route::post('verify', VerifyOtpController::class)
                    ->name('verify')
                    ->middleware('throttle:15,1');

                /**
                 * @api {post} /v1/auth/forgot-password Request password reset
                 *
                 * @apiGroup Account
                 *
                 * @apiBody {String} email Email
                 *
                 * @apiSuccessExample {json} Success-Response:
                 *   HTTP/1.1 200 OK
                 * {
                 * "data": [],
                 * "success": true
                 * }
                 *
                 * @apiUse ThrottleError
                 * @apiUse ValidationError
                 *
                 * @apiSuccess {String} message Success message
                 */
                Route::post('forgot-password', ForgotPasswordController::class)
                    ->name('forgot-password')
                    ->middleware('throttle:5,1');

                /**
                 * @api {post} /v1/auth/new-password New password
                 *
                 * @apiGroup Account
                 *
                 * @apiBody {String} token Token
                 * @apiBody {String} email Email
                 * @apiBody {String} password Password
                 * @apiBody {String} password_confirmation Password confirmation
                 *
                 * @apiSuccessExample {json} Success-Response:
                 *    HTTP/1.1 200 OK
                 * {
                 * "data": {
                 * "token": "2|ZgbLsIJqxXQA2tTD4yZ0QaWVR2iQuz3xctLxU8FQ84721228",
                 * "customer": {
                 * "name": "John Doe",
                 * "email": "john@example.com",
                 * "email_verified": true
                 * }
                 * },
                 * "success": true
                 * }
                 *
                 * @apiUse ValidationError
                 */
                Route::post('new-password', NewPasswordController::class)
                    ->name('new-password');

                /**
                 * @api {post} /v1/auth/resend-otp Resend password
                 *
                 * @apiGroup Account
                 *
                 * @apiBody {String} email Email
                 *
                 * @apiUse ValidationError
                 */
                Route::post('resend-otp', ResendOTPController::class)
                    ->name('resend-otp')
                    ->middleware('throttle:3,1');

                /**
                 * @api {delete} /v1/auth/logout Logout
                 *
                 * @apiGroup Account
                 *
                 * @apiHeader {String="Bearer :token"} Authorization Replace <code>:token</code> with supplied Auth Token
                 *
                 * @apiSuccessExample {json} Success-Response:
                 *     HTTP/1.1 200 OK
                 * {
                 * "data": {
                 * "message": "Successfully logged out"
                 * },
                 * "success": true
                 * }
                 *
                 * @apiUse AuthirizationError
                 */
                Route::delete('logout', LogoutController::class)
                    ->name('logout')
                    ->middleware('auth:customers');
            });

        /**
         * @api {post} /v1/support Support send
         *
         * @apiGroup Support
         *
         * @apiBody {String} theme Theme
         * @apiBody {Number} order_id Order id
         * @apiBody {string} message Message
         *
         * @apiSuccessExample {json} Success-Response:
         *     HTTP/1.1 200 OK
         * {
         * "data": {
         * "message": "Support request created successfully"
         * },
         * "success": true
         * }
         *
         * @apiUse ValidationError
         */
        Route::post('support', SupportController::class)
            ->name('support.store');

        /**
         * @api {get} /v1/pages/terms Statis page terms
         *
         * @apiGroup Static Pages
         *
         * @apiSuccessExample {json} Success-Response:
         *     HTTP/1.1 200 OK
         * {
         * "data": {
         * "title": "Политика конфиденциальности",
         * "last_updated": "2026-01-08T06:27:52.000000Z",
         * "sections": [
         * {
         * "number": 1,
         * "title": "Сбор информации",
         * "content": "Мы собираем персональные данные, которые вы предоставляете при регистрации и использовании нашего приложения. Это включает имя, контактную информацию, адрес доставки и данные о заказах."
         * },
         * {
         * "number": 2,
         * "title": "Использование данных",
         * "content": "Ваши данные используются для обработки заказов, улучшения качества сервиса, предоставления персонализированных предложений и связи с вами по вопросам обслуживания."
         * },
         * {
         * "number": 3,
         * "title": "Защита данных",
         * "content": "Мы применяем современные методы защиты данных для обеспечения безопасности вашей информации. Доступ к данным имеет только уполномоченный персонал."
         * },
         * {
         * "number": 4,
         * "title": "Передача данных третьим лицам",
         * "content": "Мы не продаем вашу информацию третьим лицам. Данные могут передаваться партнерам (службы доставки, платежные системы) исключительно для выполнения заказов."
         * },
         * {
         * "number": 5,
         * "title": "Cookies и технологии отслеживания",
         * "content": "Мы используем cookies для улучшения пользовательского опыта, аналитики и безопасности приложения. Вы можете управлять настройками cookies в вашем браузере."
         * },
         * {
         * "number": 6,
         * "title": "Права пользователя",
         * "content": "Вы имеете право на доступ, исправление, удаление своих персональных данных и ограничение их обработки. Для этого свяжитесь с нашей службой поддержки."
         * },
         * {
         * "number": 7,
         * "title": "Хранение данных",
         * "content": "Мы храним ваши данные только столько, сколько необходимо для предоставления услуг и соблюдения законодательных требований."
         * }
         * ]
         * },
         * "success": true
         * }
         *
         * @apiErrorExample {json} Error-Response:
         *     HTTP/1.1 404 Not Found
         *     {
         *       "success": false,
         *       "message": "Page not found"
         *     }
         */
        Route::get('pages/{slug}', [StaticPageController::class, 'show'])
            ->name('static.pages.show');

        /**
         * @api {get} /v1/pages/privacy Statis page privacy
         *
         * @apiGroup Static Pages
         *
         * @apiSuccessExample {json} Success-Response:
         *     HTTP/1.1 200 OK
         * {
         * "data": {
         * "title": "Условия использования",
         * "last_updated": "2026-01-08T06:14:26.000000Z",
         * "sections": [
         * {
         * "number": 1,
         * "title": "Общие положения",
         * "content": "Добро пожаловать в наше приложение для доставки свежих продуктов. Настоящие Условия регулируют использование вами нашего сервиса, включая все функциональные возможности и интерфейсы. Мы оставляем за собой право вносить изменения в данные условия в любое время без предварительного уведомления."
         * },
         * {
         * "number": 2,
         * "title": "Регистрация аккаунта",
         * "content": "Для использования полного функционала приложения необходимо создать учетную запись. Вы несете ответственность за конфиденциальность данных вашего аккаунта.",
         * "requirements": [
         * "Вам должно быть не менее 18 лет.",
         * "Вы обязаны предоставить достоверную информацию."
         * ]
         * },
         * {
         * "number": 3,
         * "title": "Доставка и оплата",
         * "content": "Мы стремимся доставлять заказы в указанные сроки, однако время доставки может варьироваться в зависимости от загруженности и погодных условий.",
         * "important_note": "Важно: Оплата производится через безопасный шлюз. Мы не храним полные данные ваших банковских карт."
         * },
         * {
         * "number": 4,
         * "title": "Возврат товаров",
         * "content": "Если качество доставленных продуктов вас не устраивает, вы можете оформить возврат в течение 24 часов с момента получения заказа. Пожалуйста, свяжитесь с нашей службой поддержки через раздел \"Помощь\"."
         * },
         * {
         * "number": 5,
         * "title": "Конфиденциальность",
         * "content": "Мы уважаем вашу приватность. Сбор и обработка персональных данных осуществляется в строгом соответствии с нашей Политикой конфиденциальности."
         * }
         * ]
         * },
         * "success": true
         * }
         *
         * @apiErrorExample {json} Error-Response:
         *     HTTP/1.1 404 Not Found
         *     {
         *       "success": false,
         *       "message": "Page not found"
         *     }
         */
        Route::get('pages/{slug}', [StaticPageController::class, 'show'])
            ->name('static.pages.show');

        Route::prefix('home')
            ->name('home.')
            ->group(function () {
                /**
                 * @api {get} /v1/home/frequently-purchased Frequently purchased (products)
                 *
                 * @apiGroup Main screen
                 *
                 * @apiSuccessExample {json} Success-Response:
                 *     HTTP/1.1 200 OK
                 *{
                 * "data": [
                 * {
                 * "id": 2,
                 * "name": "Qui eveniet commodi",
                 * "slug": "qui-eveniet-commodi",
                 * "price": "106.31",
                 * "old_price": "202.76",
                 * "sku": "PRD-2448-jd",
                 * "unit": "pcs",
                 * "image": "http://example.com/storage/products/image1.jpg",
                 * "category": {
                 * "id": 3,
                 * "name": "Ad animi",
                 * "slug": "ad-animi"
                 * }
                 * },
                 * {
                 * "id": 5,
                 * "name": "Est atque in",
                 * "slug": "est-atque-in",
                 * "price": "381.86",
                 * "old_price": "446.71",
                 * "sku": "PRD-7841-ph",
                 * "category": {
                 * "id": 6,
                 * "name": "Aut ut",
                 * "slug": "aut-ut"
                 * }
                 * },
                 * {
                 * "id": 8,
                 * "name": "Debitis debitis autem",
                 * "slug": "debitis-debitis-autem",
                 * "price": "93.53",
                 * "old_price": "129.58",
                 * "sku": "PRD-6674-yq",
                 * "category": {
                 * "id": 9,
                 * "name": "Nihil odio",
                 * "slug": "nihil-odio"
                 * }
                 * },
                 * {
                 * "id": 1,
                 * "name": "Dolores qui eveniet",
                 * "slug": "dolores-qui-eveniet",
                 * "price": "379.41",
                 * "old_price": null,
                 * "sku": "PRD-5604-kr",
                 * "category": {
                 * "id": 2,
                 * "name": "Eum amet",
                 * "slug": "eum-amet"
                 * }
                 * },
                 * {
                 * "id": 3,
                 * "name": "Quo laudantium ducimus",
                 * "slug": "quo-laudantium-ducimus",
                 * "price": "186.18",
                 * "old_price": null,
                 * "sku": "PRD-2064-zs",
                 * "category": {
                 * "id": 4,
                 * "name": "Voluptates itaque",
                 * "slug": "voluptates-itaque"
                 * }
                 * },
                 * {
                 * "id": 4,
                 * "name": "Mollitia libero enim",
                 * "slug": "mollitia-libero-enim",
                 * "price": "411.47",
                 * "old_price": "432.84",
                 * "sku": "PRD-9564-kh",
                 * "category": {
                 * "id": 5,
                 * "name": "Excepturi sint",
                 * "slug": "excepturi-sint"
                 * }
                 * },
                 * {
                 * "id": 6,
                 * "name": "Atque numquam maiores",
                 * "slug": "atque-numquam-maiores",
                 * "price": "485.93",
                 * "old_price": "579.48",
                 * "sku": "PRD-7905-fl",
                 * "category": {
                 * "id": 7,
                 * "name": "Excepturi quasi",
                 * "slug": "excepturi-quasi"
                 * }
                 * },
                 * {
                 * "id": 9,
                 * "name": "Voluptatem alias eum",
                 * "slug": "voluptatem-alias-eum",
                 * "price": "410.44",
                 * "old_price": "464.14",
                 * "sku": "PRD-3033-pa",
                 * "category": {
                 * "id": 10,
                 * "name": "Occaecati ex",
                 * "slug": "occaecati-ex"
                 * }
                 * },
                 * {
                 * "id": 7,
                 * "name": "Recusandae vel et",
                 * "slug": "recusandae-vel-et",
                 * "price": "112.70",
                 * "old_price": null,
                 * "sku": "PRD-5344-ou",
                 * "category": {
                 * "id": 8,
                 * "name": "Saepe ut",
                 * "slug": "saepe-ut"
                 * }
                 * },
                 * {
                 * "id": 10,
                 * "name": "Sint blanditiis ut",
                 * "slug": "sint-blanditiis-ut",
                 * "price": "468.63",
                 * "old_price": null,
                 * "sku": "PRD-3422-yh",
                 * "category": {
                 * "id": 11,
                 * "name": "Vel consequatur",
                 * "slug": "vel-consequatur"
                 * }
                 * }
                 * ],
                 * "success": true
                 * }
                 */
                Route::get('frequently-purchased', FrequentlyPurchasedController::class)
                    ->name('frequently-purchased');

                /**
                 * @api {get} /v1/home/popular-products Popular products
                 *
                 * @apiGroup Main screen
                 *
                 * @apiSuccessExample {json} Success-Response:
                 *     HTTP/1.1 200 OK
                 *
                 * {
                 * "data": [
                 * {
                 * "id": 5,
                 * "name": "Est atque in",
                 * "slug": "est-atque-in",
                 * "price": "381.86",
                 * "old_price": "446.71",
                 * "sku": "PRD-7841-ph",
                 * "unit" : "pcs",
                 * "image": "http://example.com/storage/products/image2.jpg",
                 * "category": {
                 * "id": 6,
                 * "name": "Aut ut",
                 * "slug": "aut-ut"
                 * }
                 * },
                 * {
                 * "id": 6,
                 * "name": "Atque numquam maiores",
                 * "slug": "atque-numquam-maiores",
                 * "price": "485.93",
                 * "old_price": "579.48",
                 * "sku": "PRD-7905-fl",
                 * "unit" : "pcs",
                 * "image": "http://example.com/storage/products/image7.jpg",
                 * "category": {
                 * "id": 7,
                 * "name": "Excepturi quasi",
                 * "slug": "excepturi-quasi"
                 * }
                 * },
                 * {
                 * "id": 2,
                 * "name": "Qui eveniet commodi",
                 * "slug": "qui-eveniet-commodi",
                 * "price": "106.31",
                 * "old_price": "202.76",
                 * "sku": "PRD-2448-jd",
                 * "unit": "pcs",
                 * "image": "http://example.com/storage/products/image1.jpg",
                 * "category": {
                 * "id": 3,
                 * "name": "Ad animi",
                 * "slug": "ad-animi"
                 * }
                 * },
                 * {
                 * "id": 10,
                 * "name": "Sint blanditiis ut",
                 * "slug": "sint-blanditiis-ut",
                 * "price": "468.63",
                 * "old_price": null,
                 * "sku": "PRD-3422-yh",
                 * "unit" : "pcs",
                 * "image": "http://example.com/storage/products/image10.jpg",
                 * "category": {
                 * "id": 11,
                 * "name": "Vel consequatur",
                 * "slug": "vel-consequatur"
                 * }
                 * },
                 * {
                 * "id": 8,
                 * "name": "Debitis debitis autem",
                 * "slug": "debitis-debitis-autem",
                 * "price": "93.53",
                 * "old_price": "129.58",
                 * "sku": "PRD-6674-yq",
                 * "unit" : "pcs",
                 * "image": "http://example.com/storage/products/image3.jpg",
                 * "category": {
                 * "id": 9,
                 * "name": "Nihil odio",
                 * "slug": "nihil-odio"
                 * }
                 * },
                 * {
                 * "id": 9,
                 * "name": "Voluptatem alias eum",
                 * "slug": "voluptatem-alias-eum",
                 * "price": "410.44",
                 * "old_price": "464.14",
                 * "sku": "PRD-3033-pa",
                 * "unit" : "pcs",
                 * "image": "http://example.com/storage/products/image8.jpg",
                 * "category": {
                 * "id": 10,
                 * "name": "Occaecati ex",
                 * "slug": "occaecati-ex"
                 * }
                 * },
                 * {
                 * "id": 1,
                 * "name": "Dolores qui eveniet",
                 * "slug": "dolores-qui-eveniet",
                 * "price": "379.41",
                 * "old_price": null,
                 * "sku": "PRD-5604-kr",
                 * "unit" : "pcs",
                 * "image": "http://example.com/storage/products/image4.jpg",
                 * "category": {
                 * "id": 2,
                 * "name": "Eum amet",
                 * "slug": "eum-amet"
                 * }
                 * },
                 * {
                 * "id": 3,
                 * "name": "Quo laudantium ducimus",
                 * "slug": "quo-laudantium-ducimus",
                 * "price": "186.18",
                 * "old_price": null,
                 * "sku": "PRD-2064-zs",
                 * "unit" : "pcs",
                 * "image": "http://example.com/storage/products/image5.jpg",
                 * "category": {
                 * "id": 4,
                 * "name": "Voluptates itaque",
                 * "slug": "voluptates-itaque"
                 * }
                 * },
                 * {
                 * "id": 4,
                 * "name": "Mollitia libero enim",
                 * "slug": "mollitia-libero-enim",
                 * "price": "411.47",
                 * "old_price": "432.84",
                 * "sku": "PRD-9564-kh",
                 * "unit" : "pcs",
                 * "image": "http://example.com/storage/products/image6.jpg",
                 * "category": {
                 * "id": 5,
                 * "name": "Excepturi sint",
                 * "slug": "excepturi-sint"
                 * }
                 * },
                 * {
                 * "id": 7,
                 * "name": "Recusandae vel et",
                 * "slug": "recusandae-vel-et",
                 * "price": "112.70",
                 * "old_price": null,
                 * "sku": "PRD-5344-ou",
                 * "unit" : "pcs",
                 * "image": "http://example.com/storage/products/image9.jpg",
                 * "category": {
                 * "id": 8,
                 * "name": "Saepe ut",
                 * "slug": "saepe-ut"
                 * }
                 * }
                 * ],
                 * "success": true
                 * }
                 */
                Route::get('popular-products', PopularProductsController::class)
                    ->name('popular-products');

                /**
                 * @api {get} /v1/home/categories Categories
                 *
                 * @apiGroup Main screen
                 *
                 * @apiSuccessExample {json} Success-Response:
                 *     HTTP/1.1 200 OK
                 *
                 * {
                 * "data": [
                 * {
                 * "id": 3,
                 * "name": "Ad animi",
                 * "slug": "ad-animi"
                 * },
                 * {
                 * "id": 6,
                 * "name": "Aut ut",
                 * "slug": "aut-ut"
                 * },
                 * {
                 * "id": 2,
                 * "name": "Eum amet",
                 * "slug": "eum-amet"
                 * },
                 * {
                 * "id": 7,
                 * "name": "Excepturi quasi",
                 * "slug": "excepturi-quasi"
                 * },
                 * {
                 * "id": 5,
                 * "name": "Excepturi sint",
                 * "slug": "excepturi-sint"
                 * },
                 * {
                 * "id": 9,
                 * "name": "Nihil odio",
                 * "slug": "nihil-odio"
                 * },
                 * {
                 * "id": 10,
                 * "name": "Occaecati ex",
                 * "slug": "occaecati-ex"
                 * },
                 * {
                 * "id": 8,
                 * "name": "Saepe ut",
                 * "slug": "saepe-ut"
                 * },
                 * {
                 * "id": 11,
                 * "name": "Vel consequatur",
                 * "slug": "vel-consequatur"
                 * },
                 * {
                 * "id": 4,
                 * "name": "Voluptates itaque",
                 * "slug": "voluptates-itaque"
                 * },
                 * ],
                 * "success": true
                 * }
                 */
                Route::get('categories', HomeCategoriesController::class)
                    ->name('categories');
            });

        /**
         * @api {post} /v1/search-products Search products
         *
         * @apiParam {Number} page Page
         * @apiParam {String} limit Per page
         * @apiParam {String} direction Sort
         *
         * @apiGroup Search
         *
         * @apiBody {String} query Query string
         * @apiBody {Object} [sort] Sort options
         * @apiBody {String="asc","desc"} [sort.price] Sort by price
         * @apiBody {String="asc","desc"} [sort.name] Sort by name
         * @apiBody {Number} category Category
         * @apiBody {Number} price_from Price from
         * @apiBody {Number} price_to Price to
         * @apiBody {boolean} in_stock In stock
         *
         * @apiSuccessExample {json} Success-Response:
         *     HTTP/1.1 200 OK
         *
         * {
         * "data": [
         * {
         * "id": 7,
         * "name": "Aut adipisci quisquam",
         * "slug": "aut-adipisci-quisquam",
         * "price": "323.48",
         * "old_price": null,
         * "sku": "PRD-5630-jx",
         * "unit" : "pcs",
         * "image": "http://example.com/storage/products/image11.jpg",
         * "category": {
         * "id": 8,
         * "name": "Aliquid quia",
         * "slug": "aliquid-quia"
         * }
         * }
         * ],
         * "paginator": {
         * "per_page": 1,
         * "current_page": 1,
         * "last_page": 10,
         * "total": 10,
         * "has_more": true
         * },
         * "success": true
         * }
         */
        Route::match(['get', 'post'], 'search-products', ProductSearchController::class)
            ->name('search-products');

        /**
         * @api {get} /v1/products/{product} Product show
         *
         * @apiParam {product} product
         *
         * @apiGroup Products
         *
         * @apiSuccessExample {json} Success-Response:
         *     HTTP/1.1 200 OK
         *
         * {
         * "data": {
         * "id": 2,
         * "name": "Rerum magnam sed",
         * "slug": "rerum-magnam-sed",
         * "description": "Porro a est neque corrupti voluptatum voluptas aliquam. Molestiae asperiores expedita et explicabo ut temporibus labore.\n\nDeserunt qui nobis quaerat adipisci. Reiciendis voluptates quibusdam facere voluptas nobis. Sunt et iste dolore nulla.",
         * "price": "177.21",
         * "old_price": null,
         * "unit" : "pcs",
         * "sku": "PRD-7144-fx",
         * "images": [
         *   "http://example.com/storage/products/image12-1.jpg",
         *   "http://example.com/storage/products/image12-2.jpg"
         * ],
         * "rating": {
         *   "average": 4.5,
         *   "count": 23
         * }
         * },
         * "success": true
         * }
         */
        Route::get('products/{product:slug}', ProductController::class)
            ->name('products.show');

        Route::get('products/{product:slug}/availability', \App\Http\Controllers\Api\V1\ProductAvailabilityController::class)
            ->name('products.availability');

        /**
         * @api {get} /v1/products/{product}/reviews Product reviews
         *
         * @apiParam {product} product
         *
         * @apiGroup Product reviews
         *
         * @apiSuccessExample {json} Success-Response:
         *     HTTP/1.1 200 OK
         *
         *{
         * "data": [
         * {
         * "id": 1,
         * "rating": 5,
         * "comment": "Numquam est repellendus quia a a quis. Molestiae ullam adipisci quia illum explicabo commodi dolorum non. Fuga neque repellat molestias ipsa dolorum animi sit nesciunt.",
         * "created_at": "2026-01-19T18:59:58.000000Z",
         * "customer": {
         * "id": 1,
         * "name": "Brown Weissnat IV"
         * }
         * }
         * ],
         * "paginator": {
         * "per_page": 10,
         * "current_page": 1,
         * "last_page": 1,
         * "total": 1,
         * "has_more": false
         * },
         * "success": true
         * }
         */

        /**
         * @api {post} /v1/products/{product}/reviews Create product review
         *
         * @apiGroup Product reviews
         *
         * @apiUse AuthirizationError
         *
         * @apiParam {product} product
         *
         * @apiBody {Number=1,2,3,4,5} rating Rating (1-5)
         * @apiBody {String} [comment] Review comment (max 1000 chars)
         *
         * @apiSuccess {String} message Success message
         *
         * @apiUse ValidationError
         */
        Route::apiResource('products/{product:slug}/reviews', ProductReviewController::class)
            ->only('index', 'store', 'update', 'destroy')
            ->middlewareFor(['store', 'update', 'destroy'], ['auth:customers']);

        /**
         * @api {get} /v1/carts-clear Cart clear
         *
         * @apiGroup Cart
         *
         * @apiParam {String} session_id (optional) Session ID of the cart to clear
         *
         * @apiSuccessExample {json} Success-Response:
         *     HTTP/1.1 200 OK
         *     {
         *         "data": [],
         *         "success": true
         *     }
         */
        Route::get('carts-clear', [CartController::class, 'clear'])
            ->name('carts-clear');

        /**
         * @api {get} /v1/carts
         *
         * @apiGroup Cart
         *
         * @apiParam [session_id] session id for guest
         *
         * @apiSuccessExample {json} Success-Response:
         *
         * @apiUse ValidationError
         */

        /**
         * @api {post} /v1/carts
         *
         * @apiGroup Cart
         *
         * @apiBody {product_id} product unique identifier
         * @apiBody {quantity} quantity
         * @apiBody [session_id] session id for guest
         *
         * @apiSuccessExample {json} Success-Response:
         * HTTP/1.1 200 OK
         * {
         *  "data": [],
         *  "success": true
         * }
         *
         * @apiUse ValidationError
         */

        /**
         * @api {delete} /v1/carts
         *
         * @apiGroup Cart
         *
         * @apiParam {product_id} product unique identifier
         * @apiParam [session_id] session id for guest
         *
         * @apiSuccessExample {json} Success-Response:
         * HTTP/1.1 200 OK
         * {
         *  "data": [],
         *  "success": true
         * }
         *
         * @apiUse ValidationError
         */
        Route::apiResource('carts', CartController::class)
            ->middleware(OptionalSanctumMiddleware::class);

        /**
         * @api {patch} /v1/orders/{order}/cancel
         *
         * @apiGroup Order
         *
         * @apiParam {order} Order unique identifier
         *
         * @apiSuccessExample {json} Success-Response:
         * HTTP/1.1 200 OK
         * {
         *  "data": [],
         *  "success": true
         * }
         *
         * @apiUse ValidationError
         */
        Route::put('orders/{order}/cancel', [OrderController::class, 'cancel'])
            ->middleware('auth:customers')
            ->name('orders.cancel');

        /**
         * @api {post} /v1/orders-repeat/check Check order for repeat
         *
         * @apiGroup Order
         *
         * @apiParam {Number} order_id Order ID to check for repeat
         *
         * @apiSuccessExample {json} Success-Response:
         * HTTP/1.1 200 OK
         * {
         * "data": {
         * "order_id": 1,
         * "available_items": [
         * {
         * "id": 1,
         * "product_id": 5,
         * "product_name": "Product Name",
         * "product_slug": "product-name",
         * "product_sku": "PRD-001",
         * "product_image": "http://example.com/image.jpg",
         * "requested_quantity": 2,
         * "available_quantity": 5,
         * "price": "100.00",
         * "old_price": "150.00",
         * "unit": "pcs",
         * "is_available": true,
         * "shortage": 0
         * }
         * ],
         * "unavailable_items": [
         * {
         * "id": 2,
         * "product_id": 6,
         * "product_name": "Out of Stock Product",
         * "product_sku": "PRD-002",
         * "requested_quantity": 3,
         * "reason": "out_of_stock"
         * }
         * ],
         * "can_repeat": false,
         * "total_available_items": 1,
         * "total_unavailable_items": 1
         * },
         * "success": true
         * }
         *
         * @apiUse AuthirizationError
         * @apiUse NotFoundError
         */

        /**
         * @api {post} /v1/orders-repeat Repeat order
         *
         * @apiGroup Order
         *
         * @apiParam {Number} order_id Order ID to repeat
         *
         * @apiSuccessExample {json} Success-Response:
         * HTTP/1.1 200 OK
         * {
         * "data": {
         * "id": 2,
         * "total_amount": "200.00",
         * "status": "pending",
         * "payment_status": "pending",
         * "created_at": "2024-01-01T00:00:00.000000Z"
         * },
         * "success": true
         * }
         *
         * @apiErrorExample {json} Error-Response:
         * HTTP/1.1 400 Bad Request
         * {
         * "data": [],
         * "message": "Order cannot be repeated: some items are unavailable",
         * "success": false
         * }
         *
         * @apiUse AuthirizationError
         * @apiUse NotFoundError
         */
        Route::post('orders-repeat/check', [\App\Http\Controllers\Api\V1\OrderRepeatController::class, 'check'])
            ->middleware('auth:customers')
            ->name('orders-repeat.check');

        Route::post('orders-repeat', [\App\Http\Controllers\Api\V1\OrderRepeatController::class, 'repeat'])
            ->middleware('auth:customers')
            ->name('orders-repeat.store');

        /**
         * @api {get} /v1/orders
         *
         * @apiGroup Order
         *
         * @apiParam [per_page] Per page items (min 1, max 20)
         * @apiParam [page] Selected page. Default page 1 (min 1, max 100)
         *
         * @apiSuccessExample {json} Success-Response:
         * HTTP/1.1 200 OK
         *
         * {
         * "data": [
         * {
         * "id": 1,
         * "total_amount": "359.89",
         * "shipping_amount": "36.16",
         * "delivery_method": {
         * "id": 4,
         * "label": "ullam quae omnis",
         * "description": "Ex alias consequuntur consequatur eius nostrum.",
         * "cost": 200
         * },
         * "delivery_cost": "150.72",
         * "delivery_address": "29045 Marlon Spurs Apt. 158\nWuckertstad, AR 15831-4254",
         * "delivery_phone": "(209) 710-9956",
         * "payment_method": {
         * "id": 4,
         * "label": "est ut",
         * "description": "Debitis fuga ratione optio magni."
         * },
         * "status": "delivering",
         * "payment_status": "failed",
         * "delivered_at": null,
         * "created_at": "2026-01-21T19:17:36.000000Z"
         * }
         * ],
         * "paginator": {
         * "per_page": 1,
         * "current_page": 1,
         * "last_page": 5,
         * "total": 5,
         * "has_more": true
         * },
         * "success": true
         * }
         *
         * @apiUse AuthirizationError
         * @apiUse ValidationError
         */

        /**
         * @api {get} /v1/orders/{order}
         *
         * @apiGroup Order
         *
         * @apiParam {order} Order unique identifier
         *
         * @apiSuccessExample {json} Success-Response:
         * HTTP/1.1 200 OK
         *
         * {
         * "data": {
         * "id": 1,
         * "total_amount": "359.89",
         * "shipping_amount": "36.16",
         * "delivery_method": {
         * "id": 4,
         * "label": "ullam quae omnis",
         * "description": "Ex alias consequuntur consequatur eius nostrum.",
         * "cost": 200
         * },
         * "delivery_cost": "150.72",
         * "delivery_address": "29045 Marlon Spurs Apt. 158\nWuckertstad, AR 15831-4254",
         * "delivery_phone": "(209) 710-9956",
         * "payment_method": {
         * "id": 4,
         * "label": "est ut",
         * "description": "Debitis fuga ratione optio magni."
         * },
         * "status": "delivering",
         * "payment_status": "failed",
         * "delivered_at": null,
         * "created_at": "2026-01-21T19:17:36.000000Z",
         * "products": [
         * {
         * "id": 16,
         * "name": "Eveniet earum dicta",
         * "slug": "eveniet-earum-dicta",
         * "price": "140.92",
         * "old_price": null,
         * "sku": "PRD-8160-fu",
         * "unit": "ml",
         * "image": "",
         * "category": {
         * "id": 17,
         * "name": "Quasi et",
         * "slug": "quasi-et",
         * "image": ""
         * }
         * },
         * {
         * "id": 17,
         * "name": "In consequuntur dolorem",
         * "slug": "in-consequuntur-dolorem",
         * "price": "321.22",
         * "old_price": null,
         * "sku": "PRD-7887-cf",
         * "unit": "kg",
         * "image": "",
         * "category": {
         * "id": 18,
         * "name": "Officia ea",
         * "slug": "officia-ea",
         * "image": ""
         * }
         * },
         * {
         * "id": 18,
         * "name": "Sit iure distinctio",
         * "slug": "sit-iure-distinctio",
         * "price": "48.27",
         * "old_price": null,
         * "sku": "PRD-7322-vq",
         * "unit": "ml",
         * "image": "",
         * "category": {
         * "id": 19,
         * "name": "Aspernatur consequatur",
         * "slug": "aspernatur-consequatur",
         * "image": ""
         * }
         * },
         * {
         * "id": 19,
         * "name": "Non error voluptates",
         * "slug": "non-error-voluptates",
         * "price": "473.06",
         * "old_price": null,
         * "sku": "PRD-3324-at",
         * "unit": "kg",
         * "image": "",
         * "category": {
         * "id": 20,
         * "name": "Omnis similique",
         * "slug": "omnis-similique",
         * "image": ""
         * }
         * },
         * {
         * "id": 20,
         * "name": "Iusto nihil autem",
         * "slug": "iusto-nihil-autem",
         * "price": "283.62",
         * "old_price": null,
         * "sku": "PRD-4170-uk",
         * "unit": "pcs",
         * "image": "",
         * "category": {
         * "id": 21,
         * "name": "Adipisci quam",
         * "slug": "adipisci-quam",
         * "image": ""
         * }
         * }
         * ]
         * },
         * "success": true
         * }
         *
         * @apiUse ValidationError
         * @apiUse AuthirizationError
         */

        /**
         * @api {post} /v1/orders
         *
         * @apiGroup Order
         *
         * @apiBody {delivery_method} Delivery method
         * @apiBody {delivery_address} Delivery address
         * @apiBody {delivery_phone} Delivery phone
         * @apiBody {delivery_notes} Delivery notes
         * @apiBody {payment_method} Payment method
         * @apiBody {notes} Notes
         *
         * @apiSuccessExample {json} Success-Response:
         * HTTP/1.1 200 OK
         * {
         * "data": {
         * "id": 6,
         * "total_amount": "0.00",
         * "shipping_amount": null,
         * "delivery_method": {
         * "id": 1,
         * "label": "Pickup",
         * "description": "Pick up your order at our pickup point",
         * "cost": 0
         * },
         * "delivery_cost": "0.00",
         * "delivery_address": "address",
         * "delivery_phone": "123123123123",
         * "payment_method": {
         * "id": 1,
         * "label": "Bank Card",
         * "description": "Payment by bank card online"
         * },
         * "status": "pending",
         * "payment_status": "pending",
         * "delivered_at": null,
         * "created_at": "2026-01-22T18:38:06.000000Z"
         * },
         * "success": true
         * }
         *
         * @apiUse AuthirizationError
         * @apiUse ValidationError
         */
        Route::apiResource('orders', OrderController::class)
            ->middleware('auth:customers');

        Route::prefix('payments')
            ->name('payments.')
            ->group(function () {
                Route::post('{order}/initiate', [\App\Http\Controllers\Api\V1\PaymentController::class, 'initiate'])
                    ->middleware('auth:customers')
                    ->name('initiate');

                Route::get('{order}/callback', [\App\Http\Controllers\Api\V1\PaymentController::class, 'callback'])
                    ->name('callback');
            });

        Route::get('settings', [SettingsController::class, 'settings'])
            ->middleware(OptionalSanctumMiddleware::class);

        /**
         * @api {get} /v1/profile Get customer profile
         *
         * @apiGroup Customer
         *
         * @apiSuccessExample {json} Success-Response:
         *     HTTP/1.1 200 OK
         * {
         * "data": {
         * "id": 1,
         * "name": "John Doe",
         * "email": "john@example.com",
         * "email_verified_at": "2024-01-01T00:00:00.000000Z",
         * "phone": "+79281234567",
         * "delivery_street": "123 Main St",
         * "delivery_city": "New York",
         * "delivery_apartment": "4A",
         * "delivery_postal_code": "10001",
         * "delivery_building": "Building A",
         * "delivery_entrance": "Main",
         * "delivery_floor": "5",
         * "created_at": "2024-01-01T00:00:00.000000Z",
         * },
         * "success": true
         * }
         *
         * @apiUse AuthirizationError
         */

        /**
         * @api {put} /v1/profile Update customer profile
         *
         * @apiGroup Customer
         *
         * @apiParam {string} [name] Customer name (max: 190)
         * @apiParam {string} [email] Customer email (max: 190, unique)
         * @apiParam {string} [password] New password (min: 8, confirmed)
         * @apiParam {string} [phone] Phone number (regex: +7|7|8 followed by 10 digits)
         * @apiParam {string} [delivery_street] Delivery street (max: 190)
         * @apiParam {string} [delivery_city] Delivery city (max: 100)
         * @apiParam {string} [delivery_apartment] Delivery apartment (max: 10)
         * @apiParam {string} [delivery_postal_code] Delivery postal code (max: 20)
         * @apiParam {string} [delivery_building] Delivery building (max: 10)
         * @apiParam {string} [delivery_entrance] Delivery entrance (max: 10)
         * @apiParam {string} [delivery_floor] Delivery floor (max: 10)
         * @apiParam {string} [password_confirmation] Password confirmation
         *
         * @apiParamExample {json} Request-Example:
         * {
         *   "name": "Updated Name",
         *   "phone": "+79287654321",
         *   "delivery_street": "456 Updated St",
         *   "delivery_city": "Los Angeles",
         *   "delivery_apartment": "7B",
         *   "delivery_postal_code": "90001",
         *   "delivery_building": "Building B",
         *   "delivery_entrance": "Side",
         *   "delivery_floor": "3"
         * }
         *
         * @apiSuccessExample {json} Success-Response:
         *     HTTP/1.1 200 OK
         * {
         * "data": {
         * "id": 1,
         * "name": "Updated Name",
         * "email": "john@example.com",
         * "email_verified_at": "2024-01-01T00:00:00.000000Z",
         * "phone": "+79287654321",
         * "delivery_street": "456 Updated St",
         * "delivery_city": "Los Angeles",
         * "delivery_apartment": "7B",
         * "delivery_postal_code": "90001",
         * "delivery_building": "Building B",
         * "delivery_entrance": "Side",
         * "delivery_floor": "3",
         * "created_at": "2024-01-01T00:00:00.000000Z",
         * },
         * "success": true
         * }
         *
         * @apiErrorExample {json} Error-Response:
         *     HTTP/1.1 422 Unprocessable Entity
         * {
         * "message": "The given data was invalid.",
         * "errors": {
         * "email": ["The email has already been taken."],
         * "phone": ["Please enter a valid Russian phone number (e.g., +79281234567, 79281234567, or 89281234567)."]
         * }
         * }
         *
         * @apiUse AuthirizationError
         */
        Route::singleton('profile', CustomerController::class)
            ->middleware('auth:customers');

        /**
         * @api {get} /v1/settings Get application settings
         *
         * @apiGroup Settings
         *
         * @apiSuccessExample {json} Success-Response:
         *     HTTP/1.1 200 OK
         * {
         * "data": {
         * "app_name": "Home Delivery App",
         * "app_version": "1.0.0",
         * "currency": "RUB",
         * "contact_email": "support@example.com",
         * "contact_phone": "+74951234567",
         * "social_links": {
         * "facebook": "https://facebook.com/example",
         * "instagram": "https://instagram.com/example"
         * },
         * "delivery_settings": {
         * "min_order_amount": "500.00",
         * "free_delivery_threshold": "1500.00",
         * "delivery_fee": "150.00"
         * },
         * "payment_methods": [
         * {
         * "id": 1,
         * "name": "Наличные при получении",
         * "description": "Оплата наличными курьеру при доставке"
         * },
         * {
         * "id": 2,
         * "name": "Apple Pay",
         * "description": "Быстрая оплата через Apple Pay"
         * },
         * {
         * "id": 3,
         * "name": "Google Pay",
         * "description": "Быстрая оплата через Google Pay"
         * }
         * ]
         * },
         * "success": true
         * }
         */
        Route::get('settings', [SettingsController::class, 'settings'])
            ->name('settings');
    });
