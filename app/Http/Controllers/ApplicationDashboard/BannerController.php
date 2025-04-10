<?php

namespace App\Http\Controllers\ApplicationDashboard;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Utils\ProductUtil;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;

class BannerController extends Controller
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
        if (!auth()->user()->can('banners.view')) {
            abort(403, 'Unauthorized action.');
        }
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $banners = Banner::
                select(['id', 'name','image', 'business_id', 'active']);

            return Datatables::of($banners)
            ->addColumn('action', function ($row) {
                $user = auth()->user();
                $buttons = '';
        
                // Check if the user has permission to edit
                if ($user->can('banners.update')) {
                    $buttons .= '<button data-href="' . action('\App\Http\Controllers\ApplicationDashboard\BannerController@edit', [$row->id]) . '" 
                                 class="btn btn-xs btn-primary edit_banner_button">
                                 <i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</button> &nbsp;';
                }
        
                // Check if the user has permission to delete
                if ($user->can('banners.delete')) {
                    $buttons .= '<button data-href="' . action('\App\Http\Controllers\ApplicationDashboard\BannerController@destroy', [$row->id]) . '" 
                                 class="btn btn-xs btn-danger delete_banner_button">
                                 <i class="glyphicon glyphicon-trash"></i> ' . __("messages.delete") . '</button>';
                }
        
                return $buttons;
            })
            ->editColumn('image', function ($row) {
                return '<div style="display: flex;"><img src="' . $row->image_url . '" alt="Product image" class="product-thumbnail-small"></div>';
            })
                ->rawColumns(['action','image'])
                ->make(true);
        }

        return view('applicationDashboard.pages.banners.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        if (!auth()->user()->can('banners.create')) {
            abort(403, 'Unauthorized action.');
        }

        return view('applicationDashboard.pages.banners.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        if (!auth()->user()->can('banners.create')) {
            abort(403, 'Unauthorized action.');
        }
     
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'active' => 'boolean',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Image validation
            'module_type'=>'nullable|string',
            'module_id'=>'nullable',
        ]);
    
        try {
            $business_id = request()->session()->get('user.business_id');
    
            $input = $request->only(['name', 'active','module_type','module_id']);
            $input['business_id'] = $business_id;

            $input['active'] = $input['active'] ?? 0;
    
            // Upload the image if present
            if ($request->hasFile('image')) {
                $input['image'] = $this->productUtil->uploadFile($request, 'image', config('constants.product_img_path'), 'image');
            }
    
            $banner = Banner::create($input);
    
            $output = [
                'success' => true,
                'data' => $banner,
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
     * @param  \App\Models\Banner  $banner
     * @return \Illuminate\Http\Response
     */
    public function show(Banner $banner)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Banner  $banner
     * @return \Illuminate\Http\Response
     */
    public function edit(Banner $banner)
    {
        //
        if (!auth()->user()->can('banners.update')) {
            abort(403, 'Unauthorized action.');
        }
        return view('applicationDashboard.pages.banners.edit')
        ->with(compact('banner'));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Banner  $banner
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('banners.update')) {
            abort(403, 'Unauthorized action.');
        }
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'active' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Image validation
            'module_type'=>'nullable|string',
            'module_id'=>'nullable',
        ]);
    
        try {
            $banner = Banner::findOrFail($id); // Find the banner by ID
            $business_id = request()->session()->get('user.business_id');
    
            $input = $request->only(['name', 'active']);
            $input['business_id'] = $business_id;

            $input['active'] = $input['active'] ?? 0;

    
            // Upload the image if present
            if ($request->hasFile('image')) {
                $input['image'] = $this->productUtil->uploadFile($request, 'image', config('constants.product_img_path'), 'image');
            
                if (!empty($input['image'])) {
    
                    //If previous image found then remove
                    if (!empty($category->image_path) && file_exists($banner->image_path)) {
                        unlink($banner->image_path);
                    }
                }
            }
    
            $banner->update($input); // Update the banner
    
            $output = [
                'success' => true,
                'data' => $banner,
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
     * @param  \App\Models\Banner  $banner
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
{
    try {

        if (!auth()->user()->can('banners.delete')) {
            abort(403, 'Unauthorized action.');
        }

        $banner = Banner::findOrFail($id); // Find the banner by ID

        // Delete the associated image if it exists
        if ($banner->image) {
            unlink($banner->image_path); // Adjust this method to delete the file
        }

        $banner->delete(); // Delete the banner

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


public function getProducts()
{
    \Log::info('Fetching products');
    $products = Product::where('products.type', '!=', 'modifier')
        ->businessId()
        ->productForSales()
        ->get(['id', 'name']);

    \Log::info('Products fetched: ' . $products->count());
    return response()->json($products);
}

public function getCategories()
{
    \Log::info('Fetching categories');
    $categories = Category::with('sub_categories')->get(['id', 'name']);
    \Log::info('Categories fetched: ' . $categories->count());
    return response()->json($categories);
}


}
