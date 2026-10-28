<x-app-layout>
    <x-slot name="header">
        <h1 class="page-title">Profile settings</h1>
    </x-slot>

    <div class="profile-grid">
        <section class="panel profile-panel">
            @include('profile.partials.update-profile-information-form')
        </section>

        <section class="panel profile-panel">
            @include('profile.partials.update-password-form')
        </section>

        <section class="panel profile-panel profile-panel--danger">
            @include('profile.partials.delete-user-form')
        </section>
    </div>
</x-app-layout>
