<?php
namespace brunojk\LaravelRethinkdb\Queue;

use Carbon\Carbon;
use Illuminate\Queue\DatabaseQueue;
use Illuminate\Queue\Jobs\DatabaseJob;

class RethinkDBQueue extends DatabaseQueue
{
    /**
     * Pop the next job off of the queue.
     *
     * @param  string  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        $queue = $this->getQueue($queue);

        if (!is_null($this->expire)) {
            $this->releaseJobsThatHaveBeenReservedTooLong($queue);
        }

        if ($job = $this->getNextAvailableJobAndReserve($queue)) {
            return new DatabaseJob(
                $this->container, $this, $job, $queue
            );
        }
    }

    /**
     * Get the next available job for the queue and mark it as reserved.
     *
     * When using multiple daemon queue listeners to process jobs there
     * is a possibility that multiple processes can end up reading the
     * same record before one has flagged it as reserved.
     *
     * This race condition can result in random jobs being run more then
     * once. To solve this we use findOneAndUpdate to lock the next jobs
     * record while flagging it as reserved at the same time.
     *
     * @param  string|null $queue
     *
     * @return \StdClass|null
     */
    protected function getNextAvailableJobAndReserve($queue)
    {
        $job = $this->database->table($this->table)->findOneAndUpdate(
            [
                'queue'        => $this->getQueue($queue),
                'reserved'     => 0,
                'available_at' => ['$lte' => $this->getTime()],

            ],
            [
                '$set' => [
                    'reserved'    => 1,
                    'reserved_at' => $this->getTime(),
                ],
            ],
            [
                'returnDocument' => FindOneAndUpdate::RETURN_DOCUMENT_AFTER,
                'sort'           => ['available_at' => 1],
            ]
        );

        if ($job) {
            $job->id = $job->_id;
        }

        return $job;
    }

    /**
     * Release the jobs that have been reserved for too long.
     *
     * @param  string  $queue
     * @return void
     */
    protected function releaseJobsThatHaveBeenReservedTooLong($queue)
    {
        $expired = Carbon::now()->subSeconds($this->expire)->getTimestamp();

        $reserved = $this->database->table($this->table)
            ->where('queue', $this->getQueue($queue))
            ->where('reserved', 1)
            ->where('reserved_at', '<=', $expired)->get();

        foreach ($reserved as $job) {
            $attempts = $job['attempts'] + 1;
            $this->releaseJob($job['_id'], $attempts);
        }
    }

    /**
     * Release the given job ID from reservation.
     *
     * @param  string $id
     * @param  int $attempts
     * @return void
     */
    protected function releaseJob($id, $attempts)
    {
        $this->database->table($this->table)->get($id)->update([
            'reserved'    => 0,
            'reserved_at' => null,
            'attempts'    => $attempts,
        ])->run();
    }

    /**
     * Delete a reserved job from the queue.
     *
     * @param  string  $queue
     * @param  string  $id
     * @return void
     */
    public function deleteReserved($queue, $id)
    {
        $this->database->table($this->table)->get($id)->delete();
    }
}