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

    public function sendOrderedEvent(array $data)
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

        // Далее сформируем событие в Kafka о то что резерв произошел
        /** @var User $user */
        $user = User::find($request->get('user_id'));

        $data = [
            'price' => $allPrice,
            'orderId' => $order->getKey(),
            'userId' => $user->getKey(),
            'email' => $user->email,
            'message' => 'Ваш заказа №' . $order->getKey() . ' начал собираться. Сумма заказа ' . $allPrice . ' руб.'
        ];

        // События использовать в 8 домашнем задании
        $this->sendOrderedEvent($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Ваш номер заказа №' . $order->getKey() . ' Ожидайте получения.',
            'price' => $allPrice
        ]);
    }

    public function shipment(Request $request)
    {
        if (empty($request->get('order_id'))) {
            return $this->sendErrorResponse('Не указан обязательный параметр "order_id"', 400);
        }
        $orderId = $request->get('order_id');

        /** @var Orders $order */
        $order = Orders::find($orderId);

        if (empty($order)) {
            return $this->sendErrorResponse('Не удалось найти заказ с №' . $orderId, 400);
        }

        $order->status_id = StatusEnum::SHIPPED->value;
        $order->save();

        /** @var User $user */
        $user = User::find($order->user_id);

        /** @var OrderDetails[] $orderDetails */
        $orderDetails = OrderDetails::where(['order_id' => $orderId])->get();

        // Отгрузим заказ в магазин
        $shopAppUrl = getenv('SHOP_APP_URL');

        $products = [];

        foreach ($orderDetails as $orderDetail) {
            $products[] = [
              'product_id' => $orderDetail->product_id,
              'quantity' => $orderDetail->quantity,
            ];
        }

        $response = Http::post($shopAppUrl . '/api/order', [
            'order_id' => $orderId,
            'user_id' => $user->getKey(),
            'products' => $products
        ]);

        if ($response->status() !== 200) {
            $message = $response->json('message');
            return $this->sendErrorResponse('Не удалось отгрузить заказ: ' . $message , 400);
        }

        // Отправим уведомления пользователю, что его заказ отгружен со склада
        $message = 'Заказ №' . $orderId . ' успешно отгружен со склада';
        $data = [
            'email' => $user->email,
            'message' => $message
        ];
        $this->sendOrderedEvent($data);

        return response()->json([[
            'status' => 'success',
            'message' => $message
        ]]);
    }

    private function sendErrorResponse(string $message, int $status = 200)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
        ], $status);
    }
}
