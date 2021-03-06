<?php

namespace brunojk\LaravelRethinkdb\Schema;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use r;

class Blueprint extends \Illuminate\Database\Schema\Blueprint
{
    /**
     * Create a new schema blueprint.
     *
     * @param Connection $connection
     * @param string     $table
     */
    protected $options;

    public function __construct(Connection $connection, $table)
    {
        $this->connection = $connection;

        $options = is_array($table) && isset($table['options']) ? $table['options'] : [];
        $table = is_array($table) ? $table['name'] : $table;

        $this->table = $table;
        $this->options = $options;
    }

    protected function getOptions() {
        if( count($this->options) )
            return $this->options;

        $res = [];

        $conf = Config::get('rethinkdb.clustering');

        if( $conf != 'default' and is_array($conf) ) {
            $def = (array) Arr::get($conf, 'all_tables');
            $tdef = Arr::get($conf, $this->table);

            if( $tdef != 'default' )
                $res = array_merge($def, (array) $tdef );
        }

        return $res;
    }

    /**
     * Execute the blueprint against the database.
     *
     * @param \Illuminate\Database\Connection              $connection
     * @param \Illuminate\Database\Schema\Grammars\Grammar $grammar
     *
     * @return void
     */
    public function build(Connection $connection, Grammar $grammar)
    {
    }

    /**
     * Indicate that the table needs to be created.
     *
     * @return bool
     */
    public function create()
    {
        $conn = $this->connection->getConnection();
        $db = r\db($this->connection->getDatabaseName());

        $this->options = $this->getOptions();

        if( count($this->options) )
            $db->tableCreate($this->table, $this->options)->run($conn);
        else
            $db->tableCreate($this->table)->run($conn);
    }

    /**
     * Indicate that the collection should be dropped.
     *
     * @return bool
     */
    public function drop()
    {
        $conn = $this->connection->getConnection();
        $db = r\db($this->connection->getDatabaseName());
        $db->tableDrop($this->table)->run($conn);
    }

    /**
     * Specify an index for the collection.
     *
     * @param string $column
     * @param mixed $options
     * @param null $algorithm
     *
     * @return Blueprint
     */
    public function index($column, $options = null, $algorithm = NULL)
    {
        $conn = $this->connection->getConnection();
        $db = r\db($this->connection->getDatabaseName());
        $db->table($this->table)->indexCreate($column)
            ->run($conn);

        return $this;
    }
}
