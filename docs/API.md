# REST API v1

Base URL: `https://your-domain.example/api/v1`

## Health
`GET /health` — checks application database connectivity.

## Login
`POST /auth/login`

JSON body:
```json
{"username":"NIM atau username","password":"password"}
```
Returns a bearer token. The API stores only the SHA-256 hash of the token. Treat the returned token as a secret.

## Current profile
`GET /me`
Header: `Authorization: Bearer ACCESS_TOKEN`

## Logout
`POST /auth/logout`
Header: `Authorization: Bearer ACCESS_TOKEN`
Revokes the current token.

All errors use JSON with `success:false` and a message. Never expose SQL errors or secrets to clients.
