@extends('layouts.profile', ['activeNav' => 'profile'])

@section('content')
    @php
        $firstFinishedAt = $remainderStatistics['first_finished_at']
            ? \Illuminate\Support\Carbon::parse($remainderStatistics['first_finished_at'])->format('d M Y')
            : 'No completed sessions yet';
        $lastFinishedAt = $remainderStatistics['last_finished_at']
            ? \Illuminate\Support\Carbon::parse($remainderStatistics['last_finished_at'])->format('d M Y')
            : 'No completed sessions yet';
    @endphp

    <main class="profile-main">
        <div class="profile-container">
            <header class="profile-page-header">
                <h1 class="profile-title">Remainder Statistic</h1>
            </header>

            <section class="profile-card profile-statistics-card">
                <div class="profile-section">
                    <div class="profile-statistics-table-wrap">
                        <table class="profile-statistics-table">
                            <tbody>
                                <tr>
                                    <th scope="row">Completed sessions</th>
                                    <td>{{ $remainderStatistics['sessions_count'] }}</td>
                                </tr>
                                <tr>
                                    <th scope="row">First completed session</th>
                                    <td>{{ $firstFinishedAt }}</td>
                                </tr>
                                <tr>
                                    <th scope="row">Last completed session</th>
                                    <td>{{ $lastFinishedAt }}</td>
                                </tr>
                                <tr>
                                    <th scope="row">Preferred mode</th>
                                    <td>{{ $remainderStatistics['preferred_mode'] ?? 'Not enough data yet' }}</td>
                                </tr>
                                <tr>
                                    <th scope="row">Preferred direction</th>
                                    <td>{{ $remainderStatistics['preferred_direction'] ?? 'Not enough data yet' }}</td>
                                </tr>
                                <tr>
                                    <th scope="row">Total words in all games</th>
                                    <td>{{ $remainderStatistics['total_words'] }}</td>
                                </tr>
                                <tr>
                                    <th scope="row">Total correct answers</th>
                                    <td>{{ $remainderStatistics['correct_answers'] }}</td>
                                </tr>
                                <tr>
                                    <th scope="row">Total incorrect answers</th>
                                    <td>{{ $remainderStatistics['incorrect_answers'] }}</td>
                                </tr>
                                <tr>
                                    <th scope="row">Correct answers percentage</th>
                                    <td>
                                        {{ $remainderStatistics['accuracy_percentage'] !== null ? rtrim(rtrim(number_format($remainderStatistics['accuracy_percentage'], 1), '0'), '.') . '%' : 'No answers yet' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <header class="profile-page-header profile-page-header--spacious">
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
