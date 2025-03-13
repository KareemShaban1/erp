<?php

namespace App\Http\Controllers\ApplicationDashboard;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Models\Category;
use App\Models\Product;
use App\Utils\ProductUtil;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;

class TagController extends Controller
{

    /**
     * All Utils instance.
     *
     */

    protected $productUtil;



    public function __construct(ProductUtil $productUtil)
    {
        $this->productUtil = $productUtil;

    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('tags.view')) {
            abort(403, 'Unauthorized action.');
        }
        if (request()->ajax()) {

            $tags = Tag::
                select(['id', 'name', 'active']);

            return Datatables::of($tags)

                ->addColumn('action', function ($row) {
                    $user = auth()->user();
                    $buttons = '';

                    // Check if the user has permission to edit
                    if ($user->can('tags.update')) {
                        $buttons .= '<button data-href="' . action('\App\Http\Controllers\ApplicationDashboard\TagController@edit', [$row->id]) . '" 
                                     class="btn btn-xs btn-primary edit_tag_button">
                                     <i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</button> &nbsp;';
                    }

                    // Check if the user has permission to delete
                    if ($user->can('tags.delete')) {
                        $buttons .= '<button data-href="' . action('\App\Http\Controllers\ApplicationDashboard\TagController@destroy', [$row->id]) . '" 
                                     class="btn btn-xs btn-danger delete_tag_button">
                                     <i class="glyphicon glyphicon-trash"></i> ' . __("messages.delete") . '</button>';
                    }

                    return $buttons;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('applicationDashboard.pages.tags.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        if (!auth()->user()->can('tags.create')) {
            abort(403, 'Unauthorized action.');
        }

        return view('applicationDashboard.pages.tags.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        if (!auth()->user()->can('tags.create')) {
            abort(403, 'Unauthorized action.');
        }

        $this->validate($request, [
            'name' => 'required|string|max:255',
            'active' => 'boolean',
        ]);

        try {

            $input = $request->only(['name', 'active']);

            $input['active'] = $input['active'] ?? 0;


            $tag = Tag::create($input);

            $output = [
                'success' => true,
                'data' => $tag,
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
     * @param  \App\Models\Tag  $tag
     * @return \Illuminate\Http\Response
     */
    public function show(Tag $tag)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Tag  $tag
     * @return \Illuminate\Http\Response
     */
    public function edit(Tag $tag)
    {
        //
        if (!auth()->user()->can('tags.update')) {
            abort(403, 'Unauthorized action.');
        }
        return view('applicationDashboard.pages.tags.edit')
            ->with(compact('tag'));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Tag  $tag
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('tags.update')) {
            abort(403, 'Unauthorized action.');
        }
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'active' => 'boolean',
        ]);

        try {
            $tag = Tag::findOrFail($id); // Find the tag by ID

            $input = $request->only(['name', 'active']);

            $input['active'] = $input['active'] ?? 0;


            $tag->update($input); // Update the tag

            $output = [
                'success' => true,
                'data' => $tag,
                'msg' => __("lang_v1.updated_success")
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


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Tag  $tag
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {

            if (!auth()->user()->can('tags.delete')) {
                abort(403, 'Unauthorized action.');
            }

            $tag = Tag::findOrFail($id); // Find the tag by ID

            $tag->delete(); // Delete the tag

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



}
