@extends('layouts.profile', ['activeNav' => 'profile'])

@section('content')
    @php
        $firstFinishedAt = $remainderStatistics['first_finished_at']
            ? \Illuminate\Support\Carbon::parse($remainderStatistics['first_finished_at'])->translatedFormat('d M Y')
            : __('profile.statistics.fallbacks.no_completed_sessions');
        $lastFinishedAt = $remainderStatistics['last_finished_at']
            ? \Illuminate\Support\Carbon::parse($remainderStatistics['last_finished_at'])->translatedFormat('d M Y')
            : __('profile.statistics.fallbacks.no_completed_sessions');
    @endphp

    <main class="profile-main" x-data="{ statisticsOpen: false }">
        <div class="profile-container">
            <header class="profile-page-header">
                <button
                    type="button"
                    class="profile-title-toggle"
                    @click="statisticsOpen = !statisticsOpen"
                    :aria-expanded="statisticsOpen.toString()"
                    aria-controls="remainder-statistics-panel"
                >
                    <span class="profile-title">{{ __('profile.statistics.title') }}</span>
                    <span class="profile-title-toggle__icon" :class="{ 'profile-title-toggle__icon--open': statisticsOpen }" aria-hidden="true">
                        <svg viewBox="0 0 20 20" fill="currentColor" focusable="false">
                            <path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 0 1 1.06 0L10 10.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
                        </svg>
                    </span>
                </button>
            </header>

            <section
                id="remainder-statistics-panel"
                class="profile-card profile-statistics-card"
                x-show="statisticsOpen"
                x-cloak
            >
                <div class="profile-section">
                    <div class="profile-statistics-table-wrap">
                        <table class="profile-statistics-table">
                            <tbody>
                                <tr>
                                    <th scope="row">{{ __('profile.statistics.rows.completed_sessions') }}</th>
                                    <td>{{ $remainderStatistics['sessions_count'] }}</td>
                                </tr>
                                <tr>
                                    <th scope="row">{{ __('profile.statistics.rows.first_completed_session') }}</th>
                                    <td>{{ $firstFinishedAt }}</td>
                                </tr>
                                <tr>
                                    <th scope="row">{{ __('profile.statistics.rows.last_completed_session') }}</th>
                                    <td>{{ $lastFinishedAt }}</td>
                                </tr>
                                <tr>
                                    <th scope="row">{{ __('profile.statistics.rows.preferred_mode') }}</th>
                                    <td>{{ $remainderStatistics['preferred_mode'] ?? __('profile.statistics.fallbacks.not_enough_data') }}</td>
                                </tr>
                                <tr>
                                    <th scope="row">{{ __('profile.statistics.rows.preferred_direction') }}</th>
                                    <td>{{ $remainderStatistics['preferred_direction'] ?? __('profile.statistics.fallbacks.not_enough_data') }}</td>
                                </tr>
                                <tr>
                                    <th scope="row">{{ __('profile.statistics.rows.total_words') }}</th>
                                    <td>{{ $remainderStatistics['total_words'] }}</td>
                                </tr>
                                <tr>
                                    <th scope="row">{{ __('profile.statistics.rows.correct_answers') }}</th>
                                    <td>{{ $remainderStatistics['correct_answers'] }}</td>
                                </tr>
                                <tr>
                                    <th scope="row">{{ __('profile.statistics.rows.incorrect_answers') }}</th>
                                    <td>{{ $remainderStatistics['incorrect_answers'] }}</td>
                                </tr>
                                <tr>
                                    <th scope="row">{{ __('profile.statistics.rows.accuracy_percentage') }}</th>
                                    <td>
                                        {{ $remainderStatistics['accuracy_percentage'] !== null ? rtrim(rtrim(number_format($remainderStatistics['accuracy_percentage'], 1), '0'), '.') . '%' : __('profile.statistics.fallbacks.no_answers') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <header class="profile-page-header profile-page-header--spacious">
                <h1 class="profile-title">{{ __('profile.settings.title') }}</h1>
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
