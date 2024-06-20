<?php

namespace App\Console\Commands;

use App\Http\Controllers\NotificationController;
use App\Models\Notification;
use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Illuminate\Console\Command;

/*
 * Консьюмер, который слушает очередь ordered.
 */
class NotificationConsumer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:consumer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $kafkaBrokerList = env('KAFKA_BROKER_LIST', 'kafka');

        $connectionFactory = new RdKafkaConnectionFactory([
            'global' => [
                'group.id' => 'notification',
                'metadata.broker.list' => $kafkaBrokerList,
                'enable.auto.commit' => 'false'
            ],
            'topic'  => [
                'auto.offset.reset' => 'beginning',
            ]
        ]);

        $context = $connectionFactory->createContext();

        $infoQueue = $context->createQueue('ordered');

        $consumer = $context->createConsumer($infoQueue);

        while (true) {
            $message = $consumer->receive();

            if ($message) {
                ### Вывод тела сообщения

                $messageRow = json_decode($message->getBody(), true);

                Notification::create([
                    'email' => $messageRow['email'],
                    'message' => $messageRow['message'],
                    'status_id' => NotificationController::STATUS_WAITING,
                ]);

                dump($messageRow);

                $consumer->acknowledge($message);
                $consumer->reject($message);
            }
        }
    }
}
