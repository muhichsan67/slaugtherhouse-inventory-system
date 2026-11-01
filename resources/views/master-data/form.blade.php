@extends('layouts.app')

@section('title', ($record ? 'Edit ' : 'Create ').$title)

@section('content')
    @php
        $editing = $record !== null;
    @endphp
    <div class="mb-4 page-head">
        <h1 class="h3 mb-1">{{ $editing ? 'Edit' : 'Create' }} {{ $title }}</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route($routeName.'.index') }}">{{ $title }} list</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $editing ? 'Edit' : 'Create' }}</li>
            </ol>
        </nav>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ $editing ? route($routeName.'.update', $record) : route($routeName.'.store') }}">
                @csrf
                @if ($editing)
                    @method('PUT')
                @endif

                <div class="row g-3">
                    @foreach ($fields as $field)
                        @php
                            $name = $field['name'];
                            $value = old($name, $record ? data_get($record, $name) : ($field['default'] ?? ''));
                            $required = ($field['required'] ?? false) && !($editing && $field['type'] === 'password');
                            $fieldOptions = $field['options'] ?? data_get($options, $field['options_key'] ?? '', []);
                            $hasError = $errors->has($name);
                        @endphp
                        <div class="col-md-6">
                            <label class="form-label" for="{{ $name }}">{{ $field['label'] }}</label>
                            @if ($field['type'] === 'textarea')
                                <textarea class="form-control {{ $hasError ? 'is-invalid' : '' }}" id="{{ $name }}"
                                    name="{{ $name }}" rows="3" maxlength="{{ $field['maxlength'] ?? '' }}"
                                    @required($required)>{{ $value }}</textarea>
                            @elseif ($field['type'] === 'select')
                                <select class="form-select {{ $hasError ? 'is-invalid' : '' }}" id="{{ $name }}" name="{{ $name }}" @required($required)>
                                    <option value="">Choose...</option>
                                    @foreach ($fieldOptions as $optionValue => $optionLabel)
                                        <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>
                                            {{ $optionLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            @elseif ($field['type'] === 'checkbox')
                                <input type="hidden" name="{{ $name }}" value="0">
                                <div class="form-check">
                                    <input class="form-check-input {{ $hasError ? 'is-invalid' : '' }}" type="checkbox"
                                        id="{{ $name }}" name="{{ $name }}" value="1" @checked((bool) $value)>
                                    <label class="form-check-label" for="{{ $name }}">{{ $field['label'] }}</label>
                                </div>
                            @else
                                <input class="form-control {{ $hasError ? 'is-invalid' : '' }}" type="{{ $field['type'] }}"
                                    id="{{ $name }}" name="{{ $name }}"
                                    @if ($field['type'] !== 'password') value="{{ $value }}" @endif
                                    @if ($field['type'] === 'password' && $editing) placeholder="Leave blank to keep the current password" @endif
                                    @if (isset($field['maxlength'])) maxlength="{{ $field['maxlength'] }}" @endif
                                    @if (isset($field['minlength'])) minlength="{{ $field['minlength'] }}" @endif
                                    @if (isset($field['min'])) min="{{ $field['min'] }}" @endif
                                    @if (isset($field['max'])) max="{{ $field['max'] }}" @endif
                                    @if (isset($field['step'])) step="{{ $field['step'] }}" @endif
                                    @required($required)>
                            @endif
                            @error($name)
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    @endforeach
                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-check2 me-1"></i>{{ $editing ? 'Save changes' : 'Create' }}
                        </button>
                        <a class="btn btn-outline-secondary" href="{{ route($routeName.'.index') }}">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
