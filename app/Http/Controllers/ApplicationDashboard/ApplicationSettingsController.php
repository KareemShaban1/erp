<?php

namespace App\Http\Controllers\ApplicationDashboard;

use App\Http\Controllers\Controller;
use App\Models\ApplicationSettings;
use Illuminate\Http\Request;

class ApplicationSettingsController extends Controller
{
    /**
     * Display a listing of the settings.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $settings = ApplicationSettings::all();
            return response()->json($settings);
        }
    
        $settings = ApplicationSettings::all();
        return view('applicationDashboard.pages.settings.index', compact('settings'));
    }
    
    /**
     * Show the form for creating a new setting.
     */
    public function create()
    {
        $types = ['string', 'boolean', 'text', 'integer', 'float', 'json'];
        return view('applicationDashboard.pages.settings.create', compact('types'));
    }

    /**
     * Store a newly created setting in storage.
     */
    public function store(Request $request)
    {

        $request->validate([
            'key' => 'required|string|unique:application_settings,key',
            'type' => 'required|in:string,boolean,text,integer,float,json',
            'value' => 'nullable',
            'group' => 'nullable|string',
        ]);

        $setting = new ApplicationSettings();
        $setting->key = $request->key;
        $setting->type = $request->type;
        $setting->group = $request->group;
        $setting->value = $this->castValue($request->value, $request->type);


        $setting->save();

        return redirect()->route('application_settings.index')->with('success', 'Setting created successfully.');
    }

    public function show($id)
    {
    // Fetch the setting by ID
    $setting = ApplicationSettings::findOrFail($id);

    // Return the setting details as a JSON response
        return response()->json([
            'data' => $setting
        ]);
    }


    /**
     * Show the form for editing the specified setting.
     */
    public function edit(ApplicationSettings $applicationSetting)
    {
        $types = ['string', 'boolean', 'text', 'integer', 'float', 'json'];
        return view('application_settings.edit', compact('applicationSetting', 'types'));
    }

    /**
     * Update the specified setting in storage.
     */
    public function update(Request $request,  $id)
    {
        $applicationSetting = ApplicationSettings::findOrFail($id);

        $request->validate([
            'key' => 'required|string|unique:application_settings,key,' . $applicationSetting->id,
            'type' => 'required|in:string,boolean,text,integer,float,json',
            'value' => 'nullable',
            'group' => 'nullable|string',
        ]);


        $applicationSetting->key = $request->key;
        $applicationSetting->type = $request->type;
        $applicationSetting->group = $request->group;
        $applicationSetting->value = $this->castValue($request->value, $request->type);

        $applicationSetting->save();

        return redirect()->route('application_settings.index')->with('success', 'Setting updated successfully.');
    }

    /**
     * Remove the specified setting from storage.
     */
    public function destroy( $id)
    {
        $applicationSetting = ApplicationSettings::findOrFail($id);
        $applicationSetting->delete();
        return redirect()->route('application_settings.index')->with('success', 'Setting deleted successfully.');
    }

    /**
     * Cast value based on type.
     */
    private function castValue($value, $type)
    {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'json':
                return json_encode($value);
            default:
                return $value;
        }
    }
    
}
