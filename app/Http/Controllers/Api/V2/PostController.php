<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\V2\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $posts = Post::with(['user:id,name', 'images'])
            ->withCount(['likes', 'comments'])
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return PostResource::collection($posts);
    }

    public function show($id)
    {
        $post = Post::with(['user:id,name', 'images', 'comments' => fn ($query) => $query->latest()])
            ->withCount(['likes', 'comments'])
            ->findOrFail($id);

        return new PostResource($post);
    }
}
