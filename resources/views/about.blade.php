<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="تعرف على أمبير تك - شركة رائدة في توزيع الكهرباء وخدمة المشتركين">
    
    <title>حول أمبير تك - منصة توزيع الكهرباء الموثوقة</title>

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
        
        .btn-primary-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary-custom {
            background: transparent;
            border: 2px solid white;
            color: white;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
        }
        
        .btn-secondary-custom:hover {
            background: white;
            color: #667eea;
        }
        
        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }
        
        .w-16 {
            width: 4rem;
            height: 4rem;
        }
        
        .fs-4 {
            font-size: 1.5rem;
        }
        
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        
        @keyframes floating {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }
        
        .fade-in {
            animation: fadeIn 1s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .timeline-item {
            position: relative;
            padding-right: 40px;
            margin-bottom: 30px;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            right: 0;
            top: 0;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #667eea;
        }
        
        .timeline-item::after {
            content: '';
            position: absolute;
            right: 9px;
            top: 20px;
            width: 2px;
            height: calc(100% + 10px);
            background: #667eea;
        }
        
        .timeline-item:last-child::after {
            display: none;
        }
        
        .team-member {
            text-align: center;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .team-member:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }
        
        .team-member img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin-bottom: 20px;
            border: 5px solid #667eea;
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
                    <i class="fas fa-building text-white fs-2"></i>
                </div>
            </div>
            
            <h1 class="display-4 fw-bold mb-4">حول أمبير تك</h1>
            <h2 class="h2 mb-4">نلتزم بتقديم كهرباء موثوقة للجميع</h2>
            <br>
            <span class="text-warning">الشريك الموثوق في توزيع الطاقة</span>
            
            <p class="lead mb-5 mx-auto" style="max-width: 600px;">
                منذ تأسيسنا، نسعى لتوفير حلول كهرباء مبتكرة وموثوقة للمنازل والشركات. شبكتنا الواسعة من المولدات الحديثة تضمن استمرارية الخدمة في جميع الظروف.
            </p>
            
            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center mb-5">
                <a href="#services" class="btn btn-primary-custom btn-lg px-4">
                    خدماتنا
                </a>
                <a href="#contact" class="btn btn-secondary-custom btn-lg px-4 border-white text-white hover:bg-white hover:text-purple-600">
                    تواصل معنا
                </a>
            </div>
        </div>
    </section>

    <!-- Mission Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2 class="display-5 fw-bold mb-4">رؤيتنا ورسالتنا</h2>
                    <div class="mb-4">
                        <h4 class="fw-semibold text-primary mb-3">رؤيتنا</h4>
                        <p class="text-muted">
                            أن نكون الشريك الموثوق الأول في توفير حلول الكهرباء المبتكرة والمستدامة لجميع المشتركين في المناطق التي نخدمها.
                        </p>
                    </div>
                    <div class="mb-4">
                        <h4 class="fw-semibold text-primary mb-3">رسالتنا</h4>
                        <p class="text-muted">
                            توفير كهرباء موثوقة وبأسعار تنافسية مع ضمان أعلى معايير الجودة والأمان والخدمة الممتازة للمشتركين.
                        </p>
                    </div>
                    <div class="mb-4">
                        <h4 class="fw-semibold text-primary mb-3">قيمنا</h4>
                        <ul class="text-muted">
                            <li>الموثوقية والاستمرارية</li>
                            <li>الجودة في كل جانب من جوانب خدمتنا</li>
                            <li>الابتكار والتطور المستمر</li>
                            <li>التركيز على رضا المشتركين</li>
                            <li>المسؤولية تجاه البيئة والمجتمع</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="glass-effect p-4 rounded-4">
                        <div class="row g-4">
                            <div class="col-6">
                                <div class="text-center">
                                    <i class="fas fa-award text-primary fs-2 mb-3"></i>
                                    <h5 class="fw-semibold">جائزة الأداء المتميز</h5>
                                    <p class="text-muted small">2023</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <i class="fas fa-certificate text-primary fs-2 mb-3"></i>
                                    <h5 class="fw-semibold">شهادة الجودة</h5>
                                    <p class="text-muted small">ISO 9001</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <i class="fas fa-users text-primary fs-2 mb-3"></i>
                                    <h5 class="fw-semibold">فريق محترف</h5>
                                    <p class="text-muted small">50+ موظف</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <i class="fas fa-leaf text-primary fs-2 mb-3"></i>
                                    <h5 class="fw-semibold">صديق للبيئة</h5>
                                    <p class="text-muted small">مولدات حديثة</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- History Timeline -->
    <section class="py-5 hero-gradient">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold text-white mb-4">رحلتنا</h2>
                <p class="text-white-50 mx-auto" style="max-width: 600px;">
                    من فكرة بسيطة إلى شركة رائدة في توزيع الكهرباء
                </p>
            </div>
            
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="glass-effect p-5 rounded-4">
                        <div class="timeline-item">
                            <h5 class="fw-semibold">2018</h5>
                            <p class="text-muted">تأسيس أمبير تك كشركة ناشئة في مجال توزيع الكهرباء</p>
                        </div>
                        <div class="timeline-item">
                            <h5 class="fw-semibold">2019</h5>
                            <p class="text-muted">توسيع شبكة المولدات لتغطية 5 مناطق جديدة</p>
                        </div>
                        <div class="timeline-item">
                            <h5 class="fw-semibold">2020</h5>
                            <p class="text-muted">الوصول إلى 1000 مشترك وتطبيق نظام الفواتير الإلكترونية</p>
                        </div>
                        <div class="timeline-item">
                            <h5 class="fw-semibold">2021</h5>
                            <p class="text-muted">إطلاق تطبيق المشتركين المحمول وإدارة الحسابات عبر الإنترنت</p>
                        </div>
                        <div class="timeline-item">
                            <h5 class="fw-semibold">2022</h5>
                            <p class="text-muted">توسيع الخدمات لتشمل 15 منطقة والوصول إلى 3000 مشترك</p>
                        </div>
                        <div class="timeline-item">
                            <h5 class="fw-semibold">2023</h5>
                            <p class="text-muted">الحصول على شهادة الجودة وجائزة الأداء المتميز في قطاع الطاقة</p>
                        </div>
                        <div class="timeline-item">
                            <h5 class="fw-semibold">2024</h5>
                            <p class="text-muted">الوصول إلى 5000+ مشترك وتقديم باكيتات اشتراك متعددة</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold mb-4">فريق العمل</h2>
                <p class="lead text-muted mx-auto" style="max-width: 600px;">
                    فريق من الخبراء المتخصصين في مجال توزيع الكهرباء وخدمة العملاء
                </p>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="team-member">
                        <img src="https://picsum.photos/seed/ceo/150/150" alt="المدير التنفيذي">
                        <h5 class="fw-semibold">أحمد محمد</h5>
                        <p class="text-muted small">المدير التنفيذي</p>
                        <div class="d-flex justify-content-center gap-2">
                            <a href="#" class="text-primary"><i class="fab fa-linkedin"></i></a>
                            <a href="#" class="text-primary"><i class="fab fa-twitter"></i></a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="team-member">
                        <img src="https://picsum.photos/seed/cto/150/150" alt="المدير التقني">
                        <h5 class="fw-semibold">خالد العلي</h5>
                        <p class="text-muted small">المدير التقني</p>
                        <div class="d-flex justify-content-center gap-2">
                            <a href="#" class="text-primary"><i class="fab fa-linkedin"></i></a>
                            <a href="#" class="text-primary"><i class="fab fa-github"></i></a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="team-member">
                        <img src="https://picsum.photos/seed/manager/150/150" alt="مدير العمليات">
                        <h5 class="fw-semibold">سارة أحمد</h5>
                        <p class="text-muted small">مديرة العمليات</p>
                        <div class="d-flex justify-content-center gap-2">
                            <a href="#" class="text-primary"><i class="fab fa-linkedin"></i></a>
                            <a href="#" class="text-primary"><i class="fab fa-twitter"></i></a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="team-member">
                        <img src="https://picsum.photos/seed/support/150/150" alt="مدير خدمة العملاء">
                        <h5 class="fw-semibold">محمد علي</h5>
                        <p class="text-muted small">مدير خدمة العملاء</p>
                        <div class="d-flex justify-content-center gap-2">
                            <a href="#" class="text-primary"><i class="fab fa-linkedin"></i></a>
                            <a href="#" class="text-primary"><i class="fab fa-twitter"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="py-5 hero-gradient">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold text-white mb-4">أرقامنا تتحدث</h2>
                <p class="text-white-50 mx-auto" style="max-width: 600px;">
                    إنجازاتنا بالأرقام تعكس التزامنا بالتميز
                </p>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="text-center">
                        <div class="fs-1 fw-bold text-warning">5000+</div>
                        <p class="text-white-50">مشترك راضٍ</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="text-center">
                        <div class="fs-1 fw-bold text-warning">15+</div>
                        <p class="text-white-50">منطقة مغطاة</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="text-center">
                        <div class="fs-1 fw-bold text-warning">99.9%</div>
                        <p class="text-white-50">موثوقية الخدمة</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="text-center">
                        <div class="fs-1 fw-bold text-warning">24/7</div>
                        <p class="text-white-50">دعم فني</p>
                    </div>
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

        // Navbar scroll effect
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
