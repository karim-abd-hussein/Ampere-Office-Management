<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanViewDashboard
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // نتحرك فقط إذا المستخدم داخل على الداشبورد (/admin أو /admin/dashboard)
        $isDashboard = $request->is('admin') || $request->is('admin/') || $request->is('admin/dashboard');

        if ($isDashboard && $user && $user->cannot('عرض لوحة التحكم')) {
            // حوّل لصفحة فواتير الشركات (بدّل الصفحة إذا بدك مسار ثاني)
            if (app('router')->has('filament.admin.resources.company-invoices.index')) {
                return redirect()->route('filament.admin.resources.company-invoices.index');
            }

            // احتياط لو ما كانت الصفحة موجودة
            return redirect('/admin/login');
        }

        return $next($request);
    }
}
