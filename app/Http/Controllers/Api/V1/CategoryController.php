<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\CategoryLogic;
use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Model\HomeMenu;
use App\Models\AddonCategory;
use App\Models\ProductGrouping;

class CategoryController extends Controller
{
    public function __construct(
        private Category $category,
    )
    {
    }


    /**
     * @return JsonResponse
     */
    public function get_categories(): JsonResponse
    {
        try {
            // select categories with their menus with the menu properties being the category properties except the ids
            // $categories = Category::select('categories.*')
            //     ->join('menus', 'categories.id', '=', 'menus.category_id')
            //     ->select('menus.title as name', 'menus.id as menu_id', 'menus.category_id as category_id', 'menus.image as image')
            //     ->get();

            // return response()->json($categories, 200);
            // $categories = Category::select()
            
            $categories = $this->category->where(['position' => 0, 'status' => 1])->orderBY('priority', 'ASC')->get();
            return response()->json($categories, 200);

        } catch (\Exception $e) {
            return response()->json([], 200);
        }
    }

    public function get_menus(): JsonResponse
    {
        try {
            // select menu title as name with the categories alongside
            $menus = HomeMenu::select('home_menus.title as name', 'home_menus.image', 'home_menus.category_id as id', 'home_menus.description as description', 'home_menus.id as menu_id')
            ->join('categories', 'home_menus.category_id', '=', 'categories.id')
            // where category status is 1
            ->where('categories.status', 1)
            // order by categories position
            ->orderBY('categories.priority', 'ASC')
            ->get();
             
            // $menus =HomeMenu::with('category')
            // ->select('menus.title as name')
            // ->get();
            return response()->json($menus, 200);

        } catch (\Exception $e) {
            return response()->json([], 200);
        }
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function get_childes($id): JsonResponse
    {
        try {
            $categories = $this->category->where(['parent_id' => $id, 'status' => 1])->get();
            return response()->json($categories, 200);

        } catch (\Exception $e) {
            return response()->json([], 200);
        }
    }

    /**
     * @param $id
     * @param Request $request
     * @return JsonResponse
     */
    public function get_products($id, Request $request): JsonResponse
    {
        $product_type = $request['product_type'];
        $search = $request['search'];
        // return response()->json(Helpers::product_data_formatting(CategoryLogic::products($id, $product_type, $search), true), 200);
        return response()->json(Helpers::product_data_formatting(
            CategoryLogic::productsT($id, $product_type, $search), true
        ), 200);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function get_all_products($id): JsonResponse
    {
        try {
            return response()->json(Helpers::product_data_formatting(CategoryLogic::all_products($id), true), 200);

        } catch (\Exception $e) {
            return response()->json(['error'=> $e->getMessage()], 200);
        }
    }

    public function get_product_groups(): JsonResponse
    {
        // ProductGrouping::select('name')->where('name', '<>', 'Regular')->get();
        // Select ProductGrouping name and return it as list of strings instead of list of objects
        
        $groups = ProductGrouping::select('name')->where('name', '<>', 'Regular')->orderBy('order', 'ASC')->get();
        foreach ($groups as $key => $value) {
            $response[$key] = $value->name;
        }

        return response()->json($response, 200);
    }

    public function get_addon_categories(): JsonResponse
    {
        $addon_categories = AddonCategory::select('name')->orderBy('order', 'ASC')->get();
        foreach ($addon_categories as $key => $value) {
            $response[$key] = $value->name;
        }

        return response()->json($response, 200);
    }
}
