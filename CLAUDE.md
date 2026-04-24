# LMS API — Claude Code Instructions

## Project Overview

This is the backend API for a white-label Learning Management System (LMS).
Built with Laravel 12, MySQL, Redis, and Docker.
Sold to educational institutions — each client gets their own branded deployment.

**First client:** A language school offering English and French courses.

**Business model:** One-time license fee + annual maintenance (security + bug fixes only).
New features are separate contracts — do not add unrequested features.

---

## Architecture

```
Request → Nginx → PHP-FPM → Middleware → Router → Controller → Service → Model → Database
                                                                        ↓
                                                              Resource → JSON Response
```

**Strict layer separation — never skip a layer:**

| Layer | Responsibility | Never does |
|-------|---------------|-----------|
| Controller | Receives request, calls service, returns response | Business logic, DB queries |
| Service | Business logic, orchestration | HTTP concerns, returning responses |
| Model | Database representation, relationships, scopes | Business logic |
| Resource | Shapes JSON output | Any logic |
| FormRequest | Validates and sanitizes input | Business logic |

---

## Folder Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/           → AdminCourseController, AdminStudentController, etc.
│   │   ├── Instructor/      → InstructorCourseController, etc.
│   │   ├── Student/         → StudentEnrollmentController, etc.
│   │   └── Auth/            → AuthController
│   ├── Requests/
│   │   ├── Admin/
│   │   ├── Instructor/
│   │   └── Student/
│   ├── Resources/
│   │   ├── Admin/
│   │   └── Student/
│   └── Middleware/
├── Models/
├── Services/
│   ├── Auth/
│   ├── Course/
│   ├── Enrollment/
│   ├── Payment/
│   ├── Certificate/
│   ├── Quiz/
│   └── Notification/
├── Events/
├── Listeners/
├── Jobs/
├── Notifications/
└── Exceptions/
```

---

## API Conventions

### Route Structure

```
/api/v1/public/...        → no auth required (browse courses, verify certificate)
/api/v1/auth/...          → authentication (login, register, logout)
/api/v1/student/...       → auth:sanctum + role:student
/api/v1/instructor/...    → auth:sanctum + role:instructor
/api/v1/admin/...         → auth:sanctum + role:admin
```

### Standard Response Format

Every response MUST follow this exact structure:

```php
// Success
return response()->json([
    'success' => true,
    'data'    => $resource,
    'message' => 'Course retrieved successfully',
    'meta'    => [],
]);

// Success with pagination
return response()->json([
    'success' => true,
    'data'    => $resource,
    'message' => 'Courses retrieved successfully',
    'meta'    => [
        'total'        => $paginator->total(),
        'per_page'     => $paginator->perPage(),
        'current_page' => $paginator->currentPage(),
        'last_page'    => $paginator->lastPage(),
    ],
]);

// Error
return response()->json([
    'success' => false,
    'data'    => null,
    'message' => 'Course not found',
    'meta'    => [],
], 404);
```

### HTTP Status Codes

```
200 → success (GET, PUT, PATCH)
201 → created (POST)
204 → no content (DELETE)
400 → bad request
401 → unauthenticated
403 → unauthorized (authenticated but wrong role/permission)
404 → not found
422 → validation failed
429 → too many requests
500 → server error
```

---

## Coding Standards

### General Rules

- Use PHP 8.2+ features: constructor property promotion, match expressions, named arguments, enums
- Type hint everything — parameters, return types, properties
- Strict types declaration on every file: `declare(strict_types=1);`
- No `var_dump()`, `dd()`, or `dump()` in committed code
- No unused imports or variables
- Maximum function length: 20 lines. If longer, extract to a private method

### Naming Conventions

```php
// Controllers — named by role + resource + "Controller"
AdminCourseController
InstructorLessonController
StudentEnrollmentController

// Services — named by domain
CourseService
EnrollmentService
CertificateService

// Models — singular PascalCase
Course, Enrollment, QuizAttempt

// Database tables — plural snake_case
courses, enrollments, quiz_attempts

// Foreign keys — singular_id
course_id, user_id, enrollment_id

// Events — past tense, what happened
CoursePublished, StudentEnrolled, CertificateIssued

// Listeners — present tense, what to do
SendEnrollmentEmail, GenerateCertificate, ClearCourseCache

