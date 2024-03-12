<?php

namespace App\services;

use App\DAO\TokenDAO;
use App\Models\TokenModel;
use App\Models\UserModel;
use Firebase\JWT\JWT;

class AuthService
{
    public static function encodeToken($token): string
    {
        try {
            return JWT::encode($token, getenv('JWT_SECRET_KEY'));

        } catch (\Exception $e) {
            throw $e;
        }
    }

    public static function decodeToken(string $token): object
    {
        try {
            return JWT::decode(
                $token,
                getenv('JWT_SECRET_KEY'),
                ['HS256']
            );

        } catch (\Exception $e) {
            throw $e;
        }
    }

    public static function setTokens(UserModel $usuario): array
    {
        $expiredAt = (new \DateTime())->modify('+1 hour')->format('Y-m-d H:i:s');

        $tokenPayload = [
            'sub' => $usuario->getId(),
            'nome' => $usuario->getName(),
            'email' => $usuario->getEmail(),
            'exp' => (new \DateTime($expiredAt))->getTimestamp()
        ];

        $token = self::encodeToken($tokenPayload);

        $refreshTokenPayload = [
            'email' => $usuario->getEmail(),
            'random' => uniqid()
        ];

        $refreshToken = self::encodeToken($refreshTokenPayload);

        $tokenModel = new TokenModel();
        $tokenModel->setExpiredAt($expiredAt)
            ->setToken($token)
            ->setRefreshToken($refreshToken)
            ->setUserId($usuario->getId());

        $tokenDAO = new TokenDAO();
        $tokenDAO->createToken($tokenModel);

        return [
            "token" => $token,
            "refresh_token" => $refreshToken
        ];
    }
}