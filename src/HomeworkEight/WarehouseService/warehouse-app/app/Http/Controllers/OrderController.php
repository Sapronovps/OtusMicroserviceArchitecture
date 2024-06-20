<?php

namespace App\Http\Controllers;

use App\Enums\StatusEnum;
use App\Models\OrderDetails;
use App\Models\Orders;
use App\Models\Product;
use App\Models\User;
use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Orders::all();

        return response()->json([
            'status' => 'success',
            'orders' => $orders
        ]);
    }

    public function testKafka()
    {
        $data = [
            'email' => 'pavel@otus.ru',
            'message' => 'Тестовое сообщение'
        ];

        $this->sendOrderedEvent($data);

        $kafkaBrokerList = env('KAFKA_BROKER_LIST', 'kafka');

        return response()->json([
           'kafkaBrokerList' => $kafkaBrokerList,
           'message' => 'Test message successfully has been sent'
        ]);
    }

    public function sendOrderedEvent(array $data, string $topic)
    {
        $kafkaBrokerList = env('KAFKA_BROKER_LIST', 'kafka');

        $connectionFactory = new RdKafkaConnectionFactory([
           'global' => [
               'group.id' => 'order',
               'metadata.broker.list' => $kafkaBrokerList,
               'enable.auto.commit' => 'false'
           ]
        ]);

        $context = $connectionFactory->createContext();
        $infoTopic = $context->createTopic($topic);


        $message = $context->createMessage(json_encode($data, JSON_THROW_ON_ERROR));
        $message->setKey('key-order');
        $context->createProducer()->send($infoTopic, $message);
    }

    public function getEnv()
    {
        dump($_ENV);

        echo getenv('BILLING_APP_URL');
    }

    public function store(Request $request)
    {
        if (empty($request->get('user_id'))) {
            return $this->sendErrorResponse('Не указан обязательный параметр "user_id"', 400);
        }

        if (empty($request->get('products'))) {
            return $this->sendErrorResponse('Не указан обязательный параметр "product_ids"', 400);
        }

        $products = $request->get('products');
        $allPrice = 0;

        $productsInDb = [];

        foreach ($products as $product) {
            $productInDb = $productsInDb[$product['product_id']] ?? Product::find($product['product_id']);
            $productsInDb[$product['product_id']] = $productInDb;

            if ($productInDb->quantity < $product['quantity']) {
                return $this->sendErrorResponse('Не хватает товара для резерва');
            }

            $productInDb->quantity -= $product['quantity'];

            $allPrice += $product['quantity'] * $productInDb->price;
        }

        // Далее создаем событие для оплаты
        $topic = 'order-payment';
        $data = [
            'user_id' => $request->get('user_id'),
            'products' => $products,
            'allPrice' => $allPrice,
        ];
        $this->sendOrderedEvent($data, $topic);

        return response()->json([
            'status' => 'success',
            'message' => 'Заказ начал оформляться, ожидайте подтверждения.',
            'price' => $allPrice
        ]);
    }

    private function sendErrorResponse(string $message, int $status = 200)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
        ], $status);
    }
}
