# تطبيق سواعد الموظفين — Android WebView

تطبيق Android أصلي خفيف يفتح وضع الموظف الآمن على:

`https://ss.sawaedarab.com/index.php?mobile=employee`

يدعم جلسة الدخول، الكاميرا المطلوبة للتحقق، الموقع الجغرافي للحضور، اختيار ورفع الملفات، التنزيل، زر الرجوع، ومنع الروابط غير التابعة للنطاق من العمل داخل WebView.

## إنشاء APK

1. افتح هذا المجلد في Android Studio (JDK 17).
2. انتظر تنزيل Android SDK 35 وGradle واعتماد المشروع.
3. للاختبار اختر `Build > Build APK(s)`؛ الناتج في `app/build/outputs/apk/debug/app-debug.apk`.
4. للإصدار المؤسسي اختر `Build > Generate Signed Bundle / APK` وأنشئ مفتاح توقيع خاصًا بالشركة. لا ترفع ملف المفتاح أو كلمة مروره ضمن مشروع النظام.

معرّف التطبيق: `com.sawaedarab.employee`، والحد الأدنى Android 6.0. يجب أن تبقى شهادة HTTPS للنطاق صالحة؛ التطبيق يرفض تجاوز أخطاء الشهادة والاتصالات غير المشفرة.
