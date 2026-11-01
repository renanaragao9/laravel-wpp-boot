<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\ListWebChatMessagesRequest;
use App\Http\Requests\Chat\SendWebChatMessageRequest;
use App\Http\Requests\Chat\StartWebChatRequest;
use App\Http\Resources\ChatMessageResource;
use App\Models\ChatSession;
use App\Services\Chat\ListWebChatMessagesService;
use App\Services\Chat\SendWebChatMessageService;
use App\Services\Chat\StartWebChatService;
use Illuminate\Http\JsonResponse;

class ChatController extends Controller
{
    public function start(
        StartWebChatRequest $startWebChatRequest,
        StartWebChatService $startWebChatService,
        ListWebChatMessagesService $listWebChatMessagesService
    ): JsonResponse {
        $data = $startWebChatRequest->validated();
        $session = $startWebChatService->run($data);

        return response()->json([
            'token' => $session->token,
            'name' => $session->name,
            'data' => ChatMessageResource::collection($listWebChatMessagesService->run($session, 0)),
        ], 201);
    }

    public function messages(
        ListWebChatMessagesRequest $listWebChatMessagesRequest,
        ChatSession $chatSession,
        ListWebChatMessagesService $listWebChatMessagesService
    ): JsonResponse {
        $data = $listWebChatMessagesRequest->validated();

        return response()->json([
            'data' => ChatMessageResource::collection($listWebChatMessagesService->run($chatSession, (int) ($data['after'] ?? 0))),
        ]);
    }

    public function send(
        SendWebChatMessageRequest $sendWebChatMessageRequest,
        ChatSession $chatSession,
        SendWebChatMessageService $sendWebChatMessageService
    ): JsonResponse {
        $data = $sendWebChatMessageRequest->validated();
        $message = $sendWebChatMessageService->run($chatSession, $data);

        return response()->json(['data' => new ChatMessageResource($message)], 201);
    }
}
