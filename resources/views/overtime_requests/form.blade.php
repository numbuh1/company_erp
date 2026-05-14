<x-app-layout>
    @php $readonly = $readonly ?? false; @endphp
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $readonly ? 'OT Request Details' : (isset($ot) ? 'Edit OT Request' : 'Create OT Request') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <form method="POST"
                    action="{{ isset($ot) ? route('overtime-requests.update', $ot) : route('overtime-requests.store') }}">
                    @csrf
                    @if(isset($ot)) @method('PUT') @endif

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
                        <x-input-label value="Mô tả" />
                        <textarea name="description" class="w-full border rounded p-2" @disabled($readonly)>{{ old('description', $ot->description ?? '') }}</textarea>
                    </div>

                    {{-- Buttons --}}
                    <div class="flex justify-end mt-6 space-x-2">
                        @if(!$readonly)
                            <x-primary-button>{{ isset($ot) ? 'Update' : 'Create' }}</x-primary-button>
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

                        <a href="{{ route('overtime-requests.index') }}">
                            <x-secondary-button>{{ $readonly ? 'Back' : 'Cancel' }}</x-secondary-button>
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
</x-app-layout>
