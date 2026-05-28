# Church Management System Requirements

## 1. Project Summary

Mfumo huu ni kwa ajili ya kusimamia shughuli za kanisa la TAG-CICC kwa njia ya web na mobile-friendly interface. Mfumo utatumika na viongozi wa kanisa, viongozi wa idara, viongozi wa zone, na watendaji wa fedha/reporting kulingana na majukumu yao.

Kwa kuwa budget ya sasa hairuhusu VPS, mfumo utaanza kwa Laravel kwenye Hostinger Business Web Hosting na MariaDB/MySQL. Muundo wa database na code utawekwa kwa namna ambayo itarahisisha kuhamia PostgreSQL baadaye bila kujenga mfumo upya.

System name: TAG-CICC

Production domain: www.tag-cicc.or.tz

## 2. Main Goals

- Kusajili na kusimamia washirika wa kanisa.
- Kusimamia idara zinazoweza kuongezeka au kupungua kwa muda.
- Kusimamia zones za washirika kulingana na maeneo wanayoishi.
- Kusimamia ibada za kanisa, idara, na zone.
- Kurekodi sadaka, zaka, michango, kapu, gunia, na matoleo mengine.
- Kusimamia kalenda ya mwaka kutoka makao makuu ya TAG.
- Kupima utekelezaji wa idara kwa kulinganisha targets za kalenda na reports halisi.
- Kuwapa viongozi access kulingana na majukumu yao bila mwingiliano.
- Kutoa reports za uongozi, fedha, mahudhurio, idara, zones, na utekelezaji.
- Kuwezesha matumizi kupitia browser kwenye computer na simu.

## 3. Recommended Technical Direction

### 3.1 Initial Stack

- Backend: Laravel
- Database: MariaDB/MySQL on Hostinger Business Web Hosting
- Frontend: Laravel Blade + Tailwind CSS + Livewire
- Authentication: Laravel Breeze or Laravel Jetstream
- Roles and permissions: Spatie Laravel Permission
- Excel import/export: Laravel Excel
- PDF reports: Laravel DomPDF or Browsershot if hosting supports it
- Deployment: GitHub + Hostinger SSH/Git workflow

### 3.2 Mobile Direction

Awamu ya kwanza itumie responsive web app na PWA support. Hii itaruhusu users kutumia mfumo kwenye simu kama app bila gharama ya kujenga Android native app mapema.

Android native app inaweza kujengwa baadaye kwa Flutter au React Native baada ya MVP kuthibitishwa.

## 4. Users and Roles

Roles za mwanzo:

- Super Admin
- Mchungaji Kiongozi
- Katibu wa Kanisa
- Mhasibu wa Kanisa
- Mweka Hazina wa Kanisa
- Mkurugenzi wa Idara
- Makamu Mkurugenzi wa Idara
- Katibu wa Idara
- Makamu Katibu wa Idara
- Mweka Hazina wa Idara
- Kiongozi wa Zone
- Mshirika

Note: User mmoja anaweza kuwa na role zaidi ya moja, mfano mshirika anaweza pia kuwa Mkurugenzi wa Idara na Kiongozi wa Zone.

## 5. Permissions Model

Mfumo utahitaji permission-based access badala ya kutegemea role names pekee.

### 5.1 Mchungaji Kiongozi

- Kuona dashboard kuu ya kanisa.
- Kuona reports zote za idara, zones, fedha, na membership.
- Kuomba reports kutoka kwa viongozi wa idara.
- Kuapprove au kureject reports muhimu.
- Kuona utekelezaji wa kalenda kwa asilimia.
- Kutoa maelekezo ya kuassign washirika kwenye idara maalum.

### 5.2 Katibu wa Kanisa

- Kusajili mshirika mmoja mmoja.
- Kuimport washirika wengi kwa Excel.
- Kuassign washirika kwenye zones.
- Kuhamisha mshirika kutoka zone moja kwenda nyingine.
- Kuassign washirika kwenye idara manually.
- Kusimamia departments na zones.
- Kusimamia calendar events.
- Kuandaa administrative reports.

### 5.3 Mhasibu/Mweka Hazina wa Kanisa

- Kurekodi mapato ya kanisa.
- Kurekodi sadaka, zaka, michango, kapu, gunia, na michango ya kumtegemeza mchungaji.
- Kuona financial reports.
- Kuandaa summary za fedha kwa leadership.
- Ku-export reports PDF/Excel.

### 5.4 Viongozi wa Idara

Viongozi wa idara waone data ya idara yao tu.

- Mkurugenzi wa Idara: kuona dashboard ya idara, members wa idara, reports za idara, na progress.
- Makamu Mkurugenzi: kusaidia director kulingana na permissions zitakazopewa.
- Katibu wa Idara: kuandaa na kuwasilisha reports za idara.
- Makamu Katibu: kusaidia secretary kulingana na permissions.
- Mweka Hazina wa Idara: kurekodi sadaka/michango ya idara na kuandaa reports za fedha za idara.

