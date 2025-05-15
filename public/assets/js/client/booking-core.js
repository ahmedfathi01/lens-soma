document.addEventListener('DOMContentLoaded', function() {
    try {
        const servicesData = document.getElementById('services-data').value;
        const packagesData = document.getElementById('packages-data').value;
        const addonsData = document.getElementById('addons-data').value;
        const bookingsData = document.getElementById('bookings-data').value;

        const allServices = servicesData ? JSON.parse(servicesData) : [];
        const allPackages = packagesData ? JSON.parse(packagesData) : [];
        const allAddons = addonsData ? JSON.parse(addonsData) : [];
        const currentBookings = bookingsData ? JSON.parse(bookingsData) : [];

        const oldSessionTimeElement = document.getElementById('old-session-time');
        const oldSessionTime = oldSessionTimeElement ? oldSessionTimeElement.value : '';

        const oldPackageIdElement = document.getElementById('old-package-id');
        const oldPackageId = oldPackageIdElement ? oldPackageIdElement.value : 0;

        document.querySelectorAll('.badge.bg-primary').forEach(badge => {
            if (badge.textContent.includes('$')) {
                const addonId = badge.closest('.form-check').querySelector('input[type="checkbox"]').value;
                const addon = allAddons.find(a => a.id == addonId);
                if (addon) {
                    badge.textContent = `${addon.price} ريال`;
                }
            }
        });

        const serviceSelect = document.querySelector('select[name="service_id"]');
        const packagesContainer = document.querySelector('.row:has(.package-card)');
        const addonsSection = document.getElementById('addons-section');
        const sessionDateInput = document.querySelector('input[name="session_date"]');

        if (!serviceSelect.value) {
            packagesContainer.style.display = 'none';
            addonsSection.style.display = 'none';
        } else {
            packagesContainer.style.display = 'flex';
        }

        // Add event listeners for any existing Tabi/Promo add-ons on page load
        document.querySelectorAll('input[type="checkbox"][name^="tabby_"], input[type="checkbox"][name^="promo_"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateTotalPriceAndInstallment();
            });
        });

        // Also add listeners for any other existing addon type inputs
        document.querySelectorAll('input[type="checkbox"][name*="addon"]:not(.addon-checkbox), input[type="radio"][name*="addon"]').forEach(input => {
            input.addEventListener('change', function() {
                updateTotalPriceAndInstallment();
            });
        });

        function updateAvailableTimes(packageDuration) {
            const sessionTimeSelect = document.getElementById('sessionTime');
            const timeNote = document.getElementById('timeNote');
            const selectedPackageRadio = document.querySelector('.package-select:checked');
            const selectedServiceId = document.querySelector('select[name="service_id"]').value;

            if (!sessionTimeSelect || !sessionDateInput || !timeNote || !selectedPackageRadio || !selectedServiceId) {
                console.error('Required elements not found');
                return;
            }

            if (!packageDuration || !sessionDateInput.value || !selectedPackageRadio.value) {
                sessionTimeSelect.disabled = true;
                sessionTimeSelect.innerHTML = '<option value="">يرجى اختيار الباقة والتاريخ أولاً</option>';
                timeNote.textContent = '';
                const infoIcon = document.createElement('i');
                infoIcon.className = 'fas fa-info-circle';
                timeNote.appendChild(infoIcon);
                timeNote.appendChild(document.createTextNode(' يرجى اختيار الباقة والتاريخ أولاً'));
                return;
            }

            sessionTimeSelect.disabled = true;
            sessionTimeSelect.innerHTML = '<option value="">جاري تحميل المواعيد المتاحة...</option>';
            timeNote.textContent = '';
            const spinnerIcon = document.createElement('i');
            spinnerIcon.className = 'fas fa-spinner fa-spin';
            timeNote.appendChild(spinnerIcon);
            timeNote.appendChild(document.createTextNode(' جاري التحقق من المواعيد المتاحة...'));

            const formattedDate = sessionDateInput.value.split('T')[0];

            const tokenElement = document.querySelector('meta[name="csrf-token"]');
            if (!tokenElement) {
                console.error('CSRF token not found');
                timeNote.textContent = '';
                const errorIcon = document.createElement('i');
                errorIcon.className = 'fas fa-exclamation-circle text-danger';
                timeNote.appendChild(errorIcon);
                timeNote.appendChild(document.createTextNode('حدث خطأ في النظام. يرجى تحديث الصفحة والمحاولة مرة أخرى'));
                return;
            }

            const token = tokenElement.getAttribute('content');

            const requestData = {
                date: formattedDate,
                package_id: selectedPackageRadio.value,
                service_id: selectedServiceId
            };

            fetch('/client/bookings/available-slots', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(requestData)
            })
            .then(async response => {
                const responseText = await response.text();

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}, response: ${responseText}`);
                }

                try {
                    return JSON.parse(responseText);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    throw new Error('Invalid JSON response');
                }
            })
            .then(data => {
                sessionTimeSelect.innerHTML = '';
                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = 'اختر الوقت المناسب';
                sessionTimeSelect.appendChild(defaultOption);

                if (data.status === 'success') {
                    let alertHtml = '';

                    if (data.slots && data.slots.has_alternative_packages &&
                        data.slots.alternative_packages &&
                        data.slots.alternative_packages.length > 0) {
                        let alternativePackagesHtml = '';
                        let hasAnyValidAlternative = false;

                        data.slots.alternative_packages.forEach(alt => {
                            const pkg = alt.package;
                            if (alt.available_slots && alt.available_slots.length > 0) {
                                hasAnyValidAlternative = true;
                                const duration = pkg.duration >= 60
                                    ? `${Math.floor(pkg.duration / 60)} ساعة${pkg.duration % 60 > 0 ? ` و ${pkg.duration % 60} دقيقة` : ''}`
                                    : `${pkg.duration} دقيقة`;

                                const div = document.createElement('div');
                                div.className = 'alternative-package mb-2';

                                const h6 = document.createElement('h6');
                                h6.textContent = pkg.name;
                                div.appendChild(h6);

                                const p = document.createElement('p');
                                p.className = 'small text-muted mb-1';
                                p.textContent = pkg.description;
                                div.appendChild(p);

                                const ul = document.createElement('ul');
                                ul.className = 'list-unstyled small';

                                const durationLi = document.createElement('li');
                                const durationIcon = document.createElement('i');
                                durationIcon.className = 'fas fa-clock me-1';
                                durationLi.appendChild(durationIcon);
                                durationLi.appendChild(document.createTextNode(`المدة: ${duration}`));
                                ul.appendChild(durationLi);

                                const priceLi = document.createElement('li');
                                const priceIcon = document.createElement('i');
                                priceIcon.className = 'fas fa-tag me-1';
                                priceLi.appendChild(priceIcon);
                                priceLi.appendChild(document.createTextNode(`السعر: ${pkg.base_price} ريال`));
                                ul.appendChild(priceLi);

                                const slotsLi = document.createElement('li');
                                const slotsIcon = document.createElement('i');
                                slotsIcon.className = 'fas fa-calendar-check me-1';
                                slotsLi.appendChild(slotsIcon);
                                slotsLi.appendChild(document.createTextNode(`المواعيد المتاحة: ${alt.available_slots.length}`));
                                ul.appendChild(slotsLi);

                                div.appendChild(ul);

                                const button = document.createElement('button');
                                button.className = 'btn btn-warning btn-sm';
                                button.onclick = function() { selectPackage(pkg.id, selectedServiceId); };

                                const buttonIcon = document.createElement('i');
                                buttonIcon.className = 'fas fa-exchange-alt me-1';
                                button.appendChild(buttonIcon);
                                button.appendChild(document.createTextNode('اختيار هذه الباقة'));
                                div.appendChild(button);

                                alternativePackagesHtml += div.outerHTML;
                            }
                        });

                        if (hasAnyValidAlternative) {
                            const alertDiv = document.createElement('div');
                            alertDiv.className = 'alert alert-info mb-2';

                            const infoIcon = document.createElement('i');
                            infoIcon.className = 'fas fa-info-circle me-2';
                            alertDiv.appendChild(infoIcon);

                            alertDiv.appendChild(document.createTextNode('لا تتوفر مواعيد لهذه الباقة حالياً، ولكن هناك باقات متاحة في نفس الخدمة:'));

                            const alternativesDiv = document.createElement('div');
                            alternativesDiv.className = 'mt-2';
                            alternativesDiv.innerHTML = alternativePackagesHtml;
                            alertDiv.appendChild(alternativesDiv);

                            alertHtml += alertDiv.outerHTML;
                        }
                    }

                    if (data.slots && data.slots.next_available_date) {
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-warning';

                        const calendarIcon = document.createElement('i');
                        calendarIcon.className = 'fas fa-calendar-alt me-2';
                        alertDiv.appendChild(calendarIcon);

                        alertDiv.appendChild(document.createTextNode(`أقرب موعد متاح هو يوم ${data.slots.next_available_formatted_date}`));

                        const button = document.createElement('button');
                        button.className = 'btn btn-warning btn-sm float-end';
                        button.onclick = function() { selectDate(data.slots.next_available_date); };
                        button.textContent = 'اختيار هذا اليوم';
                        alertDiv.appendChild(button);

                        alertHtml += alertDiv.outerHTML;
                    }

                    if (alertHtml) {
                        const timeContainer = sessionTimeSelect.closest('.col-md-6');
                        if (timeContainer.querySelector('.alert')) {
                            timeContainer.querySelectorAll('.alert').forEach(alert => alert.remove());
                        }
                        timeContainer.insertAdjacentHTML('afterbegin', alertHtml);
                    }

                    if (Array.isArray(data.slots) && data.slots.length > 0) {
                        data.slots.forEach(slot => {
                            const option = document.createElement('option');
                            option.value = slot.time;
                            option.textContent = `${slot.formatted_time} (${slot.time} - ${slot.end_time})`;
                            if (oldSessionTime && oldSessionTime === slot.time) {
                                option.selected = true;
                            }
                            sessionTimeSelect.appendChild(option);
                        });

                        sessionTimeSelect.disabled = false;

                        timeNote.textContent = '';
                        const infoIcon = document.createElement('i');
                        infoIcon.className = 'fas fa-info-circle';
                        timeNote.appendChild(infoIcon);

                        const duration = packageDuration >= 60
                            ? `${Math.floor(packageDuration / 60)} ساعة${packageDuration % 60 > 0 ? ` و ${packageDuration % 60} دقيقة` : ''}`
                            : `${packageDuration} دقيقة`;

                        timeNote.appendChild(document.createTextNode(` المواعيد المتاحة تأخذ في الاعتبار مدة الجلسة (${duration})`));

                        const timeContainer = sessionTimeSelect.closest('.col-md-6');
                        if (timeContainer.querySelector('.alert')) {
                            timeContainer.querySelector('.alert').remove();
                        }
                    } else {
                        sessionTimeSelect.disabled = true;

                        timeNote.textContent = '';
                        const errorIcon = document.createElement('i');
                        errorIcon.className = 'fas fa-exclamation-circle text-danger';
                        timeNote.appendChild(errorIcon);
                        timeNote.appendChild(document.createTextNode(' لا توجد مواعيد متاحة في هذا اليوم'));
                    }
                } else {
                    sessionTimeSelect.disabled = true;

                    timeNote.textContent = '';
                    const errorIcon = document.createElement('i');
                    errorIcon.className = 'fas fa-exclamation-circle text-danger';
                    timeNote.appendChild(errorIcon);
                    timeNote.appendChild(document.createTextNode(` ${data.message || 'حدث خطأ أثناء تحميل المواعيد المتاحة'}`));
                }
            })
            .catch(error => {
                console.error('Error details:', error);
                sessionTimeSelect.disabled = true;
                sessionTimeSelect.innerHTML = '<option value="">حدث خطأ أثناء تحميل المواعيد</option>';

                timeNote.textContent = '';
                const errorIcon = document.createElement('i');
                errorIcon.className = 'fas fa-exclamation-circle text-danger';
                timeNote.appendChild(errorIcon);
                timeNote.appendChild(document.createTextNode(' حدث خطأ أثناء تحميل المواعيد المتاحة. يرجى المحاولة مرة أخرى'));
            });
        }

        function handlePackageSelection(packageId) {
            const selectedPackage = allPackages.find(pkg => pkg.id == packageId);
            if (!selectedPackage) {
                addonsSection.style.display = 'none';
                document.getElementById('sessionTime').disabled = true;
                document.getElementById('sessionTime').innerHTML = '<option value="">يرجى اختيار الباقة أولاً</option>';
                return;
            }

            if (document.getElementById('coupon-details') && !document.getElementById('coupon-details').classList.contains('d-none')) {
                removeCoupon();
            }

            const sessionTimeSelect = document.getElementById('sessionTime');
            const sessionDateInput = document.querySelector('input[name="session_date"]');

            if (sessionDateInput.value) {
                sessionTimeSelect.disabled = false;
                updateAvailableTimes(selectedPackage.duration);
            }

            const addonsContainer = addonsSection.querySelector('.row');
            addonsContainer.innerHTML = '';

            if (selectedPackage.addons && selectedPackage.addons.length) {
                addonsContainer.innerHTML = selectedPackage.addons.map(addon => `
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="form-check">
                                    <input type="checkbox" name="addons[${addon.id}][id]"
                                           value="${addon.id}"
                                           class="form-check-input addon-checkbox"
                                           id="addon-${addon.id}">
                                    <input type="hidden" name="addons[${addon.id}][quantity]" value="1">
                                    <label class="form-check-label" for="addon-${addon.id}">
                                        <h6>${addon.name}</h6>
                                        <p class="text-muted small mb-2">${addon.description}</p>
                                        <span class="badge bg-primary">${addon.price} ريال</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('');

                setTimeout(() => {
                    document.querySelectorAll('.addon-checkbox').forEach(checkbox => {
                        checkbox.addEventListener('change', function() {
                            updateTotalPriceAndInstallment();
                        });
                    });

                    // Add event listeners for Tabi/Promo addons
                    document.querySelectorAll('input[type="checkbox"][name^="tabby_"], input[type="checkbox"][name^="promo_"]').forEach(checkbox => {
                        checkbox.addEventListener('change', function() {
                            updateTotalPriceAndInstallment();
                        });
                    });

                    // Also add listeners for any other addon type inputs that might be present
                    document.querySelectorAll('input[type="checkbox"][name*="addon"], input[type="radio"][name*="addon"]').forEach(input => {
                        input.addEventListener('change', function() {
                            updateTotalPriceAndInstallment();
                        });
                    });
                }, 100);

                addonsSection.style.display = 'block';
            } else {
                addonsSection.style.display = 'none';
            }

            updateTotalPriceAndInstallment();

            // Dispatch an event to notify other scripts about package selection
            document.dispatchEvent(new CustomEvent('packageSelected', {
                detail: { packageId: packageId }
            }));
        }

        serviceSelect.addEventListener('change', function() {
            const selectedServiceId = this.value;
            if (!selectedServiceId) {
                packagesContainer.style.display = 'none';
                addonsSection.style.display = 'none';
                return;
            }

            if (document.getElementById('coupon-details') && !document.getElementById('coupon-details').classList.contains('d-none')) {
                removeCoupon();
            }

            document.querySelectorAll('.package-select:checked').forEach(radio => radio.checked = false);
            document.querySelectorAll('.package-card.selected').forEach(card => card.classList.remove('selected'));

            document.querySelectorAll('.addon-checkbox:checked').forEach(checkbox => {
                checkbox.checked = false;
            });

            const dateTimeSection = document.getElementById('dateTimeSection');
            if (dateTimeSection) {
                dateTimeSection.style.display = 'none';

                const dateInput = document.querySelector('input[name="session_date"]');
                const timeSelect = document.getElementById('sessionTime');
                if (dateInput) dateInput.value = '';
                if (timeSelect) {
                    timeSelect.innerHTML = '<option value="">اختر الوقت</option>';
                    timeSelect.disabled = true;
                }
            }

            addonsSection.style.display = 'none';

            const servicePackages = allPackages.filter(pkg =>
                pkg.service_ids.includes(parseInt(selectedServiceId))
            );

            packagesContainer.innerHTML = servicePackages.map(pkg => {
                return `
                <div class="col-md-6">
                    <div class="package-card">
                        <input type="radio" name="package_id" value="${pkg.id}"
                               class="form-check-input package-select" required>
                        <h5>${pkg.name}</h5>
                        <p class="text-muted">${pkg.description}</p>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-clock me-2"></i>المدة:

                                ${pkg.duration >= 60
                                ? `${Math.floor(pkg.duration / 60)} ساعة${pkg.duration % 60 > 0 ? ` و ${pkg.duration % 60} دقيقة` : ''}`
                                : `${pkg.duration} دقيقة`
                            }</li>
                            <li><i class="fas fa-images me-2"></i>عدد الصور: ${pkg.num_photos}</li>
                            <li><i class="fas fa-palette me-2"></i>عدد الثيمات: ${pkg.themes_count}</li>
                            <li><i class="fas fa-tag me-2"></i>السعر: ${pkg.base_price} ريال</li>
                            ${pkg.best_coupon ? `
                            <li class="coupon-info">
                                <i class="fas fa-ticket-alt me-2 coupon-icon"></i>
                                كوبون خصم:
                                <span class="coupon-code">${pkg.best_coupon.code}</span>
                                <span class="discount-value">(${pkg.discount_text})</span>
                            </li>
                            ` : ''}
                        </ul>
                    </div>
                </div>
                `;
            }).join('');

            packagesContainer.style.display = 'flex';
            addonsSection.style.display = 'none';

            attachPackageListeners();
        });

        function attachPackageListeners() {
            document.querySelectorAll('.package-card').forEach(card => {
                card.addEventListener('click', function() {
                    const radio = this.querySelector('input[type="radio"]');
                    if (radio) {
                        // Clear selected addons
                        document.querySelectorAll('.addon-checkbox:checked').forEach(checkbox => {
                            checkbox.checked = false;
                        });

                        // Deselect all other packages visually
                        document.querySelectorAll('.package-card').forEach(c => {
                            c.classList.remove('selected');
                        });

                        // Select this package
                        radio.checked = true;
                        this.classList.add('selected');

                        // Trigger change event on the radio button to ensure it's recognized by other listeners
                        radio.dispatchEvent(new Event('change', { bubbles: true }));

                        // Process the selection
                        handlePackageSelection(radio.value);
                        updateTotalPriceAndInstallment();
                    }
                });

                // Add change listener to radio buttons as well
                const radioButton = card.querySelector('input[type="radio"]');
                if (radioButton) {
                    radioButton.addEventListener('change', function() {
                        if (this.checked) {
                            // Deselect all packages visually
                            document.querySelectorAll('.package-card').forEach(c => {
                                c.classList.remove('selected');
                            });

                            // Select this package
                            this.closest('.package-card').classList.add('selected');

                            // Process the selection
                            handlePackageSelection(this.value);
                            updateTotalPriceAndInstallment();
                        }
                    });
                }
            });
        }

        function updateTotalPriceAndInstallment() {
            const selectedPackageRadio = document.querySelector('.package-select:checked');
            if (!selectedPackageRadio) return;

            const packageId = selectedPackageRadio.value;
            const selectedPackage = allPackages.find(pkg => pkg.id == packageId);
            if (!selectedPackage) return;

            let basePrice = parseFloat(selectedPackage.base_price);
            let totalPrice = basePrice;
            let addonsTotal = 0;

            // Regular addons
            document.querySelectorAll('.addon-checkbox:checked').forEach(checkbox => {
                const addonId = checkbox.value;
                const addon = allAddons.find(a => a.id == addonId) ||
                             (selectedPackage.addons && selectedPackage.addons.find(a => a.id == addonId));
                if (addon) {
                    const addonPrice = parseFloat(addon.price);
                    totalPrice += addonPrice;
                    addonsTotal += addonPrice;
                }
            });

            // Tabi and Promo addons
            document.querySelectorAll('input[type="checkbox"][name^="tabby_"]:checked, input[type="checkbox"][name^="promo_"]:checked').forEach(checkbox => {
                const priceElement = checkbox.closest('.form-check').querySelector('.badge');
                if (priceElement) {
                    const priceText = priceElement.textContent;
                    const priceMatch = priceText.match(/\d+(\.\d+)?/);
                    if (priceMatch && priceMatch[0]) {
                        const addonPrice = parseFloat(priceMatch[0]);
                        totalPrice += addonPrice;
                        addonsTotal += addonPrice;
                    }
                }
            });

            // Any other addon inputs that might be present
            document.querySelectorAll('input[type="checkbox"][name*="addon"]:checked:not(.addon-checkbox), input[type="radio"][name*="addon"]:checked').forEach(input => {
                const priceElement = input.closest('.form-check, .card-body').querySelector('.badge');
                if (priceElement) {
                    const priceText = priceElement.textContent;
                    const priceMatch = priceText.match(/\d+(\.\d+)?/);
                    if (priceMatch && priceMatch[0]) {
                        const addonPrice = parseFloat(priceMatch[0]);
                        totalPrice += addonPrice;
                        addonsTotal += addonPrice;
                    }
                }
            });

            window.packageData.price = totalPrice;

            // Dispatch a detailed price update event with all the information needed
            const priceUpdateEvent = new CustomEvent('priceUpdate', {
                detail: {
                    price: totalPrice,
                    basePrice: basePrice,
                    addonsTotal: addonsTotal
                }
            });
            document.dispatchEvent(priceUpdateEvent);

            return totalPrice;
        }

        if (serviceSelect.value) {
            serviceSelect.dispatchEvent(new Event('change'));
        }

        const selectedPackageRadio = document.querySelector('.package-select:checked');
        if (selectedPackageRadio) {
            handlePackageSelection(selectedPackageRadio.value);
            selectedPackageRadio.closest('.package-card').classList.add('selected');

            if (sessionDateInput.value) {
                const packageId = selectedPackageRadio.value;
                const selectedPackage = allPackages.find(pkg => pkg.id == packageId);
                if (selectedPackage) {
                    updateAvailableTimes(selectedPackage.duration);
                }
            }
        }

        document.querySelector('input[name="session_date"]').addEventListener('change', function() {
            const selectedPackageRadio = document.querySelector('.package-select:checked');
            if (selectedPackageRadio) {
                const packageId = selectedPackageRadio.value;
                const selectedPackage = allPackages.find(pkg => pkg.id == packageId);

                if (selectedPackage && this.value) {
                    updateAvailableTimes(selectedPackage.duration);
                } else {
                    document.getElementById('sessionTime').disabled = true;
                    document.getElementById('sessionTime').innerHTML = '<option value="">يرجى اختيار التاريخ أولاً</option>';
                }
            } else {
                document.getElementById('sessionTime').disabled = true;
                document.getElementById('sessionTime').innerHTML = '<option value="">يرجى اختيار الباقة أولاً</option>';
            }
        });

        document.querySelectorAll('.form-control, .form-select').forEach(element => {
            element.addEventListener('focus', function() {
                this.closest('.input-group')?.classList.add('focused');
            });
            element.addEventListener('blur', function() {
                this.closest('.input-group')?.classList.remove('focused');
            });
        });

        window.selectDate = function(date) {
            const dateInput = document.querySelector('input[name="session_date"]');
            dateInput.value = date;
            dateInput.dispatchEvent(new Event('change'));

            const timeContainer = document.getElementById('sessionTime').closest('.col-md-6');
            const alerts = timeContainer.querySelectorAll('.alert');
            alerts.forEach(alert => alert.remove());
        }

        window.selectPackage = function(packageId, serviceId) {
            const packageRadio = document.querySelector(`input[name="package_id"][value="${packageId}"]`);
            if (packageRadio) {
                packageRadio.checked = true;

                document.querySelectorAll('.package-card').forEach(card => {
                    card.classList.remove('selected');
                });
                packageRadio.closest('.package-card').classList.add('selected');

                const serviceSelect = document.querySelector('select[name="service_id"]');
                if (serviceSelect.value !== serviceId.toString()) {
                    serviceSelect.value = serviceId;
                    serviceSelect.dispatchEvent(new Event('change'));
                }

                handlePackageSelection(packageId);

                const timeContainer = document.getElementById('sessionTime').closest('.col-md-6');
                const alerts = timeContainer.querySelectorAll('.alert');
                alerts.forEach(alert => alert.remove());

                packageRadio.closest('.package-card').scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
        }
    } catch (error) {
        console.error('Error in data parsing:', error);
        const formContainer = document.querySelector('.booking-form');
        if (formContainer) {
            formContainer.insertAdjacentHTML('afterbegin', `
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    حدثت مشكلة في تحميل بيانات الصفحة. يرجى <a href="?reset_session=1" class="alert-link">إعادة ضبط الصفحة</a> للمتابعة.
                </div>
            `);
        }
    }
});
