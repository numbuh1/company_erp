<div id="rejectModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 hidden">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Từ chối yêu cầu tăng ca</h3>
        <form id="rejectForm" method="POST" action="" class="inline">
            @csrf
            @method('POST')
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Lý do <span class="text-red-500">*</span>
                </label>
                <textarea name="reject_reason" id="reject_reason" rows="4" required
                    class="w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm px-3 py-2 text-sm text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-red-500"
                    placeholder="Nhập lý do từ chối…"></textarea>
            </div>
            <div class="flex justify-end space-x-2">
                <x-secondary-button type="button" onclick="closeRejectModal()">Hủy</x-secondary-button>
                <x-danger-button type="submit">Xác nhận từ chối</x-danger-button>
            </div>
        </form>
    </div>
</div>

<script>
    function openRejectModal(url) {
        document.getElementById('rejectForm').action = url;
        document.getElementById('reject_reason').value = '';
        document.getElementById('rejectModal').classList.remove('hidden');
    }
    function closeRejectModal() {
        document.getElementById('rejectModal').classList.add('hidden');
    }
    document.getElementById('rejectModal').addEventListener('click', function(e) {
        if (e.target === this) closeRejectModal();
    });
</script>