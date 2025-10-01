<?php

return [

    'backup' => [
        /*
         * اسم المجلد الذي سيُحفظ بداخله النسخ على القرص المحدد.
         * سنستخدمه أيضًا في المراقبة. (سيصبح: storage/app/backups)
         */
        'name' => env('BACKUP_FOLDER', 'backups'),

        'source' => [
            'files' => [
                /*
                 * مسارات الملفات/المجلدات التي ستدخل في النسخة.
                 */
                'include' => [
                    base_path(),
                ],

                /*
                 * مسارات مستثناة من النسخ.
                 * (منع تضمين vendor / node_modules / ملفات الكاش واللوجز / مجلد النسخ نفسه)
                 */
                'exclude' => [
                    base_path('vendor'),
                    base_path('node_modules'),
                    storage_path('framework'),
                    storage_path('logs'),
                    storage_path('app/backups'),
                ],

                'follow_links' => false,
                'ignore_unreadable_directories' => false,

                /*
                 * اجعله null ليتم تضمين المسارات كاملة داخل الأرشيف.
                 */
                'relative_path' => null,
            ],

            /*
             * قواعد البيانات المطلوب أخذ نسخة لها.
             */
            'databases' => [
                env('DB_CONNECTION', 'mysql'),
            ],
        ],

        'database_dump_compressor' => null,
        'database_dump_file_timestamp_format' => null, // خله null لأننا سنضع بادئة للملف النهائي
        'database_dump_filename_base' => 'database',
        'database_dump_file_extension' => '',

        'destination' => [
            /*
             * طريقة الضغط داخل الأرشيف (ZipArchive).
             */
            'compression_method' => \ZipArchive::CM_DEFAULT,
            'compression_level' => 9,

            /*
             * بادئة لاسم ملف النسخة النهائي.
             */
            'filename_prefix' => 'daily-',

            /*
             * الأقراص التي تُحفظ عليها النسخ (هنا القرص local => storage/app).
             * سيُنشئ مجلدًا باسم backup.name (backups) بداخله.
             */
            'disks' => [
                'local',
            ],
        ],

        /*
         * مجلد مؤقت أثناء توليد النسخة.
         */
        'temporary_directory' => storage_path('app/backup-temp'),

        /*
         * كلمة مرور لتشفير الأرشيف (اختياري).
         */
        'password' => env('BACKUP_ARCHIVE_PASSWORD'),

        /*
         * خوارزمية التشفير. 'default' تستخدم AES-256 إذا كان مدعومًا.
         */
        'encryption' => 'default',

        'tries' => 1,
        'retry_delay' => 0,
    ],

    /*
     * الإشعارات (اتركها كما هي أو حدّث البريد من env).
     */
    'notifications' => [
        'notifications' => [
            \Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification::class => ['mail'],
        ],

        'notifiable' => \Spatie\Backup\Notifications\Notifiable::class,

        'mail' => [
            'to' => 'your@example.com',
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                'name' => env('MAIL_FROM_NAME', 'Example'),
            ],
        ],

        'slack' => [
            'webhook_url' => '',
            'channel' => null,
            'username' => null,
            'icon' => null,
        ],

        'discord' => [
            'webhook_url' => '',
            'username' => '',
            'avatar_url' => '',
        ],
    ],

    /*
     * مراقبة صحة النسخ.
     */
    'monitor_backups' => [
        [
            'name' => env('BACKUP_FOLDER', 'backups'),
            'disks' => ['local'],
            'health_checks' => [
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays::class => 1,
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes::class => 5000,
            ],
        ],
    ],

    'cleanup' => [
        'strategy' => \Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy::class,

        'default_strategy' => [
            'keep_all_backups_for_days' => 7,
            'keep_daily_backups_for_days' => 16,
            'keep_weekly_backups_for_weeks' => 8,
            'keep_monthly_backups_for_months' => 4,
            'keep_yearly_backups_for_years' => 2,
            'delete_oldest_backups_when_using_more_megabytes_than' => 5000,
        ],

        'tries' => 1,
        'retry_delay' => 0,
    ],

];
