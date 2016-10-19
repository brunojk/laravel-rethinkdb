<?php
namespace brunojk\LaravelRethinkdb\Auth;

use Illuminate\Support\Str;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Hashing\Hasher;

class RethinkUserProvider extends EloquentUserProvider
{
    /**
     * Create a new database user provider.
     *
     * @param  \Illuminate\Contracts\Hashing\Hasher $hasher
     * @param  string $model
     */
    public function __construct(Hasher $hasher, $model) {
        parent::__construct($hasher, $model);
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier) {
        $model = $this->createModel();

        $result = $model->newQuery()->getQuery()
            ->r()->get($identifier)->run();

        $result = $this->hydrate($result);

        if( $result )
            $result = $result->first();

        return $result;
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed  $identifier
     * @param  string  $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($identifier, $token)
    {
        $model = $this->createModel();

        $result = $model->newQuery()->getQuery()
            ->r()->getAll($identifier)
            ->filter(function($row) use($token){
                return $row('token')->eq($token);
            })
            ->run();

        $result = $this->hydrate($result);

        if( $result )
            $result = $result->first();

        return $result;
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials) {
        if (empty($credentials))
            return null;

        $new_credentials = [];

        foreach ($credentials as $key => $value) {
            if (! Str::contains($key, 'password')) {
                $new_credentials[0] = isset($new_credentials[0]) ? $new_credentials[0] . '_' . $key : $key;
                $new_credentials[1] = isset($new_credentials[1]) ? $new_credentials[1] : [];
                $new_credentials[1][] = $value;
            }
        }

        $model = $this->createModel();

        $result = $model->newQuery()->getQuery()
            ->r()->getAll($new_credentials[1], ['index' => $new_credentials[0]])->run();

        $result = $this->hydrate($result);

        if( $result )
            $result = $result->first();

        return $result;
    }

    protected function hydrate( $result ) {
        if( $result instanceof \ArrayObject)
            $result = [(array) $result];

        else if( is_object($result) )
            $result = $result->toArray();

        return $result ? call_user_func(array($this->model, 'hydrate'), $result) : null;
    }
}