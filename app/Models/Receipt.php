<?php

// App/Models/Receipt.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receipt extends Model
{
    protected $fillable = ['invoice_id','type','issued_at','amount'];
    protected $casts = ['issued_at' => 'datetime','amount' => 'decimal:2'];

    // 👇 نخلي الرمز القصير يظهر تلقائياً بالحمل (اختياري)
    protected $appends = ['short_code'];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** ===== ترميز Base36 مع محرف تدقيق بسيط ===== */

    // حرف ثابت قصير كبادئة (اختياري)
    public const SHORT_PREFIX = 'R';
    private const ALPHABET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public function getShortCodeAttribute(): string
    {
        $b36 = self::toBase36($this->id);
        $cs  = self::checksum36($this->id);
        return self::SHORT_PREFIX . $b36 . $cs;
    }

    /** بحث/فك الترميز من الرمز القصير → id (وترجيع null لو باطل) */
    public static function decodeShortCode(?string $code): ?int
    {
        if (!$code) return null;
        $code = strtoupper(trim($code));

        if (str_starts_with($code, self::SHORT_PREFIX)) {
            $code = substr($code, strlen(self::SHORT_PREFIX));
        }

        if (strlen($code) < 2) return null;
        $payload  = substr($code, 0, -1);
        $checksum = substr($code, -1);

        $id = self::fromBase36($payload);
        if ($id === null) return null;

        return self::checksum36($id) === $checksum ? $id : null;
    }

    /** Scope مريح: whereShortCode('R1Z3K9') */
    public function scopeWhereShortCode($q, string $code)
    {
        $id = self::decodeShortCode($code);
        return $id ? $q->whereKey($id) : $q->whereRaw('0=1');
    }

    /**=== Helpers ===*/
    private static function toBase36(int $n): string
    {
        if ($n === 0) return '0';
        $s = '';
        while ($n > 0) { $s = self::ALPHABET[$n % 36] . $s; $n = intdiv($n, 36); }
        return $s;
    }

    private static function fromBase36(string $s): ?int
    {
        $s = strtoupper($s);
        $n = 0;
        for ($i = 0, $len = strlen($s); $i < $len; $i++) {
            $pos = strpos(self::ALPHABET, $s[$i]);
            if ($pos === false) return null;
            $n = $n * 36 + $pos;
        }
        return $n;
    }

    private static function checksum36(int $id): string
    {
        return self::ALPHABET[$id % 36];
    }
}
