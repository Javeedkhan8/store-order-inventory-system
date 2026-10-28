@extends("layouts.app")

@section("title", "Edit Customer | Store Manager")

@section("content")
    <section class="panel" style="max-width: 600px; margin: 0 auto;">
        <div class="panel__header">
            <div>
                <h2>Edit customer</h2>
            </div>
        </div>
        <form method="POST" action="{{ route("customers.update", $customer) }}" class="product-form">
            @csrf
            @method("PUT")
            <label class="field">
                <span>Name</span>
                <input name="name" value="{{ old("name", $customer->name) }}" required>
                @error("name")
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </label>
            <label class="field">
                <span>Email</span>
                <input name="email" type="email" value="{{ old("email", $customer->email) }}" required>
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
