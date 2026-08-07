<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Import người dùng</h2>
            <a href="javascript:history.back()"><x-secondary-button>Quay lại</x-secondary-button></a>
        </div>
    </x-slot>

    <div>
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6 py-6">

            @if(session('success'))
                <div class="p-3 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded text-sm">{{ session('success') }}</div>
            @endif
            @if(session('import_results'))
            @php $results = session('import_results'); @endphp
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-5 space-y-3">
                <h3 class="font-semibold text-gray-700 dark:text-gray-200 text-sm">Kết quả import</h3>
                <div class="flex gap-6 text-sm">
                    <span class="text-green-600 dark:text-green-400 font-medium">✓ {{ $results['created'] }} tạo thành công</span>
                    @if($results['skipped'] > 0)
                        <span class="text-yellow-600 dark:text-yellow-400 font-medium">⚠ {{ $results['skipped'] }} bỏ qua</span>
                    @endif
                    @if($results['failed'] > 0)
                        <span class="text-red-600 dark:text-red-400 font-medium">✗ {{ $results['failed'] }} lỗi</span>
                    @endif
                </div>
                @if(!empty($results['errors']))
                <div class="text-xs space-y-1 mt-2 max-h-40 overflow-y-auto">
                    @foreach($results['errors'] as $err)
                        <div class="text-red-600 dark:text-red-400 font-mono">{{ $err }}</div>
                    @endforeach
                </div>
                @endif
            </div>
            @endif

            {{-- Format guide --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-5">
                <h3 class="font-semibold text-gray-700 dark:text-gray-200 mb-3 text-sm">Định dạng file CSV</h3>
                <div class="overflow-x-auto">
                    <table class="text-xs min-w-full border border-gray-200 dark:border-gray-700 rounded">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                @foreach(['name*','email*','password','position','grade','roles','team','team_leader'] as $col)
                                    <th class="px-3 py-2 text-left font-semibold text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">{{ $col }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-t border-gray-100 dark:border-gray-700">
                                <td class="px-3 py-2 text-gray-500 dark:text-gray-400">Nguyễn Văn A</td>
                                <td class="px-3 py-2 text-gray-500 dark:text-gray-400">vana@company.com</td>
                                <td class="px-3 py-2 text-gray-500 dark:text-gray-400">secret123</td>
                                <td class="px-3 py-2 text-gray-500 dark:text-gray-400">Developer</td>
                                <td class="px-3 py-2 text-gray-500 dark:text-gray-400">Junior</td>
                                <td class="px-3 py-2 text-gray-500 dark:text-gray-400">Staff|Manager</td>
                                <td class="px-3 py-2 text-gray-500 dark:text-gray-400">Dev Team</td>
                                <td class="px-3 py-2 text-gray-500 dark:text-gray-400">0</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <ul class="mt-3 text-xs text-gray-500 dark:text-gray-400 space-y-1">
                    <li>* Bắt buộc. Dòng đầu tiên phải là tiêu đề (header row).</li>
                    <li>• <strong>password</strong> — để trống để tự động tạo mật khẩu ngẫu nhiên.</li>
                    <li>• <strong>roles</strong> — phân cách bằng dấu <code>|</code>, ví dụ: <code>Staff|Manager</code>.</li>
                    <li>• <strong>team</strong> — tùy chọn. Tên nhóm để thêm người dùng vào; nếu chưa tồn tại sẽ tự động tạo mới.</li>
                    <li>• <strong>team_leader</strong> — tùy chọn. Đặt <code>1</code> (hoặc <code>true</code> / <code>yes</code>) để gán người dùng làm trưởng nhóm; mặc định là <code>0</code>.</li>
                    <li>• Email trùng lặp sẽ bị bỏ qua.</li>
                </ul>
                <div class="mt-3">
                    <a href="{{ route('users.import.template') }}"
                        class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                        ↓ Tải file mẫu (CSV)
                    </a>
                </div>
            </div>

            {{-- Upload form --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-5">
                <h3 class="font-semibold text-gray-700 dark:text-gray-200 mb-4 text-sm">Tải lên file CSV</h3>
                <form method="POST" action="{{ route('users.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-4">
                        <x-input-label for="csv_file" value="File CSV *" />
                        <input type="file" id="csv_file" name="csv_file" accept=".csv,text/csv"
                            class="mt-1 block w-full text-sm text-gray-700 dark:text-gray-300
                                   file:mr-4 file:py-2 file:px-4 file:rounded file:border-0
                                   file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700
                                   dark:file:bg-indigo-900 dark:file:text-indigo-300
                                   hover:file:bg-indigo-100 dark:hover:file:bg-indigo-800">
                        <x-input-error :messages="$errors->get('csv_file')" class="mt-1" />
                    </div>

                    <div class="mb-4 flex items-center gap-2">
                        <input type="checkbox" id="send_email" name="send_email" value="1" checked
                            class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                        <label for="send_email" class="text-sm text-gray-700 dark:text-gray-300">
                            Gửi email chào mừng kèm thông tin đăng nhập
                        </label>
                    </div>

                    <x-primary-button type="submit">Import</x-primary-button>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
