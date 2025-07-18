<?php

return [
    'nav_title' => 'Яйця',
    'model_label' => 'Яйце',
    'model_label_plural' => 'Яйця',
    'tabs' => [
        'configuration' => 'Налаштування',
        'process_management' => 'Керування процесом',
        'egg_variables' => 'Змінні яйця',
        'install_script' => 'Скрипт встановлення',
    ],
    'import' => [
        'file' => 'Файл',
        'url' => 'URL',
        'egg_help' => 'Це має бути безпосередньо .json файл (наприклад, egg-minecraft.json)',
        'url_help' => 'URL-адреси повинні вказувати безпосередньо до файлу .json',
        'add_url' => 'Нова URL-адреса',
        'import_failed' => 'Помилка імпорту',
        'import_success' => 'Імпорт успішний',
        'github' => 'Додати з Github',
        'refresh' => 'Оновити',
    ],
    'in_use' => 'Використовується',
    'servers' => 'Сервери',
    'name' => 'Назва',
    'egg_uuid' => 'UUID яйця',
    'egg_id' => 'ID яйця',
    'name_help' => 'Просте, зрозуміле ім’я, яке буде використовуватися як ідентифікатор цього яйця.',
    'author' => 'Автор',
    'uuid_help' => 'Це глобально унікальний ідентифікатор цього яйця, який використовується у Wings.',
    'author_help' => 'Автор цієї версії яйця.',
    'author_help_edit' => 'Автор цієї версії яйця. Завантаження нової конфігурації від іншого автора змінить це поле.',
    'description' => 'Опис',
    'description_help' => 'Опис цього яйця, який буде відображатися у панелі за потреби.',
    'startup' => 'Команда запуску',
    'startup_help' => 'Команда запуску за замовчуванням для нових серверів, які використовують це яйце.',
    'file_denylist' => 'Список заборонених файлів',
    'file_denylist_help' => 'Список файлів, які користувач не може редагувати.',
    'features' => 'Функції',
    'force_ip' => 'Примусова вихідна IP-адреса',
    'force_ip_help' => 'Примушує весь вихідний трафік мати IP-джерело, яке відповідає основному виділеному IP сервера. Необхідно для деяких ігор, якщо вузол має кілька публічних IP-адрес. Увімкнення цього параметра вимкне внутрішню мережу для серверів, які використовують це яйце, через що вони не зможуть підключатися до інших серверів на тому ж вузлі.',
    'tags' => 'Теги',
    'update_url' => 'URL-адреса оновлення',
    'update_url_help' => 'URL-адреси повинні вказувати безпосередньо до файлу .json',
    'add_image' => 'Додати Docker зображення',
    'docker_images' => 'Docker зображення',
    'docker_name' => 'Назва зображення',
    'docker_uri' => 'URI зображення',
    'docker_help' => 'Docker зображення, доступні для серверів, що використовують це яйце.',

    'stop_command' => 'Команда зупинки',
    'stop_command_help' => 'Команда, яка надсилається процесу сервера для його коректного завершення. Якщо потрібно надіслати SIGINT, введіть тут ^C.',
    'copy_from' => 'Скопіювати налаштування з',
    'copy_from_help' => 'Якщо ви хочете використовувати налаштування іншого яйця за замовчуванням, виберіть його зі списку вище.',
    'none' => 'Нічого',
    'start_config' => 'Конфігурація запуску',
    'start_config_help' => 'Список значень, які Daemon має перевіряти при запуску сервера для визначення його готовності.',
    'config_files' => 'Конфігураційні файли',
    'config_files_help' => 'JSON-репрезентація конфігураційних файлів для зміни та частин, які потрібно змінити.',
    'log_config' => 'Конфігурація журналу',
    'log_config_help' => 'JSON-репрезентація місць збереження логів і того, чи повинен Daemon створювати власні логи.',

    'environment_variable' => 'Змінна середовища',
    'default_value' => 'Значення за замовчуванням',
    'user_permissions' => 'Дозволи користувача',
    'viewable' => 'Доступний для перегляду',
    'editable' => 'Доступний для редагування',
    'rules' => 'Правила',
    'add_new_variable' => 'Додати нову змінну',

    'error_unique' => 'Змінна з таким ім\'ям уже існує.',
    'error_required' => 'Поле змінної середовища є обов\'язковим.',
    'error_reserved' => 'Ця змінна середовища зарезервована і не може бути використана.',

    'script_from' => 'Скрипт із',
    'script_container' => 'Контейнер скрипту',
    'script_entry' => 'Точка входу скрипту',
    'script_install' => 'Скрипт встановлення',
    'no_eggs' => 'Немає яєць',
    'no_servers' => 'Немає серверів',
    'no_servers_help' => 'Жоден сервер не призначено цьому яйцю.',

    'update' => 'Оновити|Оновити вибране',
    'updated' => 'Яйце оновлено|:count/:total Яйця оновлено',
    'updated_failed' => 'Невдало :count',
    'update_question' => 'Ви впевнені, що хочете оновити це яйце?|Ви впевнені, що хочете оновити вибрані яйця?',
    'update_description' => 'Якщо ви внесли будь-які зміни в яйце, вони будуть перезаписані!|Якщо ви внесли будь-які зміни в яйця, вони будуть перезаписані!',
    'no_updates' => 'Немає оновлень для вибраних яєць',
];
