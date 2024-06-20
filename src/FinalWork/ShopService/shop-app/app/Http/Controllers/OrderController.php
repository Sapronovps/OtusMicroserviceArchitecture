<?php

namespace App\Http\Controllers;

use App\Enums\StatusEnum;
use App\Models\OrderDetails;
use App\Models\Orders;
use App\Models\User;
use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Illuminate\Http\Request;

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
        if (empty($request->get('order_id'))) {
            return $this->sendErrorResponse('Не указан обязательный параметр "order_id"', 400);
        }

        if (empty($request->get('user_id'))) {
            return $this->sendErrorResponse('Не указан обязательный параметр "user_id"', 400);
        }

        if (empty($request->get('products'))) {
            return $this->sendErrorResponse('Не указан обязательный параметр "product_ids"', 400);
        }
        $orderId = $request->get('order_id');

        /** @var Orders $order */
        $order = Orders::find($orderId);

        if (!empty($order)) {
            return $this->sendErrorResponse('Заказ №' . $orderId . ' уже прибыл в магазин', 400);
        }

        $products = $request->get('products');

        // Далее резервируем товар
        $order = new Orders();
        $order->id = $orderId;
        $order->user_id = $request->get('user_id');
        $order->status_id = StatusEnum::CREATED->value;
        $order->save();

        foreach ($products as $product) {
            $orderDetail = new OrderDetails();
            $orderDetail->order_id = $order->getKey();
            $orderDetail->product_id = $product['product_id'];
            $orderDetail->quantity = $product['quantity'];
            $orderDetail->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Заказ №' . $order->getKey() . ' прибыл в магазин.',
        ]);
    }

    public function shipped(Request $request)
    {
        if (empty($request->get('order_id'))) {
            return $this->sendErrorResponse('Не указан обязательный параметр "order_id"', 400);
        }

        $orderId = $request->get('order_id');
        /** @var Orders $order */
        $order = Orders::find($orderId);

        if (empty($order)) {
            return $this->sendErrorResponse('Не удалось найти заказа №' . $orderId, 400);
        }

        if ($order->status_id === StatusEnum::SHIPPED->value) {
            return $this->sendErrorResponse('Заказ №' . $orderId . ' уже был выдан', 400);
        }

        $order->status_id = StatusEnum::SHIPPED->value;
        $order->save();

        // Отправим уведомление пользователю с благодарностью о покупке
        /** @var User $user */
        $user = User::find($order->user_id);
        $data = [
            'email' => $user->email,
            'message' => 'Спасибо за заказ №' . $order->id . '. Ждем Вас снова.'
        ];
        $this->sendOrderedEvent($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Заказ №' . $orderId .  ' успешно выдан',
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
