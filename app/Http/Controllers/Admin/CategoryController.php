<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Category;
use App\Model\HomeMenu;
use App\Model\Order;
use App\Model\Product;
use App\Model\ProductTwo;
use App\Model\Translation;
use App\Services\FileService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function __construct(
        private Category    $category,
        private Translation $translation
    )
    {
    }


    /**
     * @param Request $request
     * @return Renderable
     */
    function index(Request $request)
    {
        $query_param = [];
        $search = $request['search'];
        if ($request->has('search')) {
            $key = explode(' ', $request['search']);

            $categories = $this->category->where('position', 0)->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('name', 'like', "%{$value}%");
                }
            });
            $query_param = ['search' => $request['search']];
        } else {
            $categories = $this->category->where('position', 0);
        }

        $mostOrderedProducts = ProductTwo::select('products.*', 'categories.name as category_name')
            ->join(DB::raw('(SELECT product_id, COUNT(DISTINCT id) as order_count FROM order_details GROUP BY product_id) as order_summary'), 'products.id', '=', 'order_summary.product_id')
            ->join('categories', DB::raw('JSON_UNQUOTE(JSON_EXTRACT(products.category_ids, "$[0].id") )'), '=', 'categories.id') // Join on category id extracted from JSON
            ->orderByDesc('order_summary.order_count')
        ->get();

        // Now group the products by category_id
        $groupedProducts = $mostOrderedProducts->groupBy(function($product) {
            // Decode the category_ids JSON string
            $categoryIds = json_decode($product->category_ids, true);
            
            // Return the category id (assuming there's always one category in the array)
            return $categoryIds[0]['id'] ?? null;
        });

        // Prepare the result with category names and sum of products
        $categorySummary = $groupedProducts->map(function($group, $categoryId) use ($mostOrderedProducts) {
            // Find the category name by the category ID
            $category = $mostOrderedProducts->firstWhere(function($product) use ($categoryId) {
                // Extract category name from the first product in the group
                $categoryIds = json_decode($product->category_ids, true);
                return $categoryIds[0]['id'] == $categoryId;
            });
            
            // Calculate the sum of the 'popularity_count' or any other field you want to sum
            $totalPopularity = $group->sum('popularity_count');
            
            return [
                'category_id' => $categoryId,
                'category_name' => $category ? $category->category_name : 'Unknown',
                'total_products' => $group->count(),
                'total_popularity' => $totalPopularity,
                'products' => $group  // Add the products under this category
            ];
        });
        
        // Sort categories by total_popularity in descending order
        $categorySummary = $categorySummary->sortByDesc('total_products')->values();

        // Add a rank to each category based on the sorted order
        $categorySummary = $categorySummary->map(function ($category, $index) {
            // Add a 'rank' field where the highest popularity gets rank 1
            $category['rank'] = $index + 1; // $index is 0-based, so add 1 to start ranking from 1
            return $category;
        });
        

        $categories = $categories->orderBY('priority', 'ASC')->paginate(Helpers::getPagination())->appends($query_param);
        return view('admin-views.category.index', compact('categories', 'search', 'categorySummary'));
    }

    /**
     * @param Request $request
     * @return Renderable
     */
    function sub_index(Request $request): Renderable
    {
        $search = $request['search'];
        $query_param = ['search' => $search];


        $categories = $this->category->with(['parent'])
            ->when($request['search'], function ($query) use ($search) {
                $query->orWhere('name', 'like', "%{$search}%");
            })
            ->where(['position' => 1])
            ->latest()
            ->paginate(Helpers::getPagination())
            ->appends($query_param);

        return view('admin-views.category.sub-index', compact('categories', 'search'));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $key = explode(' ', $request['search']);
        $categories = $this->category
            ->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('name', 'like', "%{$value}%");
                }
            })->get();

        return response()->json([
            'view' => view('admin-views.category.partials._table', compact('categories'))->render()
        ]);
    }

    /**
     * @return Renderable
     */
    function sub_sub_index(): Renderable
    {
        return view('admin-views.category.sub-sub-index');
    }

    /**
     * @return Renderable
     */
    function sub_category_index(): Renderable
    {
        return view('admin-views.category.index');
    }

    /**
     * @return Renderable
     */
    function sub_sub_category_index(): Renderable
    {
        return view('admin-views.category.index');
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    function store(Request $request): RedirectResponse
    {
        
        $request->validate([
            'name' => 'required',
        ]);

        if($request->has('attach_menu')){
            $request->validate([
                'menu_title' => 'required|string|max:255',
                'menu_image' => 'required|mimes:png,jpg,jpeg,svg|max:215',
                
            ]);
        }

        if ($request->has('type')) {
            $request->validate([
                'parent_id' => 'required',
            ], [
                'parent_id.required' => translate('Select a category first')
            ]);
        }

        foreach ($request->name as $name) {
            if (strlen($name) > 255) {
                toastr::error(translate('Name is too long!'));
                return back();
            }
        }

        //uniqueness check
        $cat = $this->category->where('name', $request->name)->where('parent_id', $request->parent_id ?? 0)->first();
        if (isset($cat)) {
            Toastr::error(translate(($request->parent_id == null ? 'Category' : 'Sub-category') . ' already exists!'));
            return back();
        }

        //image upload
        if (!empty($request->file('image'))) {
            $image_name = Helpers::upload('category/', 'png', $request->file('image'));
        } else {
            $image_name = 'def.png';
        }
        if (!empty($request->file('menu_image'))) {
            $menu_image_name = Helpers::upload('category/menu/', 'png', $request->file('menu_image'));
        } else {
            $menu_image_name = 'def.png';
        }

        //into db
        $category = $this->category;
        $category->name = $request->name[array_search('en', $request->lang)];
        $category->image = $image_name;
        $category->banner_image = $menu_image_name;
        $category->parent_id = Helpers::sanitize_input($request->parent_id) == null ? 0 : Helpers::sanitize_input($request->parent_id);
        $category->position = Helpers::sanitize_input($request->position);
        $category->save();

        $menu = new HomeMenu();
        $menu->title = Helpers::sanitize_input($request->menu_title);
        $menu->category_id = $category->id;
        $menu->image = $menu_image_name;
        $menu->description = Helpers::sanitize_input($request->menu_description);
        $menu->save();

        //translation
        $data = [];
        foreach ($request->lang as $index => $key) {
            if ($request->name[$index] && $key != 'en') {
                $data[] = array(
                    'translationable_type' => 'App\Model\Category',
                    'translationable_id' => $category->id,
                    'locale' => $key,
                    'key' => 'name',
                    'value' => $request->name[$index],
                );
            }
        }
        if (count($data)) {
            $this->translation->insert($data);
        }

        Toastr::success($request->parent_id == 0 ? translate('Category Added Successfully!') : translate('Sub Category Added Successfully!'));
        return back();
    }

    /**
     * @param $id
     * @return Renderable
     */
    public function edit($id): Renderable
    {
        $category = $this->category->with('menu')->withoutGlobalScopes()->with('translations')->find($id);
        // dd($category);
        return view('admin-views.category.edit', compact('category'));
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function status(Request $request): RedirectResponse
    {
        $category = $this->category->find($request->id);
        $category->status = $request->status;
        $category->save();

        Toastr::success($category->parent_id == 0 ? translate('Category status updated!') : translate('Sub Category status updated!'));
        return back();
    }

    /**
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function update(Request $request, $id): RedirectResponse
    {
        // dd($request);
        $request->validate([
            'name' => 'required',
        ]);

        if($request->has('menu_title')){
            $request->validate([
                'menu_title'=>'required'
            ]);
        };

        if($request->hasFile('menu_image')){
            $request->validate([
                'menu_image'=>'mimes:png,jpg,jpeg,svg|max:215'
            ]);
        };

        foreach ($request->name as $name) {
            if (strlen($name) > 255) {
                toastr::error(translate('Name is too long!'));
                return back();
            }
        }

        $category = $this->category->find($id);
        $category->name = $request->name[array_search('en', $request->lang)];
        $category->image = $request->hasFile('category_image') ? FileService::upload(
            $request,   
            'category_image', 
            'public',
            'category',
            $category->image

        )
        : $category->image;

        
            // Helpers::update('category/', $category->image, 'png', $request->file('image')) : $category->image;
        $category->banner_image = $request->has('banner_image') ? Helpers::update('category/banner/', $category->banner_image, 'png', $request->file('banner_image')) : $category->banner_image;
        $category->save();

        if($request->has('menu_title')){
           
            $menu = HomeMenu::where('category_id', $category->id)->first();
            if($menu){
                
                $existing_image_name = Helpers::getFileNameFromPath($menu->image);

                $menu_image = $request->hasFile('menu_image') ? FileService::upload(
                    $request,   
                    'menu_image', 
                    'public',
                    'category/menu',
                    $existing_image_name
                )
                : $existing_image_name;
                $menu->image = $menu_image;
                
            }else{
                $menu = new HomeMenu();
                $menu->image = $request->hasFile('menu_image') ? FileService::upload(
                    $request,   
                    'menu_image', 
                    'public',
                    'category/menu'
                )
                :'';

                $menu->category_id = $category->id;
            }
            
            $menu->title = Helpers::sanitize_input($request->menu_title);
            $menu->description = Helpers::sanitize_input($request->menu_description);
            // Helpers::update('category/menu', $menu->image, 'png', $request->file('menu_image')): $menu->image;
            $menu->save();
        }

        // foreach ($request->lang as $index => $key) {
        //     if ($request->name[$index] && $key != 'en') {
        //         $this->translation->updateOrInsert(
        //             ['translationable_type' => 'App\Model\Category',
        //                 'translationable_id' => $category->id,
        //                 'locale' => $key,
        //                 'key' => 'name'],
        //             ['value' => $request->name[$index]]
        //         );
        //     }
        // }

        Toastr::success($category->parent_id == 0 ? translate('Category updated successfully!') : translate('Sub Category updated successfully!'));
        return back();
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function delete(Request $request): RedirectResponse
    {
        $category = $this->category->find($request->id);
        Helpers::delete('category/' . $category['image']);

        if ($category->childes->count() == 0) {
            $category->delete();
            Toastr::success($category->parent_id == 0 ? translate('Category removed!') : translate('Sub Category removed!'));
        } else {
            Toastr::warning($category->parent_id == 0 ? translate('Remove subcategories first!') : translate('Sub Remove subcategories first!'));
        }

        return back();
    }

    public function priority(Request $request)
    {
        $category = $this->category->find($request->id);
        $category->priority = $request->priority;
        $category->save();

        Toastr::success(translate('priority updated!'));
        return back();
    }
}
