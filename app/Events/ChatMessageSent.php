<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ChatMessage $message;

    /**
     * Create a new event instance.
     */
    public function __construct(ChatMessage $message)
    {
        // Pastikan relasi author termuat untuk serialization data broadcast
        $this->message = $message->loadMissing('author');
    }

    /**
     * Get the channels the event should broadcast on.
     * Menggunakan PrivateChannel: chat.rt.{rtId} atau chat.rw.{rwId}
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channelName = ($this->message->scope_type === 'rt')
            ? 'chat.rt.' . $this->message->scope_id
            : 'chat.rw.' . $this->message->scope_id;

        return [
            new PrivateChannel($channelName),
        ];
    }

    /**
     * Nama event yang dipancarkan ke client.
     */
    public function broadcastAs(): string
    {
        return 'ChatMessageSent';
    }

    /**
     * Payload data yang dikirimkan ke WebSocket client.
     * Mengikuti prinsip UU PDP & AGENTS.md: NIK dan data sensitif dilarang diekspos.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'scope_type' => $this->message->scope_type,
            'scope_id' => (int) $this->message->scope_id,
            'author_id' => (int) $this->message->author_id,
            'konten' => $this->message->konten,
            'created_at' => $this->message->created_at?->toISOString() ?? now()->toISOString(),
            'created_at_human' => $this->message->created_at?->diffForHumans() ?? 'baru saja',
            'author' => [
                'id' => $this->message->author?->id,
                'nama' => $this->message->author?->nama,
                'role_badge' => $this->message->author?->getHighestRoleBadge() ?? 'Warga',
            ],
        ];
    }
}
