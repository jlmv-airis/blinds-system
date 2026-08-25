<?php
namespace App\classes;

use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class ValidateTokenFile {
    public function verifyToken() {
        if (JWTAuth::parseToken()->authenticate()) {
            return true;
        } else {
            return false;
        }
    }
}