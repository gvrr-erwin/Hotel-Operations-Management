# Hotel Operations Management System

A production-quality, full-stack Hotel Operations Management SaaS built with **Laravel 11** (REST API) and **Vue 3** (SPA frontend). Designed as a portfolio-grade application demonstrating senior-level full-stack engineering skills.

---

## Tech Stack

| Layer       | Technology                                              |
|-------------|---------------------------------------------------------|
| Backend     | Laravel 11, PHP 8.3, Laravel Sanctum (token auth)       |
| Frontend    | Vue 3 (Composition API), Vite, Pinia, Vue Router 4      |
| Styling     | Tailwind CSS 3, @tailwindcss/forms                      |
| Charts      | Chart.js via vue-chartjs                                |
| Database    | MySQL 8                                                 |
| HTTP Client | Axios with request/response interceptors                |
| Utilities   | dayjs, @vueuse/core                                     |

---

## Project Structure

```
hotel-operation-management/
├── backend/                        # Laravel 11 REST API
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/Api/    # AuthController, DashboardController, HotelRateController,
│   │   │   │                       # TipController, TimeLogController, UserController, AuditLogController
│   │   │   ├── Middleware/         # CheckRole.php — role-based route protection
│   │   │   ├── Requests/           # StoreUserRequest, StoreHotelRateRequest, StoreTipRequest, StoreTimeLogRequest
│   │   │   └── Resources/          # UserResource, HotelRateResource, TipResource, TimeLogResource, AuditLogResource
│   │   ├── Models/                 # User, Hotel, RoomType, HotelRate, Tip, TimeLog, AuditLog
│   │   └── Services/               # AuditService, DashboardService, RateAnalyticsService
│   ├── database/
│   │   ├── migrations/             # All 7 migration files
│   │   └── seeders/                # DatabaseSeeder with 60 days of realistic data
│   └── routes/api.php              # All REST API routes
│
└── frontend/                       # Vue 3 SPA
    └── src/
        ├── api/                    # http.js + per-module API services (auth, rates, tips, ...)
        ├── stores/                 # Pinia stores (auth, dashboard, rates, tips, timeLogs, users, audit)
        ├── router/                 # Vue Router with beforeEach guards
        ├── layouts/                # AuthLayout.vue, DashboardLayout.vue
        ├── components/
        │   ├── layout/             # AppSidebar.vue, AppTopbar.vue
        │   ├── ui/                 # AppModal, AppBadge, AppAlert, AppSpinner, ConfirmModal, ToastContainer
        │   └── shared/             # KpiCard, PageHeader, EmptyState
        ├── pages/                  # LoginPage, DashboardPage, RatesPage, TipsPage, TimeLogsPage, UsersPage, AuditPage
        ├── composables/            # useToast
        └── utils/                  # formatters.js (currency, date, initials, etc.)
```

---

## Features

### Dashboard
- KPI cards: average rate, daily tips, monthly tips, active staff
- 30-day rate trend chart (line) and daily tip chart (bar)
- Top earners leaderboard
- Today's shift summary
- Live activity feed

### Hotel Rate Management
- Per-hotel, per-room-type daily rate entry
- Today vs. yesterday comparison with delta and % change
- Historical trend view with date range filter
- Full CRUD with validation

### Tip Tracker
- Record employee tips with date and notes
- Filter by employee, date range
- Summary KPIs: total amount, entries, average
- Full CRUD — edit and delete support

### Time Logging (Self-Service)
- **Time Clock** — every staff member can clock in/out for themselves; auto-detects shift from current time and shows a live working duration
- **Manager Review** — GMs / Assistant GMs can view all logs, filter by employee / department / shift / status, approve pending entries, and manually correct or back-log entries
- Weekly per-employee attendance + hours summary
- Pending approval & "currently clocked in" KPIs

### Shift Scheduling
- Weekly grid view of staff shifts with quick-add per day cell
- Department and employee filters
- Conflict detection — overlapping shifts for the same employee are rejected
- 14-day forward roster seeded with realistic patterns

### Operations Task Board
- Housekeeping, maintenance, front-desk and F&B requests on a single Kanban board
- Priority (low → urgent), category, room number, due date, assignee
- Anyone can raise a task; assignees can advance status; managers can edit/delete
- Live counters for Open / In Progress / Completed / Overdue
- Search and filter by category, priority, assignee, or "only mine"

### User Management (Admin only)
- Create, edit, disable, reactivate users
- Role-based badge display with distribution overview
- Search by name/username, filter by role/status
- Cannot disable the last admin or self

### Audit Log (Admin only)
- Full action history: login, logout, CRUD on all modules
- Filter by date range and action type
- IP address tracking

---

## Role Permissions

