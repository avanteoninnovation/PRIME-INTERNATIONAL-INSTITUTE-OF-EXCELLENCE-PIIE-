<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AuditLog;
use App\Models\GraduationApplication;
use App\Models\ProcurementRequest;
use App\Models\Programme;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AssetController extends Controller
{
    private $school_id;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->school_id = Auth::user()->school_id;
            return $next($request);
        });
    }

    // ── Asset Categories ──────────────────────────────────────────────────

    public function categories()
    {
        $categories = AssetCategory::where('school_id', $this->school_id)
            ->withCount('assets')
            ->orderBy('name')
            ->get();
        return view('admin.asset.categories', compact('categories'));
    }

    public function categoryModal()
    {
        return view('admin.asset.category_modal');
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate(['name' => 'required|max:100', 'icon' => 'nullable|max:50', 'color' => 'nullable|max:10']);
        $validated['school_id'] = $this->school_id;
        AssetCategory::create($validated);
        return response()->json(['status' => 'success', 'message' => get_phrase('Category created')]);
    }

    public function destroyCategory($id)
    {
        AssetCategory::where('school_id', $this->school_id)->findOrFail($id)->delete();
        return redirect()->back()->with('success', get_phrase('Category deleted'));
    }

    // ── Assets ────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $search     = $request->search ?? '';
        $category   = $request->category ?? '';
        $assets     = Asset::where('school_id', $this->school_id)
            ->when($search, fn($q) => $q->where('name', 'like', "%$search%")->orWhere('asset_tag', 'like', "%$search%"))
            ->when($category, fn($q) => $q->where('category_id', $category))
            ->with(['category', 'assignedUser'])
            ->orderBy('name')
            ->paginate(20);

        $categories = AssetCategory::where('school_id', $this->school_id)->orderBy('name')->get();
        $staff      = User::where('school_id', $this->school_id)
            ->whereIn('role_id', [2, 3, 6, 7, 8])
            ->orderBy('name')->get();

        return view('admin.asset.index', compact('assets', 'categories', 'staff', 'search', 'category'));
    }

    public function openModal(Request $request)
    {
        $id         = $request->id;
        $asset      = $id ? Asset::where('school_id', $this->school_id)->findOrFail($id) : null;
        $categories = AssetCategory::where('school_id', $this->school_id)->orderBy('name')->get();
        $staff      = User::where('school_id', $this->school_id)
            ->whereIn('role_id', [2, 3, 6, 7, 8])
            ->orderBy('name')->get();
        return view('admin.asset.modal', compact('asset', 'categories', 'staff'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|max:255',
            'asset_tag'     => 'nullable|max:50',
            'category_id'   => 'nullable|exists:asset_categories,id',
            'serial_number' => 'nullable|max:100',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'location'      => 'nullable|max:150',
            'condition'     => 'required|in:new,good,fair,poor,condemned',
            'assigned_to'   => 'nullable|exists:users,id',
        ]);
        $validated['school_id'] = $this->school_id;
        $a = Asset::create($validated);
        AuditLog::record('create', 'Assets', "Registered asset: {$a->name}");
        return response()->json(['status' => 'success', 'message' => get_phrase('Asset registered')]);
    }

    public function update(Request $request, $id)
    {
        $asset     = Asset::where('school_id', $this->school_id)->findOrFail($id);
        $validated = $request->validate([
            'name'          => 'required|max:255',
            'asset_tag'     => 'nullable|max:50',
            'category_id'   => 'nullable|exists:asset_categories,id',
            'serial_number' => 'nullable|max:100',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'location'      => 'nullable|max:150',
            'condition'     => 'required',
            'assigned_to'   => 'nullable|exists:users,id',
        ]);
        $asset->update($validated);
        return response()->json(['status' => 'success', 'message' => get_phrase('Asset updated')]);
    }

    public function destroy($id)
    {
        $a = Asset::where('school_id', $this->school_id)->findOrFail($id);
        AuditLog::record('delete', 'Assets', "Deleted asset: {$a->name}");
        $a->delete();
        return redirect()->back()->with('success', get_phrase('Asset deleted'));
    }
}
