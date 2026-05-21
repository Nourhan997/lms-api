# LMS API

Backend API for a white-label Learning Management System. Built with Laravel 12, MySQL, Redis, and Docker.

## What it does

- Students take a placement test, enroll in courses, track progress, and earn certificates
- Instructors create courses with video, audio, PDF, and text content
- Admins manage students, courses, payments, and platform branding
- Certificates are generated as PDFs and publicly verifiable by unique ID
- Emails and PDF generation run in the background via queues

## Stack

- **Laravel 12** — PHP 8.2
- **MySQL 8.0** — primary database
- **Redis** — cache, sessions, queues
- **Docker** — containerized environment
- **GitHub Actions** — CI/CD pipeline

## Quick start

Requires Docker Desktop.

```bash
git clone https://github.com/nourhanfa97/lms-api.git
cd lms-api
cp .env.example .env
docker-compose up -d --build
docker exec -it lms_app php artisan key:generate
docker exec -it lms_app php artisan migrate --seed
```

API runs at `http://localhost:8000`
Email preview at `http://localhost:8025`

## Test accounts

```
Admin      → admin@lms.test / password
Instructor → instructor@lms.test / password
```

## Tests

```bash
docker exec -it lms_app php artisan test
```

121 tests, 516 assertions — all passing.

## CI/CD

Every push to `main` runs all tests, builds a Docker image, and pushes to Docker Hub.

```
nourhanfa97/lms-api:latest
```

## API

80+ endpoints across 5 route groups:

```
/api/v1/public/...      → no auth required
/api/v1/auth/...        → register, login, logout
/api/v1/student/...     → enrollment, progress, certificates
/api/v1/instructor/...  → course management
/api/v1/admin/...       → full platform management
/api/health             → health check
```

Every response follows the same structure:

```json
{
  "success": true,
  "data": {},
  "message": "Success",
  "meta": {}
}
```

## Built by

[Nour](https://github.com/nourhanfa97) — Programi Tech, Muscat, Oman