| Module             | Admin | Gen. Manager | Asst. Manager | Housekeeping Mgr | Employee |
|--------------------|:-----:|:------------:|:-------------:|:----------------:|:--------:|
| Dashboard          | ✅    | ✅           | ✅            | ❌               | ❌       |
| Rates (view)       | ✅    | ✅           | ✅            | ❌               | ❌       |
| Rates (edit)       | ✅    | ✅           | ❌            | ❌               | ❌       |
| Tips (view)        | ✅    | ✅           | ✅            | ✅ (own)         | ✅ (own) |
| Tips (edit)        | ✅    | ✅           | ✅            | ❌               | ❌       |
| Time Clock (self)  | ✅    | ✅           | ✅            | ✅               | ✅       |
| Time Logs (review) | all   | all          | all           | own              | own      |
| Time Logs (edit)   | ✅    | ✅           | ✅            | ❌               | ❌       |
| Shifts (view)      | all   | all          | all           | own              | own      |
| Shifts (edit)      | ✅    | ✅           | ✅            | ❌               | ❌       |
| Tasks (view)       | all   | all          | all           | own              | own      |
| Tasks (edit)       | ✅    | ✅           | ✅            | ✅                | ✅ (own) |
| Users              | ✅    | ❌           | ❌            | ❌               | ❌       |
| Audit Log          | ✅    | ❌           | ❌            | ❌               | ❌       |

---

## Getting Started

### Prerequisites
- PHP 8.2+
- Composer 2+
- Node.js 18+
- MySQL 8

### Backend Setup

```bash
cd backend

# Install dependencies
composer install

# Configure environment
cp .env.example .env
# Edit .env — set DB_DATABASE, DB_USERNAME, DB_PASSWORD

# Generate app key
php artisan key:generate

# Create the database
mysql -u root -p -e "CREATE DATABASE hotel_operations CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations and seed demo data
php artisan migrate --seed

# Start the API server (port 8000)
php artisan serve
```

### Frontend Setup

```bash
cd frontend

# Install dependencies
npm install

# Start the dev server (port 5173)
npm run dev
```

Open `http://localhost:5173` in your browser.

---

## Demo Credentials

| Role              | Username     | Password     |
|-------------------|--------------|--------------|
| Admin             | admin        | admin123     |
| General Manager   | gm           | gm123        |
| Assistant Manager | assistant    | assistant123 |
| Housekeeping Mgr  | housekeeping | house123     |
| Employee          | employee1    | emp123       |

---

## API Reference

All endpoints are prefixed with `/api`. Protected routes require `Authorization: Bearer <token>`.

| Method | Endpoint                    | Auth    | Description                        |
|--------|-----------------------------|---------|------------------------------------|
| POST   | /auth/login                 | Public  | Login and receive token            |
| GET    | /auth/me                    | ✅      | Get current user                   |
| POST   | /auth/logout                | ✅      | Revoke current token               |
| GET    | /dashboard                  | Admin/GM| Dashboard analytics                |
| GET    | /rates                      | ✅      | List hotel rates (filterable)      |
| POST   | /rates                      | Admin/GM| Create/update rate                 |
| GET    | /rates/compare              | ✅      | Compare two dates                  |
| GET    | /rates/historical           | ✅      | Historical summary                 |
| GET    | /tips                       | ✅      | List tips (role-scoped)            |
| POST   | /tips                       | Managers| Create tip                         |
| GET    | /tips/analytics             | ✅      | Tip analytics by employee/date     |
| GET    | /time-logs                  | ✅      | List time logs (role-scoped)       |
| GET    | /time-logs/active           | ✅      | Current open session (self)        |
| POST   | /time-logs/clock-in         | ✅      | Self clock-in (auto-detect shift)  |
| POST   | /time-logs/clock-out        | ✅      | Self clock-out                     |
| GET    | /time-logs/summary          | Managers| Per-employee attendance summary    |
| POST   | /time-logs                  | Managers| Manual entry / correction          |
| POST   | /time-logs/{id}/approve     | Managers| Approve a pending entry            |
| GET    | /shifts                     | ✅      | List shifts (role-scoped)          |
| POST   | /shifts                     | Managers| Schedule a shift (conflict check)  |
| PUT    | /shifts/{id}                | Managers| Update a shift                     |
| DELETE | /shifts/{id}                | Managers| Remove a shift                     |
| GET    | /tasks                      | ✅      | List tasks (role-scoped)           |
| POST   | /tasks                      | ✅      | Create an operations task          |
| PATCH  | /tasks/{id}/status          | ✅ (assignee/mgr) | Update status only       |
| PUT    | /tasks/{id}                 | mgr/owner| Full update                       |
| DELETE | /tasks/{id}                 | mgr/owner| Delete                            |
| GET    | /users                      | Admin   | List users                         |
| POST   | /users                      | Admin   | Create user                        |
| GET    | /audit-logs                 | Admin   | List audit events                  |

---

## Production Build

```bash
# Frontend
cd frontend
npm run build
# Output: frontend/dist/

# Serve backend
cd backend
php artisan config:cache
php artisan route:cache
php artisan optimize
```

---

## Customization

- **Colors**: Edit `frontend/tailwind.config.js` → `theme.extend.colors.primary`
- **Roles / Permissions**: Edit `frontend/src/stores/auth.js` → `canAccess()` and `canEdit()`; mirror in `backend/routes/api.php`
- **New Modules**: Add a migration, model, controller, API resource, Pinia store, and Vue page. Register the route in `api.php` and add a nav item in `AppSidebar.vue`
- **Database**: Update `.env` with your `DB_*` credentials and re-run `php artisan migrate --seed`

---

## Future Modules (Architecture Ready)

The service-layer + modular router + Pinia store pattern makes it straightforward to add:

- Payroll management
- Booking / reservation system
- Housekeeping task tracker
- Inventory management
- Announcements / noticeboard
- Staff scheduling

---

*Built with Laravel 11 + Vue 3 — portfolio project by Erwin A. Guevarra*
