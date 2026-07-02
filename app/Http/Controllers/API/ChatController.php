<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chats\ChatRequest;
use App\Http\Requests\Chats\StoreChatRequest;
use App\Http\Resources\Chats\ChatResource;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;

class ChatController extends Controller
{

    public function __construct(protected readonly ChatService $chatService)
    {
    }

    public function chats(): JsonResponse
    {
        // [B7] Was an empty stub. Return the user's conversations (latest message per partner).
        $conversations = $this->chatService->conversations();

        return response()->json([
            'chats' => ChatResource::collection($conversations),
            'status' => true,
        ]);
    }


    public function chat(ChatRequest $chatRequest): JsonResponse
    {
        $chat = $this->chatService->chat($chatRequest->all());
        return response()->json([
            'chat' => ChatResource::collection($chat),
            'status' => true,
        ]);
    }

    public function store(StoreChatRequest $chatRequest): JsonResponse
    {
        $this->chatService->create($chatRequest->all());
        return response()->json([
            'status' => true,
            'message' => trans('app.done'),
        ]);
    }
}
