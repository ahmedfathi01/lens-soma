// Add URL validation function at the top of the file
function validateRedirectUrl(url) {
    // إذا كانت القيمة فارغة، ارجع المسار الافتراضي الآمن
    if (!url) return '/appointments';

    // تحقق أولاً من مسارات داخلية محددة بشكل صريح (whitelist)
    const safeInternalPaths = ['/cart', '/appointments', '/products', '/account', '/'];
    if (safeInternalPaths.includes(url)) {
        return url;
    }

    try {
        // للروابط المطلقة، تحقق مما إذا كانت تنتمي لنطاقنا
        if (url.indexOf('://') > -1 || url.indexOf('//') === 0) {
            // حظر URL مطلقة خارجية تمامًا
            console.error('تم حظر محاولة إعادة توجيه خارجية:', url);
            return '/appointments';
        }
        // للمسارات النسبية، اسمح فقط بتلك التي تبدأ بشرطة مائلة واحدة
        else if (!url.startsWith('/')) {
            console.error('تم حظر مسار نسبي غير صالح:', url);
            return '/appointments';
        }

        // حظر المسارات التي تبدأ بشرطتين مائلتين
        if (url.startsWith('//')) {
            console.error('تم حظر محاولة حقن بروتوكول نسبي:', url);
            return '/appointments';
        }

        // فحص إضافي للحماية من حقن البروتوكولات الضارة
        const lowerUrl = url.toLowerCase().trim();
        if (lowerUrl.startsWith('javascript:') ||
            lowerUrl.startsWith('data:') ||
            lowerUrl.startsWith('vbscript:')) {
            console.error('تم حظر محاولة حقن بروتوكول:', url);
            return '/appointments';
        }

        // اجتاز URL جميع الفحوصات الأمنية
        return url;
    } catch (e) {
        // إذا فشل تحليل URL، ارجع المسار الافتراضي الآمن
        console.error('خطأ في التحقق من URL:', e);
        return '/appointments';
    }
}

// استبدال دالة safeRedirect لتستخدم نهجًا أكثر أمانًا
function safeRedirect(url) {
    // تطبيق التحقق الأمني
    const safeUrl = validateRedirectUrl(url);

    // استخدام وظيفة موجهة للأمان بدلاً من التعيين المباشر لـ window.location.href
    if (safeUrl.startsWith('/')) {
        // مسار نسبي آمن داخل تطبيقنا
        window.location.replace(safeUrl); // استخدام replace بدلاً من href
    } else {
        // ضمان إضافي للأمان، لا يجب أن نصل إلى هنا
        window.location.replace('/appointments');
    }
}

