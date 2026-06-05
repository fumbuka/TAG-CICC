<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\Department;
use App\Models\Expense;
use App\Models\FinancialTransaction;
use App\Models\Member;
use App\Models\MemberLeadershipAssignment;
use App\Models\Pledge;
use App\Models\PledgePayment;
use App\Models\Service;
use App\Models\SmsCampaign;
use App\Models\SmsLog;
use App\Models\SmsPurchase;
use App\Models\SmsTransaction;
use App\Models\SmsWallet;
use App\Models\User;
use App\Models\Zone;
use App\Support\UserDataScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;

class OperationalModuleReportPdfService
{
    public const CONTENT_TYPE = 'application/pdf';

    /**
     * @var array<int, string>
     */
    private const MODULES = [
        'members',
        'departments',
        'zones',
        'services',
        'finance',
        'calendar',
        'leadership',
        'sms',
    ];

    public function __construct(private readonly OperationalReportPdfAssets $assets) {}

    /**
     * @return array<int, array{key: string, label: string, description: string}>
     */
    public function availableModules(User $user): array
    {
        return collect(self::MODULES)
            ->filter(fn (string $module): bool => $this->canDownloadModule($module, $user))
            ->map(fn (string $module): array => $this->moduleOption($module))
            ->values()
            ->all();
    }

    public function canDownloadModule(string $module, User $user): bool
    {
        if (! in_array($module, self::MODULES, true)) {
            return false;
        }

        if ($module === 'finance') {
            return $user->can('finance.view') || $user->can('finance.record');
        }

        if ($module === 'sms') {
            return $user->can('sms.reports') || $user->can('sms.view');
        }

        return $user->can('reports.view')
            || $user->can('reports.submit')
            || $user->can('reports.approve');
    }

    /**
     * @return array{path: string, filename: string}
     */
    public function create(string $module, User $downloadedBy): array
    {
        if (! $this->canDownloadModule($module, $downloadedBy)) {
            throw new InvalidArgumentException('The requested report module is not available.');
        }

        $downloadedAt = now();
        $scope = UserDataScope::for($downloadedBy);
        $report = $this->buildReport($module, $downloadedBy, $scope);

        $filename = sprintf(
            'tag-cicc-%s-operational-report-%s.pdf',
            Str::slug($module),
            $downloadedAt->format('Ymd-His'),
        );
        $directory = storage_path('app/private/report-downloads');
        $path = $directory.DIRECTORY_SEPARATOR.$filename;

        File::ensureDirectoryExists($directory);

        $pdf = Pdf::loadView('pdf.operational-module-report', [
            ...$this->assets->branding($downloadedBy, $downloadedAt),
            'report' => $report,
        ])
            ->setPaper('a4')
            ->setOption('defaultFont', 'DejaVu Sans');

        $pdf->save($path);

        return [
            'path' => $path,
            'filename' => $filename,
        ];
    }

