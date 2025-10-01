<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

/** موديلات وسياساتك */
use App\Models\Subscriber;
use App\Policies\SubscriberPolicy;

use App\Models\Company;
use App\Policies\CompanyPolicy;

use App\Models\User;
use App\Policies\UserPolicy;

use App\Models\Receipt;
use App\Policies\ReceiptPolicy;

use App\Models\Invoice;
use App\Policies\InvoicePolicy;

use App\Models\Cycle;
use App\Policies\CyclePolicy;

use App\Models\CompanyInvoice;
use App\Policies\CompanyInvoicePolicy;

use App\Models\Collector;
use App\Policies\CollectorPolicy;

use App\Models\Generator;
use App\Policies\GeneratorPolicy;

use App\Models\Area;
use App\Policies\AreaPolicy;

/** Spatie Role/Permission */
use Spatie\Permission\Models\Role as SpatieRole;
use App\Policies\RolePolicy;

use Spatie\Permission\Models\Permission as SpatiePermission;
use App\Policies\PermissionPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Subscriber::class     => SubscriberPolicy::class,
        Company::class        => CompanyPolicy::class,
        User::class           => UserPolicy::class,
        Receipt::class        => ReceiptPolicy::class,
        Invoice::class        => InvoicePolicy::class,
        Cycle::class          => CyclePolicy::class,
        CompanyInvoice::class => CompanyInvoicePolicy::class,
        Collector::class      => CollectorPolicy::class,
        Generator::class      => GeneratorPolicy::class,   // ✅ المولدات
        Area::class           => AreaPolicy::class,        // ✅ المناطق
        SpatieRole::class     => RolePolicy::class,
        SpatiePermission::class => PermissionPolicy::class, // ✅ الصلاحيات
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // السماح الشامل للأدوار العليا
        Gate::before(function ($user, string $ability) {
            return $user?->hasAnyRole(['مشرف','admin','super-admin']) ? true : null;
        });
    }
}
