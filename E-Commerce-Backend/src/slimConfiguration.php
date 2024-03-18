<?php

namespace src;

use App\DAO\{
    LocationDAO,
    UserDAO,
    TokenDAO,
    RoleDAO
};


function slimConfiguration(): \Slim\Container
{
    $configuration = [
        "settings" => [
            "displayErrorDetails" => getenv("DISPLAY_ERRORS_DETAILS"),
        ],
    ];
    $container = new \Slim\Container($configuration);



    //dependences

    $container->offsetSet(UserDAO::class, new UserDAO());
    $container->offsetSet(TokenDAO::class, new TokenDAO());
    $container->offsetSet(LocationDAO::class, new LocationDAO());
    $container->offsetSet(RoleDAO::class, new RoleDAO());


    return $container;
}