<?php

namespace App\Console\Commands;

use App\Models\AppSetting;
use App\Models\LeaveBalanceLog;
use App\Models\User;
use Illuminate\Console\Command;

class ProcessMonthlyLeavePolicy extends Command
{
    protected $signature = 'leave:monthly-policy';

    protected $description = 'Reset leave balances (in the configured reset month) and apply the monthly accrual to all users.';

    public function handle(): int
    {
        $resetMonth = (int) AppSetting::get('leave_balance_reset_month', 0);
        $increase   = (float) AppSetting::get('leave_balance_monthly_increase', 0);
        $isResetMonth = $resetMonth > 0 && now()->month === $resetMonth;

        if (!$isResetMonth && $increase <= 0) {
            $this->info('Nothing to do: no reset this month and no monthly increase configured.');
            return self::SUCCESS;
        }

        $count = 0;

        User::query()->chunkById(100, function ($users) use ($isResetMonth, $increase, &$count) {
            foreach ($users as $user) {
                if ($isResetMonth && (float) $user->leave_balance !== 0.0) {
                    $old = (float) $user->leave_balance;
                    $user->update(['leave_balance' => 0]);
                    LeaveBalanceLog::create([
                        'user_id'       => $user->id,
                        'changed_by'    => null,
                        'change_hours'  => -$old,
                        'balance_after' => 0,
                        'reason'        => 'Đặt lại số dư phép đầu kỳ (tự động)',
                    ]);
                }

                if ($increase > 0) {
                    $old = (float) $user->leave_balance;
                    $new = $old + $increase;
                    $user->update(['leave_balance' => $new]);
                    LeaveBalanceLog::create([
                        'user_id'       => $user->id,
                        'changed_by'    => null,
                        'change_hours'  => $increase,
                        'balance_after' => $new,
                        'reason'        => 'Tăng số dư phép hàng tháng (tự động)',
                    ]);
                }

                $count++;
            }
        });

        $this->info("Processed leave policy for {$count} user(s)." . ($isResetMonth ? ' (reset applied)' : ''));

        return self::SUCCESS;
    }
}
