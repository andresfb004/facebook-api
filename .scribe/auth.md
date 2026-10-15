# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {TU_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Obtén el token con <code>POST /api/v1/register</code> o <code>POST /api/v1/login</code> y envíalo en el header <code>Authorization: Bearer {TU_TOKEN}</code>.
