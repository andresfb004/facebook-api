# Introduction

API REST tipo Facebook construida con Laravel: publicaciones con imágenes, comentarios, likes por usuario, búsqueda y autenticación con Sanctum.

<aside>
    <strong>Base URL</strong>: <code>{{ config("app.url") }}</code>
</aside>

    Esta documentación describe cada endpoint de la API: método, URL, parámetros, body y respuestas posibles.

    La API está versionada: la **v1** (`/api/v1`) es la versión estable y la **v2** (`/api/v2`) está en desarrollo, con un nuevo formato de respuesta para las publicaciones.

    <aside>A la derecha (o debajo, en el celular) verás ejemplos de cada petición en bash y JavaScript. Con el botón <b>Try it out</b> puedes probar los endpoints desde el navegador.</aside>

