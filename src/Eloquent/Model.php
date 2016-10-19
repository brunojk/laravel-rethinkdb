<?php

namespace brunojk\LaravelRethinkdb\Eloquent;

use DateTime;
use Carbon\Carbon;
use brunojk\LaravelRethinkdb\Eloquent\Relations\BelongsTo;
use brunojk\LaravelRethinkdb\Eloquent\Relations\BelongsToMany;
use brunojk\LaravelRethinkdb\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Model extends \Illuminate\Database\Eloquent\Model
{

    /**
     * Append one or more values to an array.
     *
     * @return mixed
     */
    public function push()
    {
        if ($parameters = func_get_args()) {
            $unique = false;
            if (count($parameters) == 3) {
                list($column, $values, $unique) = $parameters;
            } else {
                list($column, $values) = $parameters;
            }
            // Do batch push by default.
            if (! is_array($values)) {
                $values = [$values];
            }
            $query = $this->setKeysForSaveQuery($this->newQuery());
            $this->pushAttributeValues($column, $values, $unique);
            return $query->push($column, $values, $unique);
        }
        return parent::push();
    }

    /**
     * Remove one or more values from an array.
     *
     * @param  string  $column
     * @param  mixed   $values
     * @return mixed
     */
    public function pull($column, $values)
    {
        // Do batch pull by default.
        if (! is_array($values)) {
            $values = [$values];
        }
        $query = $this->setKeysForSaveQuery($this->newQuery());
        $this->pullAttributeValues($column, $values);
        return $query->pull($column, $values);
    }

    /**
     * Append one or more values to the underlying attribute value and sync with original.
     *
     * @param  string  $column
     * @param  array   $values
     * @param  bool    $unique
     */
    protected function pushAttributeValues($column, array $values, $unique = false)
    {
        $current = $this->getAttributeFromArray($column) ?: [];

        foreach ($values as $value) {
            // Don't add duplicate values when we only want unique values.
            if ($unique and in_array($value, $current)) {
                continue;
            }
            array_push($current, $value);
        }
        $this->attributes[$column] = $current;

        $this->syncOriginalAttribute($column);
    }

    /**
     * Remove one or more values to the underlying attribute value and sync with original.
     *
     * @param  string  $column
     * @param  array   $values
     */
    protected function pullAttributeValues($column, array $values)
    {
        $current = $this->getAttributeFromArray($column) ?: [];
        foreach ($values as $value) {
            $keys = array_keys($current, $value);
            foreach ($keys as $key) {
                unset($current[$key]);
            }
        }
        $this->attributes[$column] = array_values($current);
        $this->syncOriginalAttribute($column);
    }

    /**
     * Get an attribute from the $attributes array.
     *
     * @param  string  $key
     * @return mixed
     */
    protected function getAttributeFromArray($key)
    {
        // Support keys in dot notation.
        if (str_contains($key, '.')) {
            $attributes = array_dot($this->attributes);
            if (array_key_exists($key, $attributes)) {
                return $attributes[$key];
            }
        }
        return parent::getAttributeFromArray($key);
    }

    /**
     * Set a given attribute on the model.
     *
     * @param  string  $key
     * @param  mixed   $value
     */
    public function setAttribute($key, $value)
    {
        // Support keys in dot notation.
        if (str_contains($key, '.')) {
            if (in_array($key, $this->getDates()) && $value) {
                $value = $this->fromDateTime($value);
            }
            array_set($this->attributes, $key, $value);
            return;
        }
        parent::setAttribute($key, $value);
    }

    /**
     * Convert the model's attributes to an array.
     *
     * @return array
     */
    public function attributesToArray()
    {
        $attributes = parent::attributesToArray();

        // Convert dot-notation dates.
        foreach ($this->getDates() as $key) {
            if (str_contains($key, '.') and array_has($attributes, $key)) {
                array_set($attributes, $key, (string) $this->asDateTime(array_get($attributes, $key)));
            }
        }
        return $attributes;
    }

    /**
     * Get the format for database stored dates.
     *
     * @return string
     */
    protected function getDateFormat()
    {
        return $this->dateFormat ?: 'Y-m-d H:i:s';
    }

    /**
     * Get the table qualified key name.
     *
     * @return string
     */
    public function getQualifiedKeyName()
    {
        return $this->getKeyName();
    }

    /**
     * Ensure Timestamps are returned in DateTime.
     *
     * @param \DateTime $value
     *
     * @return \DateTime
     */
    protected function asDateTime($value)
    {
        // Legacy support for Laravel 5.0
        if (!$value instanceof Carbon) {
            return Carbon::instance($value);
        }

        return parent::asDateTime($value);
    }

    /**
     * Retain DateTime format for storage.
     *
     * @param \DateTime $value
     *
     * @return string
     */
    public function fromDateTime($value)
    {
        if ($value instanceof DateTime) {
            return $value;
        }

        return parent::asDateTime($value);
    }

    protected function originalIsNumericallyEquivalent($key)
    {
        $current = $this->attributes[$key];
        $original = $this->original[$key];
        // Date comparison.
        if (in_array($key, $this->getDates())) {
            $current = $current instanceof DateTime ? $this->asDateTime($current) : $current;
            $original = $original instanceof DateTime ? $this->asDateTime($original) : $original;

            return $current == $original;
        }

       return parent::originalIsNumericallyEquivalent($key);
    }

    /**
     * Get a new query builder instance for the connection.
     *
     * @return Builder
     */
    protected function newBaseQueryBuilder()
    {
        $connection = $this->getConnection();
        // Check the connection type
        if ($connection instanceof \brunojk\LaravelRethinkdb\Connection) {
            return new QueryBuilder($connection);
        }

        return parent::newBaseQueryBuilder();
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param \brunojk\LaravelRethinkdb\Query\Builder $query
     *
     * @return \brunojk\LaravelRethinkdb\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * Define an inverse one-to-one or many relationship.
     *
     * @param string $related
     * @param string $foreignKey
     * @param string $otherKey
     * @param string $relation
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function belongsTo($related, $foreignKey = null, $otherKey = null, $relation = null)
    {
        // If no relation name was given, we will use this debug backtrace to extract
        // the calling method's name and use that as the relationship name as most
        // of the time this will be what we desire to use for the relationships.
        if (is_null($relation)) {
            list(, $caller) = debug_backtrace(false, 2);
            $relation = $caller['function'];
        }
        // If no foreign key was supplied, we can use a backtrace to guess the proper
        // foreign key name by using the name of the relationship function, which
        // when combined with an "_id" should conventionally match the columns.
        if (is_null($foreignKey)) {
            $foreignKey = snake_case($relation).'_id';
        }
        $instance = new $related();
        // Once we have the foreign key names, we'll just create a new Eloquent query
        // for the related models and returns the relationship instance which will
        // actually be responsible for retrieving and hydrating every relations.
        $query = $instance->newQuery();
        $otherKey = $otherKey ?: $instance->getKeyName();

        return new BelongsTo($query, $this, $foreignKey, $otherKey, $relation);
    }

    /**
     * Define a one-to-one relationship.
     *
     * @param string $related
     * @param string $foreignKey
     * @param string $localKey
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function hasOne($related, $foreignKey = null, $localKey = null)
    {
        $foreignKey = $foreignKey ?: $this->getForeignKey();
        $instance = new $related();
        $localKey = $localKey ?: $this->getKeyName();

        return new HasOne($instance->newQuery(), $this, $foreignKey, $localKey);
    }

    /**
     * Define a one-to-many relationship.
     *
     * @param string $related
     * @param string $foreignKey
     * @param string $localKey
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function hasMany($related, $foreignKey = null, $localKey = null)
    {
        $foreignKey = $foreignKey ?: $this->getForeignKey();
        $instance = new $related();
        $localKey = $localKey ?: $this->getKeyName();

        return new HasMany($instance->newQuery(), $this, $foreignKey, $localKey);
    }

    /**
     * Define a many-to-many relationship.
     *
     * @param  string  $related
     * @param  string  $collection
     * @param  string  $foreignKey
     * @param  string  $otherKey
     * @param  string  $relation
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function belongsToMany($related, $collection = null, $foreignKey = null, $otherKey = null, $relation = null)
    {
        // If no relationship name was passed, we will pull backtraces to get the
        // name of the calling function. We will use that function name as the
        // title of this relation since that is a great convention to apply.
        if (is_null($relation))
            $relation = $this->getBelongsToManyCaller();

        // Check if it is a relation with an original model.
        if (! is_subclass_of($related, 'brunojk\LaravelRethinkdb\Eloquent\Model')) {
            return parent::belongsToMany($related, $collection, $foreignKey, $otherKey, $relation);
        }
        // First, we'll need to determine the foreign key and "other key" for the
        // relationship. Once we have determined the keys we'll make the query
        // instances as well as the relationship instances we need for this.
        $foreignKey = $foreignKey ?: $this->getForeignKey() . 's';
        $instance = new $related;
        $otherKey = $otherKey ?: $instance->getForeignKey() . 's';
        // If no table name was provided, we can guess it by concatenating the two
        // models using underscores in alphabetical order. The two model names
        // are transformed to snake case from their default CamelCase also.
        if (is_null($collection)) {
            $collection = $instance->getTable();
        }
        // Now we're ready to create a new query builder for the related model and
        // the relationship instances for the relation. The relations will set
        // appropriate query constraint and entirely manages the hydrations.
        $query = $instance->newQuery();
        return new BelongsToMany($query, $this, $collection, $foreignKey, $otherKey, $relation);
    }
}
