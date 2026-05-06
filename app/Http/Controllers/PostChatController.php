<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostConversation;
use App\Models\PostMessage;
use Illuminate\Http\Request;

class PostChatController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = auth()->user();
        abort_unless($user->user_type === 'user', 403);

        $conversations = PostConversation::query()
            ->with(['post', 'seller', 'buyer'])
            ->where(function ($q) use ($user) {
                $q->where('seller_id', $user->id)->orWhere('buyer_id', $user->id);
            })
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('landing-page.post-chat-index', compact('conversations'));
    }

    public function start(Post $post)
    {
        $user = auth()->user();
        abort_unless($user->user_type === 'user', 403);
        abort_unless($post->service_type === 'classified' && $post->status == 1, 404);

        $sellerId = (int) $post->provider_id;
        abort_if($sellerId === $user->id, 403, __('messages.chat_with_yourself_not_allowed'));

        $conversation = PostConversation::query()->firstOrCreate(
            [
                'post_id' => $post->id,
                'seller_id' => $sellerId,
                'buyer_id' => $user->id,
            ],
            [
                'last_message_at' => now(),
            ]
        );

        return redirect()->route('post.chat.show', $conversation);
    }

    public function show(PostConversation $conversation)
    {
        $user = auth()->user();
        abort_unless($user->user_type === 'user', 403);
        abort_unless($conversation->seller_id === $user->id || $conversation->buyer_id === $user->id, 403);

        $conversation->load(['post', 'seller', 'buyer', 'messages.sender']);
        $messages = $conversation->messages()->orderBy('id')->get();

        return view('landing-page.post-chat-show', compact('conversation', 'messages'));
    }

    public function send(Request $request, PostConversation $conversation)
    {
        $user = auth()->user();
        abort_unless($user->user_type === 'user', 403);
        abort_unless($conversation->seller_id === $user->id || $conversation->buyer_id === $user->id, 403);

        $validated = $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        PostMessage::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'message' => trim($validated['message']),
        ]);

        $conversation->last_message_at = now();
        $conversation->save();

        return redirect()->route('post.chat.show', $conversation)->with('success', __('messages.message_successfully_send'));
    }
}
