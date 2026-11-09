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


## Public homepage content
`GET /public/home` (no authentication required) returns the public campus profile, active study programs, and up to 10 published news posts. Draft and scheduled-for-future posts are excluded. Apply the Phase 5A migration before calling this endpoint.


## Profil mahasiswa — field tambahan
Endpoint `GET /api/v1/me` mengembalikan objek `student` untuk akun mahasiswa. Selain NIM, program studi, tahun masuk, telepon, alamat, dan status, objek tersebut kini dapat berisi `national_id`, `birth_place`, `birth_date`, `gender`, `religion`, `marital_status`, `province`, `city`, `district`, `village`, `postal_code`, `father_name`, `mother_name`, `guardian_name`, `guardian_phone`, `emergency_contact_name`, `emergency_contact_phone`, `admission_pathway`, `registration_date`, dan `photo_url`. Field yang belum diisi bernilai `null`. Data ini hanya tersedia setelah autentikasi; lindungi respons profil sesuai hak akses dan kebijakan privasi kampus.
