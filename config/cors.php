<?php

/*
 * CORS لواجهة التطبيق: الدخول برمز Bearer لا بملفات تعريف، فالأصول كلها
 * مقبولة افتراضيًا (معاينة الويب للتطبيق وأدوات التطوير). CORS_ALLOWED_ORIGINS
 * يقيّدها بقائمة مفصولة بفواصل إن لزم. ETag و Idempotency-Replayed مكشوفتان
 * ليقرأهما تطبيق الويب.
 */
return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', '*'))))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['ETag', 'Idempotency-Replayed'],

    'max_age' => 3600,

    'supports_credentials' => false,

];
