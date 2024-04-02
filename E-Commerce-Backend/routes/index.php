<?php
use App\Controllers\{
    AuthController,
    LocationController,
    UserController,
    ProductController,
    ShoppingCartController
};
use App\Middlewares\TokenMiddleware;
use function src\{slimConfiguration, jwtAuth};

$app = new \Slim\App(slimConfiguration());

$app->options("/{routes:.+}", function ($request, $response, $args) {
    return $response;
});

$app->add(function ($request, $response, $next) {
    $response = $next($request, $response);
    return $response->withHeader("Access-Control-Allow-Origin", "*")
        ->withHeader("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, OPTIONS")
        ->withHeader("Access-Control-Allow-Headers", "Content-Type, Authorization, X-Auth-Token, X-Refresh-Token")
        ->withHeader("Access-Control-Expose-Headers", "Content-Type, Authorization, X-Auth-Token, X-Refresh-Token");

});




// ROUTES 
// ================================

$app->post("/login", AuthController::class . ":login");

$app->post("/user", UserController::class . ":insertUser");

$app->group("", function () use ($app) {
    $app->get("/locations", LocationController::class . ":getAllLocationsByType");

    $app->get("/states", LocationController::class . ":getStates");

    $app->get("/cities", LocationController::class . ":getCitiesByState");

    $app->get("/cep/{cep}", LocationController::class . ":getLocationByCep");

    $app->put("/user", UserController::class . ":updateUser");

    $app->put("/password", UserController::class . ":updatePassword");

    $app->put("/userRole[/{id}]", UserController::class . ":updateUserRole");

    $app->get("/users", UserController::class . ":getAllUsers");

    $app->get("/user[/{id}]", UserController::class . ":getUserById");

    $app->delete("/user/{id}", UserController::class . ":deleteUserById");

    $app->get("/products", ProductController::class . ":getAllProducts");

    $app->get("/products/my", ProductController::class . ":getMyProducts");

    $app->get("/product/{id}", ProductController::class . ":getProductById");

    $app->post("/product", ProductController::class . ":insertProduct");

    $app->put("/product", ProductController::class . ":updateProduct");

    $app->delete("/product/{id}", ProductController::class . ":deleteProduct");

    $app->get("/shoppingCart", ShoppingCartController::class . ":getShoppingCartByUserId");

    $app->post("/shoppingCart", ShoppingCartController::class . ":addProductToShoppingCart");

    $app->delete("/shoppingCart/{id}", ShoppingCartController::class . ":removeProductFromShoppingCart");


})->add(TokenMiddleware::class)->add(jwtAuth());

// ================================

$app->map(["GET", "POST", "PUT", "DELETE", "PATCH"], "/{routes:.+}", function ($req, $res) {
    $handler = $this->notFoundHandler;
    return $handler($req, $res);
});

$app->run();