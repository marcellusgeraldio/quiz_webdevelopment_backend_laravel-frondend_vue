<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProdukController extends Controller
{
    public function list(Request $request)
    {
        // 1. Ambil Input (Filter & Pagination)
        $keyword = $request->input('keyword');
        $sortBy = $request->input('sort_by', 'nama_produk');
        $sortDir = $request->input('sort_dir', 'asc');
        $start = $request->input('start', 0);
        $limit = $request->input('limit', 10);

        // 2. Siapkan Query
        $query = DB::table('produk');

        // 3. Filter Pencarian (Nama ATAU Kategori)
        if (!empty($keyword)) {
            $query->where(function($q) use ($keyword) {
                $q->where('nama_produk', 'like', '%' . $keyword . '%')
                  ->orWhere('kategori', 'like', '%' . $keyword . '%');
            });
        }

        // 4. Hitung Total (Untuk Pagination Vue)
        $totalData = $query->count();

        // 5. Ambil Data (Offset & Limit)
        $data = $query->orderBy($sortBy, $sortDir)
                      ->offset($start)
                      ->limit($limit)
                      ->get();

        // 6. Kirim JSON
        return response()->json([
            'count' => $totalData,
            'produk' => $data
        ]);
    }
}