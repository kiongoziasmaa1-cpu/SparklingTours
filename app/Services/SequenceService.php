<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

final class SequenceService
{
    /** Must be called inside the same transaction that creates the document. */
    public function next(string $prefix): string
    {
        $key = $prefix.'-'.now('Africa/Dar_es_Salaam')->year;
        DB::table('document_sequences')->insertOrIgnore(['key' => $key, 'value' => 0]);
        $row = DB::table('document_sequences')->where('key', $key)->lockForUpdate()->first();
        $n = $row->value + 1;
        DB::table('document_sequences')->where('key', $key)->update(['value' => $n]);

        return $key.'-'.str_pad((string) $n, 6, '0', STR_PAD_LEFT);
    }
}
