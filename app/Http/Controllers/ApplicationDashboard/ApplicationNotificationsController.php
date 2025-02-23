<?php

namespace App\Http\Controllers\ApplicationDashboard;

use App\Http\Controllers\Controller;
use App\Models\ApplicationNotifications;
use App\Models\Client;
use App\Services\FirebaseClientService;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;

class ApplicationNotificationsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        if (request()->ajax()) {

            $applicationNotifications = ApplicationNotifications::
                with('client')->
                select(['id', 'title', 'body', 'type', 'client_id']);

            return Datatables::of($applicationNotifications)
                ->addColumn('client',function($item){
                    return $item->client->contact->name ?? '';

                })
                ->addColumn(
                    'action',
                    '
                    <button data-href="{{ action(\'\\App\\Http\\Controllers\\ApplicationDashboard\\ApplicationNotificationsController@destroy\', [$id]) }}" class="btn btn-xs btn-danger delete_notification_button">
                    <i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</button>
                '
                )
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('applicationDashboard.pages.notifications.index');

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        return view('applicationDashboard.pages.notifications.create');

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
        //
        $request->validate([
            'title' => 'required',
            'body' => 'required',
            'type' => 'required',
            'client_id' => 'nullable|exists:clients,id'
        ]);
        $notification = ApplicationNotifications::create([
            'title' => $request->title,
            'body' => $request->body,
            'type' => $request->type,
            'client_id' => $request->client_id ?? null,
        ]);

        $client = Client::find($request->client_id);
        if ($client) {
            app(FirebaseClientService::class)->sendAndStoreNotification(
                $client->id,
                $client->fcm_token,
                $request->title,
                $request->body,
                []
            );
        } else {
            $clients = Client::all();
            foreach ($clients as $client) {
                app(FirebaseClientService::class)->sendAndStoreNotification(
                    $client->id,
                    $client->fcm_token,
                    $request->title,
                    $request->body,
                    []
                );
            }
        }

        $output = [
            'success' => true,
            'data' => $notification,
            'msg' => __("lang_v1.added_success")
        ];

    } catch (\Exception $e) {
        \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

        $output = [
            'success' => false,
            'msg' => __("messages.something_went_wrong")
        ];
    }

    return response()->json($output);

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ApplicationNotifications  $applicationNotifications
     * @return \Illuminate\Http\Response
     */
    public function show(ApplicationNotifications $applicationNotifications)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ApplicationNotifications  $applicationNotifications
     * @return \Illuminate\Http\Response
     */
    public function edit(ApplicationNotifications $applicationNotifications)
    {
        //
        return view('applicationDashboard.pages.notifications.edit')
            ->with(compact('applicationNotifications'));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ApplicationNotifications  $applicationNotifications
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ApplicationNotifications $applicationNotifications)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ApplicationNotifications  $applicationNotifications
     * @return \Illuminate\Http\Response
     */
    public function destroy(ApplicationNotifications $applicationNotifications)
    {
        //
        $applicationNotifications->delete();
        return redirect()->route('applicationDashboard.notifications.index')->with('success', 'Notification deleted successfully');
    }

    public function getClients()
    {
        $clients = Client::with('contact')->get();
        return response()->json($clients);
    }
}