    /**
     * @return array{key: string, label: string, description: string}
     */
    private function moduleOption(string $module): array
    {
        return [
            'key' => $module,
            'label' => __('messages.module_report_'.$module),
            'description' => __('messages.module_report_'.$module.'_summary'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReport(string $module, User $user, UserDataScope $scope): array
    {
        return match ($module) {
            'members' => $this->membersReport($scope),
            'departments' => $this->departmentsReport($scope),
            'zones' => $this->zonesReport($scope),
            'services' => $this->servicesReport($scope),
            'finance' => $this->financeReport($scope),
            'calendar' => $this->calendarReport($scope),
            'leadership' => $this->leadershipReport($user, $scope),
            'sms' => $this->smsReport($user, $scope),
            default => throw new InvalidArgumentException('Unsupported report module.'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function baseReport(string $module, UserDataScope $scope): array
    {
        return [
            'module' => $module,
            'title' => __('messages.module_report_pdf_title', ['module' => __('messages.module_report_'.$module)]),
            'subtitle' => __('messages.module_report_'.$module.'_summary'),
            'scopeLabel' => $scope->label(),
            'metrics' => [],
            'chartRows' => [],
            'sections' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function membersReport(UserDataScope $scope): array
    {
        $query = $scope->applyMemberScope(Member::query());
        $total = (clone $query)->count();
        $active = (clone $query)->where('membership_status', 'active')->count();
        $male = (clone $query)->where('gender', 'male')->count();
        $female = (clone $query)->where('gender', 'female')->count();
        $withAccess = (clone $query)->whereHas('user', fn (Builder $query): Builder => $query->where('is_active', true))->count();

        $zoneRows = $scope->applyZoneScope(Zone::query())
            ->withCount(['members as active_members_count' => fn (Builder $query): Builder => $scope->applyMemberScope($query)->where('membership_status', 'active')])
            ->orderByDesc('active_members_count')
            ->limit(8)
            ->get();

        $departmentRows = $scope->applyDepartmentScope(Department::query())
            ->withCount(['members as active_members_count' => fn (Builder $query): Builder => $scope->applyMemberScope($query)->where('member_departments.is_active', true)])
            ->orderByDesc('active_members_count')
            ->limit(8)
            ->get();

        $recentMembers = (clone $query)
            ->with(['zone', 'departments'])
            ->latest()
            ->limit(8)
            ->get();

        $report = $this->baseReport('members', $scope);
        $report['metrics'] = [
            $this->metric(__('messages.members'), $this->number($total), __('messages.total_registered_members')),
            $this->metric(__('messages.active'), $this->number($active), __('messages.membership_status_active')),
            $this->metric(__('messages.has_login_access'), $this->number($withAccess), __('messages.members_with_system_access')),
            $this->metric(__('messages.zones'), $this->number($zoneRows->count()), __('messages.active_zones_count')),
        ];
        $report['chartRows'] = $this->chartRows([
            ['label' => __('messages.male'), 'value' => $male],
            ['label' => __('messages.female'), 'value' => $female],
            ['label' => __('messages.active'), 'value' => $active],
        ]);
        $report['sections'] = [
            $this->tableSection(__('messages.members_by_zone'), [__('messages.zone'), __('messages.members')], $zoneRows->map(fn (Zone $zone): array => [
                $zone->name,
                $this->number($zone->active_members_count),
            ])),
            $this->tableSection(__('messages.members_by_department'), [__('messages.department'), __('messages.members')], $departmentRows->map(fn (Department $department): array => [
                $department->name,
                $this->number($department->active_members_count),
            ])),
            $this->tableSection(__('messages.recent_members'), [__('messages.name'), __('messages.gender'), __('messages.zone')], $recentMembers->map(fn (Member $member): array => [
                $member->fullName(),
                $this->genderLabel($member->gender),
                $member->zone?->name ?: __('messages.no_zone_selected'),
            ])),
        ];

        return $report;
    }

    /**
     * @return array<string, mixed>
     */
    private function departmentsReport(UserDataScope $scope): array
    {
        $query = $scope->applyDepartmentScope(Department::query());
        $total = (clone $query)->count();
        $active = (clone $query)->where('is_active', true)->count();
        $ageBased = (clone $query)->where('is_age_based', true)->count();

        $departments = (clone $query)
            ->withCount(['members as active_members_count' => fn (Builder $query): Builder => $query->where('member_departments.is_active', true)])
            ->orderByDesc('active_members_count')
            ->orderBy('name')
            ->get();

        $report = $this->baseReport('departments', $scope);
        $report['metrics'] = [
            $this->metric(__('messages.departments'), $this->number($total), __('messages.records')),
            $this->metric(__('messages.active'), $this->number($active), __('messages.active_departments_count')),
            $this->metric(__('messages.age_rule'), $this->number($ageBased), __('messages.rule')),
            $this->metric(__('messages.inactive'), $this->number(max($total - $active, 0)), __('messages.status')),
        ];
        $report['chartRows'] = $this->chartRows($departments->take(8)->map(fn (Department $department): array => [
            'label' => $department->name,
            'value' => (int) $department->active_members_count,
        ])->all());
        $report['sections'] = [
            $this->tableSection(__('messages.existing_departments'), [__('messages.department'), __('messages.members'), __('messages.status')], $departments->map(fn (Department $department): array => [
                $department->name,
                $this->number($department->active_members_count),
                $department->is_active ? __('messages.active') : __('messages.inactive'),
            ])),
        ];

        return $report;
    }

    /**
     * @return array<string, mixed>
     */
    private function zonesReport(UserDataScope $scope): array
    {
        $query = $scope->applyZoneScope(Zone::query());
        $total = (clone $query)->count();
        $active = (clone $query)->where('is_active', true)->count();

        $zones = (clone $query)
            ->withCount(['members as active_members_count' => fn (Builder $query): Builder => $scope->applyMemberScope($query)->where('membership_status', 'active')])
            ->orderByDesc('active_members_count')
            ->orderBy('name')
            ->get();

        $report = $this->baseReport('zones', $scope);
        $report['metrics'] = [
            $this->metric(__('messages.zones'), $this->number($total), __('messages.records')),
            $this->metric(__('messages.active'), $this->number($active), __('messages.active_zones_count')),
            $this->metric(__('messages.members'), $this->number($zones->sum('active_members_count')), __('messages.total_registered_members')),
            $this->metric(__('messages.inactive'), $this->number(max($total - $active, 0)), __('messages.status')),
        ];
        $report['chartRows'] = $this->chartRows($zones->take(8)->map(fn (Zone $zone): array => [
            'label' => $zone->name,
            'value' => (int) $zone->active_members_count,
        ])->all());
        $report['sections'] = [
            $this->tableSection(__('messages.existing_zones'), [__('messages.zone'), __('messages.members'), __('messages.status')], $zones->map(fn (Zone $zone): array => [
                $zone->name,
                $this->number($zone->active_members_count),
                $zone->is_active ? __('messages.active') : __('messages.inactive'),
            ])),
        ];

        return $report;
    }

    /**
     * @return array<string, mixed>
     */
    private function servicesReport(UserDataScope $scope): array
    {
        $query = $scope->applyServiceScope(Service::query());
        $total = (clone $query)->count();
        $attendanceTotal = (int) (clone $query)->sum('attendance_count');
        $monthTotal = (clone $query)
            ->whereBetween('service_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->count();

        $typeRows = (clone $query)
            ->selectRaw('service_type_id, count(*) as services_count, coalesce(sum(attendance_count), 0) as attendance_total')
            ->with('serviceType')
            ->groupBy('service_type_id')
            ->orderByDesc('services_count')
            ->limit(8)
            ->get();

        $latestServices = (clone $query)
            ->with(['serviceType', 'department', 'zone'])
            ->latest('service_date')
            ->latest()
            ->limit(10)
            ->get();

        $report = $this->baseReport('services', $scope);
        $report['metrics'] = [
            $this->metric(__('messages.services'), $this->number($total), __('messages.recorded_services_count')),
            $this->metric(__('messages.month_total'), $this->number($monthTotal), now()->translatedFormat('M Y')),
            $this->metric(__('messages.attendance'), $this->number($attendanceTotal), __('messages.total_attendance')),
            $this->metric(__('messages.average_attendance'), $this->number($total > 0 ? round($attendanceTotal / $total) : 0), __('messages.services')),
        ];
        $report['chartRows'] = $this->chartRows($typeRows->map(fn (Service $service): array => [
            'label' => $service->serviceType?->name ?: __('messages.service_type'),
            'value' => (int) $service->services_count,
        ])->all());
        $report['sections'] = [
            $this->tableSection(__('messages.services_by_type'), [__('messages.service_type'), __('messages.services'), __('messages.attendance')], $typeRows->map(fn (Service $service): array => [
                $service->serviceType?->name ?: __('messages.not_recorded'),
                $this->number($service->services_count),
                $this->number($service->attendance_total),
            ])),
            $this->tableSection(__('messages.latest_services'), [__('messages.date'), __('messages.service'), __('messages.context'), __('messages.attendance')], $latestServices->map(fn (Service $service): array => [
                $service->service_date?->translatedFormat('d M Y') ?: '-',
                $service->title,
                $this->contextLabel($service->department?->name, $service->zone?->name),
                $service->attendance_count !== null ? $this->number($service->attendance_count) : __('messages.not_recorded'),
            ])),
        ];

        return $report;
    }

    /**
     * @return array<string, mixed>
     */
    private function financeReport(UserDataScope $scope): array
    {
        $incomeQuery = $scope->applyFinanceScope(FinancialTransaction::query());
        $expenseQuery = $scope->applyFinanceScope(Expense::query());
        $pledgeQuery = $scope->applyFinanceScope(Pledge::query());

        $incomeTotal = (float) (clone $incomeQuery)->sum('amount');
        $expenseTotal = (float) (clone $expenseQuery)->sum('amount');
        $pledgedTotal = (float) (clone $pledgeQuery)->sum('pledged_amount');
        $paidPledges = (float) $scope->applyPledgePaymentScope(PledgePayment::query())->sum('amount');

        $categoryRows = (clone $incomeQuery)
            ->selectRaw('income_category_id, count(*) as transactions_count, coalesce(sum(amount), 0) as amount_total')
            ->with('incomeCategory')
            ->groupBy('income_category_id')
            ->orderByDesc('amount_total')
            ->limit(8)
            ->get();

        $expenseRows = (clone $expenseQuery)
            ->selectRaw('expense_category_id, count(*) as expenses_count, coalesce(sum(amount), 0) as amount_total')
            ->with('expenseCategory')
            ->groupBy('expense_category_id')
            ->orderByDesc('amount_total')
            ->limit(8)
            ->get();

        $report = $this->baseReport('finance', $scope);
        $report['metrics'] = [
            $this->metric(__('messages.total_income'), $this->currency($incomeTotal), __('messages.records')),
            $this->metric(__('messages.expenses'), $this->currency($expenseTotal), __('messages.records')),
            $this->metric(__('messages.cash_on_hand'), $this->currency($incomeTotal - $expenseTotal), __('messages.recorded_income_total')),
            $this->metric(__('messages.pledge_balance'), $this->currency(max($pledgedTotal - $paidPledges, 0)), __('messages.total_pledged')),
        ];
        $report['chartRows'] = $this->chartRows($categoryRows->map(fn (FinancialTransaction $transaction): array => [
            'label' => $transaction->incomeCategory?->name ?: __('messages.income_category'),
            'value' => (float) $transaction->amount_total,
            'formatted' => $this->currency((float) $transaction->amount_total),
        ])->all());
        $report['sections'] = [
            $this->tableSection(__('messages.income_by_category'), [__('messages.income_category'), __('messages.transactions'), __('messages.total_income')], $categoryRows->map(fn (FinancialTransaction $transaction): array => [
                $transaction->incomeCategory?->name ?: __('messages.not_recorded'),
                $this->number($transaction->transactions_count),
                $this->currency((float) $transaction->amount_total),
            ])),
            $this->tableSection(__('messages.expenses_by_category'), [__('messages.expense_category'), __('messages.records'), __('messages.expenses')], $expenseRows->map(fn (Expense $expense): array => [
                $expense->expenseCategory?->name ?: __('messages.not_recorded'),
                $this->number($expense->expenses_count),
                $this->currency((float) $expense->amount_total),
            ])),
        ];

        return $report;
    }

    /**
     * @return array<string, mixed>
     */
    private function calendarReport(UserDataScope $scope): array
    {
        $query = $scope->applyCalendarEventScope(CalendarEvent::query());
        $total = (clone $query)->count();
        $important = (clone $query)->where('is_important', true)->count();
        $upcoming = (clone $query)->whereDate('event_date', '>=', now()->toDateString())->count();
        $reported = (clone $query)->whereHas('departmentEventReport')->count();

        $contextCounts = [
            ['label' => __('messages.church'), 'value' => (clone $query)->whereNull('department_id')->whereNull('zone_id')->count()],
            ['label' => __('messages.departments'), 'value' => (clone $query)->whereNotNull('department_id')->count()],
            ['label' => __('messages.zones'), 'value' => (clone $query)->whereNotNull('zone_id')->count()],
        ];

        $events = (clone $query)
            ->with(['department', 'zone', 'departmentEventReport'])
            ->whereDate('event_date', '>=', now()->toDateString())
            ->orderBy('event_date')
            ->orderBy('starts_at')
            ->limit(10)
            ->get();

        $report = $this->baseReport('calendar', $scope);
        $report['metrics'] = [
            $this->metric(__('messages.calendar_events'), $this->number($total), __('messages.records')),
            $this->metric(__('messages.important_event'), $this->number($important), __('messages.calendar')),
            $this->metric(__('messages.upcoming_events'), $this->number($upcoming), __('messages.next_important_events')),
            $this->metric(__('messages.submitted_reports'), $this->number($reported), __('messages.department_execution_reports')),
        ];
        $report['chartRows'] = $this->chartRows($contextCounts);
        $report['sections'] = [
            $this->tableSection(__('messages.upcoming_events'), [__('messages.event_date'), __('messages.calendar_event'), __('messages.context'), __('messages.status')], $events->map(fn (CalendarEvent $event): array => [
                $event->event_date?->translatedFormat('d M Y') ?: '-',
                $event->title,
                $this->contextLabel($event->department?->name, $event->zone?->name),
                $event->departmentEventReport ? __('messages.report_status_'.$event->departmentEventReport->status) : __('messages.not_recorded'),
            ])),
        ];

        return $report;
    }

    /**
     * @return array<string, mixed>
     */
    private function leadershipReport(User $user, UserDataScope $scope): array
    {
        $query = $this->leadershipAssignmentsQuery($user, $scope);
        $total = (clone $query)->count();
        $church = (clone $query)->whereHas('leadershipTitle', fn (Builder $query): Builder => $query->where('scope', 'church'))->count();
        $department = (clone $query)->whereHas('leadershipTitle', fn (Builder $query): Builder => $query->where('scope', 'department'))->count();
        $zone = (clone $query)->whereHas('leadershipTitle', fn (Builder $query): Builder => $query->where('scope', 'zone'))->count();

        $assignments = (clone $query)
            ->with(['member.user', 'leadershipTitle', 'department', 'zone'])
            ->latest()
            ->limit(12)
            ->get();

        $report = $this->baseReport('leadership', $scope);
        $report['metrics'] = [
            $this->metric(__('messages.leadership_assignments'), $this->number($total), __('messages.active')),
            $this->metric(__('messages.church'), $this->number($church), __('messages.leadership_titles')),
            $this->metric(__('messages.departments'), $this->number($department), __('messages.department')),
            $this->metric(__('messages.zones'), $this->number($zone), __('messages.zone')),
        ];
        $report['chartRows'] = $this->chartRows([
            ['label' => __('messages.church'), 'value' => $church],
            ['label' => __('messages.departments'), 'value' => $department],
            ['label' => __('messages.zones'), 'value' => $zone],
        ]);
        $report['sections'] = [
            $this->tableSection(__('messages.leadership_assignments'), [__('messages.name'), __('messages.leadership_title'), __('messages.context'), __('messages.leader_login_access')], $assignments->map(fn (MemberLeadershipAssignment $assignment): array => [
                $assignment->member?->fullName() ?: __('messages.not_recorded'),
                $assignment->leadershipTitle?->name ?: __('messages.not_recorded'),
                $this->contextLabel($assignment->department?->name, $assignment->zone?->name),
                $assignment->member?->user?->is_active ? __('messages.has_login_access') : __('messages.missing_login_access'),
            ])),
        ];

        return $report;
    }

    /**
     * @return array<string, mixed>
     */
    private function smsReport(User $user, UserDataScope $scope): array
    {
        $wallets = $this->smsWalletsQuery($user, $scope)
            ->with(['department', 'user'])
            ->orderBy('owner_type')
            ->orderBy('name')
            ->get();
        $walletIds = $wallets->pluck('id');

        $campaignQuery = SmsCampaign::query()->whereIn('sms_wallet_id', $walletIds);
        $logsQuery = SmsLog::query()->whereHas('campaign', fn (Builder $query): Builder => $query->whereIn('sms_wallet_id', $walletIds));
        $purchaseQuery = SmsPurchase::query()->whereIn('sms_wallet_id', $walletIds);

        $sentCampaigns = (clone $campaignQuery)->whereIn('status', [SmsCampaign::STATUS_SENT, SmsCampaign::STATUS_PARTIAL])->count();
        $scheduledCampaigns = (clone $campaignQuery)->where('status', SmsCampaign::STATUS_SCHEDULED)->count();
        $failedCampaigns = (clone $campaignQuery)->where('status', SmsCampaign::STATUS_FAILED)->count();
        $deliveredLogs = (clone $logsQuery)->where('status', SmsLog::STATUS_DELIVERED)->count();
        $failedLogs = (clone $logsQuery)->whereIn('status', [SmsLog::STATUS_FAILED, SmsLog::STATUS_UNDELIVERED])->count();
        $paidRevenue = (float) (clone $purchaseQuery)->where('status', SmsPurchase::STATUS_PAID)->sum('total_amount');

        $recentCampaigns = (clone $campaignQuery)
            ->with(['wallet', 'sentBy', 'scheduledBy'])
            ->latest()
            ->limit(12)
            ->get();
        $recentPurchases = (clone $purchaseQuery)
            ->with(['wallet', 'requestedBy', 'approvedBy'])
            ->latest()
            ->limit(12)
            ->get();

        $report = $this->baseReport('sms', $scope);
        $report['metrics'] = [
            $this->metric(__('messages.sms_report_balance'), $this->number($wallets->sum('balance')), __('messages.sms_remaining')),
            $this->metric(__('messages.sms_report_purchased'), $this->number($wallets->sum('credits_purchased')), __('messages.sms_purchased')),
            $this->metric(__('messages.sms_report_used'), $this->number($wallets->sum('credits_used')), __('messages.sms_used')),
            $this->metric(__('messages.sms_report_paid_revenue'), $this->currency($paidRevenue), __('messages.sms_purchase_approval')),
        ];
        $report['chartRows'] = $this->chartRows($wallets->map(fn (SmsWallet $wallet): array => [
            'label' => $wallet->name,
            'value' => (int) $wallet->credits_used,
            'formatted' => $this->number($wallet->credits_used).' '.__('messages.sms_used'),
        ])->all());
        $report['sections'] = [
            $this->tableSection(__('messages.sms_wallets_management'), [__('messages.sms_wallet'), __('messages.sms_purchased'), __('messages.sms_used'), __('messages.sms_remaining')], $wallets->map(fn (SmsWallet $wallet): array => [
                $wallet->name,
                $this->number($wallet->credits_purchased),
                $this->number($wallet->credits_used),
                $this->number($wallet->balance),
            ])),
            $this->tableSection(__('messages.sms_campaign_history'), [__('messages.sms_campaign_title'), __('messages.sms_recipients'), __('messages.sms_credits_used'), __('messages.status')], $recentCampaigns->map(fn (SmsCampaign $campaign): array => [
                $campaign->title,
                $this->number($campaign->recipients_count),
                $this->number($campaign->total_credits_used),
                __('messages.sms_status_'.$campaign->status),
            ])),
            $this->tableSection(__('messages.sms_purchase_approval'), [__('messages.sms_wallet'), __('messages.requested_by'), __('messages.sms_quantity'), __('messages.status')], $recentPurchases->map(fn (SmsPurchase $purchase): array => [
                $purchase->wallet?->name ?: __('messages.not_recorded'),
                $purchase->requestedBy?->name ?: __('messages.not_recorded'),
                $this->number($purchase->sms_quantity),
                __('messages.sms_purchase_status_'.$purchase->status),
            ])),
            $this->tableSection(__('messages.sms_delivery_summary'), [__('messages.metric'), __('messages.value')], [
                [__('messages.sms_campaigns_sent'), $this->number($sentCampaigns)],
                [__('messages.sms_scheduled_campaigns'), $this->number($scheduledCampaigns)],
                [__('messages.sms_failed_count'), $this->number($failedCampaigns)],
                [__('messages.sms_status_delivered'), $this->number($deliveredLogs)],
                [__('messages.sms_status_failed'), $this->number($failedLogs)],
            ]),
        ];

        return $report;
    }

    private function smsWalletsQuery(User $user, UserDataScope $scope): Builder
    {
        return SmsWallet::query()
            ->when(! $scope->isChurchWide(), function (Builder $query) use ($user, $scope): void {
                $query->where(function (Builder $query) use ($user, $scope): void {
                    if ($scope->departmentIds() !== []) {
                        $query->where(function (Builder $query) use ($scope): void {
                            $query->where('owner_type', SmsWallet::OWNER_DEPARTMENT)
                                ->whereIn('department_id', $scope->departmentIds());
                        });
                    }

                    $method = $scope->departmentIds() !== [] ? 'orWhere' : 'where';
                    $query->{$method}(function (Builder $query) use ($user): void {
                        $query->where('owner_type', SmsWallet::OWNER_USER)
                            ->where('user_id', $user->id);
                    });
                });
            });
    }

    private function leadershipAssignmentsQuery(User $user, UserDataScope $scope): Builder
    {
        $query = MemberLeadershipAssignment::query()->where('is_active', true);

        if ($scope->isChurchWide()) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user, $scope): void {
            if ($scope->departmentIds() !== []) {
                $query->whereIn('department_id', $scope->departmentIds());
            }

            if ($scope->zoneIds() !== []) {
                $method = $scope->departmentIds() !== [] ? 'orWhereIn' : 'whereIn';
                $query->{$method}('zone_id', $scope->zoneIds());
            }

            if ($user->member?->id) {
                $method = $scope->departmentIds() !== [] || $scope->zoneIds() !== [] ? 'orWhere' : 'where';
                $query->{$method}('member_id', $user->member->id);
            }
        });
    }

    /**
     * @return array{label: string, value: string, note: string}
     */
    private function metric(string $label, string $value, string $note): array
    {
        return compact('label', 'value', 'note');
    }

    /**
     * @param  iterable<int, array{label: string, value: int|float, formatted?: string}>  $rows
     * @return array<int, array{label: string, value: int|float, formatted: string, percentage: int}>
     */
    private function chartRows(iterable $rows): array
    {
        $rows = collect($rows)
            ->map(fn (array $row): array => [
                'label' => $row['label'],
                'value' => $row['value'],
                'formatted' => $row['formatted'] ?? $this->number($row['value']),
            ])
            ->filter(fn (array $row): bool => (float) $row['value'] > 0)
            ->values();

        $max = max((float) $rows->max('value'), 1);

        return $rows
            ->map(fn (array $row): array => [
                ...$row,
                'percentage' => (int) max(round(((float) $row['value'] / $max) * 100), 6),
            ])
            ->all();
    }

    /**
     * @param  iterable<int, array<int, string>>  $rows
     * @return array{title: string, headers: array<int, string>, rows: array<int, array<int, string>>}
     */
    private function tableSection(string $title, array $headers, iterable $rows): array
    {
        return [
            'title' => $title,
            'headers' => $headers,
            'rows' => collect($rows)->take(12)->values()->all(),
        ];
    }

    private function contextLabel(?string $department, ?string $zone): string
    {
        if ($department) {
            return __('messages.department').': '.$department;
        }

        if ($zone) {
            return __('messages.zone').': '.$zone;
        }

        return __('messages.church');
    }

    private function genderLabel(?string $gender): string
    {
        return match ($gender) {
            'male' => __('messages.male'),
            'female' => __('messages.female'),
            default => __('messages.not_recorded'),
        };
    }

    private function number(int|float|string|null $value): string
    {
        return number_format((float) ($value ?? 0));
    }

    private function currency(int|float|string|null $value): string
    {
        return __('messages.currency_tzs').' '.number_format((float) ($value ?? 0), 2);
    }
}
