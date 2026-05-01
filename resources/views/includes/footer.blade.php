<!-- Footer -->
<footer class="bg-dark text-white py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-lg d-flex align-items-center justify-center ms-2">
                        <i class="fas fa-bolt text-white"></i>
                    </div>
                    <span class="fs-4 fw-bold">أمبير تك</span>
                </div>
                <p class="text-white-50">
                    منصة متخصصة لخدمة المشتركين في توزيع الكهرباء الموثوقة.
                </p>
            </div>
            
            <div class="col-md-2">
                <h5 class="fw-semibold mb-3">خدمات المشتركين</h5>
                <ul class="list-unstyled text-white-50">
                    <li class="mb-2"><a href="/#services" class="text-white-50 text-decoration-none">باكيت الاشتراك</a></li>
                    <li class="mb-2"><a href="/#features" class="text-white-50 text-decoration-none">فوائد الاشتراك</a></li>
                    <li class="mb-2"><a href="/#contact" class="text-white-50 text-decoration-none">التسجيل</a></li>
                </ul>
            </div>
            
            <div class="col-md-2">
                <h5 class="fw-semibold mb-3">الدعم</h5>
                <ul class="list-unstyled text-white-50">
                    <li class="mb-2"><a href="{{ route('about') }}" class="text-white-50 text-decoration-none">من نحن</a></li>
                    <li class="mb-2"><a href="/#contact" class="text-white-50 text-decoration-none">اتصل بنا</a></li>
                    <li class="mb-2"><a href="tel:+966501234567" class="text-white-50 text-decoration-none">دعم فوري</a></li>
                </ul>
            </div>
            
            <div class="col-md-4">
                <h5 class="fw-semibold mb-3">تواصل معنا</h5>
                <div class="d-flex gap-3">
                    <a href="#" class="text-white-50 fs-4"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-white-50 fs-4"><i class="fab fa-linkedin"></i></a>
                    <a href="#" class="text-white-50 fs-4"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="text-white-50 fs-4"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
        
        <hr class="border-secondary my-4">
        <div class="text-center text-white-50">
            <p>&copy; {{ date('Y') }} أمبير تك. جميع الحقوق محفوظة.</p>
        </div>
    </div>
</footer>
