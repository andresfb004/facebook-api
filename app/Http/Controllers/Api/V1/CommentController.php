<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Mail\NewCommentMail;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class CommentController extends Controller
{
    public function index($postId)
    {
        $post = Post::findOrFail($postId);
        return response()->json($post->comments()->latest()->get(), 200);
    }

    public function store(Request $request, $postId)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        $post = Post::findOrFail($postId);

        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'author'  => $request->user()->name,
            'content' => $request->content,
        ]);

        if ($post->user) {
            try {
                Mail::to($post->user->email)->send(new NewCommentMail($comment));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json($comment, 201);
    }
}
