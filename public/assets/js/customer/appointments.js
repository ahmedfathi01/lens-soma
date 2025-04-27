function validateRedirectUrl(url) {
    if (!url) return '/appointments';

    const safeInternalPaths = ['/cart', '/appointments', '/products', '/account', '/'];
    if (safeInternalPaths.includes(url)) {
        return url;
    }

    try {
        if (url.indexOf('://') > -1 || url.indexOf('//') === 0) {
            console.error('تم حظر محاولة إعادة توجيه خارجية:', url);
            return '/appointments';
        }
        else if (!url.startsWith('/')) {
            console.error('تم حظر مسار نسبي غير صالح:', url);
            return '/appointments';
        }

        if (url.startsWith('//')) {
            console.error('تم حظر محاولة حقن بروتوكول نسبي:', url);
            return '/appointments';
        }

        const lowerUrl = url.toLowerCase().trim();
        if (lowerUrl.startsWith('javascript:') ||
            lowerUrl.startsWith('data:') ||
            lowerUrl.startsWith('vbscript:')) {
            console.error('تم حظر محاولة حقن بروتوكول:', url);
            return '/appointments';
        }

        return url;
    } catch (e) {
        console.error('خطأ في التحقق من URL:', e);
        return '/appointments';
    }
}

function safeRedirect(url) {
    const safeUrl = validateRedirectUrl(url);

    if (safeUrl.startsWith('/')) {
        window.location.replace(safeUrl);
    } else {
        window.location.replace('/appointments');
    }
}

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

    if (dateInput) {
        const today = new Date();
        const maxDate = new Date();
        maxDate.setDate(today.getDate() + 30);

        dateInput.min = today.toISOString().split('T')[0];
        dateInput.max = maxDate.toISOString().split('T')[0];

        dateInput.addEventListener('change', function() {
            const timeSelect = document.getElementById('appointment_time');
            const dateError = document.getElementById('date-error');

            timeSelect.innerHTML = '<option value="">اختر الوقت المناسب</option>';
            timeSelect.disabled = true;
            dateError.textContent = '';
            this.classList.remove('is-invalid');

            if (!this.value) return;

            const [year, month, day] = this.value.split('-').map(Number);
            const selectedDate = new Date(year, month - 1, day);
            const dayOfWeek = selectedDate.getDay();

            const arabicDays = {
                0: 'الأحد',
                1: 'الإثنين',
                2: 'الثلاثاء',
                3: 'الأربعاء',
                4: 'الخميس',
                5: 'الجمعة',
                6: 'السبت'
            };

            const today = new Date();
            today.setHours(0, 0, 0, 0);
            if (selectedDate < today) {
                this.classList.add('is-invalid');
                dateError.textContent = 'لا يمكن اختيار تاريخ في الماضي';
                timeSelect.disabled = true;
                return;
            }

            const slots = dayOfWeek === 5 ?
                [{ start: '17:00', end: '23:00', label: 'الفترة المسائية' }] :
                [
                    { start: '11:00', end: '14:00', label: 'الفترة الصباحية' },
                    { start: '17:00', end: '23:00', label: 'الفترة المسائية' }
                ];

            timeSelect.innerHTML = `<option value="">اختر الوقت المناسب ليوم ${arabicDays[dayOfWeek]}</option>`;

            slots.forEach(slot => {
                const group = document.createElement('optgroup');
                group.label = slot.label;

                let currentTime = new Date(`2000-01-01T${slot.start}`);
                const endTime = new Date(`2000-01-01T${slot.end}`);

                const isToday = selectedDate.toDateString() === new Date().toDateString();
                const now = new Date();

                while (currentTime < endTime) {
                    const option = document.createElement('option');
                    const hours = currentTime.getHours().toString().padStart(2, '0');
                    const minutes = currentTime.getMinutes().toString().padStart(2, '0');
                    const timeValue = `${hours}:${minutes}`;

                    if (isToday) {
                        const slotTime = new Date(selectedDate);
                        slotTime.setHours(parseInt(hours), parseInt(minutes), 0, 0);
                        if (slotTime <= now) {
                            currentTime.setMinutes(currentTime.getMinutes() + 30);
                            continue;
                        }
                    }

                    const timeString = new Date(`2000-01-01T${timeValue}`)
                        .toLocaleTimeString('ar-SA', {
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: true
                        });

                    option.value = timeValue;
                    option.textContent = timeString;
                    group.appendChild(option);

                    currentTime.setMinutes(currentTime.getMinutes() + 30);
                }

                if (group.children.length > 0) {
                    timeSelect.appendChild(group);
                }
            });

            const hasSlots = timeSelect.querySelectorAll('option').length > 1;
            timeSelect.disabled = !hasSlots;

            if (!hasSlots && isToday) {
                dateError.textContent = 'لا توجد مواعيد متاحة اليوم، يرجى اختيار يوم آخر';
                this.classList.add('is-invalid');
            }
        });

        if (dateInput.value) {
            dateInput.dispatchEvent(new Event('change'));
        }
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const notes = document.getElementById('notes');
        if (notes.value.length < 10) {
            errorDiv.style.display = 'block';
            errorDiv.textContent = 'يرجى إضافة تفاصيل كافية للتصميم المخصص (10 أحرف على الأقل)';
            notes.focus();
            return;
        }

        if (!timeSelect.value) {
            errorDiv.style.display = 'block';
            errorDiv.textContent = 'يرجى اختيار وقت للموعد';
            timeSelect.focus();
            return;
        }

        submitBtn.disabled = true;
        spinner.style.display = 'inline-block';
        errorDiv.style.display = 'none';
        errorDiv.textContent = '';

        const formData = new FormData(form);

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
                const notification = document.createElement('div');
                notification.className = 'alert alert-success';
                notification.textContent = data.message;
                form.insertBefore(notification, form.firstChild);

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
            submitBtn.disabled = false;
            spinner.style.display = 'none';
        });
    });

    document.getElementById('cancelBtn')?.addEventListener('click', function() {
        if (confirm('هل أنت متأكد من إلغاء حجز الموعد؟')) {
            safeRedirect('/appointments');
        }
    });
});
