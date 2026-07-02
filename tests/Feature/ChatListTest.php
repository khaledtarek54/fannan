<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guard for CODE_REVIEW_FINDINGS.md B7 — GET /api/chat used to be an empty stub; it now returns
 * one entry per conversation partner (the latest message).
 */
class ChatListTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_one_entry_per_conversation_partner(): void
    {
        $me = User::factory()->create();
        $a = User::factory()->create();
        $b = User::factory()->create();

        Chat::forceCreate(['from_user_id' => $me->id, 'to_user_id' => $a->id, 'type' => 'text', 'message' => 'hi A', 'is_read' => false]);
        Chat::forceCreate(['from_user_id' => $a->id, 'to_user_id' => $me->id, 'type' => 'text', 'message' => 're A', 'is_read' => false]);
        Chat::forceCreate(['from_user_id' => $me->id, 'to_user_id' => $b->id, 'type' => 'text', 'message' => 'hi B', 'is_read' => false]);

        $res = $this->actingAs($me, 'api')->getJson('/api/chat')->assertStatus(200);

        $res->assertJsonPath('status', true);
        $this->assertCount(2, $res->json('chats')); // one per partner (A and B)
    }

    public function test_is_empty_when_there_are_no_messages(): void
    {
        $res = $this->actingAs(User::factory()->create(), 'api')
            ->getJson('/api/chat')
            ->assertStatus(200);

        $this->assertCount(0, $res->json('chats'));
    }
}
