<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Table;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TableController extends Controller
{
    public function index()
    {
        $tables = Table::withCount('activeOrders')->orderBy('name')->get();
        return view('admin.tables.index', compact('tables'));
    }

    public function create()
    {
        return view('admin.tables.form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        $table = Table::create([
            'name' => $request->name,
            'capacity' => $request->capacity,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('admin.tables.index')
            ->with('success', 'Meja berhasil ditambahkan!');
    }

    public function edit(Table $table)
    {
        return view('admin.tables.form', compact('table'));
    }

    public function update(Request $request, Table $table)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        $table->update([
            'name' => $request->name,
            'capacity' => $request->capacity,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('admin.tables.index')
            ->with('success', 'Meja berhasil diperbarui!');
    }

    public function destroy(Table $table)
    {
        $table->delete();
        return redirect()->route('admin.tables.index')
            ->with('success', 'Meja berhasil dihapus!');
    }

    public function generateQr(Table $table)
    {
        $url = url("/menu/{$table->id}");
        $qrCode = QrCode::format('svg')
            ->size(300)
            ->color(185, 28, 28)
            ->backgroundColor(255, 255, 255)
            ->margin(2)
            ->generate($url);

        return response()->json([
            'success' => true,
            'qr_code' => base64_encode($qrCode),
            'url' => $url,
            'table_name' => $table->name,
        ]);
    }

    public function printQr(Table $table)
    {
        $url = url("/menu/{$table->id}");
        $settings = SiteSetting::pluck('value', 'key')->toArray();
        return view('admin.tables.print-qr', compact('table', 'url', 'settings'));
    }

    public function resetTable(Table $table)
    {
        $table->update(['status' => 'available']);
        return response()->json([
            'success' => true,
            'message' => 'Status meja berhasil direset!',
        ]);
    }
}