// Override window.location.href with a safer implementation
Object.defineProperty(window.location, 'safehref', {
    set: function(url) {
        const safeUrl = validateRedirectUrl(url);
        this.href = safeUrl;
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('appointmentForm');
    const dateInput = document.getElementById('appointment_date');
    const timeSelect = document.getElementById('appointment_time');
    const submitBtn = document.getElementById('submitBtn');
    const spinner = document.querySelector('.loading-spinner');
    const errorDiv = document.getElementById('appointmentErrors');
    const dateError = document.getElementById('date-error');
    const timeError = document.getElementById('time-error');

    // تحديد المواعيد المتاحة حسب اليوم
    if (dateInput) {
        const today = new Date();
        const maxDate = new Date();
        maxDate.setDate(today.getDate() + 30);

        dateInput.min = today.toISOString().split('T')[0];
        dateInput.max = maxDate.toISOString().split('T')[0];

        // Handle date change
        dateInput.addEventListener('change', function() {
            const timeSelect = document.getElementById('appointment_time');
            const dateError = document.getElementById('date-error');

            // Reset time select and validation states
            timeSelect.innerHTML = '<option value="">اختر الوقت المناسب</option>';
            timeSelect.disabled = true;
            dateError.textContent = '';
            this.classList.remove('is-invalid');

            if (!this.value) return;

            // Parse the date correctly for timezone handling
            const [year, month, day] = this.value.split('-').map(Number);
            const selectedDate = new Date(year, month - 1, day); // month is 0-based in JavaScript
            const dayOfWeek = selectedDate.getDay();

            // Arabic day names
            const arabicDays = {
                0: 'الأحد',
                1: 'الإثنين',
                2: 'الثلاثاء',
                3: 'الأربعاء',
                4: 'الخميس',
                5: 'الجمعة',
                6: 'السبت'
            };

            // Check if date is in the past
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            if (selectedDate < today) {
                this.classList.add('is-invalid');
                dateError.textContent = 'لا يمكن اختيار تاريخ في الماضي';
                timeSelect.disabled = true;
                return;
            }

            // Define time slots based on day
            const slots = dayOfWeek === 5 ? // Friday
                [{ start: '17:00', end: '23:00', label: 'الفترة المسائية' }] :
                [
                    { start: '11:00', end: '14:00', label: 'الفترة الصباحية' },
                    { start: '17:00', end: '23:00', label: 'الفترة المسائية' }
                ];

            // Add day name to time select
            timeSelect.innerHTML = `<option value="">اختر الوقت المناسب ليوم ${arabicDays[dayOfWeek]}</option>`;

            // Generate time slots
            slots.forEach(slot => {
                const group = document.createElement('optgroup');
                group.label = slot.label;

                let currentTime = new Date(`2000-01-01T${slot.start}`);
                const endTime = new Date(`2000-01-01T${slot.end}`);

                // If today, skip past times
                const isToday = selectedDate.toDateString() === new Date().toDateString();
                const now = new Date();

                while (currentTime < endTime) {
                    const option = document.createElement('option');
                    const hours = currentTime.getHours().toString().padStart(2, '0');
                    const minutes = currentTime.getMinutes().toString().padStart(2, '0');
                    const timeValue = `${hours}:${minutes}`;

                    // Skip if time is in the past for today
                    if (isToday) {
                        const slotTime = new Date(selectedDate);
                        slotTime.setHours(parseInt(hours), parseInt(minutes), 0, 0);
                        if (slotTime <= now) {
                            currentTime.setMinutes(currentTime.getMinutes() + 30);
                            continue;
                        }
                    }

                    // Format time in Arabic
                    const timeString = new Date(`2000-01-01T${timeValue}`)
                        .toLocaleTimeString('ar-SA', {
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: true
                        });

                    option.value = timeValue;
                    option.textContent = timeString;
                    group.appendChild(option);

                    // Add 30 minutes
                    currentTime.setMinutes(currentTime.getMinutes() + 30);
                }

                // Only add group if it has options
                if (group.children.length > 0) {
                    timeSelect.appendChild(group);
                }
            });

            // Enable time select only if there are available slots
            const hasSlots = timeSelect.querySelectorAll('option').length > 1;
            timeSelect.disabled = !hasSlots;

            if (!hasSlots && isToday) {
                dateError.textContent = 'لا توجد مواعيد متاحة اليوم، يرجى اختيار يوم آخر';
                this.classList.add('is-invalid');
            }
        });

        // Trigger change event if date is already selected
        if (dateInput.value) {
            dateInput.dispatchEvent(new Event('change'));
        }
    }

    // معالجة تقديم النموذج
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        // التحقق من الملاحظات
        const notes = document.getElementById('notes');
        if (notes.value.length < 10) {
            errorDiv.style.display = 'block';
            errorDiv.textContent = 'يرجى إضافة تفاصيل كافية للتصميم المخصص (10 أحرف على الأقل)';
            notes.focus();
            return;
        }

        // التحقق من اختيار الوقت
        if (!timeSelect.value) {
            errorDiv.style.display = 'block';
            errorDiv.textContent = 'يرجى اختيار وقت للموعد';
            timeSelect.focus();
            return;
        }

        // إظهار حالة التحميل
        submitBtn.disabled = true;
        spinner.style.display = 'inline-block';
        errorDiv.style.display = 'none';
        errorDiv.textContent = '';

        // تجميع بيانات النموذج
        const formData = new FormData(form);

        // إرسال الطلب
        fetch(form.getAttribute('data-url'), {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => {
                    throw new Error(data.message || 'حدث خطأ أثناء حجز الموعد');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // إظهار رسالة النجاح
                const notification = document.createElement('div');
                notification.className = 'alert alert-success';
                notification.textContent = data.message;
                form.insertBefore(notification, form.firstChild);

                // استخدام إعادة توجيه آمنة
                setTimeout(() => {
                    safeRedirect(data.redirect_url || '/appointments');
                }, 2000);
            } else {
                throw new Error(data.message || 'حدث خطأ أثناء حجز الموعد');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            errorDiv.style.display = 'block';
            errorDiv.textContent = error.message;
            if (error.errors) {
                const errorList = document.createElement('ul');
                Object.values(error.errors).forEach(error => {
                    const li = document.createElement('li');
                    li.textContent = error[0];
                    errorList.appendChild(li);
                });
                errorDiv.appendChild(errorList);
            }
        })
        .finally(() => {
            // إعادة تعيين حالة التحميل
            submitBtn.disabled = false;
            spinner.style.display = 'none';
        });
    });

    // معالجة زر الإلغاء
    document.getElementById('cancelBtn')?.addEventListener('click', function() {
        if (confirm('هل أنت متأكد من إلغاء حجز الموعد؟')) {
            safeRedirect('/appointments'); // العودة لصفحة المواعيد
        }
    });
});
