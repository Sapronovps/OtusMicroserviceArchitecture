<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\User;
use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Illuminate\Console\Command;

/*
 * Консьюмер, который слушает очередь order-payment.
 */
class PaymentConsumer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:consumer';

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
        $topic = 'order-payment';
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

                $userId = $messageRow['user_id'];
                $user = User::find($userId);
                $sum = $messageRow['allPrice'];

                if (!$user) {
                    $consumer->acknowledge($message);
                    continue;
                }

                /** @var ?Account $account */
                $account = Account::where('user_id', $userId)->first();
                $topicError = 'payment-error';

                if (!$account) {
                    $consumer->acknowledge($message);
                    continue;
                }

                if ($account->balance < $sum) {
                    $consumer->acknowledge($message);
                    continue;
                }

                $account->balance -= $sum;
                $account->save();

                dump($messageRow);

                $consumer->acknowledge($message);
                $consumer->reject($message);

                $topicSuccess = 'payment-success';
                $this->sendOrderedEvent($messageRow, $topicSuccess);
            }
        }
    }

    public function sendOrderedEvent(array $data, string $topic)
    {
        $kafkaBrokerList = env('KAFKA_BROKER_LIST', 'kafka');

        $connectionFactory = new RdKafkaConnectionFactory([
            'global' => [
                'group.id' => 'billing',
                'metadata.broker.list' => $kafkaBrokerList,
                'enable.auto.commit' => 'false'
            ]
        ]);

        $context = $connectionFactory->createContext();
        $infoTopic = $context->createTopic($topic);


        $message = $context->createMessage(json_encode($data, JSON_THROW_ON_ERROR));
        $message->setKey('key-billing');
        $context->createProducer()->send($infoTopic, $message);
    }
}
