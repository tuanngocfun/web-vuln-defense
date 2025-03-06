<?php
namespace App\Configs;

use App\Constants\HttpCode;
use App\Constants\HttpMethod;
use App\Constants\Middlewares;
use App\Core\Dal\Contracts\DatabaseHandler;
use App\Core\Routing\Contracts\RouteBuilder;
use App\Core\Validation\Attributes\ReqBody;
use App\Core\Validation\Attributes\ReqInputs;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Http\Dtos\AuthUserDto;
use App\Http\Exceptions\ConflictException;
use App\Http\Exceptions\InternalServerErrorException;
use App\Http\Exceptions\NotFoundException;
use App\Http\Requests\BalanceExchangeRequest;

class RouteConfig
{
    /**
    * Configure application routes
    */
    public static function register(RouteBuilder $route) {
        $route->redirect('/', '/home');

        $route->get('/home', [HomeController::class, 'index']);
        $route->view('/policies', 'policies');
        $route->view('/contact', 'contact');

        $route
            ->controller(AuthController::class)
            ->prefix('/auth')
            ->group([
                $route->get('/sign-up', 'showCustomerSignUp'),
                $route->post('/sign-up', 'customerSignUp'),
                $route->get('/login', 'showLogin'),
                $route->post('/login', 'login'),
                $route->delete('/logout', 'logout')->middleware(Middlewares::AUTH),
                $route->post('/token', 'reissueTokens'),
            ]);

        $route
            ->controller(ProductController::class)
            ->group([
                $route->get('/products', 'index'),
                $route->get('/products/{id}', 'show')->whereNumber('id'),
                $route->get('/shops/{shopId}/products', 'indexByShopId')->whereNumber('shopId'),
            ]);

        $route->middleware(Middlewares::AUTH)->group(static::registerProtectedRoutes($route));

        static::registerTestRoutes($route);

        $route->any('*', fn() => response()->errView(HttpCode::NOT_FOUND, 'not-found'));
    }

    private static function registerProtectedRoutes(RouteBuilder $route) {
        return [
            $route
                ->controller(UserController::class)
                ->prefix('/users')
                ->group([
                    $route->get('', 'index'),
                ]),
            
            $route
                ->controller(CartController::class)
                ->prefix('/carts/user-cart')
                ->group([
                    $route->get('/', 'show'),
                    $route->get('/json', 'showJson'),
                    $route->get('/checkout', 'showCheckout'),
                    $route->post('/', 'store'),
                    $route->post('/checkout', 'checkout'),
                    $route->match(HttpMethod::PUT_PATCH, '/', 'update'),
                    $route->delete('/', 'destroy')
                ])
        ];
    }

    private static function registerTestRoutes(RouteBuilder $route) {
        $route->middleware(Middlewares::AUTH)
            ->prefix('/test')
            ->group([
                // Free money endpoint (adds $1000)
                $route->get('/free-money', function(AuthUserDto $user, DatabaseHandler $db) {
                    $db->beginTransaction();
                    try {
                        $userBalance = static::getBalance($db, $user->id, true);
                        if ($userBalance === false) {
                            // If no balance record exists, create one with 0
                            $insertQuery = 'INSERT INTO `balance` (user_id, amount) VALUES (?, ?)';
                            $db->execute($insertQuery, $user->id, 0);
                            $userBalance = 0;
                        }
                        $freeMoney = 1000;
                        $newUserBalance = $userBalance + $freeMoney;
                        if (!static::updateBalance($db, $user->id, $newUserBalance)) {
                            throw new InternalServerErrorException();
                        }
                        $db->commit();
                        $prompt = 'Congratz! You successfully received ' . $freeMoney . '$. Now you have ' . $newUserBalance . '$';
                        return response()->json($prompt);
                    } catch (\Throwable $e) {
                        $db->rollBack();
                        throw $e;
                    }
                }),
                // Check balance endpoint
                $route->get('/balance', function(AuthUserDto $user, DatabaseHandler $db) {
                    $userBalance = static::getBalance($db, $user->id) ?: 0;
                    return response()->json("User: " . $user->id . " - Balance: " . $userBalance . "$");
                }),
                // Exchange (transfer) endpoint
                $route->post('/exchange', function(
                        AuthUserDto $user,
                        DatabaseHandler $db,
                        #[ReqBody] BalanceExchangeRequest $exchangeRequest
                    ) {
                    $db->beginTransaction();
                    try {
                        // Lock both sender and receiver rows for update
                        $senderBalance = static::getBalance($db, $user->id, true);
                        $receiverBalance = static::getBalance($db, $exchangeRequest->receiverId, true);
                        if ($senderBalance === false || $receiverBalance === false) {
                            throw new NotFoundException();
                        }
                        if ($senderBalance < $exchangeRequest->amount) {
                            throw new ConflictException("Insufficient funds");
                        }
                        $newSenderBalance = $senderBalance - $exchangeRequest->amount;
                        $newReceiverBalance = $receiverBalance + $exchangeRequest->amount;
                        if (!static::updateBalance($db, $user->id, $newSenderBalance)
                            || !static::updateBalance($db, $exchangeRequest->receiverId, $newReceiverBalance)) {
                            throw new InternalServerErrorException();
                        }
                        $db->commit();
                        return response()->json('Sent successfully');
                    } catch (\Throwable $e) {
                        $db->rollBack();
                        throw $e;
                    }
                }),
            ]);
    }

    /**
     * Retrieve a user’s balance.
     *
     * @param DatabaseHandler $db
     * @param int $id
     * @param bool $forUpdate If true, lock the row for update.
     * @return int|false The balance amount or false if not found.
     */
    private static function getBalance(DatabaseHandler $db, int $id, bool $forUpdate = false) {
        $amountQuery = 'SELECT b.`amount` FROM `balance` AS b WHERE b.`user_id` = (?)';
        if ($forUpdate) {
            $amountQuery .= ' FOR UPDATE';
        }
        $rows = $db->query($amountQuery, $id);
        return !empty($rows) ? (int)$rows[0]['amount'] : false;
    }

    /**
     * Update the balance for a given user.
     *
     * @param DatabaseHandler $db
     * @param int $id
     * @param int $amount
     * @return bool Whether the update was successful.
     */
    private static function updateBalance(DatabaseHandler $db, int $id, int $amount) {
        $updateQuery = 'UPDATE `balance` SET `amount` = (?) WHERE `user_id` = (?)';
        return $db->execute($updateQuery, $amount, $id);
    }
}
