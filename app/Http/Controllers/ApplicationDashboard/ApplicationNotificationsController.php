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
                ->addColumn('client', function ($item) {
                    return $item->client->contact->name ?? '';

                })
                ->addColumn('action', function ($row) {
                    $user = auth()->user();
                    $buttons = '';

                    // Check if the user has permission to delete
                    if ($user->can('notifications.delete')) {
                        $buttons .= '<button data-href="' . action('\App\Http\Controllers\ApplicationDashboard\ApplicationNotificationsController@destroy', [$row->id]) . '" 
                                     class="btn btn-xs btn-danger delete_notifications_button">
                                     <i class="glyphicon glyphicon-trash"></i> ' . __("messages.delete") . '</button>';
                    }
            
                    return $buttons;
                })
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
                    [
                        'title' => $request->title,
                        'body' => $request->body,
                    ]
                );
            } else {
                $clients = Client::whereHas('contact', function ($query) {
                    return $query->where('contact_status', "active");
                })->get();
                foreach ($clients as $client) {
                    app(FirebaseClientService::class)->sendAndStoreNotification(
                        $client->id,
                        $client->fcm_token ?? "test123",
                        $request->title,
                        $request->body,
                        [
                            'title' => $request->title,
                            'body' => $request->body,
                        ]
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
    public function destroy($id)
    {
        //
        try {
            $applicationNotifications = ApplicationNotifications::find($id);
            $applicationNotifications->delete();
            $output = [
                'success' => true,
                'msg' => __("lang_v1.deleted_success")
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . " Line:" . $e->getLine() . " Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return response()->json($output);


    }

    public function getClients()
    {
        $clients = Client::with('contact')
            ->whereHas('contact', function ($query) {
                return $query->where('contact_status', "active");
            })->get();
        return response()->json($clients);
    }
}
