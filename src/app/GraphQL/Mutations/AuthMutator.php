<?php

namespace App\GraphQL\Mutations;

use App\Exceptions\CustomException;
use App\Models\People;
use Illuminate\Http\Response;
use Firebase\JWT\JWT;

class AuthMutator
{
    /**
     * @param $rootValue
     * @param $args
     *
     * @throws \Exception
     *
     * @return array
     */
    public function login($rootValue, array $args)
    {
        // TODO implement the resolver
        $people = People::where('PeopleUsername', $args['input']['username'])->first();

        if (!$people || $people->PeopleIsActive == 0 || (sha1($args['input']['password']) != $people->PeoplePassword)) {
            throw new CustomException(
                'Invalid credential',
                'Email and password are incorrect'
            );
        }


        $issuedAt = time();
        $startTime = $issuedAt + config('jwt.ttl');
        $expTime = $issuedAt + config('jwt.refresh_ttl');

        return [
            'message' => 'success',
            'access_token' => $people->createToken('token-name-changeme')->plainTextToken,
            'token_type' => 'bearer',
            'expires_in' => $expTime,
        ];
    }
}
