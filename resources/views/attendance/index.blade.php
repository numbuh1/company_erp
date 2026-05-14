<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Chấm công — {{ now()->locale('vi')->translatedFormat('l, d F Y') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto space-y-6">

            {{-- Flash messages --}}
            @if(session('success'))
                <div class="p-3 bg-green-100 text-green-800 rounded text-sm">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="p-3 bg-red-100 text-red-800 rounded text-sm">{{ session('error') }}</div>
            @endif

            {{-- ── Personal Check-In Section ────────────────────────────── --}}
            <div x-data="{ showWfhModal: false, hours: 8, reason: '', submitting: false }">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wide mb-4">
                    Chấm công hôm nay
                </h3>

                @if($myOnLeaveToday && !$myAttendance)
                    {{-- On approved leave --}}
                    <div class="flex items-center gap-3 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg">
                        <span class="text-2xl">🏖️</span>
                        <div>
                            <p class="font-semibold text-yellow-800 dark:text-yellow-300">Bạn đã được duyệt nghỉ phép hôm nay.</p>
                            <p class="text-sm text-yellow-600 dark:text-yellow-400">Không cần chấm công.</p>
                        </div>
                    </div>

                @elseif($myAttendance)
                    {{-- Already checked in --}}
                    @php
                        $isOnSite  = $myAttendance->type === 'on_site';
                        $isWfh     = $myAttendance->type === 'wfh';
                        $isPending = $myAttendance->status === 'pending';
                        $isApproved= $myAttendance->status === 'approved';
                        $isRejected= $myAttendance->status === 'rejected';
                    @endphp
                    <div class="flex items-center gap-4 p-4 rounded-lg border
                        {{ $isApproved && $isOnSite ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-700' : '' }}
                        {{ $isApproved && $isWfh   ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-700' : '' }}
                        {{ $isPending              ? 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-700' : '' }}
                        {{ $isRejected             ? 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-700' : '' }}">
                        <span class="text-3xl">
                            {{ $isOnSite ? '🏢' : '🏠' }}
                        </span>
                        <div class="flex-1">
                            <p class="font-semibold text-gray-800 dark:text-gray-100">
                                {{ $isOnSite ? 'On Site' : 'Working from Home' }}
                                @if($myAttendance->hours)
                                    <span class="text-sm font-normal text-gray-500 ml-1">({{ $myAttendance->hours }}h)</span>
                                @endif
                            </p>
                            @if($myAttendance->reason)
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $myAttendance->reason }}</p>
                            @endif
                            <div class="mt-1">
                                @if($isPending)
                                    <span class="text-xs font-medium px-2 py-0.5 rounded bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                                        Chờ phê duyệt
                                    </span>
                                @elseif($isApproved)
                                    <span class="text-xs font-medium px-2 py-0.5 rounded bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                        Đã duyệt
                                    </span>
                                @elseif($isRejected)
                                    <span class="text-xs font-medium px-2 py-0.5 rounded bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
                                        Đã từ chối
                                    </span>
                                    @if($myAttendance->reject_reason)
                                        <span class="text-xs text-red-500 ml-1">— {{ $myAttendance->reject_reason }}</span>
                                    @endif
                                @endif
                            </div>
                        </div>
                        <div class="text-xs text-gray-400">
                            {{ $myAttendance->created_at->format('H:i') }}
                        </div>
                    </div>

                @else
                    {{-- Not yet checked in --}}
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Bạn chưa chấm công hôm nay. Chọn hình thức làm việc:</p>

                    @error('attendance')
                        <div class="mb-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg text-sm text-red-700 dark:text-red-300">
                            {{ $message }}
                        </div>
                    @enderror

                    <div class="flex flex-wrap gap-3">
                        {{-- On Site button --}}
                        <form method="POST" action="{{ route('attendance.store') }}">
                            @csrf
                            <input type="hidden" name="type" value="on_site">
                            <button type="submit"
                                class="flex items-center gap-2 px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl shadow transition">
                                <span class="text-xl">🏢</span>
                                <span>Tại văn phòng</span>
                            </button>
                        </form>


                        {{-- WFH button --}}
                        <button type="button" @click="showWfhModal = true"
                            class="flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl shadow transition">
                            <span class="text-xl">🏠</span>
                            Làm việc tại nhà
                        </button>

                        {{-- Geolocation error --}}
                        <div id="geoErrorBox"
                             class="hidden w-full mt-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg text-sm text-red-700 dark:text-red-300">
                        </div>
                    </div>
                @endif

                {{-- WFH Modal --}}
                <div x-show="showWfhModal" x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                     @click.self="showWfhModal = false">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 w-full max-w-md mx-4">
                        <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100 mb-4">
                            🏠 Work from Home Request
                        </h3>

                        <form method="POST" action="{{ route('attendance.store') }}" @submit="submitting = true">
                            @csrf
                            <input type="hidden" name="type" value="wfh">

                            <div class="mb-4">
                                <x-input-label value="Số giờ làm hôm nay" />
                                <x-text-input type="number" name="hours" step="0.5" min="0.5" max="24"
                                    x-model="hours" class="w-full mt-1" />
                                @error('hours')
                                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mb-5">
                                <x-input-label value="Lý do / Nhiệm vụ hôm nay" />
                                <textarea name="reason" rows="3" x-model="reason"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Mô tả ngắn gọn công việc bạn sẽ làm..."></textarea>
                                @error('reason')
                                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex justify-end gap-2">
                                <button type="button" @click="showWfhModal = false"
                                    class="px-4 py-2 text-sm rounded border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                    Hủy
                                </button>
                                <button type="submit" :disabled="submitting"
                                    class="px-4 py-2 text-sm rounded bg-blue-600 hover:bg-blue-700 text-white font-medium transition disabled:opacity-50">
                                    <span x-show="!submitting">Gửi yêu cầu làm tại nhà</span>
                                    <span x-show="submitting" x-cloak>Đang gửi…</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- ── Stats Dashboard (module attendance only) ─────────────── --}}
            @if($canSeeStats)
                <div x-data="{ filter: 'all' }">

                    {{-- Stat Cards --}}
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-4">

                        @php
                            $cards = [
                                ['key' => 'all',            'label' => 'Tất cả',          'icon' => '👥', 'color' => 'gray'],
                                ['key' => 'on_site',        'label' => 'On Site',      'icon' => '🏢', 'color' => 'green'],
                                ['key' => 'wfh',            'label' => 'WFH',          'icon' => '🏠', 'color' => 'blue'],
                                ['key' => 'on_leave',       'label' => 'Nghỉ phép',     'icon' => '🏖️', 'color' => 'yellow'],
                                ['key' => 'wfh_pending',    'label' => 'Chờ duyệt WFH',  'icon' => '⏳', 'color' => 'orange'],
                                ['key' => 'not_checked_in', 'label' => 'Chưa chấm công',  'icon' => '❓', 'color' => 'red'],
                            ];
                            $colorBase = [
                                'gray'   => ['card' => 'bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600',       'active' => 'ring-2 ring-gray-400',   'num' => 'text-gray-700 dark:text-gray-200'],
                                'green'  => ['card' => 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-700','active' => 'ring-2 ring-green-500',  'num' => 'text-green-600 dark:text-green-400'],
                                'blue'   => ['card' => 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-700',    'active' => 'ring-2 ring-blue-500',   'num' => 'text-blue-600 dark:text-blue-400'],
                                'yellow' => ['card' => 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-700','active'=>'ring-2 ring-yellow-500','num'=> 'text-yellow-600 dark:text-yellow-400'],
                                'orange' => ['card' => 'bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-700','active'=>'ring-2 ring-orange-500','num'=> 'text-orange-600 dark:text-orange-400'],
                                'red'    => ['card' => 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-700',        'active' => 'ring-2 ring-red-500',    'num' => 'text-red-600 dark:text-red-400'],
                            ];
                        @endphp

                        @foreach($cards as $card)
                            @php $c = $colorBase[$card['color']]; @endphp
                            <button @click="filter = '{{ $card['key'] }}'"
                                :class="filter === '{{ $card['key'] }}' ? '{{ $c['active'] }}' : ''"
                                class="flex flex-col items-center justify-center p-3 rounded-lg border {{ $c['card'] }} cursor-pointer hover:opacity-80 transition text-center">
                                <span class="text-xl mb-1">{{ $card['icon'] }}</span>
                                <span class="text-2xl font-bold {{ $c['num'] }}">{{ $counts[$card['key']] }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $card['label'] }}</span>
                            </button>
                        @endforeach
                    </div>

                    {{-- User List --}}
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wide">
                                Nhân sự
                            </h3>
                            <span class="text-xs text-gray-400">
                                <span x-text="
                                    @js($attendanceUsers->values())
                                        .filter(u => '{{ '' }}' + (document.querySelector ? '' : '') || true)
                                        .length
                                "></span>
                            </span>
                        </div>

                        @php $usersJson = $attendanceUsers->values(); @endphp

                        <div x-data="{ allUsers: @js($usersJson), get shown() { return this.filter === 'all' ? this.allUsers : this.allUsers.filter(u => u.category === this.filter) } }"
                             class="divide-y divide-gray-100 dark:divide-gray-700">

                            <template x-if="shown.length === 0">
                                <div class="px-5 py-8 text-center text-sm text-gray-400">
                                    No users in this category today.
                                </div>
                            </template>

                            <template x-for="u in shown" :key="u.id">
                                <div class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition">

                                    {{-- Avatar --}}
                                    <template x-if="u.profile_picture">
                                        <img :src="'/storage/profile_pictures/' + u.profile_picture"
                                             class="w-8 h-8 rounded-full object-cover border border-gray-200 dark:border-gray-600 shrink-0">
                                    </template>
                                    <template x-if="!u.profile_picture">
                                        <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center text-indigo-600 dark:text-indigo-300 text-sm font-bold shrink-0"
                                             x-text="u.name.charAt(0).toUpperCase()">
                                        </div>
                                    </template>

                                    {{-- Name & Position --}}
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate" x-text="u.name"></p>
                                        <p class="text-xs text-gray-400 truncate" x-text="u.position || ''"></p>
                                    </div>

                                    {{-- Status badge --}}
                                    <div class="shrink-0 flex items-center gap-2">
                                        <span x-text="{ on_site: '🏢 On Site', wfh: '🏠 WFH', on_leave: '🏖️ On Leave', wfh_pending: '⏳ WFH Pending', not_checked_in: '❓ Not In' }[u.category]"
                                              :class="{
                                                  'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300':  u.category === 'on_site',
                                                  'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300':    u.category === 'wfh',
                                                  'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300': u.category === 'on_leave',
                                                  'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300': u.category === 'wfh_pending',
                                                  'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400':    u.category === 'not_checked_in',
                                              }"
                                              class="text-xs font-medium px-2 py-0.5 rounded">
                                        </span>

                                        {{-- Approve / Reject for pending WFH --}}
                                        <template x-if="u.category === 'wfh_pending' && u.can_approve">
                                            <div class="flex gap-1 ml-1">
                                                <form :action="'/attendance/' + u.att_id + '/approve'" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit"
                                                        class="text-xs px-2 py-0.5 rounded bg-green-600 hover:bg-green-700 text-white transition">
                                                        Phê duyệt
                                                    </button>
                                                </form>

                                                <div x-data="{ showReject: false, reason: '' }">
                                                    <button type="button" @click="showReject = !showReject"
                                                        class="text-xs px-2 py-0.5 rounded bg-red-600 hover:bg-red-700 text-white transition">
                                                        Từ chối
                                                    </button>
                                                    <div x-show="showReject" x-cloak
                                                         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                                                         @click.self="showReject = false">
                                                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 w-full max-w-sm mx-4">
                                                            <h4 class="font-semibold text-gray-800 dark:text-gray-100 mb-3">Từ chối yêu cầu làm tại nhà</h4>
                                                            <form :action="'/attendance/' + u.att_id + '/reject'" method="POST">
                                                                @csrf
                                                                <textarea name="reject_reason" rows="3" x-model="reason" required
                                                                    class="block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm mb-3"
                                                                    placeholder="Reason for rejection..."></textarea>
                                                                <div class="flex justify-end gap-2">
                                                                    <button type="button" @click="showReject = false"
                                                                        class="px-3 py-1.5 text-sm rounded border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300">
                                                                        Hủy
                                                                    </button>
                                                                    <button type="submit"
                                                                        class="px-3 py-1.5 text-sm rounded bg-red-600 hover:bg-red-700 text-white">
                                                                        Xác nhận từ chối
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
    @push('scripts')
        @vite('resources/js/attendance/check-in.js')
    @endpush
</x-app-layout>
