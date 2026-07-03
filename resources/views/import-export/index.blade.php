<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Nhập / Xuất dữ liệu</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6"
             x-data="{
                 tab: 'export',
                 importType: 'users',
                 templateUrl() {
                     return '{{ url('data-transfer/template') }}/' + this.importType;
                 }
             }">

            {{-- Tab bar --}}
            <div class="flex gap-1 border-b border-gray-200 dark:border-gray-700">
                @php
                    $tabOn  = 'border-b-2 border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400';
                    $tabOff = 'border-b-2 border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200';
                @endphp
                <button type="button" @click="tab = 'export'"
                    :class="tab === 'export' ? '{{ $tabOn }}' : '{{ $tabOff }}'"
                    class="px-5 py-3 text-sm font-medium whitespace-nowrap transition-colors">
                    Xuất dữ liệu
                </button>
                <button type="button" @click="tab = 'import'"
                    :class="tab === 'import' ? '{{ $tabOn }}' : '{{ $tabOff }}'"
                    class="px-5 py-3 text-sm font-medium whitespace-nowrap transition-colors">
                    Nhập dữ liệu
                </button>
            </div>

            {{-- Flash: import results --}}
            @if(session('import_results'))
                @php $res = session('import_results'); @endphp
                <div class="rounded-lg border p-4
                    {{ $res['skipped'] > 0 ? 'border-amber-300 bg-amber-50 dark:bg-amber-900/20' : 'border-green-300 bg-green-50 dark:bg-green-900/20' }}">
                    <p class="text-sm font-medium {{ $res['skipped'] > 0 ? 'text-amber-800 dark:text-amber-300' : 'text-green-800 dark:text-green-300' }}">
                        Đã tạo: {{ $res['created'] }}
                        @if($res['updated'] > 0) &nbsp;·&nbsp; Đã cập nhật: {{ $res['updated'] }} @endif
                        @if($res['skipped'] > 0) &nbsp;·&nbsp; Bỏ qua: {{ $res['skipped'] }} @endif
                    </p>
                    @if(!empty($res['errors']))
                        <ul class="mt-2 space-y-0.5 text-xs text-amber-700 dark:text-amber-400 list-disc list-inside">
                            @foreach($res['errors'] as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif

            @if(session('import_error'))
                <div class="rounded-lg border border-red-300 bg-red-50 dark:bg-red-900/20 p-4">
                    <p class="text-sm text-red-700 dark:text-red-400">{{ session('import_error') }}</p>
                </div>
            @endif

            {{-- ═══════════ EXPORT TAB ═══════════ --}}
            <div x-show="tab === 'export'" x-cloak>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    @php
                        $exports = [
                            ['type' => 'users',          'label' => 'Người dùng',       'desc' => 'Tên, email, chức vụ, vai trò, số dư phép, nhóm.',      'color' => 'indigo'],
                            ['type' => 'teams',          'label' => 'Nhóm',             'desc' => 'Tên nhóm, trưởng nhóm, danh sách thành viên.',          'color' => 'purple'],
                            ['type' => 'leave-requests', 'label' => 'Yêu cầu nghỉ phép','desc' => 'Tất cả yêu cầu nghỉ với trạng thái và người duyệt.',   'color' => 'green'],
                            ['type' => 'ot-requests',    'label' => 'Yêu cầu tăng ca',  'desc' => 'Tất cả yêu cầu OT với dự án, công việc, người duyệt.', 'color' => 'orange'],
                        ];
                    @endphp

                    @foreach($exports as $ex)
                    @php
                        $ring = match($ex['color']) {
                            'purple' => 'ring-purple-200 dark:ring-purple-800',
                            'green'  => 'ring-green-200 dark:ring-green-800',
                            'orange' => 'ring-orange-200 dark:ring-orange-800',
                            default  => 'ring-indigo-200 dark:ring-indigo-800',
                        };
                        $iconBg = match($ex['color']) {
                            'purple' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400',
                            'green'  => 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400',
                            'orange' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400',
                            default  => 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400',
                        };
                        $btnCls = match($ex['color']) {
                            'purple' => 'bg-purple-600 hover:bg-purple-700 focus:ring-purple-500',
                            'green'  => 'bg-green-600 hover:bg-green-700 focus:ring-green-500',
                            'orange' => 'bg-orange-600 hover:bg-orange-700 focus:ring-orange-500',
                            default  => 'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500',
                        };
                    @endphp
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 {{ $ring }} p-5 flex flex-col gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg {{ $iconBg }} flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $ex['label'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $ex['desc'] }}</p>
                            </div>
                        </div>
                        <a href="{{ route('import-export.export', $ex['type']) }}"
                           class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium text-white {{ $btnCls }} focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Tải xuống Excel
                        </a>
                    </div>
                    @endforeach

                </div>

                <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">
                    File xuất ở định dạng <strong>.xlsx</strong>. Dữ liệu phản ánh toàn bộ bản ghi trong cơ sở dữ liệu tại thời điểm xuất.
                </p>
            </div>

            {{-- ═══════════ IMPORT TAB ═══════════ --}}
            <div x-show="tab === 'import'" x-cloak>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 p-6">

                    <form method="POST"
                          :action="'{{ url('data-transfer/import') }}/' + importType"
                          enctype="multipart/form-data"
                          class="space-y-5">
                        @csrf

                        {{-- Type selector --}}
                        <div>
                            <x-input-label value="Loại dữ liệu" />
                            <select x-model="importType"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                                <option value="users">Người dùng</option>
                                <option value="teams">Nhóm</option>
                                <option value="leave-requests">Yêu cầu nghỉ phép</option>
                                <option value="ot-requests">Yêu cầu tăng ca</option>
                            </select>
                        </div>

                        {{-- Template download --}}
                        <div class="flex items-center gap-2 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-sm text-blue-700 dark:text-blue-300">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Tải file mẫu trước khi nhập:&nbsp;</span>
                            <a :href="templateUrl()"
                               class="font-medium underline hover:text-blue-900 dark:hover:text-blue-100">
                                Tải template (.xlsx)
                            </a>
                        </div>

                        {{-- Column notes per type --}}
                        <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1 border border-dashed border-gray-200 dark:border-gray-700 rounded-lg p-3">
                            <template x-if="importType === 'users'">
                                <div>
                                    <p class="font-medium text-gray-700 dark:text-gray-300 mb-1">Cột bắt buộc: <code>name</code>, <code>email</code></p>
                                    <p>Cột tuỳ chọn: <code>password</code>, <code>position</code>, <code>grade</code>, <code>leave_balance</code>, <code>roles</code></p>
                                    <p class="mt-1">• <code>roles</code>: tên vai trò, phân cách bằng <code>|</code> (ví dụ: <code>Staff|Manager</code>)</p>
                                    <p>• Email đã tồn tại → cập nhật thông tin. Email mới → tạo mới.</p>
                                </div>
                            </template>
                            <template x-if="importType === 'teams'">
                                <div>
                                    <p class="font-medium text-gray-700 dark:text-gray-300 mb-1">Cột bắt buộc: <code>name</code></p>
                                    <p>Cột tuỳ chọn: <code>leaders</code>, <code>members</code></p>
                                    <p class="mt-1">• <code>leaders</code>/<code>members</code>: tên người dùng hoặc <code>ID:Tên</code>, phân cách bằng <code>|</code></p>
                                    <p>• Tên nhóm đã tồn tại → cập nhật thành viên. Tên mới → tạo mới.</p>
                                </div>
                            </template>
                            <template x-if="importType === 'leave-requests'">
                                <div>
                                    <p class="font-medium text-gray-700 dark:text-gray-300 mb-1">Cột bắt buộc: <code>user</code>, <code>type</code>, <code>start_at</code>, <code>end_at</code>, <code>hours</code></p>
                                    <p>Cột tuỳ chọn: <code>description</code>, <code>status</code>, <code>approved_by</code>, <code>reject_reason</code></p>
                                    <p class="mt-1">• <code>user</code> / <code>approved_by</code>: tên hoặc ID người dùng</p>
                                    <p>• <code>type</code>: <code>annual</code> / <code>sick</code> / <code>unpaid</code></p>
                                    <p>• <code>start_at</code> / <code>end_at</code>: định dạng <code>dd/mm/yyyy HH:mm</code></p>
                                    <p>• <code>status</code>: <code>pending</code> (mặc định) / <code>approved</code> / <code>rejected</code></p>
                                </div>
                            </template>
                            <template x-if="importType === 'ot-requests'">
                                <div>
                                    <p class="font-medium text-gray-700 dark:text-gray-300 mb-1">Cột bắt buộc: <code>user</code>, <code>start_at</code>, <code>end_at</code>, <code>hours</code></p>
                                    <p>Cột tuỳ chọn: <code>type</code>, <code>project</code>, <code>task</code>, <code>description</code>, <code>status</code>, <code>approved_by</code>, <code>reject_reason</code></p>
                                    <p class="mt-1">• <code>user</code> / <code>approved_by</code>: tên hoặc ID người dùng</p>
                                    <p>• <code>project</code> / <code>task</code>: tên hoặc ID</p>
                                    <p>• <code>type</code>: <code>OT x1.5</code> / <code>OT x2</code> / <code>OT x3</code></p>
                                    <p>• <code>status</code>: <code>pending</code> (mặc định) / <code>approved</code> / <code>rejected</code></p>
                                </div>
                            </template>
                        </div>

                        {{-- File upload --}}
                        <div>
                            <x-input-label value="File (.xlsx, .xls, .csv)" />
                            <input type="file" name="file" accept=".xlsx,.xls,.csv"
                                   class="mt-1 block w-full text-sm text-gray-700 dark:text-gray-300
                                          file:mr-3 file:py-2 file:px-4
                                          file:rounded-md file:border-0
                                          file:text-sm file:font-medium
                                          file:bg-indigo-50 file:text-indigo-700
                                          dark:file:bg-indigo-900/30 dark:file:text-indigo-300
                                          hover:file:bg-indigo-100 dark:hover:file:bg-indigo-900/50
                                          cursor-pointer">
                            @error('file')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center gap-3 pt-1">
                            <button type="submit"
                                    class="inline-flex items-center gap-2 px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                                Bắt đầu nhập
                            </button>
                            <p class="text-xs text-gray-400">Tối đa 10 MB. Dữ liệu sẽ được xử lý ngay.</p>
                        </div>
                    </form>
                </div>

                <div class="mt-4 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg text-xs text-amber-700 dark:text-amber-300 space-y-1">
                    <p class="font-semibold">Lưu ý khi nhập dữ liệu:</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        <li>Hàng đầu tiên phải là tên cột (header). Hàng in nghiêng màu vàng trong file mẫu là ví dụ — xoá trước khi nhập thật.</li>
                        <li>Người dùng và nhóm: khớp theo email/tên → sẽ cập nhật nếu đã tồn tại.</li>
                        <li>Yêu cầu nghỉ phép / OT: luôn tạo mới, không kiểm tra trùng.</li>
                        <li>Không có thao tác hoàn tác — hãy kiểm tra file mẫu trước khi nhập.</li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
