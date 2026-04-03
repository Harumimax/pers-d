@extends('layouts.profile')

@section('content')
    <main class="profile-main">
        <div class="profile-container">
            <header class="profile-page-header">
                <h1 class="profile-title">Profile Settings</h1>
            </header>

            <section class="profile-card">
                @include('profile.partials.update-profile-information-form')
            </section>

            <section class="profile-card">
                @include('profile.partials.update-password-form')
            </section>

            <section class="profile-card profile-card--danger">
                @include('profile.partials.delete-user-form')
            </section>
        </div>
    </main>
@endsection
