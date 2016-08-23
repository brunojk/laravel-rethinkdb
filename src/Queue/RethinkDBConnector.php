<?php
namespace brunojk\LaravelRethinkdb\Queue;

use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Queue\Connectors\ConnectorInterface;
use Illuminate\Support\Arr;

class RethinkDBConnector implements ConnectorInterface
{
    /**
     * Database connections.
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $connections;

    /**
     * Create a new connector instance.
     *
     * @param ConnectionResolverInterface|\Illuminate\Database\ConnectionResolverInterface $connections
     */
    public function __construct(ConnectionResolverInterface $connections)
    {
        $this->connections = $connections;
    }

    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        return new RethinkDBQueue(
            $this->connections->connection(Arr::get($config, 'connection')),
            $config['table'],
            $config['queue'],
            Arr::get($config, 'expire', 60)
        );
    }
}
