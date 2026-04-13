@php
    /**
     * Calendar cell colour constants.
     * Edit here to apply across: Calendar, Weekly Timesheet, Monthly Timesheet.
     * These are full Tailwind class strings — Tailwind scans this file at build time.
     */

    // Day cell backgrounds
    $calWeekendBg = 'bg-gray-200 dark:bg-gray-900/50';
    $calHolidayBg = 'bg-yellow-50 dark:bg-yellow-900/15';
    $calOutsideBg = 'bg-gray-50 dark:bg-gray-900/40';   // out-of-month in calendar

    // Weekly timesheet header <th> — includes both bg + text colour
    $calWeekendHeaderCls = 'bg-gray-200 dark:bg-gray-900/50 text-gray-400 dark:text-gray-500';
    $calHolidayHeaderCls = 'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-600 dark:text-yellow-500';
@endphp
