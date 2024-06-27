<?php

namespace App\Console\Commands;

use App\Enums\StatusEnum;
use App\Models\Couriers;
use App\Models\CouriersOrders;
use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Illuminate\Console\Command;

/*
 * Консьюмер, который слушает очередь order-success.
 */
class DeliveryConsumer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delivery:consumer';

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
        $topic = 'order-success';
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

        $infoQueue = $context->createQueue($topic);

        $consumer = $context->createConsumer($infoQueue);

        while (true) {
            $message = $consumer->receive();

            if ($message) {
                ### Вывод тела сообщения

                $messageRow = json_decode($message->getBody(), true);

                if (isset($messageRow['test'])) {
                    $consumer->acknowledge($message);
                    continue;
                }

                $orderId = $messageRow['order_id'];
                $couriers = Couriers::all();
                $freeCourier = null;

                /** @var CouriersOrders $couriersOrders */
                $couriersOrders = CouriersOrders::where('order_id', $orderId)->first();

                if (null !== $couriersOrders) {
                    $consumer->acknowledge($message);
                    continue;
                }

                dump($messageRow);

                foreach ($couriers as $courier) {
                    if ($courier->status_id === StatusEnum::FREE->value) {
                        $freeCourier = $courier;
                        break;
                    }
                }

                if (null === $freeCourier) {
                    $topicError = 'delivery-error';
                    $this->sendOrderedEvent($messageRow, $topicError);
                    return;
                }

                $freeCourier->status_id = StatusEnum::BUSY->value;
                $freeCourier->save();

                $couriersOrders = new CouriersOrders();
                $couriersOrders->courier_id = $freeCourier->getKey();
                $couriersOrders->order_id = $orderId;
                $couriersOrders->save();

                $topicSuccess = 'delivery-success';
                $this->sendOrderedEvent($messageRow, $topicSuccess);
            }
        }
    }

    public function sendOrderedEvent(array $data, string $topic)
    {
        $kafkaBrokerList = env('KAFKA_BROKER_LIST', 'kafka');

        $connectionFactory = new RdKafkaConnectionFactory([
            'global' => [
                'group.id' => 'delivery',
                'metadata.broker.list' => $kafkaBrokerList,
                'enable.auto.commit' => 'false'
            ]
        ]);

        $context = $connectionFactory->createContext();
        $infoTopic = $context->createTopic($topic);


        $message = $context->createMessage(json_encode($data, JSON_THROW_ON_ERROR));
        $message->setKey('key-delivery');
        $context->createProducer()->send($infoTopic, $message);
    }
}