### 5.5 Viongozi wa Zone

- Kuona washirika wa zone yao tu.
- Kurekodi mahudhurio ya ibada za zone.
- Kurekodi sadaka za zone.
- Kuandaa report za zone.

## 6. Membership Module

### 6.1 Member Information

Taarifa za mshirika:

- Full name
- Gender
- Date of birth
- Age auto-calculated
- Phone number
- Alternative phone
- Email
- Residential area
- Zone
- Marital status
- Baptism status
- Membership status
- Date joined
- Source: member, visitor converted, transfer, other
- Emergency contact
- Notes

### 6.2 Member Statuses

- Active
- Inactive
- Visitor
- New believer
- Transferred
- Deceased

### 6.3 Registration Flow

Katibu wa Kanisa ndiye atakayesajili washirika.

Supported methods:

- Single member registration form
- Bulk Excel upload

Baada ya kusajiliwa, mfumo ufanye automatic department assignment kulingana na gender na age.

User login should support both email and phone number. Official church email format example: fumbuka.adam@tag-cicc.or.tz. Phone login example: 0654849299.

### 6.4 Automatic Department Assignment

Rules za mwanzo:

- Age less than 18: assign to Watoto
- Age 18 to 25: assign to Vijana
- Female above 25: assign to Wamama
- Male above 25: assign to Wababa

Manual assignment:

- Maendeleo
- Uinjilishaji
- Sala na Maombezi
- Other future departments

Important: Idara zote ziwe dynamic records kwenye database. Tusijenge tables tofauti kwa Wamama, Wababa, Watoto, au Vijana.

## 7. Departments Module

### 7.1 Current Departments

- Watoto
- Wamama
- Wababa
- Vijana
- Maendeleo
- Uinjilishaji
- Sala na Maombezi

### 7.2 Department Features

- Add department
- Edit department
- Archive department
- Assign members
- Remove members
- Assign leaders
- Define department service schedule
- Track department activities
- Submit department reports
- Track calendar responsibilities

### 7.3 Department Leadership Positions

Default positions:

- Mkurugenzi
- Makamu Mkurugenzi
- Katibu
- Makamu Katibu
- Mweka Hazina

Positions ziwe dynamic ili baadaye nafasi nyingine ziweze kuongezwa.

## 8. Zones Module

### 8.1 Current Zones

- Changombe
- Kanisani
- Mbwanga

### 8.2 Zone Features

- Add zone
- Edit zone
- Archive zone
- Assign members to zone
- Transfer member from one zone to another
- Track zone services
- Track zone offerings
- Track zone attendance
- Submit zone reports

Zone transfer history ihifadhiwe ili leadership ijue mshirika alihama lini na kutoka wapi kwenda wapi.

## 9. Services and Attendance Module

### 9.1 Church Services

Regular services:

- Jumapili
- Jumatano
- Ijumaa

### 9.2 Zone Services

- Ibada za zone kila Jumamosi asubuhi.
- Kila zone iwe na ibada zake na sadaka zake.

### 9.3 Department Services

Kila idara iwe na uwezo wa kuweka ratiba yake.

Examples:

- Wababa: Jumapili usiku saa 1:00
- Wamama: Jumanne jioni saa 10:00

Ratiba iwe editable kwa sababu idara zinaweza kubadilisha siku na muda.

### 9.4 Service Features

- Create service
- Select service type: main church, department, zone, special event
- Select responsible department or zone if applicable
- Record date, time, preacher/speaker, topic
- Record attendance summary
- Record offering
- Record tithe if service allows tithe
- Attach notes or report

## 10. Finance Module

### 10.1 Income Categories

Default income categories:

- Sadaka
- Zaka
- Michango
- Ahadi
- Kapu la Wamama
- Gunia la Wababa
- Kumtegemeza Mchungaji
- Sadaka ya Zone
- Sadaka ya Idara
- Special event contribution

Categories ziwe dynamic ili ziweze kuongezwa baadaye.

### 10.2 Finance Rules

- Kila ibada iwe na sadaka entry, hata kama amount ni zero.
- Ibada kuu ya Jumapili iweze kurekodi zaka.
- Michango iweze kuhusishwa na event, department, zone, au general church project.
- Kila transaction iwe na aliyeingiza, muda, source, category, na notes.

### 10.3 Financial Controls

- Audit log kwa kila transaction.
- Correction flow badala ya kufuta transaction moja kwa moja.
- Role-based visibility kwa fedha.
- Monthly financial summary.
- Export PDF/Excel.

