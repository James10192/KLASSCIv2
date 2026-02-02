<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class ServiceTechniqueBulletinStyleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:serviceTechnique');
    }

    public function index()
    {
        $this->ensureBulletinStyleSetting();

        $currentStyle = Setting::get('bulletin_style', 'yakro');

        return view('esbtp.service-technique.bulletin-style', compact('currentStyle'));
    }

    public function update(Request $request)
    {
        $this->ensureBulletinStyleSetting();

        $validated = $request->validate([
            'bulletin_style' => 'required|in:yakro,abidjan',
        ]);

        Setting::set('bulletin_style', $validated['bulletin_style'], auth()->id());

        return back()->with('success', 'Style du bulletin mis a jour avec succes.');
    }

    private function ensureBulletinStyleSetting()
    {
        Setting::firstOrCreate(
            ['key' => 'bulletin_style'],
            [
                'value' => 'yakro',
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Style de bulletin PDF',
                'is_required' => false,
                'default_value' => 'yakro',
                'validation_rules' => ['required', 'in:yakro,abidjan'],
                'sort_order' => 20,
                'is_active' => true,
            ]
        );
    }
}
