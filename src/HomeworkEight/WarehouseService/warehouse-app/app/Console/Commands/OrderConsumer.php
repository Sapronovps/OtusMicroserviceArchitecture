<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\OrderDetails;
use App\Models\Orders;
use App\Models\Product;
use App\Models\User;
use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Illuminate\Console\Command;

/*
 * Консьюмер, который слушает очередь payment-success.
 */
class OrderConsumer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:consumer';

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
        $this->init();

        $topic = 'payment-success';
        $kafkaBrokerList = env('KAFKA_BROKER_LIST', 'kafka');

        $groupId = 'order';
        $connectionFactory = new RdKafkaConnectionFactory([
            'global' => [
                'group.id' => $groupId,
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

                if (!isset($messageRow['user_id']) || isset($messageRow['test'])) {
                    $consumer->acknowledge($message);
                    continue;
                }

                $userId = $messageRow['user_id'];
                $products = $messageRow['products'];

                $productsInDb = [];

                foreach ($products as $product) {
                    $productInDb = $productsInDb[$product['product_id']] ?? Product::find($product['product_id']);
                    $productsInDb[$product['product_id']] = $productInDb;

                    if ($productInDb->quantity < $product['quantity']) {
                        $consumer->acknowledge($message);
                        continue;
                    }

                    $productInDb->quantity -= $product['quantity'];
                }

                $order = new Orders();
                $order->user_id = $userId;
                $order->save();

                foreach ($products as $product) {
                    $orderDetail = new OrderDetails();
                    $orderDetail->order_id = $order->getKey();
                    $orderDetail->product_id = $product['product_id'];
                    $orderDetail->quantity = $product['quantity'];
                    $orderDetail->save();

                    /** @var Product $productInDb */
                    $productInDb = $productsInDb[$product['product_id']];
                    $productInDb->save();


                    dump($messageRow);
                    $messageRow['order_id'] = $order->getKey();

                    $consumer->acknowledge($message);
                    $consumer->reject($message);

                    $topicSuccess = 'order-success';
                    $this->sendOrderedEvent($messageRow, $topicSuccess, $groupId);
                }
            }
        }
    }

    public function init()
    {
        $data = ['test' => 'message', 'user_id' => 1, 'allPrice' => 100, 'order_id' => 1, 'products' => []];

        $topic1 = 'order-payment';
        $this->sendOrderedEvent($data, $topic1, 'order');

        $topic2 = 'payment-success';
        $this->sendOrderedEvent($data, $topic2, 'notification');

        $topic3 = 'order-success';
        $this->sendOrderedEvent($data, $topic3, 'order');

        $topic4 = 'delivery-success';
        $this->sendOrderedEvent($data, $topic4, 'delivery');

        $topic5 = 'delivery-error';
        $this->sendOrderedEvent($data, $topic5, 'delivery');
    }

    public function sendOrderedEvent(array $data, string $topic, string $groupId)
    {
        $kafkaBrokerList = env('KAFKA_BROKER_LIST', 'kafka');

        $connectionFactory = new RdKafkaConnectionFactory([
            'global' => [
                'group.id' => $groupId,
                'metadata.broker.list' => $kafkaBrokerList,
                'enable.auto.commit' => 'false'
            ]
        ]);

        $context = $connectionFactory->createContext();
        $infoTopic = $context->createTopic($topic);


        $message = $context->createMessage(json_encode($data, JSON_THROW_ON_ERROR));
        $message->setKey('key-' . $groupId);
        $context->createProducer()->send($infoTopic, $message);
    }
}
