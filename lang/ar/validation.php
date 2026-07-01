<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'يجب قبول الحقل :attribute.',
    'accepted_if' => 'يجب قبول الحقل :attribute عندما يكون :other هو :value.',
    'active_url' => 'يجب أن يكون الحقل :attribute عنوان URL صالحًا.',
    'after' => 'يجب أن يكون الحقل :attribute تاريخًا بعد :date.',
    'after_or_equal' => 'يجب أن يكون الحقل :attribute تاريخًا بعد أو يساوي :date.',
    'alpha' => 'يجب أن يحتوي الحقل :attribute على حروف فقط.',
    'alpha_dash' => 'يجب أن يحتوي الحقل :attribute على حروف وأرقام وشرطات وشرطات سفلية فقط.',
    'alpha_num' => 'يجب أن يحتوي الحقل :attribute على حروف وأرقام فقط.',
    'array' => 'يجب أن يكون الحقل :attribute مصفوفة.',
    'ascii' => 'يجب أن يحتوي الحقل :attribute على أحرف أبجدية رقمية أحادية البايت ورموز فقط.',
    'before' => 'يجب أن يكون الحقل :attribute تاريخًا قبل :date.',
    'before_or_equal' => 'يجب أن يكون الحقل :attribute تاريخًا قبل أو يساوي :date.',
    'between' => [
        'array' => 'يجب أن يحتوي الحقل :attribute على عناصر بين :min و :max.',
        'file' => 'يجب أن يكون الحقل :attribute بين :min و :max كيلوبايت.',
        'numeric' => 'يجب أن يكون الحقل :attribute بين :min و :max.',
        'string' => 'يجب أن يكون الحقل :attribute بين :min و :max حرفًا.',
    ],
    'boolean' => 'يجب أن يكون الحقل :attribute صحيحًا أو خطأ.',
    'can' => 'يحتوي الحقل :attribute على قيمة غير مسموح بها.',
    'confirmed' => 'تأكيد الحقل :attribute لا يتطابق.',
    'current_password' => 'كلمة المرور غير صحيحة.',
    'date' => 'يجب أن يكون الحقل :attribute تاريخًا صالحًا.',
    'date_equals' => 'يجب أن يكون الحقل :attribute تاريخًا يساوي :date.',
    'date_format' => 'يجب أن يتطابق الحقل :attribute مع التنسيق :format.',
    'decimal' => 'يجب أن يحتوي الحقل :attribute على :decimal منازل عشرية.',
    'declined' => 'يجب رفض الحقل :attribute.',
    'declined_if' => 'يجب رفض الحقل :attribute عندما يكون :other هو :value.',
    'different' => 'يجب أن يكون الحقل :attribute و :other مختلفين.',
    'digits' => 'يجب أن يحتوي الحقل :attribute على :digits رقمًا.',
    'digits_between' => 'يجب أن يحتوي الحقل :attribute بين :min و :max رقمًا.',
    'dimensions' => 'يحتوي الحقل :attribute على أبعاد صورة غير صالحة.',
    'distinct' => 'يحتوي الحقل :attribute على قيمة مكررة.',
    'doesnt_end_with' => 'يجب ألا ينتهي الحقل :attribute بواحدة من القيم التالية: :values.',
    'doesnt_start_with' => 'يجب ألا يبدأ الحقل :attribute بواحدة من القيم التالية: :values.',
    'email' => 'يجب أن يكون الحقل :attribute عنوان بريد إلكتروني صالحًا.',
    'ends_with' => 'يجب أن ينتهي الحقل :attribute بواحدة من القيم التالية: :values.',
    'enum' => 'الحقل :attribute المختار غير صالح.',
    'exists' => 'الحقل :attribute المختار غير صالح.',
    'extensions' => 'يجب أن يكون الحقل :attribute ملفًا من نوع: :values.',
    'file' => 'يجب أن يكون الحقل :attribute ملفًا.',
    'filled' => 'يجب أن يحتوي الحقل :attribute على قيمة.',
    'gt' => [
        'array' => 'يجب أن يحتوي الحقل :attribute على أكثر من :value عنصرًا.',
        'file' => 'يجب أن يكون الحقل :attribute أكبر من :value كيلوبايت.',
        'numeric' => 'يجب أن يكون الحقل :attribute أكبر من :value.',
        'string' => 'يجب أن يكون الحقل :attribute أكبر من :value حرفًا.',
    ],
    'gte' => [
        'array' => 'يجب أن يحتوي الحقل :attribute على :value عنصرًا أو أكثر.',
        'file' => 'يجب أن يكون الحقل :attribute أكبر من أو يساوي :value كيلوبايت.',
        'numeric' => 'يجب أن يكون الحقل :attribute أكبر من أو يساوي :value.',
        'string' => 'يجب أن يكون الحقل :attribute أكبر من أو يساوي :value حرفًا.',
    ],
    'hex_color' => 'يجب أن يكون الحقل :attribute لونًا ست عشريًا صالحًا.',
    'image' => 'يجب أن يكون الحقل :attribute صورة.',
    'in' => 'الحقل :attribute المختار غير صالح.',
    'in_array' => 'يجب أن يكون الحقل :attribute موجودًا في :other.',
    'integer' => 'يجب أن يكون الحقل :attribute عددًا صحيحًا.',
    'ip' => 'يجب أن يكون الحقل :attribute عنوان IP صالحًا.',
    'ipv4' => 'يجب أن يكون الحقل :attribute عنوان IPv4 صالحًا.',
    'ipv6' => 'يجب أن يكون الحقل :attribute عنوان IPv6 صالحًا.',
    'json' => 'يجب أن يكون الحقل :attribute سلسلة JSON صالحة.',
    'lowercase' => 'يجب أن يكون الحقل :attribute أحرفًا صغيرة.',
    'lt' => [
        'array' => 'يجب أن يحتوي الحقل :attribute على أقل من :value عنصرًا.',
        'file' => 'يجب أن يكون الحقل :attribute أقل من :value كيلوبايت.',
        'numeric' => 'يجب أن يكون الحقل :attribute أقل من :value.',
        'string' => 'يجب أن يكون الحقل :attribute أقل من :value حرفًا.',
    ],
    'lte' => [
        'array' => 'يجب ألا يحتوي الحقل :attribute على أكثر من :value عنصرًا.',
        'file' => 'يجب أن يكون الحقل :attribute أقل من أو يساوي :value كيلوبايت.',
        'numeric' => 'يجب أن يكون الحقل :attribute أقل من أو يساوي :value.',
        'string' => 'يجب أن يكون الحقل :attribute أقل من أو يساوي :value حرفًا.',
    ],
    'mac_address' => 'يجب أن يكون الحقل :attribute عنوان MAC صالحًا.',
    'max' => [
        'array' => 'يجب ألا يحتوي الحقل :attribute على أكثر من :max عنصرًا.',
        'file' => 'يجب ألا يكون الحقل :attribute أكبر من :max كيلوبايت.',
        'numeric' => 'يجب ألا يكون الحقل :attribute أكبر من :max.',
        'string' => 'يجب ألا يكون الحقل :attribute أكبر من :max حرفًا.',
    ],
    'max_digits' => 'يجب ألا يحتوي الحقل :attribute على أكثر من :max رقمًا.',
    'mimes' => 'يجب أن يكون الحقل :attribute ملفًا من نوع: :values.',
    'mimetypes' => 'يجب أن يكون الحقل :attribute ملفًا من نوع: :values.',
    'min' => [
        'array' => 'يجب أن يحتوي الحقل :attribute على الأقل على :min عنصرًا.',
        'file' => 'يجب ألا يكون الحقل :attribute أصغر من :min كيلوبايت.',
        'numeric' => 'يجب ألا يكون الحقل :attribute أصغر من :min.',
        'string' => 'يجب ألا يكون الحقل :attribute أصغر من :min حرفًا.',
    ],
    'min_digits' => 'يجب أن يحتوي الحقل :attribute على الأقل على :min رقمًا.',
    'missing' => 'يجب أن يكون الحقل :attribute مفقودًا.',
    'missing_if' => 'يجب أن يكون الحقل :attribute مفقودًا عندما يكون :other هو :value.',
    'missing_unless' => 'يجب أن يكون الحقل :attribute مفقودًا إلا إذا كان :other هو :value.',
    'missing_with' => 'يجب أن يكون الحقل :attribute مفقودًا عندما يكون :values موجودًا.',
    'missing_with_all' => 'يجب أن يكون الحقل :attribute مفقودًا عندما تكون :values موجودة.',
    'multiple_of' => 'يجب أن يكون الحقل :attribute مضاعفًا لـ :value.',
    'not_in' => 'الحقل :attribute المختار غير صالح.',
    'not_regex' => 'تنسيق الحقل :attribute غير صالح.',
    'numeric' => 'يجب أن يكون الحقل :attribute رقمًا.',
    'password' => [
        'letters' => 'يجب أن يحتوي الحقل :attribute على حرف واحد على الأقل.',
        'mixed' => 'يجب أن يحتوي الحقل :attribute على حرف كبير واحد على الأقل وحرف صغير واحد على الأقل.',
        'numbers' => 'يجب أن يحتوي الحقل :attribute على رقم واحد على الأقل.',
        'symbols' => 'يجب أن يحتوي الحقل :attribute على رمز واحد على الأقل.',
        'uncompromised' => 'الحقل :attribute المدخل قد ظهر في تسريب بيانات. يرجى اختيار حقل :attribute مختلف.',
    ],
    'present' => 'يجب أن يكون الحقل :attribute موجودًا.',
    'present_if' => 'يجب أن يكون الحقل :attribute موجودًا عندما يكون :other هو :value.',
    'present_unless' => 'يجب أن يكون الحقل :attribute موجودًا إلا إذا كان :other هو :value.',
    'present_with' => 'يجب أن يكون الحقل :attribute موجودًا عندما يكون :values موجودًا.',
    'present_with_all' => 'يجب أن يكون الحقل :attribute موجودًا عندما تكون :values موجودة.',
    'prohibited' => 'الحقل :attribute محظور.',
    'prohibited_if' => 'الحقل :attribute محظور عندما يكون :other هو :value.',
    'prohibited_unless' => 'الحقل :attribute محظور إلا إذا كان :other في :values.',
    'prohibits' => 'الحقل :attribute يمنع :other من أن يكون موجودًا.',
    'regex' => 'تنسيق الحقل :attribute غير صالح.',
    'required' => 'الحقل :attribute مطلوب.',
    'required_array_keys' => 'يجب أن يحتوي الحقل :attribute على إدخالات لـ: :values.',
    'required_if' => 'الحقل :attribute مطلوب عندما يكون :other هو :value.',
    'required_if_accepted' => 'الحقل :attribute مطلوب عندما يتم قبول :other.',
    'required_unless' => 'الحقل :attribute مطلوب إلا إذا كان :other في :values.',
    'required_with' => 'الحقل :attribute مطلوب عندما يكون :values موجودًا.',
    'required_with_all' => 'الحقل :attribute مطلوب عندما تكون :values موجودة.',
    'required_without' => 'الحقل :attribute مطلوب عندما لا يكون :values موجودًا.',
    'required_without_all' => 'الحقل :attribute مطلوب عندما لا تكون أي من :values موجودة.',
    'same' => 'يجب أن يتطابق الحقل :attribute مع :other.',
    'size' => [
        'array' => 'يجب أن يحتوي الحقل :attribute على :size عنصرًا.',
        'file' => 'يجب أن يكون الحقل :attribute :size كيلوبايت.',
        'numeric' => 'يجب أن يكون الحقل :attribute :size.',
        'string' => 'يجب أن يكون الحقل :attribute :size حرفًا.',
    ],
    'starts_with' => 'يجب أن يبدأ الحقل :attribute بواحدة من القيم التالية: :values.',
    'string' => 'يجب أن يكون الحقل :attribute سلسلة نصية.',
    'timezone' => 'يجب أن يكون الحقل :attribute منطقة زمنية صالحة.',
    'unique' => 'الحقل :attribute محجوز بالفعل.',
    'uploaded' => 'فشل في تحميل الحقل :attribute.',
    'uppercase' => 'يجب أن يكون الحقل :attribute أحرفًا كبيرة.',
    'url' => 'يجب أن يكون الحقل :attribute عنوان URL صالحًا.',
    'ulid' => 'يجب أن يكون الحقل :attribute ULID صالحًا.',
    'uuid' => 'يجب أن يكون الحقل :attribute UUID صالحًا.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'رسالة مخصصة',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'talents.*.subcategory_id' => 'التصنيف الفرعي',
        'talents.*.has_budget' => 'هل يوجد ميزانية',
        'talents.*.budget' => 'الميزانية',
        'name' => 'الاسم',
        'city_id' => 'المدينة',
        'description' => 'الوصف',
        'latitude' => 'خط العرض',
        'longitude' => 'خط الطول',
        'address_id' => 'العنوان',
        'categories.*.category_id' => 'التصنيف',
        'categories.*.subcategory_id' => 'التصنيف الفرعي',
        'categories.*.range_id' => 'نطاق السعر',
        'to_user_id' => 'المستخدم المرسل إليه',
        'type' => 'نوع الرسالة',
        'message' => 'الرسالة',
        'reply_to' => 'الرد على الرسالة',
        'gallery_id' => 'معرّف المعرض',
        'video' => 'الفيديو',
        'is_pin' => 'مثبت',
        'order_id' => 'معرّف الطلب',
        'subcategory_id' => 'معرّف التصنيف الفرعي',
        'cost' => 'التكلفة',
        'start_date' => 'تاريخ البدء',
        'end_date' => 'تاريخ الانتهاء',
        'talents' => 'المواهب',
        'code' => 'رمز الخصم',
        'offer_id' => 'معرّف العرض',
        'reason' => 'السبب',
        'support_id' => 'معرّف الدعم',
        'phone' => 'رقم الهاتف',
        'email' => 'البريد الإلكتروني',
        'stars' => 'عدد النجوم',
        'notes' => 'الملاحظات',
        'verification_code' => 'رمز التحقق',
        'dob' => 'تاريخ الميلاد',
        'gender' => 'الجنس',
        'vat_number' => 'رقم ضريبة القيمة المضافة',
        'cr_number' => 'رقم السجل التجاري',
        'iban' => 'رقم IBAN',
        'password' => 'كلمة المرور',
        'user_id' => 'معرّف المستخدم',

    ],

];
