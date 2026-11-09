# Architecture

Android clients and browser-based Admin/PMB clients communicate with one PHP REST API over HTTPS. The API performs authentication, role authorization, validation and business logic. MySQL is private to the backend and is never accessed directly by Android.

```text
Android Student / Lecturer ----\
Admin Web -----------------------> HTTPS REST API -> PHP services -> MySQL
PMB Web ------------------------/
```

Phase 1 establishes the backend foundation. Android, Admin Web, PMB workflows, domain business rules and production operations are subsequent phases.
