<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\AddOn;
use App\Model\Category;
use App\Model\Translation;
use App\Models\AddonCategory;
use App\Services\FileService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AddonController extends Controller
{
    public function __construct(
        private AddOn       $addon,
        private Translation $translation
    ){}

    /**
     * @param Request $request
     * @return Renderable
     */
    // public function index(Request $request): Renderable
    // {
    //     $query_param = [];
    //     $search = $request['search'];
    //     if ($request->has('search')) {
    //         $key = explode(' ', $request['search']);
    //         $addons = $this->addon->where(function ($q) use ($key) {
    //             foreach ($key as $value) {
    //                 $q->orWhere('name', 'like', "%{$value}%");
    //             }
    //         });
    //         $query_param = ['search' => $request['search']];
    //     } else {
    //         $addons = $this->addon;
    //     }

    //     $addons = $addons->orderBy('id', 'DESC')->paginate(Helpers::getPagination())->appends($query_param);
    //     $categories = AddonCategory::orderBy('name', 'asc')->get();
    //     return view('admin-views.addon.index', compact('addons', 'search', 'categories'));
    // }

    public function index(Request $request): Renderable
    {
        $query_param = [];
        $search = $request['search'];

        // Start building the query with the status condition
        $addons = $this->addon->where('status', true);

        if ($request->has('search')) {
            $key = explode(' ', $request['search']);
            $addons = $addons->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('name', 'like', "%{$value}%");
                }
            });
            $query_param = ['search' => $request['search']];
        } else {
            $addons = $addons->get();
        }

        // $addons = $addons->orderBy('id', 'DESC')->paginate(Helpers::getPagination())->appends($query_param);
        $addons = AddOn::orderBy('id', 'DESC')
            ->paginate(Helpers::getPagination())
        ->appends($query_param);
        $categories = AddonCategory::orderBy('name', 'asc')->get();
        return view('admin-views.addon.index', compact('addons', 'search', 'categories'));
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|unique:add_ons',
            'price' => 'required|max:8',
            'tax' => 'required|max:100',
            'image'=> 'image|mimes:jpeg,png,jpg,gif,svg|max:215',
            'category' => 'required|exists:categories,id'
        ]);

        foreach ($request->name as $name) {
            if (strlen($name) > 255) {
                toastr::error(translate('Name is too long!'));
                return back();
            }
        }

        $addon = new AddOn();
        $addon->name = $request->name[array_search('en', $request->lang)];
        $addon->price = Helpers::sanitize_input($request->price);
        $addon->tax =  Helpers::sanitize_input($request->tax);
        $addon->category_id =  Helpers::sanitize_input($request->category);
        $addon->description = Helpers::sanitize_input($request->description);
        $addon->image = FileService::upload($request, 'image', 'public', 'addon');
        $addon->save();

        // $data = [];
        // foreach ($request->lang as $index => $key) {
        //     if ($request->name[$index] && $key != 'en') {
        //         $data[] = array(
        //             'translationable_type' => 'App\Model\AddOn',
        //             'translationable_id' => $addon->id,
        //             'locale' => $key,
        //             'key' => 'name',
        //             'value' => $request->name[$index],
        //         );
        //     }
        // }
        // if (count($data)) {
        //     $this->translation->insert($data);
        // }

        Toastr::success(translate('Addon added successfully!'));
        return back();
    }

    /**
     * @param $id
     * @return Renderable
     */
    public function edit($id): Renderable
    {
        $addon = $this->addon->withoutGlobalScopes()->with('translations')->find($id);
        $categories = AddonCategory::orderBy('name', 'asc')->get();
        return view('admin-views.addon.edit', compact('addon', 'categories'));
    }

    /**
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function update(Request $request, $id): RedirectResponse
    {
        
        $request->validate([
            'name' => 'required|unique:add_ons,name,' . $id,
            'price' => 'required|max:9',
            'image'=> 'image|mimes:jpeg,png,jpg,gif,svg|max:215',
            'category' => 'required|exists:categories,id'
        ]);

        foreach ($request->name as $name) {
            if (strlen($name) > 255) {
                toastr::error('Name is too long!');
                return back();
            }
        }
        
        $addon = $this->addon->find($id);

        if ($addon && $addon->status) {
            $addon->name = $request->name[array_search('en', $request->lang)];
            $addon->price = Helpers::sanitize_input($request->price);
            $addon->tax = Helpers::sanitize_input($request->tax); 
            $addon->category_id = Helpers::sanitize_input($request->category);
            $addon->description = Helpers::sanitize_input($request->description);
            $existing_image_name = Helpers::getFileNameFromPath($addon->image);
            
            if($request->hasFile('image')){
                $addon->image = is_null($addon->image) ? FileService::upload($request, 'image', 'public', 'addon'): FileService::upload($request, 'image', 'public', 'addon', $existing_image_name);
            }
            
            $addon->save();
            Toastr::success(translate('Addon updated successfully!'));
            return redirect()->route('admin.addon.add-new');
        }else{
            Toastr::error(translate("Sorry, AddOn isn't active!"));
            return redirect()->route('admin.addon.add-new');
        }

        // foreach ($request->lang as $index => $key) {
        //     if ($request->name[$index] && $key != 'en') {
        //         $this->translation->updateOrInsert(
        //             ['translationable_type' => 'App\Model\AddOn',
        //                 'translationable_id' => $addon->id,
        //                 'locale' => $key,
        //                 'key' => 'name'],
        //             ['value' => $request->name[$index]]
        //         );
        //     }
        // }
        
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function delete(Request $request): RedirectResponse
    {
        $addon = $this->addon->find($request->id);
        if ($addon && $addon->status) {
            $addon->delete();
            Toastr::success(translate('Addon removed!'));
        }else{
            Toastr::error(translate("Sorry, AddOn isn't active!"));
        }
        return back();
    }

    public function status($id)
    {
        $addon = $this->addon->find($id);
        $addon->status = !$addon->status;
        $addon->save();

        Toastr::success(translate('Addon status updated!'));
        return back();
    }
}
