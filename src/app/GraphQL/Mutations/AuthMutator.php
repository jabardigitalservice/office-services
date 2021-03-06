<?php

namespace App\GraphQL\Mutations;

use App\Exceptions\CustomException;
use App\Models\People;
use Illuminate\Support\Arr;

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
        /**
         * @var $people People
         */
        // TODO implement the resolver
        $people = People::where('PeopleUsername', $args['input']['username'])->first();

        if (!$people || $people->PeopleIsActive == 0 || (sha1($args['input']['password']) != $people->PeoplePassword)) {
            throw new CustomException(
                'Invalid credential',
                'Email and password are incorrect'
            );
        }


        $issuedAt = time();
        $expTime = $issuedAt + config('jwt.refresh_ttl');

        $deviceName = Arr::get($args, 'input.device', 'default');
        $deviceFcmToken = Arr::get($args, 'input.fcm_token');

        $accessToken = $people->createToken($deviceName);
        $accessToken->accessToken->update([
            'fcm_token' => $deviceFcmToken
        ]);

        return [
            'message' => 'success',
            'access_token' => $accessToken->plainTextToken,
            'token_type' => 'bearer',
            'expires_in' => $expTime,
            'profile' => $people
        ];
    }
}
