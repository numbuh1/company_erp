<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{
    protected $fillable = [
        'user_id',
        'salary',
        'salary_type',
        'allowance_adjustment',
        'allowance_bonus',
        'allowance_excl_tax',
        'parking_fee',
        'insurance',
        'personal_income_tax',
        'other_deduction',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ── Computed gross / net helpers ──────────────────────────────

    /**
     * Total allowances added on top of base salary.
     */
    public function getGrossAllowanceAttribute(): int
    {
        return (int) (($this->allowance_adjustment ?? 0)
            + ($this->allowance_bonus       ?? 0)
            + ($this->allowance_excl_tax    ?? 0));
    }

    /**
     * Total deductions subtracted from gross pay.
     */
    public function getTotalDeductionAttribute(): int
    {
        return (int) (($this->parking_fee         ?? 0)
            + ($this->insurance              ?? 0)
            + ($this->personal_income_tax    ?? 0)
            + ($this->other_deduction        ?? 0));
    }

    /**
     * Gross pay = base salary + allowances.
     */
    public function getGrossPayAttribute(): ?int
    {
        if (!$this->salary) return null;
        return (int) $this->salary + $this->gross_allowance;
    }

    /**
     * Net pay = gross pay - deductions.
     */
    public function getNetPayAttribute(): ?int
    {
        if (!$this->salary) return null;
        return $this->gross_pay - $this->total_deduction;
    }

    // ── Rate accessors (1 month = 4 weeks = 20 days = 160 hours) ─

    public function getHourlyRateAttribute(): ?float
    {
        if (!$this->salary || !$this->salary_type) return null;
        return match ($this->salary_type) {
            'monthly' => $this->salary / 160,
            'weekly'  => $this->salary / 40,
            'daily'   => $this->salary / 8,
            'hourly'  => (float) $this->salary,
            default   => null,
        };
    }

    public function getDailyRateAttribute(): ?float
    {
        if (!$this->salary || !$this->salary_type) return null;
        return match ($this->salary_type) {
            'monthly' => $this->salary / 20,
            'weekly'  => $this->salary / 5,
            'daily'   => (float) $this->salary,
            'hourly'  => $this->salary * 8,
            default   => null,
        };
    }

    public function getWeeklyRateAttribute(): ?float
    {
        if (!$this->salary || !$this->salary_type) return null;
        return match ($this->salary_type) {
            'monthly' => $this->salary / 4,
            'weekly'  => (float) $this->salary,
            'daily'   => $this->salary * 5,
            'hourly'  => $this->salary * 40,
            default   => null,
        };
    }

    public function getMonthlyRateAttribute(): ?float
    {
        if (!$this->salary || !$this->salary_type) return null;
        return match ($this->salary_type) {
            'monthly' => (float) $this->salary,
            'weekly'  => $this->salary * 4,
            'daily'   => $this->salary * 20,
            'hourly'  => $this->salary * 160,
            default   => null,
        };
    }
}
