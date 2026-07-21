<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReadyDictionary;
use App\Models\User;
use App\Models\UserDictionary;
use App\Services\Navigation\HeaderNavigationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(Request $request, HeaderNavigationService $headerNavigationService): View
    {
        $userEmailFilter = trim((string) $request->query('user_email'));
        $userDictionaryNameFilter = trim((string) $request->query('user_dictionary_name'));
        $readyDictionaryNameFilter = trim((string) $request->query('ready_dictionary_name'));
        $userSort = $this->resolveSort(
            (string) $request->query('users_sort'),
            (string) $request->query('users_direction'),
            $this->userSorts(),
            'created_at',
            'asc'
        );
        $dictionarySort = $this->resolveSort(
            (string) $request->query('dictionaries_sort'),
            (string) $request->query('dictionaries_direction'),
            $this->dictionarySorts(),
            'created_at',
            'desc'
        );
        $readyDictionarySort = $this->resolveSort(
            (string) $request->query('ready_dictionaries_sort'),
            (string) $request->query('ready_dictionaries_direction'),
            $this->readyDictionarySorts(),
            'created_at',
            'desc'
        );

        $gameCorrectAnswersSql = $this->gameCorrectAnswersSql();
        $telegramCorrectAnswersSql = $this->telegramCorrectAnswersSql();
        $gameIncorrectAnswersSql = $this->gameIncorrectAnswersSql();
        $telegramIncorrectAnswersSql = $this->telegramIncorrectAnswersSql();

        $users = User::query()
            ->select('users.*')
            ->selectRaw('(
                SELECT COUNT(*)
                FROM user_dictionaries
                WHERE user_dictionaries.user_id = users.id
            ) as owned_dictionaries_count')
            ->selectRaw('(
                SELECT COUNT(*)
                FROM user_dictionary_word
                INNER JOIN user_dictionaries
                    ON user_dictionaries.id = user_dictionary_word.user_dictionary_id
                WHERE user_dictionaries.user_id = users.id
            ) as owned_words_count')
            ->selectRaw('(
                SELECT COUNT(*)
                FROM game_sessions
                WHERE game_sessions.user_id = users.id
                    AND game_sessions.finished_at IS NOT NULL
            ) + (
                SELECT COUNT(*)
                FROM telegram_game_runs
                WHERE telegram_game_runs.user_id = users.id
                    AND telegram_game_runs.finished_at IS NOT NULL
            ) as completed_sessions_count')
            ->selectRaw("
                CASE
                    WHEN (($gameCorrectAnswersSql) + ($telegramCorrectAnswersSql) + ($gameIncorrectAnswersSql) + ($telegramIncorrectAnswersSql)) = 0
                        THEN NULL
                    ELSE ROUND(
                        ((($gameCorrectAnswersSql) + ($telegramCorrectAnswersSql)) * 100.0)
                        / ((($gameCorrectAnswersSql) + ($telegramCorrectAnswersSql)) + (($gameIncorrectAnswersSql) + ($telegramIncorrectAnswersSql))),
                        0
                    )
                END as accuracy_percentage
            ");

        if ($userEmailFilter !== '') {
            $users->whereRaw('LOWER(users.email) LIKE ?', ['%'.mb_strtolower($userEmailFilter).'%']);
        }

        $users = $users
            ->orderBy($userSort['column'], $userSort['direction'])
            ->paginate(20, ['*'], 'users_page')
            ->withQueryString();

        $dictionaries = UserDictionary::query()
            ->select('user_dictionaries.*')
            ->with('owner:id,email')
            ->withCount('words')
            ->selectRaw('(
                SELECT users.email
                FROM users
                WHERE users.id = user_dictionaries.user_id
            ) as owner_email');

        if ($userDictionaryNameFilter !== '') {
            $dictionaries->whereRaw('LOWER(user_dictionaries.name) LIKE ?', ['%'.mb_strtolower($userDictionaryNameFilter).'%']);
        }

        $dictionaries = $dictionaries
            ->orderBy($dictionarySort['column'], $dictionarySort['direction'])
            ->paginate(20, ['*'], 'dictionaries_page')
            ->withQueryString();

        $readyDictionaries = ReadyDictionary::query()
            ->select('ready_dictionaries.*')
            ->withCount('words');

        if ($readyDictionaryNameFilter !== '') {
            $readyDictionaries->whereRaw('LOWER(ready_dictionaries.name) LIKE ?', ['%'.mb_strtolower($readyDictionaryNameFilter).'%']);
        }

        $readyDictionaries = $readyDictionaries
            ->orderBy($readyDictionarySort['column'], $readyDictionarySort['direction'])
            ->paginate(20, ['*'], 'ready_dictionaries_page')
            ->withQueryString();

        return view('admin.index', [
            'users' => $users,
            'dictionaries' => $dictionaries,
            'readyDictionaries' => $readyDictionaries,
            'sorts' => [
                'users' => $userSort,
                'dictionaries' => $dictionarySort,
                'ready_dictionaries' => $readyDictionarySort,
            ],
            'filters' => [
                'user_email' => $userEmailFilter,
                'user_dictionary_name' => $userDictionaryNameFilter,
                'ready_dictionary_name' => $readyDictionaryNameFilter,
            ],
            'adminEmail' => 'harumimax@gmail.com',
            'activeNav' => 'admin',
        ] + $headerNavigationService->forUser(auth()->user()));
    }

    public function destroyUser(User $user): RedirectResponse
    {
        if (mb_strtolower((string) $user->email) === 'harumimax@gmail.com') {
            return redirect()
                ->route('admin.index')
                ->with('admin_flash', [
                    'type' => 'warning',
                    'message' => __('admin.flash.admin_user_protected'),
                ]);
        }

        $user->delete();

        return redirect()
            ->route('admin.index')
            ->with('admin_flash', [
                'type' => 'success',
                'message' => __('admin.flash.user_deleted', ['email' => $user->email]),
            ]);
    }

    public function destroyDictionary(UserDictionary $dictionary): RedirectResponse
    {
        $dictionaryName = $dictionary->name;

        $dictionary->delete();

        return redirect()
            ->route('admin.index')
            ->with('admin_flash', [
                'type' => 'success',
                'message' => __('admin.flash.dictionary_deleted', ['name' => $dictionaryName]),
            ]);
    }

    public function destroyReadyDictionary(ReadyDictionary $readyDictionary): RedirectResponse
    {
        $dictionaryName = $readyDictionary->name;

        $readyDictionary->delete();

        return redirect()
            ->route('admin.index')
            ->with('admin_flash', [
                'type' => 'success',
                'message' => __('admin.flash.ready_dictionary_deleted', ['name' => $dictionaryName]),
            ]);
    }

    /**
     * @param array<string, string> $allowedSorts
     * @return array{field:string,column:string,direction:string}
     */
    private function resolveSort(
        string $requestedField,
        string $requestedDirection,
        array $allowedSorts,
        string $defaultField,
        string $defaultDirection,
    ): array {
        $field = array_key_exists($requestedField, $allowedSorts) ? $requestedField : $defaultField;
        $direction = strtolower($requestedDirection) === 'asc' ? 'asc' : 'desc';

        if ($requestedField === '') {
            $direction = $defaultDirection;
        }

        return [
            'field' => $field,
            'column' => $allowedSorts[$field],
            'direction' => $direction,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function userSorts(): array
    {
        return [
            'email' => 'users.email',
            'created_at' => 'users.created_at',
            'total_words' => 'owned_words_count',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function dictionarySorts(): array
    {
        return [
            'owner_email' => 'owner_email',
            'created_at' => 'user_dictionaries.created_at',
            'word_count' => 'words_count',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function readyDictionarySorts(): array
    {
        return [
            'created_at' => 'ready_dictionaries.created_at',
            'word_count' => 'words_count',
        ];
    }

    private function gameCorrectAnswersSql(): string
    {
        return "
            SELECT COALESCE(SUM(game_sessions.correct_answers), 0)
            FROM game_sessions
            WHERE game_sessions.user_id = users.id
                AND game_sessions.finished_at IS NOT NULL
        ";
    }

    private function telegramCorrectAnswersSql(): string
    {
        return "
            SELECT COALESCE(SUM(telegram_game_runs.correct_answers), 0)
            FROM telegram_game_runs
            WHERE telegram_game_runs.user_id = users.id
                AND telegram_game_runs.finished_at IS NOT NULL
        ";
    }

    private function gameIncorrectAnswersSql(): string
    {
        return "
            SELECT COALESCE(SUM(game_sessions.total_words - game_sessions.correct_answers), 0)
            FROM game_sessions
            WHERE game_sessions.user_id = users.id
                AND game_sessions.finished_at IS NOT NULL
        ";
    }

    private function telegramIncorrectAnswersSql(): string
    {
        return "
            SELECT COALESCE(SUM(telegram_game_runs.incorrect_answers), 0)
            FROM telegram_game_runs
            WHERE telegram_game_runs.user_id = users.id
                AND telegram_game_runs.finished_at IS NOT NULL
        ";
    }
}
