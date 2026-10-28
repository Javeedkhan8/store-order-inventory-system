@extends("layouts.app")

@section("title", "Add Customer | Store Manager")

@section("content")
    <section class="panel" style="max-width: 600px; margin: 0 auto;">
        <div class="panel__header">
            <div>
                <h2>Add customer</h2>
            </div>
        </div>
        <form method="POST" action="{{ route("customers.store") }}" class="product-form">
            @csrf
            <label class="field">
                <span>Name</span>
                <input name="name" value="{{ old("name") }}" required>
                @error("name")
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </label>
            <label class="field">
                <span>Email</span>
                <input name="email" type="email" value="{{ old("email") }}" required>
                @error("email")
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </label>
            <div class="form-actions">
                <button type="submit" class="button button--primary">Save</button>
                <a class="button button--soft" href="{{ route("customers.index") }}">Cancel</a>
            </div>
        </form>
    </section>
@endsection
