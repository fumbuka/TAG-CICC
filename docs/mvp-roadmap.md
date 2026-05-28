# MVP Roadmap

## Product Principle

Tunaanza na mfumo mdogo unaofanya kazi vizuri: kusajili washirika, kuwaweka kwenye idara/zones, kusimamia roles, na kutoa taarifa za msingi. Baada ya hapo tunaongeza fedha, ibada, kalenda, na reports za utekelezaji.

## Phase 0: Decisions

Before coding:

- System name: TAG-CICC.
- Production domain: www.tag-cicc.or.tz.
- Confirm GitHub repository name.
- Login method: email and phone number.
- Confirm first administrator account.
- Frontend stack: Laravel + Livewire.
- Confirm whether Excel import template will use Swahili or English column names.

## Phase 1: Laravel Foundation

Deliverables:

- Laravel project initialized.
- Git repository initialized and pushed to GitHub.
- Database configured for MariaDB/MySQL.
- Authentication installed.
- Roles and permissions installed.
- Main admin dashboard shell.

Recommended packages:

- spatie/laravel-permission
- maatwebsite/excel
- barryvdh/laravel-dompdf

## Phase 2: Membership, Departments, and Zones

Deliverables:

- Members CRUD.
- Departments CRUD.
- Zones CRUD.
- Dynamic department positions.
- Member department assignment.
- Member zone assignment.
- Zone transfer history.
- Automatic assignment by age and gender.
- Excel import for members.
- Basic member reports.

Success criteria:

- Katibu wa Kanisa can register one member.
- Katibu can upload Excel and import many members.
- A member can belong to more than one department.
- Leaders can only view members within their allowed department/zone.

## Phase 3: Leadership and Permissions

Deliverables:

- Assign church-level roles.
- Assign department leaders.
- Assign zone leaders.
- Department-scoped dashboards.
- Zone-scoped dashboards.
- Audit log for important actions.

Success criteria:

- Mchungaji Kiongozi sees all summaries.
- Katibu wa Kanisa manages members, zones, and departments.
- Department leader sees only their department.
- Zone leader sees only their zone.

## Phase 4: Services and Finance

Deliverables:

- Service types.
- Church services.
- Department services.
- Zone services.
- Attendance summary.
- Offering entries.
- Tithe entries.
- Contribution campaigns.
- Kapu, Gunia, and Pastor support categories.
- Monthly financial summary.

Success criteria:

- Every service can have offering recorded.
- Sunday main service can include tithe.
- Finance users can export reports.
- Department/zone finance entries remain scoped correctly.

## Phase 5: Calendar and Reports

Deliverables:

- Annual TAG calendar events.
- Responsible departments/persons.
- Budget estimate per event.
- Event status tracking.
- Department reports.
- Report approval workflow.
- Planned vs completed performance percentage.

Success criteria:

- Pastor can compare calendar plan against submitted reports.
- Each department can see pending responsibilities.
- System can show implementation percentage by department and year.

## Phase 6: Mobile Experience

Deliverables:

- Responsive mobile UI.
- PWA manifest.
- Installable web app on Android.
- Mobile dashboards for leaders.

Success criteria:

- Users can use the system comfortably from a smartphone browser.
- Android users can install the PWA from browser.

## Suggested Build Order

1. Project setup
2. Auth and permissions
3. Members
4. Departments
5. Zones
6. Excel import
7. Leadership assignment
8. Services
9. Finance
10. Calendar
11. Reports
12. PWA polish

## Hosting Strategy

Initial hosting:

- Hostinger Business Web Hosting
- MariaDB/MySQL
- Laravel deployed through SSH/Git/Composer
- Public folder mapped correctly to Laravel public directory

Future hosting:

- Move to VPS when budget allows.
- Switch database to PostgreSQL if needed.
- Keep database migrations database-friendly to reduce future migration friction.
