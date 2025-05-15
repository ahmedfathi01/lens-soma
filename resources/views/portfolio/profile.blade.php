<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>البروفايل الشخصي | ستوديو ماسا</title>
    <meta name="description" content="بروفايل شخصي لصاحب موقع ستوديو ماسا">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/portfolio-profile.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/studio-client/style.css') }}">
</head>
<body class="rtl">
    @include('parts.navbar')
    <!-- Adding portfolio-page class to main container to match the scoped CSS -->
    <div class="portfolio-page rtl">
        <!-- Portfolio Header -->
        <header class="portfolio-header">
            <div class="container portfolio-container">
                <div class="profile-intro">
                    <div class="profile-info">
                        <h1 class="profile-title">سمية الشهري</h1>
                        <h2 class="profile-subtitle">مصممة أزياء ومصورة محترفة</h2>
                        <p class="profile-description">مرحباً بك في بروفايلي الشخصي! أنا سمية، مصممة أزياء ومصورة محترفة مع أكثر من 10 سنوات من الخبرة في مجال التصوير والتصميم. أعمل على تقديم خدمات احترافية وعصرية لكل عملائي.</p>
                        <div class="profile-social">
                            <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="social-link"><i class="fab fa-facebook"></i></a>
                            <a href="#" class="social-link"><i class="fab fa-pinterest"></i></a>
                            <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
                        </div>
                    </div>
                    <div class="profile-photo">
                        <div class="photo-frame">
                            <img src="https://images.unsplash.com/photo-1580894732444-8ecded7900cd?q=80&w=1470&auto=format&fit=crop" alt="سمية الشهري">
                        </div>
                        <div class="photo-bg"></div>
                    </div>
                </div>
            </div>
        </header>

        <!-- About Section -->
        <section class="about-section">
            <div class="container">
                <div class="about-card animate-up">
                    <h2 class="section-title">نبذة عني</h2>
                    <p>أنا سمية الشهري، مصممة أزياء ومصورة محترفة. بدأت مسيرتي في عالم التصوير والتصميم منذ أكثر من 10 سنوات، وخلال هذه الفترة اكتسبت العديد من المهارات والخبرات التي تمكنني من تقديم أفضل الخدمات لعملائي. أؤمن بأن كل لحظة تستحق التوثيق وكل شخص يستحق صوراً استثنائية تعكس جماله وشخصيته الفريدة.</p>

                    <div class="bio-grid">
                        <div>
                            <div class="bio-item">
                                <span class="bio-label">الاسم الكامل:</span>
                                <span class="bio-content">سمية محمد الشهري</span>
                            </div>
                            <div class="bio-item">
                                <span class="bio-label">البريد الإلكتروني:</span>
                                <span class="bio-content">sumaya@studiomasa.com</span>
                            </div>
                            <div class="bio-item">
                                <span class="bio-label">رقم الهاتف:</span>
                                <span class="bio-content">+966 50 123 4567</span>
                            </div>
                            <div class="bio-item">
                                <span class="bio-label">الموقع:</span>
                                <span class="bio-content">الرياض، المملكة العربية السعودية</span>
                            </div>
                        </div>
                        <div>
                            <div class="skill-wrapper">
                                <div class="skill-item">
                                    <div class="skill-info">
                                        <span class="skill-name">تصوير احترافي</span>
                                        <span class="skill-percent">95%</span>
                                    </div>
                                    <div class="skill-bar">
                                        <div class="skill-progress" data-width="95"></div>
                                    </div>
                                </div>
                                <div class="skill-item">
                                    <div class="skill-info">
                                        <span class="skill-name">تصميم أزياء</span>
                                        <span class="skill-percent">90%</span>
                                    </div>
                                    <div class="skill-bar">
                                        <div class="skill-progress" data-width="90"></div>
                                    </div>
                                </div>
                                <div class="skill-item">
                                    <div class="skill-info">
                                        <span class="skill-name">تعديل الصور</span>
                                        <span class="skill-percent">85%</span>
                                    </div>
                                    <div class="skill-bar">
                                        <div class="skill-progress" data-width="85"></div>
                                    </div>
                                </div>
                                <div class="skill-item">
                                    <div class="skill-info">
                                        <span class="skill-name">تصوير الفيديو</span>
                                        <span class="skill-percent">80%</span>
                                    </div>
                                    <div class="skill-bar">
                                        <div class="skill-progress" data-width="80"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Services Section -->
        <section class="services-section">
            <div class="container">
                <h2 class="section-title text-center animate-up">خدماتي</h2>
                <div class="services-grid">
                    <div class="service-card animate-up">
                        <div class="service-icon">
                            <i class="fas fa-camera"></i>
                        </div>
                        <h3 class="service-title">تصوير احترافي</h3>
                        <p class="service-description">تصوير احترافي بأحدث المعدات للأفراد والمناسبات بأعلى جودة وبلمسة فنية فريدة.</p>
                    </div>
                    <div class="service-card animate-up">
                        <div class="service-icon">
                            <i class="fas fa-tshirt"></i>
                        </div>
                        <h3 class="service-title">تصميم أزياء</h3>
                        <p class="service-description">تصميم أزياء عصرية وفريدة بأعلى جودة تناسب جميع الأذواق والمناسبات.</p>
                    </div>
                    <div class="service-card animate-up">
                        <div class="service-icon">
                            <i class="fas fa-image"></i>
                        </div>
                        <h3 class="service-title">تعديل وتحرير الصور</h3>
                        <p class="service-description">خدمات تعديل وتحرير الصور احترافياً باستخدام أحدث تقنيات وبرامج التعديل.</p>
                    </div>
                    <div class="service-card animate-up">
                        <div class="service-icon">
                            <i class="fas fa-video"></i>
                        </div>
                        <h3 class="service-title">تصوير فيديو</h3>
                        <p class="service-description">تصوير فيديو احترافي للمناسبات والإعلانات بأعلى جودة ممكنة.</p>
                    </div>
                    <div class="service-card animate-up">
                        <div class="service-icon">
                            <i class="fas fa-palette"></i>
                        </div>
                        <h3 class="service-title">تصميم جرافيك</h3>
                        <p class="service-description">تصميم هويات بصرية وإعلانات بأسلوب عصري وجذاب يناسب احتياجاتك.</p>
                    </div>
                    <div class="service-card animate-up">
                        <div class="service-icon">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <h3 class="service-title">دورات تدريبية</h3>
                        <p class="service-description">دورات تدريبية في مجال التصوير والتصميم للمبتدئين والمحترفين.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Portfolio Section -->
        <section class="portfolio-section">
            <div class="container">
                <h2 class="section-title text-center animate-up">معرض أعمالي</h2>
                <div class="portfolio-filter animate-up">
                    <button class="filter-btn active" data-filter="all">الكل</button>
                    <button class="filter-btn" data-filter="photography">التصوير</button>
                    <button class="filter-btn" data-filter="fashion">الأزياء</button>
                    <button class="filter-btn" data-filter="graphic">التصميم الجرافيكي</button>
                </div>
                <div class="portfolio-grid">
                    <div class="portfolio-item animate-up" data-category="photography">
                        <img src="https://images.unsplash.com/photo-1581591524425-c7e0978865fc?q=80&w=1470&auto=format&fit=crop" alt="صورة من معرض الأعمال" class="portfolio-img">
                        <div class="portfolio-overlay">
                            <h3 class="portfolio-title">جلسة تصوير عائلية</h3>
                            <p class="portfolio-category">تصوير</p>
                            <a href="#" class="portfolio-link">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </div>
                    <div class="portfolio-item animate-up" data-category="fashion">
                        <img src="https://images.unsplash.com/photo-1509631179647-0177331693ae?q=80&w=1476&auto=format&fit=crop" alt="صورة من معرض الأعمال" class="portfolio-img">
                        <div class="portfolio-overlay">
                            <h3 class="portfolio-title">تصميم فستان</h3>
                            <p class="portfolio-category">أزياء</p>
                            <a href="#" class="portfolio-link">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </div>
                    <div class="portfolio-item animate-up" data-category="graphic">
                        <img src="https://images.unsplash.com/photo-1626785774573-4b799315345d?q=80&w=1471&auto=format&fit=crop" alt="صورة من معرض الأعمال" class="portfolio-img">
                        <div class="portfolio-overlay">
                            <h3 class="portfolio-title">تصميم شعار</h3>
                            <p class="portfolio-category">تصميم جرافيكي</p>
                            <a href="#" class="portfolio-link">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </div>
                    <div class="portfolio-item animate-up" data-category="photography">
                        <img src="https://images.unsplash.com/photo-1613844237701-8f3664fc2eff?q=80&w=1528&auto=format&fit=crop" alt="صورة من معرض الأعمال" class="portfolio-img">
                        <div class="portfolio-overlay">
                            <h3 class="portfolio-title">تصوير منتجات</h3>
                            <p class="portfolio-category">تصوير</p>
                            <a href="#" class="portfolio-link">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </div>
                    <div class="portfolio-item animate-up" data-category="fashion">
                        <img src="https://images.unsplash.com/photo-1539109136881-3be0616acf4b?q=80&w=1374&auto=format&fit=crop" alt="صورة من معرض الأعمال" class="portfolio-img">
                        <div class="portfolio-overlay">
                            <h3 class="portfolio-title">مجموعة أزياء</h3>
                            <p class="portfolio-category">أزياء</p>
                            <a href="#" class="portfolio-link">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </div>
                    <div class="portfolio-item animate-up" data-category="graphic">
                        <img src="https://images.unsplash.com/photo-1551650975-87deedd944c3?q=80&w=1374&auto=format&fit=crop" alt="صورة من معرض الأعمال" class="portfolio-img">
                        <div class="portfolio-overlay">
                            <h3 class="portfolio-title">تصميم إعلان</h3>
                            <p class="portfolio-category">تصميم جرافيكي</p>
                            <a href="#" class="portfolio-link">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Modern Testimonials Section -->
        <section class="testimonials-section">
            <div class="container">
                <h2 class="section-title text-center animate-up">آراء العملاء</h2>
                <div class="modern-testimonials animate-up">
                    <div class="testimonial-slider">
                        <div class="testimonial-slide active">
                            <div class="testimonial-card">
                                <div class="testimonial-quote">
                                    <i class="fas fa-quote-right"></i>
                                </div>
                                <p class="testimonial-text">تجربتي مع سمية كانت رائعة جداً. احترافية عالية في التصوير والتعامل. الصور كانت أجمل مما توقعت والنتيجة النهائية أبهرتني.</p>
                                <div class="testimonial-author">
                                    <div class="author-avatar">
                                        <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?q=80&w=1374&auto=format&fit=crop" alt="نورة السعيد">
                                    </div>
                                    <div class="author-info">
                                        <h4 class="author-name">نورة السعيد</h4>
                                        <p class="author-title">عميلة</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="testimonial-slide">
                            <div class="testimonial-card">
                                <div class="testimonial-quote">
                                    <i class="fas fa-quote-right"></i>
                                </div>
                                <p class="testimonial-text">تصاميم سمية للأزياء رائعة وفريدة من نوعها. اهتمامها بالتفاصيل وجودة الخامات جعل التصميم الذي أعدته لي مميزاً جداً.</p>
                                <div class="testimonial-author">
                                    <div class="author-avatar">
                                        <img src="https://images.unsplash.com/photo-1534751516642-a1af1ef26a56?q=80&w=1374&auto=format&fit=crop" alt="هند الأحمد">
                                    </div>
                                    <div class="author-info">
                                        <h4 class="author-name">هند الأحمد</h4>
                                        <p class="author-title">عميلة</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="testimonial-slide">
                            <div class="testimonial-card">
                                <div class="testimonial-quote">
                                    <i class="fas fa-quote-right"></i>
                                </div>
                                <p class="testimonial-text">أشكر سمية على الاحترافية والإبداع في تصميم الهوية البصرية لمشروعي. كان التعامل معها ممتعاً والنتيجة فاقت توقعاتي.</p>
                                <div class="testimonial-author">
                                    <div class="author-avatar">
                                        <img src="https://images.unsplash.com/photo-1554727242-741c14fa561c?q=80&w=1374&auto=format&fit=crop" alt="لينا العبدالله">
                                    </div>
                                    <div class="author-info">
                                        <h4 class="author-name">لينا العبدالله</h4>
                                        <p class="author-title">صاحبة مشروع</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="testimonial-controls">
                        <button class="testimonial-prev" aria-label="السابق">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <div class="testimonial-dots">
                            <span class="dot active" data-index="0"></span>
                            <span class="dot" data-index="1"></span>
                            <span class="dot" data-index="2"></span>
                        </div>
                        <button class="testimonial-next" aria-label="التالي">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </div>
    @include('parts.footer')
    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animation on scroll
        document.addEventListener('DOMContentLoaded', function() {
            // Enhanced animations for different elements
            const animateElements = document.querySelectorAll('.animate-up');
            const profileTitle = document.querySelector('.profile-title');
            const profileSubtitle = document.querySelector('.profile-subtitle');
            const profileDescription = document.querySelector('.profile-description');
            const profileSocial = document.querySelector('.profile-social');
            const photoFrame = document.querySelector('.photo-frame');
            const sectionTitles = document.querySelectorAll('.section-title');
            const portfolioItems = document.querySelectorAll('.portfolio-item');
            const serviceCards = document.querySelectorAll('.service-card');

            // Add animation classes to elements
            if(profileTitle) profileTitle.classList.add('slide-right');
            if(profileSubtitle) profileSubtitle.classList.add('fade-in');
            if(profileDescription) profileDescription.classList.add('fade-in');
            if(profileSocial) profileSocial.classList.add('slide-right');
            if(photoFrame) photoFrame.classList.add('zoom-in', 'floating');

            // Add staggered animation to service cards
            if(serviceCards.length) {
                const servicesGrid = document.querySelector('.services-grid');
                servicesGrid.classList.add('stagger-animation');

                serviceCards.forEach(card => {
                    card.classList.add('hover-lift');
                });
            }

            // Add animation to portfolio items
            portfolioItems.forEach((item, index) => {
                item.classList.add('tilt-card');
                item.style.animationDelay = `${index * 0.1}s`;
            });

            // Add special effects to section titles
            sectionTitles.forEach(title => {
                title.classList.add('text-reveal');
            });

            // Intersection Observer for scroll animations
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-in');

                        // For staggered animations on containers
                        if(entry.target.classList.contains('stagger-animation')) {
                            entry.target.classList.add('animate-in');
                        }
                    }
                });
            }, { threshold: 0.1 });

            animateElements.forEach(element => {
                observer.observe(element);
            });

            // Observe staggered animation containers
            document.querySelectorAll('.stagger-animation').forEach(element => {
                observer.observe(element);
            });

            // Initialize skill bars with enhanced animation
            const skillBars = document.querySelectorAll('.skill-progress');
            setTimeout(() => {
                skillBars.forEach(bar => {
                    const width = bar.getAttribute('data-width');
                    bar.style.width = width + '%';
                    bar.classList.add('animate-in');
                });
            }, 500);

            // Portfolio filter with improved animations
            const filterBtns = document.querySelectorAll('.filter-btn');
            const portfolioGrid = document.querySelector('.portfolio-grid');

            filterBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    // Remove active class from all buttons
                    filterBtns.forEach(filterBtn => {
                        filterBtn.classList.remove('active');
                    });

                    // Add active class to clicked button
                    btn.classList.add('active');
                    btn.classList.add('pulse');
                    setTimeout(() => {
                        btn.classList.remove('pulse');
                    }, 1000);

                    const filter = btn.getAttribute('data-filter');

                    // Add a small animation to the grid
                    portfolioGrid.classList.add('fade-in');
                    setTimeout(() => {
                        portfolioGrid.classList.remove('fade-in');
                    }, 500);

                    portfolioItems.forEach(item => {
                        const category = item.getAttribute('data-category');

                        if (filter === 'all' || filter === category) {
                            item.style.display = 'block';
                            setTimeout(() => {
                                item.classList.add('zoom-in');
                            }, 100);
                        } else {
                            item.classList.remove('zoom-in');
                            setTimeout(() => {
                                item.style.display = 'none';
                            }, 300);
                        }
                    });
                });
            });

            // Modern Testimonials Slider with enhanced transitions
            const testimonialSlides = document.querySelectorAll('.testimonial-slide');
            const dots = document.querySelectorAll('.testimonial-dots .dot');
            const prevBtn = document.querySelector('.testimonial-prev');
            const nextBtn = document.querySelector('.testimonial-next');
            let currentIndex = 0;

            function showSlide(index) {
                // Add exit animation to current slide
                testimonialSlides[currentIndex].classList.add('slide-left');

                // Hide all slides after animation
                setTimeout(() => {
                    testimonialSlides.forEach(slide => {
                        slide.classList.remove('active', 'slide-left', 'slide-right');
                    });

                    // Show the selected slide with entrance animation
                    testimonialSlides[index].classList.add('active', 'slide-right');

                    // Remove active class from all dots
                    dots.forEach(dot => {
                        dot.classList.remove('active');
                    });

                    // Show the selected dot
                    dots[index].classList.add('active');
                    currentIndex = index;
                }, 300);
            }

            // Event listeners for dots
            dots.forEach(dot => {
                dot.addEventListener('click', () => {
                    const index = parseInt(dot.getAttribute('data-index'));
                    if (index !== currentIndex) {
                        showSlide(index);
                    }
                });
            });

            // Event listeners for next and previous buttons
            prevBtn.addEventListener('click', () => {
                let newIndex = currentIndex - 1;
                if (newIndex < 0) newIndex = testimonialSlides.length - 1;
                showSlide(newIndex);
            });

            nextBtn.addEventListener('click', () => {
                let newIndex = currentIndex + 1;
                if (newIndex >= testimonialSlides.length) newIndex = 0;
                showSlide(newIndex);
            });

            // Auto advance slides every 5 seconds
            let autoSlideInterval = setInterval(() => {
                let newIndex = currentIndex + 1;
                if (newIndex >= testimonialSlides.length) newIndex = 0;
                showSlide(newIndex);
            }, 5000);

            // Pause auto slide on hover
            const testimonialSlider = document.querySelector('.testimonial-slider');
            if (testimonialSlider) {
                testimonialSlider.addEventListener('mouseenter', () => {
                    clearInterval(autoSlideInterval);
                });

                testimonialSlider.addEventListener('mouseleave', () => {
                    autoSlideInterval = setInterval(() => {
                        let newIndex = currentIndex + 1;
                        if (newIndex >= testimonialSlides.length) newIndex = 0;
                        showSlide(newIndex);
                    }, 5000);
                });
            }

            // Add parallax effect to header
            const portfolioHeader = document.querySelector('.portfolio-header');
            if (portfolioHeader) {
                window.addEventListener('scroll', () => {
                    const scrollY = window.scrollY;
                    const headerItems = portfolioHeader.querySelectorAll('.profile-info, .profile-photo');

                    headerItems.forEach(item => {
                        item.style.transform = `translateY(${scrollY * 0.2}px)`;
                    });
                });
            }
        });
    </script>
</body>
</html>
