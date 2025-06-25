@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8 flex justify-between items-center">
        <h1 class="text-2xl font-bold">Sniffing Rules</h1>
        <button onclick="showAddRuleModal()" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            Add Rule
        </button>
    </div>

    <!-- Rules Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($rules as $rule)
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">{{ $rule->name }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ $rule->description }}</p>
                    </div>
                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                        @if($rule->severity === 'error') bg-red-100 text-red-800
                        @elseif($rule->severity === 'warning') bg-yellow-100 text-yellow-800
                        @else bg-blue-100 text-blue-800
                        @endif">
                        {{ $rule->severity }}
                    </span>
                </div>
                <div class="mt-4">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Type</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $rule->type }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Pattern</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <code class="bg-gray-50 px-2 py-1 rounded">{{ $rule->pattern }}</code>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="mt-1">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" class="form-checkbox h-4 w-4 text-indigo-600"
                                        {{ $rule->is_active ? 'checked' : '' }}
                                        onchange="toggleRule({{ $rule->id }}, this.checked)">
                                    <span class="ml-2 text-sm text-gray-900">Active</span>
                                </label>
                            </dd>
                        </div>
                    </dl>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button onclick="editRule({{ $rule->id }})" class="text-indigo-600 hover:text-indigo-900">
                        Edit
                    </button>
                    <button onclick="deleteRule({{ $rule->id }})" class="text-red-600 hover:text-red-900">
                        Delete
                    </button>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Add/Edit Rule Modal -->
    <div id="ruleModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="ruleForm" onsubmit="saveRule(event)">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    <span id="modalTitle">Add Rule</span>
                                </h3>
                                <div class="mt-4 space-y-4">
                                    <input type="hidden" id="ruleId" name="id">
                                    <div>
                                        <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                                        <input type="text" name="name" id="name" required
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                                        <textarea name="description" id="description" rows="3" required
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                    </div>
                                    <div>
                                        <label for="type" class="block text-sm font-medium text-gray-700">Type</label>
                                        <select name="type" id="type" required
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="naming">Naming Convention</option>
                                            <option value="documentation">Documentation</option>
                                            <option value="security">Security</option>
                                            <option value="performance">Performance</option>
                                            <option value="architecture">Architecture</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="pattern" class="block text-sm font-medium text-gray-700">Pattern</label>
                                        <input type="text" name="pattern" id="pattern" required
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label for="severity" class="block text-sm font-medium text-gray-700">Severity</label>
                                        <select name="severity" id="severity" required
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="error">Error</option>
                                            <option value="warning">Warning</option>
                                            <option value="info">Info</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Save
                        </button>
                        <button type="button" onclick="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function showAddRuleModal() {
    document.getElementById('modalTitle').textContent = 'Add Rule';
    document.getElementById('ruleForm').reset();
    document.getElementById('ruleId').value = '';
    document.getElementById('ruleModal').classList.remove('hidden');
}

function editRule(id) {
    document.getElementById('modalTitle').textContent = 'Edit Rule';
    fetch(`/api/sniffing/rules/${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('ruleId').value = data.id;
            document.getElementById('name').value = data.name;
            document.getElementById('description').value = data.description;
            document.getElementById('type').value = data.type;
            document.getElementById('pattern').value = data.pattern;
            document.getElementById('severity').value = data.severity;
            document.getElementById('ruleModal').classList.remove('hidden');
        });
}

function closeModal() {
    document.getElementById('ruleModal').classList.add('hidden');
}

function saveRule(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    const id = data.id;
    delete data.id;

    fetch(`/api/sniffing/rules${id ? `/${id}` : ''}`, {
        method: id ? 'PUT' : 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(() => {
        closeModal();
        window.location.reload();
    });
}

function toggleRule(id, active) {
    fetch(`/api/sniffing/rules/${id}/toggle`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ is_active: active })
    });
}

function deleteRule(id) {
    if (confirm('Are you sure you want to delete this rule?')) {
        fetch(`/api/sniffing/rules/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(() => {
            window.location.reload();
        });
    }
}
</script>
@endpush
@endsection 