## 11. Calendar and Planning Module

### 11.1 Annual TAG Calendar

Kanisa linapokea kalenda ya mwaka kutoka makao makuu ya TAG. Mfumo utaruhusu kuingiza kalenda hii kama annual plan.

### 11.2 Calendar Event Information

- Title
- Description
- Date
- End date if multi-day
- Event type
- Responsible department
- Responsible leader
- Budget estimate
- Expected output
- Required report
- Status

### 11.3 Event Statuses

- Planned
- In progress
- Completed
- Cancelled
- Postponed

### 11.4 Budget Projection

Kalenda itumike kufanya projection ya budget ya mwaka mzima.

Budget data:

- Estimated income
- Estimated expense
- Responsible department
- Funding source
- Actual income
- Actual expense
- Variance

## 12. Reporting and Performance Module

### 12.1 Department Reports

Viongozi wa idara watume reports zinazoonyesha:

- Activities completed
- Activities not completed
- Challenges
- Attendance if applicable
- Finance summary if applicable
- Evidence or notes
- Recommendations

### 12.2 Performance Comparison

Mfumo ulinganishe:

- Planned calendar activities
- Submitted reports
- Completed activities

Kisha uonyeshe percentage ya utekelezaji kwa:

- Department
- Zone
- Whole church
- Quarter
- Year

### 12.3 Report Approval Flow

Recommended flow:

- Department Secretary prepares report.
- Department Director reviews/submits.
- Church Secretary receives.
- Lead Pastor reviews or approves.

## 13. Visitors Module

Kanisa lina wageni ambao baadhi yao hugeuka washirika wa kudumu.

Features:

- Register visitor
- Capture visit date
- Capture phone/location
- Capture invited by
- Assign follow-up person
- Track follow-up status
- Convert visitor to member

Follow-up statuses:

- New
- Contacted
- Interested
- Joined
- Not reachable
- Not interested

## 14. Additional Recommended Features

Features muhimu kwa kanisa la kipentekoste:

- Prayer requests and testimonies
- Pastoral care records
- New believers class tracking
- Baptism records
- Child dedication records
- Birthday reminders
- Wedding and family records
- Asset/inventory management
- Document storage for minutes, letters, reports
- SMS/WhatsApp reminders in future phase
- Audit logs for accountability
- Data backup/export

## 15. Database Entities Draft

Core tables:

- users
- roles
- permissions
- members
- member_statuses
- departments
- department_positions
- department_leaders
- member_departments
- zones
- member_zone_history
- services
- service_types
- attendance_records
- income_categories
- financial_transactions
- contribution_campaigns
- calendar_events
- calendar_event_targets
- event_reports
- report_approvals
- visitors
- visitor_followups
- audit_logs

## 16. MVP Scope

MVP isijaribu kufanya kila kitu mara moja. Ianze na modules muhimu zinazotengeneza thamani ya haraka.

### Phase 1: Foundation

- Laravel setup
- Authentication
- Roles and permissions
- Main dashboard
- Members registration
- Departments
- Zones
- Automatic department assignment
- Excel import for members

### Phase 2: Services and Finance

- Services setup
- Offerings
- Tithes
- Contributions
- Kapu/Gunia
- Monthly support for pastor
- Financial summaries

### Phase 3: Calendar and Reports

- Annual calendar
- Department responsibilities
- Department reports
- Zone reports
- Target vs actual tracking
- Performance percentage

### Phase 4: Mobile and Notifications

- PWA install support
- Mobile dashboards
- SMS/WhatsApp notification planning
- Android app decision after usage feedback

## 17. Hosting and Storage Estimate

Hostinger 50GB storage inatosha kwa awamu ya kwanza na ya kati kama mfumo hautahifadhi video/audio kubwa.

Estimated usage:

- Laravel app and dependencies: 0.5GB to 2GB
- Database for several years: 1GB to 5GB
- Documents, Excel, and PDF reports: 2GB to 10GB
- Logs and cache: 1GB to 5GB if cleaned regularly
- Backups: 5GB to 20GB depending on retention

Recommended controls:

- Do not upload large videos/audio to hosting.
- Store heavy media externally if needed.
- Rotate logs.
- Keep weekly/monthly backups with clear retention.
- Export old reports when storage grows.

Conclusion: 50GB should be enough for this church system and likely one other moderate system, provided files and backups are managed carefully.

## 18. Immediate Next Steps

1. Confirm system name and domain.
2. Confirm whether to start with Laravel Blade + Livewire.
3. Create GitHub repository.
4. Initialize Laravel project.
5. Configure MariaDB/MySQL for Hostinger compatibility.
6. Build authentication and roles.
7. Build members, departments, and zones first.
8. Add Excel import.
9. Add services and finance.
10. Add calendar and reporting.
