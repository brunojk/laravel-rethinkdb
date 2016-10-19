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
        $result = $this->createModel()->newQuery()->getQuery()
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
        $result = $this->createModel()->newQuery()->getQuery()
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

        $index = null;
        $values = null;

        foreach ($credentials as $key => $value) {
            if (! Str::contains($key, 'password')) {
                $index = is_null($index) ? $key : $index . '_' . $key;
                $values = is_null($values) ? [] : $values;
                $values[] = $value;
            }
        }

        if (is_null($index) || is_null($values))
            return null;

        $values = count($values) == 1 ? $values[0] : $values;

        $result = $this->createModel()->newQuery()->getQuery()
            ->r()->getAll($values, ['index' => $index])->run();

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