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

                    {{-- User --}}
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
                                <select name="user_id" class="w-full border rounded p-2">
                                    @foreach($users as $u)
                                        <option value="{{ $u->id }}" @selected(old('user_id') == $u->id)>{{ $u->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endcanany
                    @endif

                    {{-- Type --}}
                    <div class="mb-4">
                        <x-input-label value="Loại" />
                        <select name="type" class="w-full border rounded p-2" @disabled($readonly)>
                            @foreach(['OT x1.5', 'OT x2', 'OT x3'] as $type)
                                <option value="{{ $type }}" @selected(old('type', $ot->type ?? '') == $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Project & Task --}}
                    @if($readonly)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <x-input-label value="Dự án" />
                                <p class="mt-1 text-sm text-gray-700 dark:text-gray-300 py-1">{{ isset($ot) ? ($ot->project?->name ?? '—') : '—' }}</p>
                            </div>
                            <div>
                                <x-input-label value="Công việc" />
                                <p class="mt-1 text-sm text-gray-700 dark:text-gray-300 py-1">{{ isset($ot) ? ($ot->task?->name ?? '—') : '—' }}</p>
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
                                    <option value="{{ $p->id }}" {{ $selProject == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
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
                                        {{ $selTask == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- Start --}}
                    <div class="mb-4">
                        <x-input-label value="Giờ bắt đầu" />
                        <input type="datetime-local" name="start_at" id="start_at"
                            value="{{ old('start_at', isset($ot) ? $ot->start_at->format('Y-m-d\TH:i') : '') }}"
                            class="w-full border rounded p-2" @disabled($readonly)>
                    </div>

                    {{-- End --}}
                    <div class="mb-4">
                        <x-input-label value="Giờ kết thúc" />
                        <input type="datetime-local" name="end_at" id="end_at"
                            value="{{ old('end_at', isset($ot) ? $ot->end_at->format('Y-m-d\TH:i') : '') }}"
                            class="w-full border rounded p-2" @disabled($readonly)>
                    </div>

                    {{-- Hours --}}
                    <div class="mb-4">
                        <x-input-label value="Giờ" />
                        <input type="number" step="0.5" id="hours" name="hours"
                            value="{{ old('hours', $ot->hours ?? '') }}"
                            class="w-full border rounded p-2" @disabled($readonly)>
                    </div>

                    {{-- Description --}}
                    <div class="mb-4">
                        <x-input-label value="Lý do" />
                        <textarea name="description" class="w-full border rounded p-2" @disabled($readonly)>{{ old('description', $ot->description ?? '') }}</textarea>
                    </div>

                    {{-- Buttons --}}
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

    @push('scripts')
        @vite('resources/js/overtime_requests/form.js')
    @endpush

    @if(!$readonly)
    @push('scripts')
    @php
        $taskJson = $tasks->map(fn($t) => [
            'value'     => (string) $t->id,
            'text'      => $t->name,
            'projectId' => $t->project_id ? (string) $t->project_id : '',
        ])->values();
    @endphp
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const allTasks = @json($taskJson);

        const selTask    = '{{ $selTask ?? '' }}';
        const selProject = '{{ $selProject ?? '' }}';

        // Task TomSelect — on change, auto-select parent project
        let projectTs;
        let taskTs = new TomSelect('#ot_task_id', {
            maxOptions: null,
            allowEmptyOption: true,
            onChange: function (val) {
                if (!val || !projectTs) return;
                const task = allTasks.find(t => t.value === String(val));
                if (task && task.projectId) {
                    projectTs.setValue(task.projectId, true); // true = silent (no re-trigger)
                }
            },
        });

        // Project TomSelect — on change, rebuild task options
        projectTs = new TomSelect('#ot_project_id', {
            maxOptions: null,
            allowEmptyOption: true,
            onChange: function (val) {
                const filtered = val
                    ? allTasks.filter(t => t.projectId === String(val))
                    : allTasks;
                taskTs.clear(true);
                taskTs.clearOptions();
                taskTs.addOption({ value: '', text: '— Không có —' });
                filtered.forEach(t => taskTs.addOption({ value: t.value, text: t.text }));
                taskTs.refreshOptions(false);
                // Re-select current task if it belongs to the new project filter
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
