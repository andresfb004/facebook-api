# Facebook API · Taller Backend

API REST simplificada inspirada en Facebook, construida con **Laravel**. Permite crear publicaciones con múltiples imágenes, listarlas, comentarlas, darles "Me gusta" y eliminarlas.

| | |
|---|---|
| **Docente** | Fabian Enrique Suarez Carvajal |
| **Institución** | Universidad Autónoma de Bucaramanga |
| **Curso** | Arquitectura y Desarrollo Backend |
| **Estudiante** | Juan Andrés Forero Becerra |

## Objetivo

Desarrollar una API RESTful funcional en Laravel que simule las interacciones básicas de una red social (publicaciones con múltiples fotos, comentarios y reacciones de "Me gusta") y validarla mediante peticiones en Postman.

## Requisitos

| Herramienta | Versión | Verificar con |
|---|---|---|
| PHP | 8.3 o superior | `php -v` |
| Composer | 2.x | `composer -V` |
| Laravel | 13.x | `php artisan --version` |
| Base de datos | SQLite | — |
| Postman | Última versión | — |

> El taller propone Laravel 10 u 11, pero el proyecto usa **Laravel 13**: Composer ya no instala Laravel 11 porque todas sus versiones tienen avisos de seguridad sin corregir.

## Instalación y ejecución

```bash
# 1. Clonar el repositorio
git clone <url-del-repositorio> facebook-api
cd facebook-api

# 2. Instalar dependencias
composer install

# 3. Configurar el entorno (usa SQLite por defecto)
cp .env.example .env
php artisan key:generate
touch database/database.sqlite

# 4. Crear las tablas
php artisan migrate

# 5. Crear el enlace simbólico para servir las imágenes
php artisan storage:link

# 6. Levantar el servidor
php artisan serve
# Servidor corriendo en http://127.0.0.1:8000
```

## Endpoints

Todos los endpoints usan el prefijo `/api`. Se recomienda enviar siempre el header `Accept: application/json`.

| Método | Ruta | Content-Type | Descripción | Body / Params | Código |
|---|---|---|---|---|---|
| POST | `/api/posts` | multipart/form-data | Crear publicación con fotos | `title`, `content`, `images[]` | 201 |
| GET | `/api/posts` | application/json | Listar publicaciones con imágenes y comentarios | — | 200 |
| GET | `/api/posts/{id}` | application/json | Ver un post individual | — | 200 |
| POST | `/api/posts/{id}/comments` | application/json | Agregar comentario | `author`, `content` | 201 |
| GET | `/api/posts/{id}/comments` | application/json | Listar comentarios de un post | — | 200 |
| POST | `/api/posts/{id}/like` | application/json | Incrementar "Me gusta" | — | 200 |
| DELETE | `/api/posts/{id}` | application/json | Eliminar un post y sus imágenes | — | 204 |

### Validaciones

- **Post:** `title` obligatorio (máx. 255 caracteres), `content` obligatorio, cada imagen debe ser `jpeg`, `png`, `jpg` o `gif` de máximo 2 MB.
- **Comentario:** `author` obligatorio (máx. 100 caracteres), `content` obligatorio.

### Códigos HTTP

| Código | Nombre | Significado | Ejemplo |
|---|---|---|---|
| 200 | OK | La operación fue exitosa y se devuelve información. | Listar posts, ver un post, dar like. |
| 201 | Created | El recurso fue creado en la base de datos. | Crear un post o un comentario. |
| 204 | No Content | La operación fue exitosa, sin cuerpo de respuesta. | Eliminar un post. |
| 404 | Not Found | El recurso solicitado no existe. | Consultar un post inexistente. |
| 422 | Unprocessable Entity | Los datos no pasan la validación. | Falta el título o el archivo no es imagen. |

### Ejemplo de respuesta (`GET /api/posts`)

```json
[
  {
    "id": 1,
    "title": "Mi primera publicación",
    "content": "Hola a todos, comparto fotos de mi viaje.",
    "likes_count": 2,
    "images": [
      {
        "id": 1,
        "post_id": 1,
        "image_path": "posts/uxYOC0SjjAVNCBA6miWwgeWfGzZDAMgFKpHF2M6Y.jpg",
        "image_url": "http://127.0.0.1:8000/storage/posts/uxYOC0SjjAVNCBA6miWwgeWfGzZDAMgFKpHF2M6Y.jpg"
      }
    ],
    "comments": [
      {
        "id": 1,
        "post_id": 1,
        "author": "Carlos Gómez",
        "content": "¡Qué buenas fotos!"
      }
    ]
  }
]
```

## Modelo de datos

```
        ┌───────────────┐
        │     posts     │
        ├───────────────┤
        │ id (PK)       │
        │ title         │
        │ content       │
        │ likes_count   │
        │ timestamps    │
        └───────┬───────┘
                │ 1
        ┌───────┴────────┐
        │ N              │ N
┌───────▼───────┐ ┌──────▼────────┐
│  post_images  │ │   comments    │
├───────────────┤ ├───────────────┤
│ id (PK)       │ │ id (PK)       │
│ post_id (FK)  │ │ post_id (FK)  │
│ image_path    │ │ author        │
│ timestamps    │ │ content       │
└───────────────┘ │ timestamps    │
                  └───────────────┘
```

Relaciones Eloquent:

- **Post → PostImage:** un Post tiene muchas (`hasMany`) PostImage.
- **Post → Comment:** un Post tiene muchos (`hasMany`) Comment.
- **PostImage → Post:** cada PostImage pertenece (`belongsTo`) a un Post.
- **Comment → Post:** cada Comment pertenece (`belongsTo`) a un Post.

Al eliminar un post, sus imágenes y comentarios se borran en cascada (`onDelete('cascade')`) y los archivos se eliminan del storage.

## Pruebas con Postman

La colección está en [`facebook-api-postman-collection.json`](facebook-api-postman-collection.json) (formato Collection v2.1).

1. En Postman: **Import** → selecciona `facebook-api-postman-collection.json`.
2. Crea un entorno con la variable `base_url` = `http://127.0.0.1:8000` y selecciónalo.
3. En la petición **Crear publicación con imágenes**, vuelve a elegir las fotos de los campos `images[]`, porque las rutas guardadas apuntan a archivos del equipo original.
4. Ejecuta las peticiones en orden:

| # | Petición | Método | URL | Resultado esperado |
|---|---|---|---|---|
| 1 | Crear publicación con imágenes | POST | `{{base_url}}/api/posts` | 201 Created |
| 2 | Listar publicaciones | GET | `{{base_url}}/api/posts` | 200 OK con array de posts |
| 3 | Agregar comentario | POST | `{{base_url}}/api/posts/1/comments` | 201 Created |
| 4 | Dar like a publicación | POST | `{{base_url}}/api/posts/1/like` | 200 OK con `likes_count` incrementado |
| 5 | Eliminar publicación | DELETE | `{{base_url}}/api/posts/1` | 204 No Content |

## Estructura del proyecto

```
facebook-api/
├── app/
│   ├── Http/Controllers/
│   │   ├── PostController.php
│   │   └── CommentController.php
│   └── Models/
│       ├── Post.php
│       ├── PostImage.php
│       └── Comment.php
├── database/
│   ├── migrations/
│   │   ├── ..._create_posts_table.php
│   │   ├── ..._create_post_images_table.php
│   │   └── ..._create_comments_table.php
│   └── database.sqlite
├── routes/
│   └── api.php
├── storage/
│   └── app/public/posts/        ← imágenes guardadas
├── public/
│   └── storage → storage/app/public  (enlace simbólico)
└── facebook-api-postman-collection.json
```
