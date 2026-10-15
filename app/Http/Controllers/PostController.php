<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $posts = Post::with(['user:id,name', 'images', 'comments'])
            ->withCount('likes')
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(function ($post) {
                $post->images->transform(function ($img) {
                    $img->image_url = asset('storage/' . $img->image_path);
                    return $img;
                });
                return $post;
            });

        return response()->json($posts, 200);
    }

    public function show($id)
    {
        $post = Post::with(['user:id,name', 'images', 'comments'])
            ->withCount('likes')
            ->findOrFail($id);

        $post->images->transform(function ($img) {
            $img->image_url = asset('storage/' . $img->image_path);
            return $img;
        });

        return response()->json($post, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'    => 'required|string|max:255',
            'content'  => 'required|string',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $post = $request->user()->posts()->create([
            'title'   => $request->title,
            'content' => $request->content,
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('posts', 'public');
                PostImage::create([
                    'post_id'    => $post->id,
                    'image_path' => $path,
                ]);
            }
        }

        return response()->json($post->load(['user:id,name', 'images']), 201);
    }

    public function like(Request $request, $id)
    {
        $post = Post::findOrFail($id);
        $user = $request->user();

        if ($post->likes()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'message'     => 'Ya le diste me gusta a esta publicación',
                'likes_count' => $post->likes()->count(),
            ], 409);
        }

        $post->likes()->attach($user->id);

        return response()->json([
            'message'     => 'Me gusta añadido correctamente',
            'likes_count' => $post->likes()->count(),
        ], 200);
    }

    public function unlike(Request $request, $id)
    {
        $post = Post::findOrFail($id);

        $post->likes()->detach($request->user()->id);

        return response()->json([
            'message'     => 'Me gusta eliminado correctamente',
            'likes_count' => $post->likes()->count(),
        ], 200);
    }

    public function destroy(Request $request, $id)
    {
        $post = Post::findOrFail($id);

        if ($post->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'No puedes eliminar una publicación que no es tuya',
            ], 403);
        }

        foreach ($post->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $post->delete();
        return response()->json(null, 204);
    }
}
