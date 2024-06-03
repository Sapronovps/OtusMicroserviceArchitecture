<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Illuminate\Http\Request;
use Throwable;

class NotificationController extends Controller
{
    public const STATUS_WAITING = 1;
    public const STATUS_SENT = 1;

    public function index()
    {
        $notifications = Notification::all();

        return response()->json([
            'status' => 'success',
            'notifications' => $notifications
        ]);
    }

    public function sendOrderedEvent()
    {
        $connectionFactory = new RdKafkaConnectionFactory([
            'global' => [
                'group.id' => 'order',
                'metadata.broker.list' => 'kafka',
                'enable.auto.commit' => 'false'
            ]
        ]);

        $context = $connectionFactory->createContext();

        $infoTopic = $context->createTopic('ordered');
        $allPrice = 100;

        $data = [
            'price' => $allPrice,
            'orderId' => 2,
            'userId' => 2
        ];

        $message = $context->createMessage(json_encode($data, JSON_THROW_ON_ERROR));
        $message->setKey('key-order');
        $context->createProducer()->send($infoTopic, $message);

        return response()->json([
            'status' => 'success',
            'message' => 'Все ок'
        ]);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'email' => "required|email|string|max:255",
                'message' => "required|string:max:255",
            ]);
        } catch (Throwable $ex) {
            return $this->sendErrorResponse('Произошла ошибка ' . $ex->getMessage(), 400);
        }

        $notification = Notification::create([
            'email' => $request->email,
            'message' => $request->message,
            'status_id' => self::STATUS_WAITING,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Уведомление успешно зарегистрировано',
            'notification' => $notification
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
