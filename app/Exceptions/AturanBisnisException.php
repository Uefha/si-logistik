<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Pelanggaran aturan bisnis yang pesannya aman ditampilkan langsung ke admin
 * (mis. menghapus master yang masih dipakai).
 */
class AturanBisnisException extends RuntimeException
{
}
