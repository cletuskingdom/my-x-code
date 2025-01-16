<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\Helpers;
use App\CentralLogics\ProductLogic;
use App\Http\Controllers\Controller;
use App\Model\AddOn;
use App\Model\Product;
use App\Model\ProductTwo;
use App\Model\Review;
use App\Model\Translation;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;


class ProductController extends Controller
{
    public function __construct(
        private Product     $product,
        private Translation $translation,
        private Review      $review
    ){}

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function get_latest_products(Request $request): JsonResponse
    {
        //update daily stock
        Helpers::update_daily_product_stock();

        $products = ProductLogic::get_latest_products($request['limit'], $request['offset'], $request['product_type'], $request['name'], $request['category_ids']);
        $products['products'] = Helpers::product_data_formatting($products['products'], true);
        return response()->json($products, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function get_popular_products(Request $request): JsonResponse
    {
        $products = ProductLogic::get_popular_products($request['limit'], $request['offset'], $request['product_type']);
        $products['products'] = Helpers::product_data_formatting($products['products'], true);
        return response()->json($products, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function get_searched_products(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $product_type = $request['product_type'];
        $products = ProductLogic::search_products($request['name'], $request['limit'], $request['offset'], $product_type);

        if ($product_type != 'veg' && $product_type != 'non_veg') {
            $product_type = 'all';
        }

        if (count($products['products']) == 0) {
            $key = explode(' ', $request['name']);
            $ids = $this->translation
                ->where(['key' => 'name'])
                ->where(function ($query) use ($key) {
                    foreach ($key as $value) {
                        $query->orWhere('value', 'like', "%{$value}%");
                    }
                })
                ->pluck('translationable_id')
                ->toArray();

            $paginator = $this->product
                ->active()
                ->whereIn('id', $ids)
                ->withCount(['wishlist'])
                ->with(['rating'])
                ->when(isset($product_type) && ($product_type != 'all'), function ($query) use ($product_type) {
                    return $query->productType(($product_type == 'veg') ? 'veg' : 'non_veg');
                })
                ->paginate($request['limit'], ['*'], 'page', $request['offset']);

            $products = [
                'total_size' => $paginator->total(),
                'limit' => $request['limit'],
                'offset' => $request['offset'],
                'products' => $paginator->items()
            ];
        }

        $products['products'] = Helpers::product_data_formatting($products['products'], true);
        return response()->json($products, 200);
    }

    public function get_searched_products_new(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $searchTerm = $request->input('query');
        $products = ProductTwo::where('name', 'LIKE', '%' . $searchTerm . '%')
            ->orWhere('description', 'LIKE', '%' . $searchTerm . '%')
        ->get();

        return response()->json($products, 200);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function get_product($id): JsonResponse
    {
        try {
            $product = ProductLogic::get_product($id);
            $product = Helpers::product_data_formatting($product, false);

            return response()->json($product, 200);

        } catch (\Exception $e) {
            return response()->json([
                'errors' => ['code' => 'product-001', 'message' => translate('no_data_found')]
            ], 404);
        }
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function get_related_products($id): JsonResponse
    {
        if ($this->product->find($id)) {
            $products = ProductLogic::get_related_products($id);
            $products = Helpers::product_data_formatting($products, true);

            return response()->json($products, 200);

        }

        return response()->json([
            'errors' => ['code' => 'product-001', 'message' => translate('no_data_found')]
        ], 404);
    }

    /**
     * @return JsonResponse
     */
    public function get_set_menus(): JsonResponse
    {
        try {
            $products = Helpers::product_data_formatting($this->product->active()
                //->with(['rating'])
                ->with(['rating', 'branch_product'])
                ->whereHas('branch_product.branch', function ($query) {
                    $query->where('status', 1);
                })
                ->whereHas('branch_product', function ($q) {
                    $q->where('is_available', 1);
                })
                ->where(['set_menu' => 1, 'status' => 1])
                ->latest()
                ->get(), true);

            return response()->json($products, 200);

        } catch (\Exception $e) {
            return response()->json([
                'errors' => ['code' => 'product-001', 'message' => translate('no_data_found')]
            ], 404);
        }
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function get_product_reviews($id): JsonResponse
    {
        $reviews = $this->review
            ->with(['customer'])
            ->where(['product_id' => $id])
            ->get();

        $storage = [];
        foreach ($reviews as $item) {
            $item['attachment'] = json_decode($item['attachment']);
            $storage[] = $item;
        }

        return response()->json($storage, 200);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function get_product_rating($id): JsonResponse
    {
        try {
            $product = $this->product->find($id);
            $overallRating = ProductLogic::get_overall_rating($product->reviews);
            return response()->json(floatval($overallRating[0]), 200);

        } catch (\Exception $e) {
            return response()->json(['errors' => $e], 403);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function submit_product_review(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required',
            'order_id' => 'required',
            'comment' => 'required',
            'rating' => 'required|numeric|max:5',
        ]);

        $product = $this->product->find($request->product_id);
        if (isset($product) == false) {
            $validator->errors()->add('product_id', translate('no_data_found'));
        }

        $multi_review = $this->review->where(['product_id' => $request->product_id, 'user_id' => $request->user()->id])->first();
        if (isset($multi_review)) {
            $review = $multi_review;
        } else {
            $review = $this->review;
        }

        if ($validator->errors()->count() > 0) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $image_array = [];
        if (!empty($request->file('attachment'))) {
            foreach ($request->file('attachment') as $image) {
                if ($image != null) {
                    if (!Storage::disk('public')->exists('review')) {
                        Storage::disk('public')->makeDirectory('review');
                    }
                    $image_array[] = Storage::disk('public')->put('review', $image);
                }
            }
        }

        $review->user_id = $request->user()->id;
        $review->product_id = $request->product_id;
        $review->order_id = $request->order_id;
        $review->comment = $request->comment;
        $review->rating = $request->rating;
        $review->attachment = json_encode($image_array);
        $review->save();

        return response()->json(['message' => translate('review_submit_success')], 200);
    }

    // public function getDiscountedProducts()
    // {
    //     $discountedProducts = ProductTwo::where('discount', '!=', 0.00)->get();
        
    //     if($discountedProducts->isEmpty()){
    //         return response()->json([
    //             'message' => 'No discounted products found',
    //         ], 200);
    //     }
    //     return response()->json($discountedProducts, 200);
    // }

    // public function getGroupedProducts()
    // {
    //     $groupedProducts = ProductTwo::where('grouping_id', 2)->get();
        
    //     if($groupedProducts->isEmpty()){
    //         return response()->json([
    //             'message' => 'No products found',
    //         ], 200);
    //     }
    //     return response()->json($groupedProducts, 200);
    // }

    public function getGroupedProducts()
    {
        // Fetch a maximum of 5 grouped products
        $groupedProducts = ProductTwo::where('grouping_id', 2)->whereStatus(1)->take(5)->get();
        
        if ($groupedProducts->isEmpty()) {
            return response()->json([
                'message' => 'No products found',
            ], 200);
        }

        // Return the grouped products as a JSON response
        return response()->json($groupedProducts, 200);
    }

    
    public function getMostOrderedProducts()
    {
        $today = Carbon::today();
        $dayOfWeek = $today->dayOfWeek;

        // Compute yesterday's date based on the current day of the week
        if ($dayOfWeek === Carbon::SATURDAY) {
            $yesterday = $today->copy()->subDays(1);
        } elseif ($dayOfWeek === Carbon::SUNDAY) {
            $yesterday = $today->copy()->subDays(2);
        } else {
            $yesterday = $today->copy()->subDay();
        }

        // Query to get the most ordered products
        $mostOrderedProducts = ProductTwo::select('products.*')
    	->join(DB::raw('(SELECT product_id, COUNT(DISTINCT id) as order_count FROM order_details WHERE DATE(created_at) = "' . $yesterday->toDateString() . '" GROUP BY product_id) as order_summary'), 'products.id', '=', 'order_summary.product_id')
    	->orderByDesc('order_summary.order_count')
    	->take(5)
   	    ->get();

        if ($mostOrderedProducts->isEmpty()) {
            return response()->json([
                'message' => 'No orders found',
            ], 404);
        }

        return response()->json($mostOrderedProducts);
    }


}
