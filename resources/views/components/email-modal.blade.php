<div id="email-modal"
    class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
    onclick="if(event.target===this) closeEmailModal()">

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] flex flex-col">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-100">✉ Send Email</h3>
            <button type="button" onclick="closeEmailModal()"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-2xl leading-none">&times;</button>
        </div>

        <!-- Body -->
        <div class="overflow-y-auto flex-1 px-6 py-4 space-y-4">

            <p class="text-xs text-gray-400 dark:text-gray-500">
                Email sẽ được gửi từ địa chỉ email của tài khoản hiện tại của bạn ({{ auth()->user()->email }}).
                Kiểm tra và chỉnh sửa nội dung trước khi gửi.
            </p>

            <div>
                <x-input-label for="email-to" value="To" />
                <x-text-input id="email-to" type="text" class="mt-1 block w-full" />
            </div>

            <div>
                <x-input-label for="email-cc" value="CC" />
                <x-text-input id="email-cc" type="text" class="mt-1 block w-full" />
            </div>

            <div>
                <x-input-label for="email-subject" value="Subject" />
                <x-text-input id="email-subject" type="text" class="mt-1 block w-full" />
            </div>

            <div>
                <x-input-label for="email-body" value="Nội dung" />
                <textarea id="email-body" rows="12"
                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm font-mono"></textarea>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex flex-wrap items-center gap-3">
            <p class="w-full text-xs text-gray-400 dark:text-gray-500">
                "Outlook (Ứng dụng)" mở ứng dụng email mặc định của hệ điều hành. Trên macOS, để link này mở
                Microsoft Outlook thay vì Mail, vào ứng dụng <strong>Mail</strong> → Settings → General →
                "Default email reader" và chọn <strong>Microsoft Outlook</strong>.
            </p>

            <button type="button" onclick="closeEmailModal()"
                class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                Hủy
            </button>

            <div class="flex-1"></div>

            <button type="button" onclick="sendEmailViaOutlookWeb()"
                class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                Outlook trên Web
            </button>
            <button type="button" onclick="sendEmailViaOutlookApp()"
                class="px-4 py-2 text-sm bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition">
                Outlook (Ứng dụng)
            </button>
        </div>
    </div>
</div>
