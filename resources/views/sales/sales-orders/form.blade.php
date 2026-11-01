@extends('layouts.app')

@section('title', ($salesOrder ? 'Edit' : 'Create').' Sales Order')

@section('content')
    @php
        $editing = $salesOrder !== null;
        $lines = old('lines', $salesOrder?->lines->map(fn ($line) => [
            'item_id' => $line->item_id,
            'qty' => $line->qty,
            'unit_price' => $line->unit_price,
        ])->all() ?? [['item_id' => '', 'qty' => '', 'unit_price' => '']]);
    @endphp
    <div class="mb-4 page-head">
        <h1 class="h3 mb-1">{{ $editing ? 'Edit' : 'Create' }} Sales Order</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('sales-orders.index') }}">Sales Orders</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $editing ? 'Edit' : 'Create' }}</li>
            </ol>
        </nav>
    </div>

    <div class="alert alert-info" role="note">
        Availability is the combined balance across warehouses. The selected delivery warehouse is checked again when stock is posted.
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ $editing ? route('sales-orders.update', $salesOrder) : route('sales-orders.store') }}">
                @csrf
                @if ($editing)
                    @method('PUT')
                @endif
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="partner_id">Customer</label>
                        <select class="form-select @error('partner_id') is-invalid @enderror" id="partner_id" name="partner_id" required>
                            <option value="">Choose customer...</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" @selected((string) old('partner_id', $salesOrder?->partner_id) === (string) $customer->id)>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('partner_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="order_date">Order date</label>
                        <input class="form-control @error('order_date') is-invalid @enderror" type="date" id="order_date"
                            name="order_date" value="{{ old('order_date', $salesOrder?->order_date?->format('Y-m-d') ?? now('Asia/Jakarta')->toDateString()) }}" required>
                        @error('order_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="notes">Notes</label>
                        <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" maxlength="255" rows="2">{{ old('notes', $salesOrder?->notes) }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <h2 class="h5 mb-0">Order lines</h2>
                            <button class="btn btn-sm btn-outline-primary" id="addLine" type="button">
                                <i class="bi bi-plus-lg me-1"></i>Add line
                            </button>
                        </div>
                        @error('lines')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th scope="col">Item</th>
                                        <th scope="col">Available</th>
                                        <th scope="col">Quantity</th>
                                        <th scope="col">Unit price</th>
                                        <th scope="col" class="text-end">Subtotal</th>
                                        <th scope="col"><span class="visually-hidden">Actions</span></th>
                                    </tr>
                                </thead>
                                <tbody id="lines">
                                    @foreach ($lines as $index => $line)
                                        <tr>
                                            <td>
                                                <label class="visually-hidden" for="item_{{ $index }}">Item</label>
                                                <select class="form-select item-select @error("lines.$index.item_id") is-invalid @enderror"
                                                    id="item_{{ $index }}" name="lines[{{ $index }}][item_id]" required>
                                                    <option value="">Choose item...</option>
                                                    @foreach ($items as $item)
                                                        <option value="{{ $item->id }}" data-available="{{ $item->available_qty ?? '0.000' }}"
                                                            data-price="{{ $item->sale_price }}"
                                                            @selected((string) ($line['item_id'] ?? '') === (string) $item->id)>
                                                            {{ $item->sku }} - {{ $item->name }} ({{ $item->unit?->code }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error("lines.$index.item_id")<div class="text-danger small">{{ $message }}</div>@enderror
                                            </td>
                                            <td><span class="available-qty text-secondary">Choose an item</span></td>
                                            <td>
                                                <label class="visually-hidden" for="qty_{{ $index }}">Quantity</label>
                                                <input class="form-control @error("lines.$index.qty") is-invalid @enderror" type="number"
                                                    id="qty_{{ $index }}" name="lines[{{ $index }}][qty]" min="0.001" step="0.001"
                                                    value="{{ $line['qty'] ?? '' }}" required>
                                                @error("lines.$index.qty")<div class="text-danger small">{{ $message }}</div>@enderror
                                            </td>
                                            <td>
                                                <label class="visually-hidden" for="price_{{ $index }}">Unit price</label>
                                                <input class="form-control @error("lines.$index.unit_price") is-invalid @enderror" type="number"
                                                    id="price_{{ $index }}" name="lines[{{ $index }}][unit_price]" min="0" step="0.01"
                                                    value="{{ $line['unit_price'] ?? '' }}" required>
                                                @error("lines.$index.unit_price")<div class="text-danger small">{{ $message }}</div>@enderror
                                            </td>
                                            <td class="text-end"><span class="line-subtotal">0,00</span></td>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-outline-danger remove-line" type="button" aria-label="Remove line">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th></th>
                                        <th>Total</th>
                                        <td><span id="orderTotalQuantity">0,000</span></td>
                                        <td></td>
                                        <th class="text-end"><span id="orderTotalAmount">0,00</span></th>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        @error('lines.*.item_id')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-primary" type="submit">{{ $editing ? 'Save changes' : 'Create draft' }}</button>
                        <a class="btn btn-outline-secondary" href="{{ $editing ? route('sales-orders.show', $salesOrder) : route('sales-orders.index') }}">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <template id="lineTemplate">
        <tr>
            <td>
                <label class="visually-hidden" for="item___INDEX__">Item</label>
                <select class="form-select item-select" id="item___INDEX__" name="lines[__INDEX__][item_id]" required>
                    <option value="">Choose item...</option>
                    @foreach ($items as $item)
                        <option value="{{ $item->id }}" data-available="{{ $item->available_qty ?? '0.000' }}" data-price="{{ $item->sale_price }}">
                            {{ $item->sku }} - {{ $item->name }} ({{ $item->unit?->code }})
                        </option>
                    @endforeach
                </select>
            </td>
            <td><span class="available-qty text-secondary">Choose an item</span></td>
            <td>
                <label class="visually-hidden" for="qty___INDEX__">Quantity</label>
                <input class="form-control" type="number" id="qty___INDEX__" name="lines[__INDEX__][qty]" min="0.001" step="0.001" required>
            </td>
            <td>
                <label class="visually-hidden" for="price___INDEX__">Unit price</label>
                <input class="form-control" type="number" id="price___INDEX__" name="lines[__INDEX__][unit_price]" min="0" step="0.01" required>
            </td>
            <td class="text-end"><span class="line-subtotal">0,00</span></td>
            <td class="text-end">
                <button class="btn btn-sm btn-outline-danger remove-line" type="button" aria-label="Remove line"><i class="bi bi-trash"></i></button>
            </td>
        </tr>
    </template>
@endsection

@push('scripts')
    <script>
        (function () {
            const lines = document.getElementById('lines');
            const template = document.getElementById('lineTemplate');
            const totalQuantity = document.getElementById('orderTotalQuantity');
            const totalAmount = document.getElementById('orderTotalAmount');
            let nextIndex = lines.querySelectorAll('tr').length;
            const updateTotals = function () {
                window.appUpdateOrderTotals(lines, totalQuantity, totalAmount);
            };

            function quantityExceeds(quantity, available) {
                const scaled = function (value) {
                    const parts = String(value || '0').split('.');
                    const whole = (parts[0] || '0').replace(/^0+(?=\d)/, '');
                    const fraction = (parts[1] || '').padEnd(3, '0').slice(0, 3);
                    return (whole + fraction).replace(/^0+(?=\d)/, '');
                };
                const requested = scaled(quantity);
                const inStock = scaled(available);

                return requested.length > inStock.length ||
                    (requested.length === inStock.length && requested > inStock);
            }

            while (lines.querySelector('[name="lines[' + nextIndex + '][item_id]"]')) {
                nextIndex++;
            }

            function updateAvailability(row, setPrice) {
                const select = row.querySelector('.item-select');
                const option = select.options[select.selectedIndex];
                const message = row.querySelector('.available-qty');
                if (!option.value) {
                    message.textContent = 'Choose an item';
                    message.className = 'available-qty text-secondary';
                    return;
                }

                const available = option.dataset.available || '0.000';
                message.textContent = window.appFormatNumber(available, 3) + ' available';
                message.className = 'available-qty text-secondary';
                if (quantityExceeds(row.querySelector('[name$="[qty]"]').value, available)) {
                    message.className = 'available-qty text-warning';
                    message.textContent += ' — quantity exceeds availability';
                }
                if (setPrice) {
                    row.querySelector('[name$="[unit_price]"]').value = option.dataset.price || '0.00';
                }
            }

            lines.querySelectorAll('tr').forEach(function (row) {
                const select = row.querySelector('.item-select');
                select.addEventListener('change', function () {
                    updateAvailability(row, true);
                    updateTotals();
                });
                row.querySelector('[name$="[qty]"]').addEventListener('input', function () { updateAvailability(row, false); });
                updateAvailability(row, false);
            });

            lines.addEventListener('input', updateTotals);
            updateTotals();

            document.getElementById('addLine').addEventListener('click', function () {
                lines.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', nextIndex++));
                const row = lines.lastElementChild;
                row.querySelector('.item-select').addEventListener('change', function () {
                    updateAvailability(row, true);
                    updateTotals();
                });
                row.querySelector('[name$="[qty]"]').addEventListener('input', function () { updateAvailability(row, false); });
                updateTotals();
            });

            lines.addEventListener('click', function (event) {
                const button = event.target.closest('.remove-line');
                if (button && lines.rows.length > 1) {
                    button.closest('tr').remove();
                    updateTotals();
                }
            });
        }());
    </script>
@endpush
