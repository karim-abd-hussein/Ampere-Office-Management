<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-light glass-effect fixed-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="/">
            <div class="w-10 h-10 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-lg d-flex align-items-center justify-center ms-2">
                <i class="fas fa-bolt text-white"></i>
            </div>
            <span class="fw-bold fs-4 me-3">أمبير تك</span>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/">الرئيسية</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('about') }}">من نحن</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/#services">الخدمات</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/#contact">اتصل بنا</a>
                </li>
            </ul>
            
            @if (Route::has('login'))
                <ul class="navbar-nav">
                    @guest
                        <li class="nav-item ms-2">
                            <a href="{{ route('login') }}" class="btn btn-outline-light btn-sm">دخول</a>
                        </li>
                        <li class="nav-item ms-2">
                            <a href="{{ route('register') }}" class="btn btn-light btn-sm">تسجيل</a>
                        </li>
                    @endguest
                    @auth
                        <li class="nav-item ms-2">
                            <a href="{{ route('dashboard') }}" class="btn btn-light btn-sm">لوحة التحكم</a>
                        </li>
                    @endauth
                </ul>
            @endif
        </div>
    </div>
</nav>
