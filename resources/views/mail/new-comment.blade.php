<x-mail::message>
# Nuevo comentario

Hola {{ $post->user->name }}, **{{ $comment->author }}** comentó tu publicación **"{{ $post->title }}"**:

<x-mail::panel>
{{ $comment->content }}
</x-mail::panel>

<x-mail::button :url="$url">
Ver publicación
</x-mail::button>

Saludos,<br>
{{ config('app.name') }}
</x-mail::message>