// Jobs — imperative, what to do
SendNotificationEmail, GenerateCertificatePdf

// Enums — PascalCase class, PascalCase cases
enum CourseStatus: string {
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
```

### Controller Pattern

Controllers must be thin. No business logic. No Eloquent queries.

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourseRequest;
use App\Http\Resources\Admin\CourseResource;
use App\Services\Course\CourseService;
use Illuminate\Http\JsonResponse;

class AdminCourseController extends Controller
{
    public function __construct(
        private readonly CourseService $courseService
    ) {}

    public function index(): JsonResponse
    {
        $courses = $this->courseService->getAllForAdmin();

        return response()->json([
            'success' => true,
            'data'    => CourseResource::collection($courses),
            'message' => 'Courses retrieved successfully',
            'meta'    => [],
        ]);
    }

    public function store(StoreCourseRequest $request): JsonResponse
    {
        $course = $this->courseService->create($request->validated());

        return response()->json([
            'success' => true,
            'data'    => new CourseResource($course),
            'message' => 'Course created successfully',
            'meta'    => [],
        ], 201);
    }
}
```

### Service Pattern

Services contain all business logic. Always injected via constructor DI.

```php
<?php

declare(strict_types=1);

namespace App\Services\Course;

use App\Events\CoursePublished;
use App\Models\Course;
use Illuminate\Support\Facades\Cache;

class CourseService
{
    public function create(array $data): Course
    {
        $course = Course::create($data);
        $this->clearCache();
        return $course;
    }

    public function publish(Course $course): Course
    {
        $course->update(['status' => CourseStatus::Published]);
        event(new CoursePublished($course));
        $this->clearCache();
        return $course;
    }

    private function clearCache(): void
    {
        Cache::forget('courses.public.page.1');
        Cache::tags(['courses'])->flush();
    }
}
```

### Model Pattern

Models define fillable, relationships, scopes, and casts. No business logic.

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Course extends Model
{
    protected $fillable = [
        'instructor_id',
        'category_id',
        'title',
        'title_ar',
        'slug',
        'description',
        'description_ar',
        'thumbnail',
        'level',
        'language',
        'price',
        'currency',
        'status',
        'next_course_id',
        'duration_minutes',
    ];

    protected $casts = [
        'price'  => 'decimal:2',
        'status' => CourseStatus::class,
        'level'  => CourseLevel::class,
    ];

    // Relationships
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('order');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function nextCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'next_course_id');
    }

    // Scopes
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', CourseStatus::Published);
    }

    public function scopeFree(Builder $query): Builder
    {
        return $query->where('price', 0);
    }

    public function scopePremium(Builder $query): Builder
    {
        return $query->where('price', '>', 0);
    }
}
```

### FormRequest Pattern

Always use FormRequest for validation. Always sanitize in `prepareForValidation`.

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title'          => strip_tags($this->title ?? ''),
            'title_ar'       => strip_tags($this->title_ar ?? ''),
            'description'    => strip_tags($this->description ?? ''),
            'description_ar' => strip_tags($this->description_ar ?? ''),
            'slug'           => str($this->title)->slug()->toString(),
        ]);
    }

    public function rules(): array
    {
        return [
            'title'          => ['required', 'string', 'max:255'],
            'title_ar'       => ['nullable', 'string', 'max:255'],
            'description'    => ['required', 'string', 'max:5000'],
            'description_ar' => ['nullable', 'string', 'max:5000'],
            'category_id'    => ['required', 'exists:categories,id'],
            'level'          => ['required', 'in:beginner,intermediate,advanced'],
            'language'       => ['required', 'in:en,ar,fr'],
            'price'          => ['required', 'numeric', 'min:0'],
            'currency'       => ['sometimes', 'string', 'size:3'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'    => 'Course title is required',
            'category_id.exists' => 'Selected category does not exist',
            'price.min'         => 'Price cannot be negative',
        ];
    }
}
```

### Resource Pattern

