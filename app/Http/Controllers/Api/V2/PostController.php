<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\V2\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;

/**
 * @group Publicaciones (v2)
 *
 * Versión 2 (en desarrollo). Cambia el formato de respuesta: los datos van dentro de `data`,
 * la paginación en `links` y `meta`, el autor en `author`, las imágenes como lista de URLs
 * y se agregan `comments_count` y `likes_count`. La v1 sigue funcionando sin cambios.
 */
class PostController extends Controller
{
    /**
     * Listar publicaciones (v2)
     *
     * Lista paginada de 10 en 10 con el formato v2. Cada publicación trae `comments_count`
     * en lugar de la lista completa de comentarios.
     *
     * @unauthenticated
     *
     * @queryParam page integer Número de página. Example: 1
     * @queryParam search string Filtra por texto en el título o el contenido. Example: viaje
     *
     * @response 200 {"data": [{"id": 2, "title": "Mi primera publicación", "content": "Hola a todos, comparto fotos de mi viaje", "author": {"id": 1, "name": "Andrés Forero"}, "images": ["http://127.0.0.1:8000/storage/posts/MULDzuiySjxINJcDxgVuEpSQgKKTBl0PqNEndKRr.jpg"], "likes_count": 1, "comments_count": 1, "created_at": "2026-10-15T06:45:05+00:00"}], "links": {"first": "http://127.0.0.1:8000/api/v2/posts?page=1", "last": "http://127.0.0.1:8000/api/v2/posts?page=2", "prev": null, "next": "http://127.0.0.1:8000/api/v2/posts?page=2"}, "meta": {"current_page": 1, "from": 1, "last_page": 2, "links": [], "path": "http://127.0.0.1:8000/api/v2/posts", "per_page": 10, "to": 10, "total": 18}}
     */
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

    /**
     * Ver una publicación (v2)
     *
     * Devuelve una publicación con el formato v2, incluyendo sus comentarios.
     *
     * @unauthenticated
     *
     * @urlParam id integer required ID de la publicación. Example: 2
     *
     * @response 200 {"data": {"id": 2, "title": "Mi primera publicación", "content": "Hola a todos, comparto fotos de mi viaje", "author": {"id": 1, "name": "Andrés Forero"}, "images": ["http://127.0.0.1:8000/storage/posts/MULDzuiySjxINJcDxgVuEpSQgKKTBl0PqNEndKRr.jpg"], "likes_count": 1, "comments_count": 1, "created_at": "2026-10-15T06:45:05+00:00", "comments": [{"id": 1, "author": "Andrés Forero", "content": "¡Qué buenas fotos!", "created_at": "2026-10-15T07:11:27+00:00"}]}}
     * @response 404 scenario="La publicación no existe" {"message": "No query results for model [App\\Models\\Post] 999"}
     */
    public function show($id)
    {
        $post = Post::with(['user:id,name', 'images', 'comments' => fn ($query) => $query->latest()])
            ->withCount(['likes', 'comments'])
            ->findOrFail($id);

        return new PostResource($post);
    }
}
