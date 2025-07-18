<?php

return [
    'appsettings' => [
        'comment' => [
            'author' => 'قم بتوفير عنوان البريد الإلكتروني الذي يجب أن تصدر منه البيوض المصدرة بواسطة هذا اللوحة. يجب أن يكون عنوان بريد إلكتروني صالحًا.',
            'url' => 'يجب أن يبدأ عنوان URL للتطبيق بـ https:// أو http:// حسب استخدامك لـ SSL أم لا. إذا لم تقم بتضمين المخطط، فقد يتم ربط رسائل البريد الإلكتروني والمحتوى الآخر بموقع غير صحيح.',
            'timezone' => 'يجب أن تتطابق المنطقة الزمنية مع إحدى المناطق الزمنية المدعومة من PHP. إذا كنت غير متأكد، يرجى الرجوع إلى https://php.net/manual/en/timezones.php.',
        ],
        'redis' => [
            'note' => 'لقد اخترت برنامج Redis لسائق واحد أو أكثر، يرجى تقديم معلومات اتصال صالحة أدناه. في معظم الحالات، يمكنك استخدام الإعدادات الافتراضية ما لم تكن قد عدلت إعدادك.',
            'comment' => 'بشكل افتراضي، يكون اسم المستخدم الافتراضي لخادم Redis بدون كلمة مرور لأنه يعمل محليًا وغير متاح للعالم الخارجي. إذا كان هذا هو الحال، فقط اضغط على Enter دون إدخال قيمة.',
            'confirm' => 'يبدو أن هناك قيمة :field محددة بالفعل لـ Redis، هل ترغب في تغييرها؟',
        ],
    ],
    'database_settings' => [
        'DB_HOST_note' => 'يُوصى بشدة بعدم استخدام "localhost" كمضيف قاعدة البيانات، حيث لاحظنا مشكلات متكررة في اتصال المقبس. إذا كنت تريد استخدام اتصال محلي، فيجب أن تستخدم "127.0.0.1".',
        'DB_USERNAME_note' => 'استخدام حساب الجذر لاتصالات MySQL ليس فقط مرفوضًا بشدة، ولكنه غير مسموح به في هذا التطبيق. ستحتاج إلى إنشاء مستخدم MySQL لهذا البرنامج.',
        'DB_PASSWORD_note' => 'يبدو أن لديك بالفعل كلمة مرور اتصال MySQL محددة، هل ترغب في تغييرها؟',
        'DB_error_2' => 'لم يتم حفظ بيانات الاعتماد الخاصة باتصالك. ستحتاج إلى تقديم معلومات اتصال صالحة قبل المتابعة.',
        'go_back' => 'العودة والمحاولة مرة أخرى',
    ],
    'make_node' => [
        'name' => 'أدخل معرفًا قصيرًا لتمييز هذه العقدة عن غيرها',
        'description' => 'أدخل وصفًا لتحديد العقدة',
        'scheme' => 'يرجى إدخال https لاستخدام SSL أو http لاتصال غير مشفر',
        'fqdn' => 'أدخل اسم النطاق (مثل node.example.com) ليتم استخدامه للاتصال بالـ Daemon. يمكن استخدام عنوان IP فقط إذا لم تكن تستخدم SSL لهذه العقدة.',
        'public' => 'هل يجب أن تكون هذه العقدة عامة؟ ملاحظة: تعيين العقدة كخاصة سيمنع إمكانية النشر التلقائي لهذه العقدة.',
        'behind_proxy' => 'هل اسم النطاق الخاص بك خلف وكيل؟',
        'maintenance_mode' => 'هل يجب تمكين وضع الصيانة؟',
        'memory' => 'أدخل الحد الأقصى للذاكرة',
        'memory_overallocate' => 'أدخل مقدار الذاكرة المطلوب تجاوزه، -1 سيعطل الفحص و 0 سيمنع إنشاء خوادم جديدة',
        'disk' => 'أدخل الحد الأقصى لمساحة القرص',
        'disk_overallocate' => 'أدخل مقدار القرص المطلوب تجاوزه، -1 سيعطل الفحص و 0 سيمنع إنشاء خوادم جديدة',
        'cpu' => 'أدخل الحد الأقصى لاستخدام المعالج',
        'cpu_overallocate' => 'أدخل مقدار تجاوز استخدام المعالج، -1 سيعطل الفحص و 0 سيمنع إنشاء خوادم جديدة',
        'upload_size' => 'أدخل الحد الأقصى لحجم التحميل',
        'daemonListen' => 'أدخل منفذ استماع الـ Daemon',
        'daemonSFTP' => 'أدخل منفذ استماع SFTP لـ Daemon',
        'daemonSFTPAlias' => 'أدخل اسم مستعار لـ SFTP (يمكن أن يكون فارغًا)',
        'daemonBase' => 'أدخل المجلد الأساسي',
        'success' => 'تم إنشاء عقدة جديدة بنجاح بالاسم :name ومعرفها :id',
    ],
    'node_config' => [
        'error_not_exist' => 'العقدة المحددة غير موجودة.',
        'error_invalid_format' => 'تنسيق غير صالح محدد. الخيارات الصالحة هي yaml و json.',
    ],
    'key_generate' => [
        'error_already_exist' => 'يبدو أنك قمت بالفعل بتكوين مفتاح تشفير التطبيق. المتابعة مع هذه العملية ستؤدي إلى استبدال هذا المفتاح وقد تسبب في تلف البيانات المشفرة الموجودة. لا تتابع ما لم تكن متأكدًا مما تفعله.',
        'understand' => 'أفهم عواقب تنفيذ هذا الأمر وأتحمل كامل المسؤولية عن فقدان البيانات المشفرة.',
        'continue' => 'هل أنت متأكد أنك تريد المتابعة؟ تغيير مفتاح تشفير التطبيق سيسبب فقدان البيانات.',
    ],
    'schedule' => [
        'process' => [
            'no_tasks' => 'لا توجد مهام مجدولة للخوادم تحتاج إلى التشغيل.',
            'error_message' => 'حدث خطأ أثناء معالجة الجدولة: ',
        ],
    ],
    'upgrade' => [
        'integrity' => 'هذا الأمر لا يتحقق من سلامة الأصول التي تم تنزيلها. يرجى التأكد من أنك تثق في مصدر التنزيل قبل المتابعة. إذا كنت لا ترغب في تنزيل أرشيف، يرجى تحديد ذلك باستخدام العلامة --skip-download، أو الإجابة بـ "لا" على السؤال أدناه.',
        'source_url' => 'مصدر التنزيل (يتم تعيينه باستخدام --url=):',
        'php_version' => 'تعذر تنفيذ عملية الترقية الذاتية. الحد الأدنى المطلوب لإصدار PHP هو 7.4.0، لديك',
        'skipDownload' => 'هل ترغب في تنزيل واستخراج ملفات الأرشيف لأحدث إصدار؟',
        'webserver_user' => 'تم اكتشاف مستخدم خادم الويب الخاص بك على أنه <fg=blue>[{:user}]:</> هل هذا صحيح؟',
        'name_webserver' => 'يرجى إدخال اسم المستخدم الذي يشغل عملية خادم الويب لديك. يختلف هذا من نظام إلى آخر، لكنه عادةً يكون "www-data"، "nginx"، أو "apache".',
        'group_webserver' => 'تم اكتشاف مجموعة خادم الويب الخاصة بك على أنها <fg=blue>[{:group}]:</> هل هذا صحيح؟',
        'group_webserver_question' => 'يرجى إدخال اسم المجموعة التي تشغل عملية خادم الويب لديك. عادةً ما تكون هي نفس اسم المستخدم.',
        'are_your_sure' => 'هل أنت متأكد أنك تريد تنفيذ عملية الترقية للوحة التحكم؟',
        'terminated' => 'تم إنهاء عملية الترقية بواسطة المستخدم.',
        'success' => 'تم ترقية اللوحة بنجاح. يرجى التأكد من تحديث أي مثيلات Daemon أيضًا.',

    ],
];
