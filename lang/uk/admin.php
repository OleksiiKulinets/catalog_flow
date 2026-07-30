<?php

return [

    'nav' => [
        'menu' => 'Меню',
        'dashboard' => 'Головна',
        'batches' => 'Батчі',
        'settings' => 'Налаштування',
        'log_out' => 'Вийти',
    ],

    'welcome' => [
        'nav_login' => 'Увійти',
        'nav_register' => 'Почати',
        'eyebrow' => 'Пакетна обробка даних на базі ШІ',
        'title' => 'Перетворюйте таблиці на готовий результат — масово.',
        'subtitle' => 'Завантажте дані, опишіть, що потрібно зробити, і ШІ обробить кожен рядок за вас.',
        'cta_primary' => 'Почати безкоштовно',
        'cta_secondary' => 'Увійти',
        'feature_upload' => [
            'title' => 'Завантажте будь-які дані',
            'text' => 'XLSX, CSV, JSON або посилання на Google Sheets.',
        ],
        'feature_describe' => [
            'title' => 'Опишіть зміни',
            'text' => 'Скажіть, що зробити, звичайною мовою.',
        ],
        'feature_results' => [
            'title' => 'Отримайте структурований результат',
            'text' => 'Завантажте чистий, готовий до використання файл.',
        ],
    ],

    'palette' => [
        'title' => 'Швидкий перехід',
        'hint_select' => 'Натисніть цифру для переходу',
        'hint_close' => 'Esc, щоб закрити',
    ],

    'common' => [
        'name' => "Ім'я",
        'email' => 'Email',
        'password' => 'Пароль',
        'confirm_password' => 'Підтвердження пароля',
        'save' => 'Зберегти',
        'saved' => 'Збережено.',
        'cancel' => 'Скасувати',
    ],

    'dashboard' => [
        'title' => 'Головна',
        'create_project' => 'Створити проєкт',
        'stats' => [
            'projects' => 'Проєкти',
            'projects_subtitle' => 'Усього проєктів',
            'running' => 'Виконується',
            'running_subtitle' => 'У процесі',
            'completed_today' => 'Завершено сьогодні',
            'completed_today_subtitle' => 'Завершено сьогодні',
            'failed' => 'Помилки',
            'failed_subtitle' => 'Батчів з помилками',
        ],
        'activity_labels' => [
            'draft' => 'Проєкт створено',
            'uploading' => 'Завантаження набору даних',
            'queued' => 'Батч у черзі',
            'in_progress' => 'Батч виконується',
            'finalizing' => 'Батч завершується',
            'completed' => 'Батч завершено',
            'failed' => 'Батч не виконано',
            'expired' => 'Термін батчу вичерпано',
            'cancelling' => 'Скасування батчу',
            'cancelled' => 'Батч скасовано',
        ],
        'ai_activity' => [
            'title' => 'Активність AI',
            'subtitle' => 'Активність за останні :weeks тижнів.',
            'less' => 'Менше',
            'more' => 'Більше',
            'total_requests' => 'Усього запитів',
            'projects_created' => 'Створено проєктів',
            'batches_completed' => 'Завершено батчів',
            'batches_failed' => 'Батчів з помилками',
        ],
        'activity_feed' => [
            'title' => 'Стрічка активності',
            'empty' => 'Ще немає активності.',
            'view_all' => 'Уся активність',
        ],
        'running' => [
            'title' => 'Батчі в роботі',
            'empty' => 'Зараз нічого не виконується.',
        ],
        'recent' => [
            'title' => 'Останні проєкти',
            'empty' => 'Ще немає проєктів — створіть перший, щоб почати.',
            'more' => 'Переглянути всі',
        ],
    ],

];