Resources shape JSON output. Never put logic here.

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'title'            => $this->title,
            'title_ar'         => $this->title_ar,
            'slug'             => $this->slug,
            'description'      => $this->description,
            'thumbnail'        => $this->thumbnail,
            'level'            => $this->level,
            'language'         => $this->language,
            'price'            => $this->price,
            'currency'         => $this->currency,
            'status'           => $this->status,
            'is_free'          => $this->price == 0,
            'enrollment_count' => $this->enrollments_count ?? 0,
            'created_at'       => $this->created_at->format('Y-m-d'),
            'instructor'       => $this->whenLoaded('instructor', fn() => [
                'id'   => $this->instructor->id,
                'name' => $this->instructor->name,
            ]),
            'category'         => $this->whenLoaded('category', fn() => [
                'id'   => $this->category->id,
                'name' => $this->category->name,
            ]),
        ];
    }
}
```

---

## Database Conventions

### Migration Rules

- Every migration must have a `down()` method that perfectly reverses `up()`
- Foreign keys must have `constrained()` and define `onDelete` behavior explicitly
- Always add indexes on: columns used in `where()`, `orderBy()`, foreign keys
- Use enums for status fields — never raw strings without constraints
- Timestamps on every table — no exceptions

```php
// Good migration example
public function up(): void
{
    Schema::create('courses', function (Blueprint $table) {
        $table->id();
        $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
        $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
        $table->foreignId('next_course_id')->nullable()->constrained('courses')->nullOnDelete();
        $table->string('title');
        $table->string('title_ar')->nullable();
        $table->string('slug')->unique();
        $table->text('description');
        $table->text('description_ar')->nullable();
        $table->string('thumbnail')->nullable();
        $table->enum('level', ['beginner', 'intermediate', 'advanced']);
        $table->enum('language', ['en', 'ar', 'fr'])->default('en');
        $table->decimal('price', 10, 2)->default(0);
        $table->string('currency', 3)->default('OMR');
        $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
        $table->integer('duration_minutes')->nullable();
        $table->timestamps();

        // Indexes
        $table->index('status');
        $table->index('level');
        $table->index('language');
        $table->index(['status', 'language']); // composite for common filter
    });
}
```

### Eloquent Rules

- Always use `with()` for relationships — never lazy load in loops (N+1)
- Always use `withCount()` instead of loading full relationship just to count
- Use `select()` to limit columns when you don't need everything
- Use `chunk()` or `cursor()` for large dataset operations — never `all()`

---

## Security Rules — NEVER VIOLATE

1. **Never use raw string concatenation in queries** — always Eloquent or parameterized
2. **Never trust user input** — always validate via FormRequest before use
3. **Always call strip_tags()** on text inputs in `prepareForValidation()`
4. **Never expose stack traces** — APP_DEBUG must be false in production
5. **Never commit .env files** — ever
6. **Never store plain text passwords** — always Hash::make()
7. **Never return sensitive fields** (password, remember_token) in Resources
8. **Always check ownership** — a student cannot access another student's data
9. **Rate limit all auth endpoints** — login, register, forgot-password
10. **Always use HTTPS** in production — HTTP redirects to HTTPS

---

## Event-Driven Rules

Use events for side effects. Services fire events. Listeners handle side effects.

```
StudentEnrolled event fires → listeners handle:
  - SendEnrollmentEmail (queued)
  - ClearEnrollmentCache
  - UpdateCourseEnrollmentCount

CertificateIssued event fires → listeners handle:
  - GenerateCertificatePdf (queued)
  - SendCertificateEmail (queued)
```

**Never dispatch jobs directly from controllers.**
Controllers call services. Services fire events. Listeners dispatch jobs.

---

## Queue Rules

- All emails MUST go through queue — never send synchronously
- All PDF generation MUST go through queue
- Jobs must define `$tries` and `$timeout`
- Jobs must implement `failed()` method with proper logging
- Use `ShouldBeUnique` for jobs that should not run in parallel

```php
class GenerateCertificatePdf implements ShouldQueue, ShouldBeUnique
{
    public int $tries   = 3;
    public int $timeout = 120;
    public int $uniqueFor = 3600; // 1 hour

    public function uniqueId(): string
    {
        return "certificate-{$this->certificate->id}";
    }
}
```

---

## Caching Rules

- Cache public course listings — invalidate on any course change
- Cache categories — invalidate on category change
- Never cache user-specific data with shared keys
- Always use cache tags when possible for grouped invalidation
- Cache TTL: public content 5 minutes, rarely changing data 1 hour

```php
// Good caching pattern
$courses = Cache::remember('courses.public.page.' . $page, 300, fn() =>
    Course::published()->with('instructor', 'category')->paginate(12)
);

