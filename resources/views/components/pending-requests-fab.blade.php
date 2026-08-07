@canany(['approve team leaves', 'approve all leaves', 'approve team ot', 'approve all ot'])
<div
    x-data="{
        open: false,
        activeTab: 'all',
        items: null,
        loading: false,
        rejectingId: null,
        rejectReason: '',

        get leaves() { return this.items ? this.items.leaves : []; },
        get ots()    { return this.items ? this.items.ots : []; },
        get all()    { return [...this.leaves, ...this.ots]; },

        total() { return this.items ? (this.items.leaves.length + this.items.ots.length) : 0; },
        tabCount(tab) {
            if (!this.items) return '';
            if (tab === 'all')   return this.leaves.length + this.ots.length;
            if (tab === 'leave') return this.leaves.length;
            if (tab === 'ot')    return this.ots.length;
            return 0;
        },
        currentItems() {
            if (this.activeTab === 'leave') return this.leaves;
            if (this.activeTab === 'ot')    return this.ots;
            return this.all;
        },

        toggle() { this.open = !this.open; if (this.open && this.items === null) this.load(); },

        load() {
            this.loading = true;
            const csrf = document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '';
            fetch('{{ url("pending-approvals") }}', { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf } })
                .then(r => r.json())
                .then(d => { this.items = d; this.loading = false; })
                .catch(() => { this.loading = false; });
        },

        approve(item) {
            const csrf = document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '';
            fetch(item.approve_url, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf } })
                .then(r => r.json())
                .then(d => { if (d.success) { this.items = null; this.load(); } });
        },

        showReject(id) { this.rejectingId = id; this.rejectReason = ''; },
        cancelReject() { this.rejectingId = null; this.rejectReason = ''; },

        confirmReject(item) {
            if (!this.rejectReason.trim()) return;
            const csrf = document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '';
            fetch(item.reject_url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ reject_reason: this.rejectReason })
            })
            .then(r => r.json())
            .then(d => { if (d.success) { this.rejectingId = null; this.items = null; this.load(); } });
        },

        openItem(item) {
            if (item.type_key === 'leave') window.openLeaveModal && openLeaveModal(item.id);
            else window.openOtModal && openOtModal(item.id);
        }
    }"
    x-init="load()"
    class="fixed bottom-6 left-6 z-40 flex flex-col items-start"
>
    {{-- Expandable panel --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-2"
        class="mb-3 w-80 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col"
        style="max-height: 480px;"
        x-cloak
    >
        {{-- Panel header --}}
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-700">
            <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Chờ phê duyệt</h4>
            <button @click="load()" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">Làm mới</button>
        </div>

        {{-- Tabs --}}
        <div class="flex border-b border-gray-100 dark:border-gray-700">
            <template x-for="tab in [{key:'all',label:'Tất cả'},{key:'leave',label:'Nghỉ phép'},{key:'ot',label:'Tăng ca'}]" :key="tab.key">
                <button
                    @click="activeTab = tab.key"
                    :class="activeTab === tab.key
                        ? 'border-b-2 border-indigo-500 text-indigo-600 dark:text-indigo-400'
                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                    class="flex-1 text-xs font-medium py-2 px-1 transition"
                >
                    <span x-text="tab.label"></span>
                    <span x-show="items !== null" class="ml-1 text-gray-400" x-text="'(' + tabCount(tab.key) + ')'"></span>
                </button>
            </template>
        </div>

        {{-- Loading --}}
        <div x-show="loading" class="py-8 text-center text-sm text-gray-400">Đang tải…</div>

        {{-- Empty --}}
        <div x-show="!loading && items !== null && currentItems().length === 0" class="py-8 text-center text-sm text-gray-400">
            Không có yêu cầu đang chờ.
        </div>

        {{-- Items --}}
        <div x-show="!loading" class="overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700">
            <template x-for="item in currentItems()" :key="item.type_key + '-' + item.id">
                <div class="px-4 py-3">
                    {{-- User row --}}
                    <div class="flex items-start gap-2.5 cursor-pointer hover:opacity-80 transition" @click="openItem(item)">
                        {{-- Avatar --}}
                        <div class="shrink-0 mt-0.5">
                            <template x-if="item.user.avatar">
                                <img :src="item.user.avatar" class="w-8 h-8 rounded-full object-cover ring-2 ring-white dark:ring-gray-800">
                            </template>
                            <template x-if="!item.user.avatar">
                                <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center ring-2 ring-white dark:ring-gray-800">
                                    <span class="text-indigo-600 dark:text-indigo-400 text-xs font-bold" x-text="item.user.initials"></span>
                                </div>
                            </template>
                        </div>
                        {{-- Info --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100" x-text="item.user.name"></span>
                                <template x-if="item.type_key === 'leave'">
                                    <span class="text-xs px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300" x-text="item.leave_label"></span>
                                </template>
                                <template x-if="item.type_key === 'ot'">
                                    <span class="text-xs px-1.5 py-0.5 rounded bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300" x-text="item.ot_type"></span>
                                </template>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-text="item.user.position"></p>
                            <p class="text-xs text-gray-600 dark:text-gray-300 mt-0.5" x-text="item.start_at_text + ' → ' + item.end_at_text"></p>
                            <span class="inline-block mt-1 text-xs font-bold px-1.5 py-0.5 rounded"
                                :class="item.type_key === 'ot' ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'"
                                x-text="item.hours + 'h'"></span>
                            <p x-show="item.description" class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 truncate" x-text="item.description"></p>
                        </div>
                    </div>

                    {{-- Reject inline --}}
                    <div x-show="rejectingId === item.id" class="mt-2 space-y-1.5">
                        <textarea x-model="rejectReason" rows="2" placeholder="Lý do từ chối…"
                            class="w-full text-xs border border-red-300 dark:border-red-600 dark:bg-gray-900 dark:text-gray-300 rounded-md px-2 py-1.5"></textarea>
                        <div class="flex gap-1.5 justify-end">
                            <button @click="cancelReject()" class="px-2.5 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">Hủy</button>
                            <button @click="confirmReject(item)" class="px-2.5 py-1 text-xs bg-red-600 hover:bg-red-700 text-white rounded transition">Xác nhận</button>
                        </div>
                    </div>

                    {{-- Action buttons --}}
                    <div x-show="rejectingId !== item.id" class="flex gap-1.5 mt-2 justify-end">
                        <button @click.stop="approve(item)"
                            class="px-2.5 py-1 text-xs bg-green-600 hover:bg-green-700 text-white rounded transition flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Duyệt
                        </button>
                        <button @click.stop="showReject(item.id)"
                            class="px-2.5 py-1 text-xs bg-red-600 hover:bg-red-700 text-white rounded transition flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            Từ chối
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- FAB button --}}
    <button
        @click="toggle()"
        class="relative flex items-center justify-center w-14 h-14 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full shadow-lg transition"
        title="Yêu cầu chờ phê duyệt"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
        </svg>
        {{-- Badge --}}
        <span
            x-show="items !== null && total() > 0"
            x-text="total()"
            class="absolute -top-1 -right-1 flex items-center justify-center min-w-[20px] h-5 px-1 text-xs font-bold bg-red-500 text-white rounded-full"
            x-cloak
        ></span>
    </button>
</div>
@endcanany
