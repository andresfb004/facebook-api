<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * @group Publicaciones
 *
 * Crear, listar, ver y eliminar publicaciones con imágenes (versión 1).
 */
class PostController extends Controller
{
    /**
     * Listar publicaciones
     *
     * Devuelve las publicaciones de la más nueva a la más antigua, paginadas de 10 en 10,
     * con su autor, imágenes, comentarios y cantidad de likes.
     *
     * @unauthenticated
     *
     * @queryParam page integer Número de página. Example: 1
     * @queryParam search string Filtra por texto en el título o el contenido. No distingue mayúsculas. Example: viaje
     *
     * @response 200 {"current_page": 1, "data": [{"id": 2, "title": "Mi primera publicación", "content": "Hola a todos, comparto fotos de mi viaje", "created_at": "2026-10-15T06:45:05.000000Z", "updated_at": "2026-10-15T06:45:05.000000Z", "user_id": 1, "likes_count": 1, "user": {"id": 1, "name": "Andrés Forero"}, "images": [{"id": 3, "post_id": 2, "image_path": "posts/MULDzuiySjxINJcDxgVuEpSQgKKTBl0PqNEndKRr.jpg", "created_at": "2026-10-15T06:45:05.000000Z", "updated_at": "2026-10-15T06:45:05.000000Z", "image_url": "http://127.0.0.1:8000/storage/posts/MULDzuiySjxINJcDxgVuEpSQgKKTBl0PqNEndKRr.jpg"}], "comments": [{"id": 1, "post_id": 2, "user_id": 1, "author": "Andrés Forero", "content": "¡Qué buenas fotos!", "created_at": "2026-10-15T07:11:27.000000Z", "updated_at": "2026-10-15T07:11:27.000000Z"}]}], "first_page_url": "http://127.0.0.1:8000/api/v1/posts?page=1", "from": 1, "last_page": 2, "last_page_url": "http://127.0.0.1:8000/api/v1/posts?page=2", "links": [], "next_page_url": "http://127.0.0.1:8000/api/v1/posts?page=2", "path": "http://127.0.0.1:8000/api/v1/posts", "per_page": 10, "prev_page_url": null, "to": 10, "total": 18}
     */
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

    /**
     * Ver una publicación
     *
     * Devuelve una publicación con su autor, imágenes, comentarios y cantidad de likes.
     *
     * @unauthenticated
     *
     * @urlParam id integer required ID de la publicación. Example: 2
     *
     * @response 200 {"id": 2, "title": "Mi primera publicación", "content": "Hola a todos, comparto fotos de mi viaje", "created_at": "2026-10-15T06:45:05.000000Z", "updated_at": "2026-10-15T06:45:05.000000Z", "user_id": 1, "likes_count": 1, "user": {"id": 1, "name": "Andrés Forero"}, "images": [{"id": 3, "post_id": 2, "image_path": "posts/MULDzuiySjxINJcDxgVuEpSQgKKTBl0PqNEndKRr.jpg", "created_at": "2026-10-15T06:45:05.000000Z", "updated_at": "2026-10-15T06:45:05.000000Z", "image_url": "http://127.0.0.1:8000/storage/posts/MULDzuiySjxINJcDxgVuEpSQgKKTBl0PqNEndKRr.jpg"}], "comments": [{"id": 1, "post_id": 2, "user_id": 1, "author": "Andrés Forero", "content": "¡Qué buenas fotos!", "created_at": "2026-10-15T07:11:27.000000Z", "updated_at": "2026-10-15T07:11:27.000000Z"}]}
     * @response 404 scenario="La publicación no existe" {"message": "No query results for model [App\\Models\\Post] 999"}
     */
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

    /**
     * Crear publicación
     *
     * Crea una publicación a nombre del usuario autenticado. Se envía como `multipart/form-data`
     * para poder adjuntar varias imágenes en el campo `images[]`.
     *
     * @authenticated
     *
     * @bodyParam title string required Título de la publicación. Máximo 255 caracteres. Example: Mi primera publicación
     * @bodyParam content string required Texto de la publicación. Example: Hola a todos, comparto fotos de mi viaje
     * @bodyParam images file[] Imágenes jpeg, png, jpg o gif de máximo 2 MB cada una.
     *
     * @response 201 {"title": "Mi primera publicación", "content": "Hola a todos, comparto fotos de mi viaje", "user_id": 1, "updated_at": "2026-10-15T06:45:05.000000Z", "created_at": "2026-10-15T06:45:05.000000Z", "id": 2, "user": {"id": 1, "name": "Andrés Forero"}, "images": [{"id": 3, "post_id": 2, "image_path": "posts/MULDzuiySjxINJcDxgVuEpSQgKKTBl0PqNEndKRr.jpg", "created_at": "2026-10-15T06:45:05.000000Z", "updated_at": "2026-10-15T06:45:05.000000Z"}]}
     * @response 401 scenario="Sin token" {"message": "Unauthenticated."}
     * @response 422 scenario="Datos inválidos" {"message": "The title field is required.", "errors": {"title": ["The title field is required."]}}
     */
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

    /**
     * Dar like
     *
     * Registra el like del usuario autenticado. Cada usuario puede dar un solo like por publicación.
     *
     * @group Likes
     * @authenticated
     *
     * @urlParam id integer required ID de la publicación. Example: 2
     *
     * @response 200 {"message": "Me gusta añadido correctamente", "likes_count": 1}
     * @response 409 scenario="Ya había dado like" {"message": "Ya le diste me gusta a esta publicación", "likes_count": 1}
     * @response 401 scenario="Sin token" {"message": "Unauthenticated."}
     * @response 404 scenario="La publicación no existe" {"message": "No query results for model [App\\Models\\Post] 999"}
     */
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

    /**
     * Quitar like
     *
     * Elimina el like del usuario autenticado y devuelve la cantidad de likes actualizada.
     *
     * @group Likes
     * @authenticated
     *
     * @urlParam id integer required ID de la publicación. Example: 2
     *
     * @response 200 {"message": "Me gusta eliminado correctamente", "likes_count": 0}
     * @response 401 scenario="Sin token" {"message": "Unauthenticated."}
     * @response 404 scenario="La publicación no existe" {"message": "No query results for model [App\\Models\\Post] 999"}
     */
    public function unlike(Request $request, $id)
    {
        $post = Post::findOrFail($id);

        $post->likes()->detach($request->user()->id);

        return response()->json([
            'message'     => 'Me gusta eliminado correctamente',
            'likes_count' => $post->likes()->count(),
        ], 200);
    }

    /**
     * Eliminar publicación
     *
     * Elimina la publicación, sus imágenes (también del disco), comentarios y likes.
     * Solo el dueño de la publicación puede eliminarla.
     *
     * @authenticated
     *
     * @urlParam id integer required ID de la publicación. Example: 2
     *
     * @response 204 scenario="Eliminada"
     * @response 403 scenario="No es el dueño" {"message": "No puedes eliminar una publicación que no es tuya"}
     * @response 401 scenario="Sin token" {"message": "Unauthenticated."}
     * @response 404 scenario="La publicación no existe" {"message": "No query results for model [App\\Models\\Post] 999"}
     */
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
