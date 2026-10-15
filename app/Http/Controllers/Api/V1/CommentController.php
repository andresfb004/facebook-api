<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Mail\NewCommentMail;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * @group Comentarios
 *
 * Comentarios de las publicaciones.
 */
class CommentController extends Controller
{
    /**
     * Listar comentarios
     *
     * Devuelve los comentarios de una publicación, del más nuevo al más antiguo.
     *
     * @unauthenticated
     *
     * @urlParam id integer required ID de la publicación. Example: 2
     *
     * @response 200 [{"id": 1, "post_id": 2, "user_id": 1, "author": "Andrés Forero", "content": "¡Qué buenas fotos!", "created_at": "2026-10-15T07:11:27.000000Z", "updated_at": "2026-10-15T07:11:27.000000Z"}]
     * @response 404 scenario="La publicación no existe" {"message": "No query results for model [App\\Models\\Post] 999"}
     */
    public function index($postId)
    {
        $post = Post::findOrFail($postId);
        return response()->json($post->comments()->latest()->get(), 200);
    }

    /**
     * Agregar comentario
     *
     * Agrega un comentario a nombre del usuario autenticado (el autor se toma del token)
     * y envía un correo con el asunto "Nuevo comentario" al dueño de la publicación.
     *
     * @authenticated
     *
     * @urlParam id integer required ID de la publicación. Example: 2
     * @bodyParam content string required Texto del comentario. Example: ¡Qué buenas fotos!
     *
     * @response 201 {"user_id": 1, "author": "Andrés Forero", "content": "¡Qué buenas fotos!", "post_id": 2, "updated_at": "2026-10-15T07:11:27.000000Z", "created_at": "2026-10-15T07:11:27.000000Z", "id": 1}
     * @response 401 scenario="Sin token" {"message": "Unauthenticated."}
     * @response 404 scenario="La publicación no existe" {"message": "No query results for model [App\\Models\\Post] 999"}
     * @response 422 scenario="Sin contenido" {"message": "The content field is required.", "errors": {"content": ["The content field is required."]}}
     */
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
