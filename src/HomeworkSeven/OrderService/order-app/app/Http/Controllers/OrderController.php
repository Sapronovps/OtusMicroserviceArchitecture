<?php

namespace App\Http\Controllers;

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

    public function sendOrderedEvent(int $allPrice, int $orderId, int $userId, string $email)
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
        $infoTopic = $context->createTopic('ordered');

        $data = [
            'price' => $allPrice,
            'orderId' => $orderId,
            'userId' => $userId,
            'email' => $email,
            'message' => 'Ваш заказа №' . $orderId . ' начал собираться. Сумма заказа ' . $allPrice . ' руб.'
        ];

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

        $billingAppUrl = getenv('BILLING_APP_URL');

        // Далее обращаемся в сервис Биллинга для оплаты заказа
        $response = Http::post($billingAppUrl . '/api/account/payment', [
            'user_id' => $request->get('user_id'),
            'sum' => $allPrice
        ]);

        if ($response->status() !== 200) {
            $message = $response->json('message');
            return $this->sendErrorResponse('Не удалось оформить заказ: ' . $message , 400);
        }

        // Далее резервируем товар
        $order = new Orders();
        $order->user_id = $request->get('user_id');
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
        }

        // Отправим асинхронный запрос в сервис Нотификации
        /** @var User $user */
        $user = User::find($request->get('user_id'));

        $notificationAppUrl = getenv('NOTIFICATION_APP_URL');
        $response = Http::post($notificationAppUrl . '/api/notification', [
            'email' => $user->email,
            'message' => 'Ваш заказа №' . $order->getKey() . ' начал собираться. Сумма заказа ' . $allPrice . ' руб.'
        ]);

        if ($response->status() !== 200) {
            $message = $response->json('message');
            return $this->sendErrorResponse('Не удалось оформить заказ:: ' . $message , 400);
        }

        // Далее сформируем событие в Kafka о то что резерв произошел
//        /** @var User $user */
//        $user = User::find($request->get('user_id'));

        // События использовать в 8 домашнем задании
//        $this->sendOrderedEvent($allPrice, $order->getKey(), $user->getKey(), $user->email);

        return response()->json([
            'status' => 'success',
            'message' => 'Ваш номер заказа №' . $order->getKey() . ' Ожидайте получения.',
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
