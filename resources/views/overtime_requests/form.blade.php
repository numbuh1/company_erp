<x-app-layout>
    @php $readonly = $readonly ?? false; @endphp
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $readonly ? 'Yêu cầu Tăng ca' : (isset($ot) ? 'Chỉnh sửa Yêu cầu Tăng ca' : 'Tạo Yêu cầu Tăng ca') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <form method="POST"
                    action="{{ isset($ot) ? route('overtime-requests.update', $ot) : route('overtime-requests.store') }}">
                    @csrf
                    @if(isset($ot)) @method('PUT') @endif

                    @if($errors->any())
                        <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded text-sm text-red-700 dark:text-red-300">
                            <ul class="list-disc list-inside space-y-1">
                                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- ── User ──────────────────────────────────────────────────── --}}
                    @if(isset($ot))
                        <div class="mb-4">
                            <x-input-label value="Người dùng" />
                            <input type="text" value="{{ $ot->user->name }}"
                                class="w-full border rounded p-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300" disabled>
                            <input type="hidden" name="user_id" value="{{ $ot->user_id }}">
                        </div>
                    @else
                        @canany(['edit team ot', 'edit all ot'])
                            <div class="mb-4">
                                <x-input-label value="Người dùng" />
                                <select id="ot-user-select" name="user_id">
                                    <option value="">— Chọn người dùng —</option>
                                    @foreach($users as $u)
                                        <option value="{{ $u->id }}"
                                            @selected(old('user_id', auth()->id()) == $u->id)>
                                            {{ $u->name }}{{ $u->position ? ' · ' . $u->position : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endcanany
                    @endif

                    {{-- ── OT Date ────────────────────────────────────────────────── --}}
                    <div class="mb-4">
                        <x-input-label for="ot_date" value="Ngày tăng ca" />
                        @if($readonly)
                            <p class="mt-1 text-sm text-gray-700 dark:text-gray-300 py-1">
                                {{ $ot->start_at->format('d/m/Y') }}
                                <span class="ml-2 text-xs text-gray-400 dark:text-gray-500">
                                    ({{ $ot->start_at->translatedFormat('l') }})
                                </span>
                            </p>
                        @else
                            <input type="date" name="ot_date" id="ot_date"
                                value="{{ old('ot_date', isset($ot) ? $ot->start_at->format('Y-m-d') : '') }}"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm px-2 py-2 cursor-pointer">
                            @error('ot_date')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        @endif
                    </div>

                    {{-- ── Start / End Time ───────────────────────────────────────── --}}
                    @if($readonly)
                        <div class="mb-4">
                            <x-input-label value="Thời gian" />
                            <p class="mt-1 text-sm text-gray-700 dark:text-gray-300 py-1">
                                {{ $ot->start_at->format('H:i') }} – {{ $ot->end_at->format('H:i') }}
                            </p>
                        </div>
                    @else
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <x-input-label for="start_time" value="Giờ bắt đầu" />
                                <input type="time" name="start_time" id="start_time"
                                    value="{{ old('start_time', isset($ot) ? $ot->start_at->format('H:i') : '') }}"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm px-2 py-2">
                                @error('start_time')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <x-input-label for="end_time" value="Giờ kết thúc" />
                                <input type="time" name="end_time" id="end_time"
                                    value="{{ old('end_time', isset($ot) ? $ot->end_at->format('H:i') : '') }}"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm px-2 py-2">
                                @error('end_time')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    @endif

                    {{-- ── OT Type (auto-selected) ────────────────────────────────── --}}
                    <div class="mb-4">
                        <x-input-label value="Loại tăng ca" />
                        @if($readonly)
                            @php
                                $typeCls = match($ot->type) {
                                    'OT x3' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                    'OT x2' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                                    default  => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                };
                            @endphp
                            <span class="mt-1 inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold {{ $typeCls }}">
                                {{ $ot->type }}
                            </span>
                        @else
                            @php
                                $currentType = old('type', $ot->type ?? 'OT x1.5');
                                $typeCls = match($currentType) {
                                    'OT x3' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                    'OT x2' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                                    default  => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                };
                            @endphp
                            <div class="mt-1 flex items-center gap-3">
                                <span id="ot_type_display"
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold {{ $typeCls }}">
                                    {{ $currentType }}
                                </span>
                                <span class="text-xs text-gray-400 dark:text-gray-500">Tự động xác định theo ngày</span>
                            </div>
                            <input type="hidden" name="type" id="ot_type_hidden" value="{{ $currentType }}">
                        @endif
                    </div>

                    {{-- ── Hours ──────────────────────────────────────────────────── --}}
                    <div class="mb-4">
                        <x-input-label value="Số giờ" />
                        @if($readonly)
                            <p class="mt-1 text-sm text-gray-700 dark:text-gray-300 py-1">{{ $ot->hours }}h</p>
                        @else
                            <input type="number" step="0.25" min="0.25" id="hours" name="hours"
                                value="{{ old('hours', $ot->hours ?? '') }}"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm px-2 py-2">
                            @error('hours')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        @endif
                    </div>

                    {{-- ── Project & Task ─────────────────────────────────────────── --}}
                    @if($readonly)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <x-input-label value="Dự án" />
                                <p class="mt-1 text-sm text-gray-700 dark:text-gray-300 py-1">{{ $ot->project?->name ?? '—' }}</p>
                            </div>
                            <div>
                                <x-input-label value="Công việc" />
                                <p class="mt-1 text-sm text-gray-700 dark:text-gray-300 py-1">{{ $ot->task?->name ?? '—' }}</p>
                            </div>
                        </div>
                    @else
                        @php
                            $selProject = old('project_id', $ot->project_id ?? '');
                            $selTask    = old('task_id',    $ot->task_id    ?? '');
                        @endphp
                        <div class="mb-4">
                            <x-input-label for="ot_project_id" value="Dự án" />
                            <select id="ot_project_id" name="project_id"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                                <option value="">— Không có —</option>
                                @foreach($projects as $p)
                                    <option value="{{ $p->id }}" {{ $selProject == $p->id ? 'selected' : '' }}>
                                        {{ $p->project_code }} · {{ $p->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <x-input-label for="ot_task_id" value="Công việc" />
                            <select id="ot_task_id" name="task_id"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                                <option value="">— Không có —</option>
                                @foreach($tasks as $t)
                                    <option value="{{ $t->id }}"
                                        data-project="{{ $t->project_id ?? '' }}"
                                        {{ $selTask == $t->id ? 'selected' : '' }}>
                                        {{ $t->task_code }} · {{ $t->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- ── Description ────────────────────────────────────────────── --}}
                    <div class="mb-4">
                        <x-input-label value="Lý do" />
                        @if($readonly)
                            <p class="mt-1 text-sm text-gray-700 dark:text-gray-300 py-1 whitespace-pre-wrap">{{ $ot->description ?? '—' }}</p>
                        @else
                            <textarea name="description"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">{{ old('description', $ot->description ?? '') }}</textarea>
                        @endif
                    </div>

                    {{-- ── Buttons ─────────────────────────────────────────────────── --}}
                    <div class="flex justify-end mt-6 space-x-2">
                        @if(!$readonly)
                            <x-primary-button>{{ isset($ot) ? 'Lưu' : 'Tạo' }}</x-primary-button>
                        @endif

                        @if($readonly)
                            @if(!in_array($ot->status, ['approved', 'rejected']))
                                @php
                                    $canEdit = auth()->user()->can('edit all ot')
                                        || auth()->user()->can('edit team ot')
                                        || (auth()->user()->can('edit own ot') && $ot->user_id === auth()->id());
                                @endphp
                                @if($canEdit)
                                    <a href="{{ route('overtime-requests.edit', $ot) }}">
                                        <x-secondary-button>Chỉnh sửa</x-secondary-button>
                                    </a>
                                @endif
                            @endif

                            @canany(['approve team ot', 'approve all ot'])
                                @if($ot->status === 'pending')
                                    <form method="POST" action="{{ route('overtime-requests.approve', $ot) }}" class="inline">
                                        @csrf
                                        <x-primary-button>Phê duyệt</x-primary-button>
                                    </form>
                                    <x-danger-button onclick="openRejectModal('{{ route('overtime-requests.reject', $ot->id) }}')">
                                        Từ chối
                                    </x-danger-button>
                                @endif
                            @endcanany
                        @endif

                        <a href="{{ route('requests.index', ['type' => 'ot']) }}">
                            <x-secondary-button>{{ $readonly ? 'Quay lại' : 'Bỏ' }}</x-secondary-button>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if($readonly)
        @include('overtime_requests._partials.reject_modal')
    @endif

    @if(!$readonly)
        {{-- Holiday dates must be set before form.js runs --}}
        @push('scripts')
        <script>window._otHolidayDates = @json($holidayDates);</script>
        @endpush
        @push('scripts')
            @vite('resources/js/overtime_requests/form.js')
        @endpush

        @php
            $taskJson = $tasks->map(fn($t) => [
                'value'     => (string) $t->id,
                'text'      => $t->task_code . ' · ' . $t->name,
                'projectId' => $t->project_id ? (string) $t->project_id : '',
            ])->values();
        @endphp
        @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const userEl = document.getElementById('ot-user-select');
            if (userEl) new TomSelect(userEl, { allowEmptyOption: true, maxOptions: 300 });

            const allTasks   = @json($taskJson);
            const selTask    = '{{ $selTask ?? '' }}';
            const selProject = '{{ $selProject ?? '' }}';

            let projectTs;
            let taskTs = new TomSelect('#ot_task_id', {
                maxOptions: null,
                allowEmptyOption: true,
                onChange: function (val) {
                    if (!val || !projectTs) return;
                    const task = allTasks.find(t => t.value === String(val));
                    if (task && task.projectId) projectTs.setValue(task.projectId, true);
                },
            });

            projectTs = new TomSelect('#ot_project_id', {
                maxOptions: null,
                allowEmptyOption: true,
                onChange: function (val) {
                    const filtered = val ? allTasks.filter(t => t.projectId === String(val)) : allTasks;
                    taskTs.clear(true);
                    taskTs.clearOptions();
                    taskTs.addOption({ value: '', text: '— Không có —' });
                    filtered.forEach(t => taskTs.addOption({ value: t.value, text: t.text }));
                    taskTs.refreshOptions(false);
                    if (selTask) {
                        const stillVisible = filtered.find(t => t.value === selTask);
                        if (stillVisible) taskTs.setValue(selTask, true);
                    }
                },
            });
        });
        </script>
        @endpush
    @endif
</x-app-layout>