// Good invalidation
Cache::tags(['courses'])->flush();
```

---

## Testing Rules

Every new feature must have:

1. **Feature test** — tests the full HTTP request/response cycle
2. **At least one negative test** — what happens when it fails

```php
// Example test structure
class CourseEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_enroll_in_free_course(): void
    {
        $student = User::factory()->student()->create();
        $course  = Course::factory()->free()->published()->create();

        $response = $this->actingAs($student)
            ->postJson("/api/v1/student/courses/{$course->id}/enroll");

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('enrollments', [
            'user_id'   => $student->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_student_cannot_enroll_in_premium_course_without_payment(): void
    {
        $student = User::factory()->student()->create();
        $course  = Course::factory()->premium()->published()->create();

        $this->actingAs($student)
            ->postJson("/api/v1/student/courses/{$course->id}/enroll")
            ->assertStatus(402);
    }
}
```

---

## Git Conventions

### Branch naming

```
feature/course-management
feature/enrollment-system
feature/certificate-generation
fix/quiz-scoring-bug
security/rate-limiting
```

### Commit messages

```
feat: add course enrollment endpoint with free/paid logic
feat: add placement quiz system with score-to-course mapping
fix: resolve N+1 query in course listing
security: add rate limiting to auth endpoints
refactor: extract certificate generation to dedicated service
test: add enrollment feature tests
```

### Rules

- Never commit directly to `main`
- Every feature in its own branch
- PR must pass CI pipeline before merge
- Squash commits before merging if more than 3 commits

---

## Docker Setup

The project runs entirely in Docker. Never install PHP, MySQL, or Redis on the host machine.

```
Containers:
- app      → PHP 8.2-FPM (Laravel)
- nginx    → Nginx (web server)
- db       → MySQL 8.0
- redis    → Redis 7 (cache, sessions, queues)
- queue    → PHP queue worker
- mailpit  → Email testing (dev only)
```

### Common commands

```bash
# Start all containers
docker-compose up -d

# Run artisan commands
docker exec -it lms_app php artisan [command]

# Run tests
docker exec -it lms_app php artisan test

# Access MySQL
docker exec -it lms_db mysql -u lms -p lms

# View queue worker logs
docker logs lms_queue -f
```

---

## Environment Variables

Required in `.env` — never hardcoded:

```
APP_KEY, APP_DEBUG, APP_URL
DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD
REDIS_HOST, REDIS_PORT
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
MAIL_MAILER, MAIL_HOST, MAIL_PORT
```

---

## What Claude Code Should Always Do

1. Follow the folder structure exactly — never create files outside defined locations
2. Use the standard response format on every endpoint
3. Create a FormRequest for every POST and PUT endpoint
4. Create a Resource for every model returned in a response
5. Add `strip_tags()` in `prepareForValidation()` on all text fields
6. Add database indexes in migrations for filterable columns
7. Use eager loading (`with()`) on all relationship queries
8. Fire events from services — never dispatch jobs directly from controllers
9. Write a feature test for every new endpoint
10. Add `declare(strict_types=1)` to every PHP file

## What Claude Code Should Never Do

1. Put business logic in controllers
2. Put Eloquent queries in controllers
3. Use `Model::all()` without pagination
4. Use lazy loading inside loops
5. Return raw model data without a Resource
6. Skip FormRequest validation
7. Use raw SQL string concatenation
8. Add `dd()` or `var_dump()` in any file
9. Commit hardcoded credentials
10. Create new features not in the BRD without asking first
11. Use `sleep()` or blocking operations in queue jobs
12. Skip the `failed()` method on jobs
13. Return passwords or sensitive fields in API responses
14. Skip writing tests for new endpoints

---

## Reference Documents

- Business Requirements Document: `/docs/LMS-Business-Requirements.docx`
- Database Schema: `/docs/LMS-Requirements.docx`
- API Endpoints Reference: Section 5 of BRD

When in doubt about a business requirement — check the BRD before implementing.
If the BRD does not cover a scenario — ask before building.