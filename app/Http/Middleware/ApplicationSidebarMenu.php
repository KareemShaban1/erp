<?php

namespace App\Http\Middleware;

use App\Utils\ModuleUtil;
use Closure;
use Menu;

class ApplicationSidebarMenu
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->ajax()) {
            return $next($request);
        }

        Menu::create('admin-sidebar-menu', function ($menu) {
            $enabled_modules = !empty(session('business.enabled_modules')) ? session('business.enabled_modules') : [];

            $common_settings = !empty(session('business.common_settings')) ? session('business.common_settings') : [];
            $pos_settings = !empty(session('business.pos_settings')) ? json_decode(session('business.pos_settings'), true) : [];

            $is_admin = auth()->user()->hasRole('Admin#' . session('business.id')) ? true : false;
            //Home
            $menu->url(action('ApplicationDashboard\HomeController@index'), __('home.home'), ['icon' => 'fa fas fa-tachometer-alt', 'active' => request()->segment(1) == 'home'])->order(5);




            //Products dropdown
            if (
                auth()->user()->can('product.view') || auth()->user()->can('product.create') ||
                auth()->user()->can('brand.view') || auth()->user()->can('unit.view') ||
                auth()->user()->can('category.view') || auth()->user()->can('brand.create') ||
                auth()->user()->can('unit.create') || auth()->user()->can('category.create')
            ) {
                $menu->dropdown(
                    __('lang_v1.products'),
                    function ($sub) {
                        if (auth()->user()->can('product.view')) {
                            $sub->url(
                                action('ProductController@index'),
                                __('lang_v1.list_products'),
                                ['icon' => 'fa fas fa-list', 'active' => request()->segment(1) == 'products' && request()->segment(2) == '']
                            );
                        }
                        if (auth()->user()->can('product.create')) {
                            $sub->url(
                                action('ProductController@create'),
                                __('product.add_product'),
                                ['icon' => 'fa fas fa-plus-circle', 'active' => request()->segment(1) == 'products' && request()->segment(2) == 'create']
                            );
                        }
                        if (auth()->user()->can('product.view')) {
                            $sub->url(
                                action('LabelsController@show'),
                                __('barcode.print_labels'),
                                ['icon' => 'fa fas fa-barcode', 'active' => request()->segment(1) == 'labels' && request()->segment(2) == 'show']
                            );
                        }
                        if (auth()->user()->can('product.create')) {
                            $sub->url(
                                action('VariationTemplateController@index'),
                                __('product.variations'),
                                ['icon' => 'fa fas fa-circle', 'active' => request()->segment(1) == 'variation-templates']
                            );
                            $sub->url(
                                action('ImportProductsController@index'),
                                __('product.import_products'),
                                ['icon' => 'fa fas fa-download', 'active' => request()->segment(1) == 'import-products']
                            );
                        }
                        if (auth()->user()->can('product.opening_stock')) {
                            $sub->url(
                                action('ImportOpeningStockController@index'),
                                __('lang_v1.import_opening_stock'),
                                ['icon' => 'fa fas fa-download', 'active' => request()->segment(1) == 'import-opening-stock']
                            );
                        }
                        if (auth()->user()->can('product.create')) {
                            $sub->url(
                                action('SellingPriceGroupController@index'),
                                __('lang_v1.selling_price_group'),
                                ['icon' => 'fa fas fa-circle', 'active' => request()->segment(1) == 'selling-price-group']
                            );
                        }
                        if (auth()->user()->can('unit.view') || auth()->user()->can('unit.create')) {
                            $sub->url(
                                action('UnitController@index'),
                                __('unit.units'),
                                ['icon' => 'fa fas fa-balance-scale', 'active' => request()->segment(1) == 'units']
                            );
                        }
                        if (auth()->user()->can('category.view') || auth()->user()->can('category.create')) {
                            $sub->url(
                                action('TaxonomyController@index') . '?type=product',
                                __('category.categories'),
                                ['icon' => 'fa fas fa-tags', 'active' => request()->segment(1) == 'taxonomies' && request()->get('type') == 'product']
                            );
                        }
                        if (auth()->user()->can('brand.view') || auth()->user()->can('brand.create')) {
                            $sub->url(
                                action('BrandController@index'),
                                __('brand.brands'),
                                ['icon' => 'fa fas fa-gem', 'active' => request()->segment(1) == 'brands']
                            );
                        }

                        $sub->url(
                            action('WarrantyController@index'),
                            __('lang_v1.warranties'),
                            ['icon' => 'fa fas fa-shield-alt', 'active' => request()->segment(1) == 'warranties']
                        );



                    },
                    ['icon' => 'fa fas fa-cube', 'id' => 'tour_step5']
                )->order(20);
            }


            $menu->dropdown(
                __('lang_v1.banners'),
                function ($sub) {

                    $sub->url(
                        action('ApplicationDashboard\BannerController@index'),
                        __('lang_v1.banners'),
                        ['icon' => 'fa fas fa-shield-alt', 'active' => request()->segment(1) == 'banners']
                    );
                },
                ['icon' => 'fa fa-flag']
            )->order(21);

            $menu->dropdown(
                __('lang_v1.orders'),
                function ($sub) {
                    // Link for All Orders (no status)
                    $sub->url(
                        action('ApplicationDashboard\OrderController@index', ['status' => 'all']),
                        __('lang_v1.all_orders'),
                        ['icon' => 'fa fas fa-list', 'active' =>request()->input('status') == 'all']
                    );

                    // Link for Pending Orders
                    $sub->url(
                        action('ApplicationDashboard\OrderController@index', ['status' => 'pending']),
                        __('lang_v1.pending_orders'),
                        ['icon' => 'fa fas fa-clock', 'active' => request()->input('status') == 'pending']
                    );

                    // Link for Processing Orders
                    $sub->url(
                        action('ApplicationDashboard\OrderController@index', ['status' => 'processing']),
                        __('lang_v1.processing_orders'),
                        ['icon' => 'fa fas fa-sync', 'active' => request()->input('status') == 'processing']
                    );

                    // Link for shipped Orders
                    $sub->url(
                        action('ApplicationDashboard\OrderController@index', ['status' => 'shipped']),
                        __('lang_v1.shipped_orders'),
                        ['icon' => 'fa fas fa-check', 'active' => request()->input('status') == 'shipped']
                    );

                    // Link for completed Orders
                    $sub->url(
                        action('ApplicationDashboard\OrderController@index', ['status' => 'completed']),
                        __('lang_v1.completed_orders'),
                        ['icon' => 'fa fas fa-check', 'active' => request()->input('status') == 'completed']
                    );

                    // Link for canceled Orders
                    $sub->url(
                        action('ApplicationDashboard\OrderController@index', ['status' => 'canceled']),
                        __('lang_v1.canceled_orders'),
                        ['icon' => 'fa fas fa-check', 'active' => request()->input('status') == 'canceled']
                    );


                },
                ['icon' => 'fa fa-cart-arrow-down']
            )->order(22);

            $menu->dropdown(
                __('lang_v1.order_cancellations'),
                function ($sub) {
                    // Link for All Orders (no status)
                    $sub->url(
                        action('ApplicationDashboard\OrderCancellationController@index', ['status' => 'all']),
                        __('lang_v1.all_order_cancellations'),
                        ['icon' => 'fa fas fa-list', 'active' => request()->segment(1) == 'order_cancellations' && !request()->segment(2)]
                    );

                    // Link for Pending Orders
                    $sub->url(
                        action('ApplicationDashboard\OrderCancellationController@index', ['status' => 'requested']),
                        __('lang_v1.requested_order_cancellations'),
                        ['icon' => 'fa fas fa-clock', 'active' => request()->input('status') == 'requested']
                    );

                    // Link for Processing Orders
                    $sub->url(
                        action('ApplicationDashboard\OrderCancellationController@index', ['status' => 'approved']),
                        __('lang_v1.approved_order_cancellations'),
                        ['icon' => 'fa fas fa-sync', 'active' => request()->input('status') == 'approved']
                    );

                    // Link for shipped Orders
                    $sub->url(
                        action('ApplicationDashboard\OrderCancellationController@index', ['status' => 'rejected']),
                        __('lang_v1.rejected_order_cancellations'),
                        ['icon' => 'fa fas fa-check', 'active' => request()->input('status') == 'rejected']
                    );


                },
                ['icon' => 'fa fa-cart-arrow-down']
            )->order(23);


            $menu->dropdown(
                __('lang_v1.order_refunds'),
                function ($sub) {
                    // Link for All Orders (no status)
                    $sub->url(
                        route('order-refunds.index', ['status' => 'all']),
                        __('lang_v1.all_order_refunds'),
                        ['icon' => 'fa fas fa-list', 'active' => request()->segment(1) == 'order_refunds' && !request()->segment(2)]
                    );

                    // Link for Pending Orders
                    $sub->url(
                        route('order-refunds.index', ['status' => 'requested']),
                        __('lang_v1.requested_order_refunds'),
                        ['icon' => 'fa fas fa-clock', 'active' => request()->input('status') == 'requested']
                    );


                    $sub->url(
                        route('order-refunds.index', ['status' => 'processed']),
                        __('lang_v1.requested_order_refunds'),
                        ['icon' => 'fa fas fa-clock', 'active' => request()->input('status') == 'processed']
                    );

                    // Link for Processing Orders
                    $sub->url(
                        route('order-refunds.index', ['status' => 'approved']),
                        __('lang_v1.approved_order_refunds'),
                        ['icon' => 'fa fas fa-sync', 'active' => request()->input('status') == 'approved']
                    );

                    // Link for shipped Orders
                    $sub->url(
                        route('order-refunds.index', ['status' => 'rejected']),
                        __('lang_v1.rejected_order_refunds'),
                        ['icon' => 'fa fas fa-check', 'active' => request()->input('status') == 'rejected']
                    );


                },
                ['icon' => 'fa fa-cart-arrow-down']
            )->order(24);

            $menu->dropdown(
                __('lang_v1.orderDeliveries'),
                function ($sub) {
                    // Link for All Deliveries (no specific delivery_id)
                    $sub->url(
                        action('ApplicationDashboard\DeliveryController@allDeliveries'),
                        __('lang_v1.allDeliveries'),
                        ['icon' => 'fa fas fa-list', 'active' => request()->segment(1)]
                    );

                    // Conditionally handle the URL for orderDeliveries with optional delivery_id
                    // Check if there's a specific delivery_id (you could determine this based on the context)
                    $delivery_id = request()->get('delivery_id'); // Or some other logic to get the delivery_id
    
                    // If delivery_id exists, include it in the URL
                    $orderDeliveriesUrl = $delivery_id
                        ? action('ApplicationDashboard\DeliveryController@orderDeliveries', ['delivery_id' => $delivery_id])
                        : action('ApplicationDashboard\DeliveryController@orderDeliveries'); // Default to no delivery_id
    
                    $sub->url(
                        $orderDeliveriesUrl,
                        __('lang_v1.orderDeliveries'),
                        ['icon' => 'fa fas fa-list', 'active' => request()->segment(1)]
                    );
                },
                ['icon' => 'fa fa-cart-arrow-down']
            )->order(25);


            $menu->url(action('ApplicationDashboard\ApplicationSettingsController@index'), __('lang_v1.application_settings'), [
                'icon' => 'fa fas fa-cogs',
                'active' => request()->segment(1)
            ])->order(80);

            // ApplicationDashboard\ApplicationSettingsController@index
        });

        //Add menus from modules
        $moduleUtil = new ModuleUtil;
        $moduleUtil->getModuleData('modifyAdminMenu');

        return $next($request);
    }
}