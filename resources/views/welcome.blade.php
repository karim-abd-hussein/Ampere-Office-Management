<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="أمبير تك - انضم كمشترك في شبكة توزيع الكهرباء الموثوقة. استمتع بالكهرباء المستمرة والدعم 24/7">
    
    <title>أمبير تك - اشترك في توزيع الكهرباء الموثوق</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .hero-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .feature-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
            border-radius: 15px;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .btn-primary-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .btn-secondary-custom {
            background: transparent;
            color: #667eea;
            border: 2px solid #667eea;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-secondary-custom:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        
        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .fade-in {
            animation: fadeIn 1s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    @include('includes.header')
    <!-- Hero Section -->
    <section class="min-vh-100 d-flex align-items-center justify-content-center pt-5">
        <div class="container text-center text-white fade-in">
            <div class="floating mb-5">
                <div class="w-24 h-24 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-3xl d-flex align-items-center justify-center mx-auto mb-4">
                    <i class="fas fa-bolt text-white"></i>
                    <i class="fas fa-bolt text-white fs-2"></i>
                </div>
            </div>
            
            <h1 class="display-4 fw-bold mb-4">أمبير تك</h1>
            <h2 class="h2 mb-4">الكهرباء الموثوقة للمنازل والشركات</h2>
            <br>
            <span class="text-warning">خدمة موثوقة 24/7</span>
            
            <p class="lead mb-5 mx-auto" style="max-width: 600px;">
                انضم إلى آلاف المشتركين الذين يستمتعون بالكهرباء المستمرة والخدمة الموثوقة. شبكتنا من المولدات الحديثة تضمن لك الطاقة عندما تحتاجها.
            </p>
            
            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center mb-5">
                <a href="#services" class="btn btn-primary-custom btn-lg px-4">
                    باكيت الاشتراك
                </a>
                <a href="#contact" class="btn btn-secondary-custom btn-lg px-4 border-white text-white hover:bg-white hover:text-purple-600">
                    سجل الآن
                </a>
            </div>
            
            <!-- Stats -->
            <div class="row g-4 mt-5">
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="stat-number">5000+</div>
                        <p class="text-white-50">مشترك سعيد</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="stat-number">99.9%</div>
                        <p class="text-white-50">موثوقية الخدمة</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="stat-number">24/7</div>
                        <p class="text-white-50">دعم فوري</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold mb-4">
                    فوائد الاشتراك مع أمبير تك
                </h2>
                <p class="lead text-muted mx-auto" style="max-width: 600px;">
                    استمتع بمزايا حصرية كمشترك في شبكتنا الموثوقة
                </p>
            </div>
            
            <div class="row g-4">
                <!-- Feature 1 -->
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card p-4 text-center h-100">
                        <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-xl d-flex align-items-center justify-center mx-auto mb-3">
                            <i class="fas fa-users text-white fs-4"></i>
                        </div>
                        <h4 class="fs-5 fw-semibold mb-3">حساب المشترك</h4>
                        <p class="text-muted">
                            إدارة حسابك الشخصي وتتبع استهلاك الكهرباء والفواتير بسهولة
                        </p>
                    </div>
                </div>
                
                <!-- Feature 2 -->
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card p-4 text-center h-100">
                        <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-emerald-500 rounded-xl d-flex align-items-center justify-center mx-auto mb-3">
                            <i class="fas fa-file-invoice-dollar text-white fs-4"></i>
                        </div>
                        <h4 class="fs-5 fw-semibold mb-3">فواتير شفافة</h4>
                        <p class="text-muted">
                            فواتير شهرية واضحة مع تفاصيل الاستهلاك وخيارات دفع متعددة
                        </p>
                    </div>
                </div>
                
                <!-- Feature 3 -->
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card p-4 text-center h-100">
                        <div class="w-16 h-16 bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl d-flex align-items-center justify-center mx-auto mb-3">
                            <i class="fas fa-chart-line text-white fs-4"></i>
                        </div>
                        <h4 class="fs-5 fw-semibold mb-3">كهرباء مستمرة</h4>
                        <p class="text-muted">
                            طاقة موثوقة 24/7 مع أنظمة احتياطية تضمن استمرارية الخدمة
                        </p>
                    </div>
                </div>
                
                <!-- Feature 4 -->
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card p-4 text-center h-100">
                        <div class="w-16 h-16 bg-gradient-to-r from-orange-500 to-red-500 rounded-xl d-flex align-items-center justify-center mx-auto mb-3">
                            <i class="fas fa-calendar-check text-white fs-4"></i>
                        </div>
                        <h4 class="fs-5 fw-semibold mb-3">دعم فوري</h4>
                        <p class="text-muted">
                            فريق دعم متواصل على مدار الساعة للطوارئ والاستفسارات
                        </p>
                    </div>
                </div>
                
                <!-- Feature 5 -->
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card p-4 text-center h-100">
                        <div class="w-16 h-16 bg-gradient-to-r from-indigo-500 to-blue-500 rounded-xl d-flex align-items-center justify-center mx-auto mb-3">
                            <i class="fas fa-shield-alt text-white fs-4"></i>
                        </div>
                        <h4 class="fs-5 fw-semibold mb-3">أسعار تنافسية</h4>
                        <p class="text-muted">
                            باكيت اشتراك مرنة تناسب احتياجاتك مع أسعار تنافسية
                        </p>
                    </div>
                </div>
                
                <!-- Feature 6 -->
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card p-4 text-center h-100">
                        <div class="w-16 h-16 bg-gradient-to-r from-teal-500 to-cyan-500 rounded-xl d-flex align-items-center justify-center mx-auto mb-3">
                            <i class="fas fa-mobile-alt text-white fs-4"></i>
                        </div>
                        <h4 class="fs-5 fw-semibold mb-3">تطبيق المشترك</h4>
                        <p class="text-muted">
                            تطبيق محمول لإدارة حسابك وتتبع الاستهلاك ودفع الفواتير
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
<!-- About Section -->
    <section id="about" class="py-5 hero-gradient">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2 class="display-5 fw-bold text-white mb-4">
                        عن أمبير تك
                    </h2>
                    <p class="text-white-50 mb-4">
                        أمبير تك هي الشريك الموثوق لتزويدك بالكهرباء المنزلية والتجارية. نخدم آلاف المشتركين بشبكة واسعة من المولدات الحديثة تضمن استمرارية الطاقة في جميع الظروف.
                    </p>
                    <p class="text-white-50 mb-4">
                        نلتزم بتوفير خدمة كهرباء موثوقة وبأسعار تنافسية مع دعم فني على مدار الساعة لضمان راحة بالك وراحة أسرتك.
                    </p>
                    <div class="row g-4">
                        <div class="col-6">
                            <div class="text-center">
                                <div class="fs-2 fw-bold text-warning">15+ منطقة</div>
                                <p class="text-white-50">تغطية خدمة</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="fs-2 fw-bold text-warning">5000+ مشترك</div>
                                <p class="text-white-50">عميل راضٍ</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="glass-effect p-4 rounded-4">
                        <div class="row g-4">
                            <div class="col-6">
                                <div class="text-center">
                                    <i class="fas fa-award text-primary fs-2 mb-3"></i>
                                    <h5 class="fw-semibold">حائز على جوائز</h5>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <a href="#services" class="btn btn-primary btn-lg ms-2">خدماتنا</a>
                                    <a href="#contact" class="btn btn-outline-light btn-lg">تواصل معنا</a>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <i class="fas fa-rocket text-primary fs-2 mb-3"></i>
                                    <h5 class="fw-semibold">نمو سريع</h5>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <i class="fas fa-heart text-primary fs-2 mb-3"></i>
                                    <h5 class="fw-semibold">العميل أولاً</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold mb-4">
                    خدماتنا
                </h2>
                <p class="lead text-muted mx-auto" style="max-width: 600px;">
                    اختر باكيت الاشتراك الذي يناسب احتياجاتك
                </p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="glass-effect p-4 rounded-3 text-center h-100">
                        <i class="fas fa-home text-primary fs-1 mb-3"></i>
                        <h5 class="fw-semibold mb-2">باكيت منزلي</h5>
                        <p class="text-muted small">كهرباء موثوقة للمنازل بأسعار تنافسية</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="glass-effect p-4 rounded-3 text-center h-100">
                        <i class="fas fa-building text-primary fs-1 mb-3"></i>
                        <h5 class="fw-semibold mb-2">باكيت تجاري</h5>
                        <p class="text-muted small">حلول طاقة متخصصة للشركات والمكاتب</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="glass-effect p-4 rounded-3 text-center h-100">
                        <i class="fas fa-industry text-primary fs-1 mb-3"></i>
                        <h5 class="fw-semibold mb-2">باكيت صناعي</h5>
                        <p class="text-muted small">طاقة عالية للمصانع والمنشآت الكبرى</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="glass-effect p-4 rounded-3 text-center h-100">
                        <i class="fas fa-star text-primary fs-1 mb-3"></i>
                        <h5 class="fw-semibold mb-2">باكيت مميز</h5>
                        <p class="text-muted small">خدمة VIP مع أولوية ودعم مميز</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-5 hero-gradient">
        <div class="container">
            <div class="text-center">
                <h2 class="display-5 fw-bold text-white mb-4">
                    هل أنت جاهز للاشتراك معنا؟
                </h2>
                <p class="text-white-50 mb-5 mx-auto" style="max-width: 600px;">
                    انضم إلى آلاف المشتركين الذين يستمتعون بالكهرباء الموثوقة والخدمة المميزة
                </p>
                
                <div class="glass-effect p-5 rounded-4">
                    <div class="row g-4 mb-5">
                        <div class="col-md-4">
                            <i class="fas fa-envelope text-primary fs-3 mb-3"></i>
                            <h5 class="fw-semibold">البريد الإلكتروني</h5>
                            <p class="text-muted">subscription@amptech.com</p>
                        </div>
                        <div class="col-md-4">
                            <i class="fas fa-phone text-primary fs-3 mb-3"></i>
                            <h5 class="fw-semibold">الهاتف</h5>
                            <p class="text-muted">+966 50 123 4567 (اشتراكات)</p>
                        </div>
                        <div class="col-md-4">
                            <i class="fas fa-map-marker-alt text-primary fs-3 mb-3"></i>
                            <h5 class="fw-semibold">الموقع</h5>
                            <p class="text-muted">دمشق - 15+ منطقة</p>
                        </div>
                    </div>
                    
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn btn-primary-custom btn-lg px-5">
                            سجل كمشترك جديد
                        </a>
                        <p class="text-white-50 mt-3">للاستفسارات حول الاشتراكات، يرجى التواصل معنا عبر البريد الإلكتروني أو الهاتف.</p>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @include('includes.footer')

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Smooth Scroll Script -->
    <script>
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Add scroll effect to navigation
        window.addEventListener('scroll', function() {
            const nav = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                nav.classList.add('shadow-lg');
            } else {
                nav.classList.remove('shadow-lg');
            }
        });
    </script>
</body>
</html>
