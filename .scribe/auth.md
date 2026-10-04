# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {YOUR_AUTH_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Los tokens se generan mediante Laravel Sanctum. Para obtener tu token, inicia sesión en la aplicación y emite un nuevo token de acceso.
