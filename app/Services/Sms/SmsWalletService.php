<?php

namespace App\Services\Sms;

use App\Models\Department;
use App\Models\SmsTransaction;
use App\Models\SmsWallet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SmsWalletService
{
    public function ensureChurchWallet(): SmsWallet
    {
        return SmsWallet::query()->firstOrCreate([
            'owner_type' => SmsWallet::OWNER_CHURCH,
            'department_id' => null,
            'user_id' => null,
        ], [
            'name' => __('messages.church_sms_wallet'),
            'is_active' => true,
        ]);
    }

    public function ensureDepartmentWallet(Department $department): SmsWallet
    {
        return SmsWallet::query()->firstOrCreate([
            'owner_type' => SmsWallet::OWNER_DEPARTMENT,
            'department_id' => $department->id,
            'user_id' => null,
        ], [
            'name' => __('messages.department_sms_wallet', ['department' => $department->name]),
            'is_active' => true,
        ]);
    }

    public function ensureUserWallet(User $user): SmsWallet
    {
        return SmsWallet::query()->firstOrCreate([
            'owner_type' => SmsWallet::OWNER_USER,
            'department_id' => null,
            'user_id' => $user->id,
        ], [
            'name' => __('messages.user_sms_wallet', ['user' => $user->name]),
            'is_active' => true,
        ]);
    }

    public function addCredits(SmsWallet $wallet, int $credits, string $description, ?User $performedBy = null, string $type = SmsTransaction::TYPE_PURCHASE): SmsWallet
    {
        if ($credits <= 0) {
            throw new RuntimeException(__('messages.sms_invalid_credit_amount'));
        }

        return DB::transaction(function () use ($wallet, $credits, $description, $performedBy, $type): SmsWallet {
            $lockedWallet = SmsWallet::query()->lockForUpdate()->findOrFail($wallet->id);
            $balanceBefore = (int) $lockedWallet->balance;
            $balanceAfter = $balanceBefore + $credits;
            $creditsPurchased = $type === SmsTransaction::TYPE_PURCHASE
                ? (int) $lockedWallet->credits_purchased + $credits
                : (int) $lockedWallet->credits_purchased;

            $lockedWallet->update([
                'credits_purchased' => $creditsPurchased,
                'balance' => $balanceAfter,
            ]);

            SmsTransaction::create([
                'sms_wallet_id' => $lockedWallet->id,
                'transaction_type' => $type,
                'credits_in' => $credits,
                'credits_out' => 0,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => $description,
                'performed_by_user_id' => $performedBy?->id,
            ]);

            return $lockedWallet->fresh();
        });
    }

    public function deductCredits(SmsWallet $wallet, int $credits, string $description, ?User $performedBy = null, string $type = SmsTransaction::TYPE_USAGE): SmsWallet
    {
        if ($credits <= 0) {
            throw new RuntimeException(__('messages.sms_invalid_credit_amount'));
        }

        return DB::transaction(function () use ($wallet, $credits, $description, $performedBy, $type): SmsWallet {
            $lockedWallet = SmsWallet::query()->lockForUpdate()->findOrFail($wallet->id);
            $balanceBefore = (int) $lockedWallet->balance;

            if ($balanceBefore < $credits) {
                throw new RuntimeException(__('messages.sms_insufficient_balance'));
            }

            $balanceAfter = $balanceBefore - $credits;
            $creditsUsed = $type === SmsTransaction::TYPE_USAGE
                ? (int) $lockedWallet->credits_used + $credits
                : (int) $lockedWallet->credits_used;

            if ($balanceAfter < 0) {
                throw new RuntimeException(__('messages.sms_negative_balance_blocked'));
            }

            $lockedWallet->update([
                'credits_used' => $creditsUsed,
                'balance' => $balanceAfter,
            ]);

            SmsTransaction::create([
                'sms_wallet_id' => $lockedWallet->id,
                'transaction_type' => $type,
                'credits_in' => 0,
                'credits_out' => $credits,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => $description,
                'performed_by_user_id' => $performedBy?->id,
            ]);

            return $lockedWallet->fresh();
        });
    }
}
