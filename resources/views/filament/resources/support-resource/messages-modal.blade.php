<style>
    .chat-container {
        display: flex;
        flex-direction: column;
        max-height: 400px;
        overflow-y: auto;
        padding: 10px;
    }

    .message {
        display: flex;
        margin-bottom: 10px;
        padding: 10px;
        border-radius: 10px;
        max-width: 70%;
        color: #fff;
    }

    .message.user {
        margin-left: auto;
        background-color: #007bff;
    }

    .message.admin {
        margin-right: auto;
        background-color: #28a745;
    }

    .message-content {
        word-wrap: break-word;
    }

    .message-time {
        font-size: 0.75rem;
        color: #e0e0e0;
        margin-top: 5px;
    }
</style>

<div class="chat-container" id="chatContainer">
    @foreach ($messages as $message)
        <div class=" {{ $message->reply_user_id ? 'message admin' : 'message user' }}">
            <div>
                <div class="message-content">{{ $message->description }}</div>
                <div class="message-time">{{ $message->created_at->format('Y-m-d, h:i A') }}</div>
            </div>
        </div>
    @endforeach
</div>
