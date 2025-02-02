<?php

namespace App\CentralLogics;

use App\Model\Category;
use App\Model\Product;
use App\Model\ProductTwo;

class CategoryLogic
{
    public static function parents()
    {
        return Category::where('position', 0)->get();
    }

    public static function child($parent_id)
    {
        return Category::where(['parent_id' => $parent_id])->get();
    }

    public static function products($category_id, $type, $search)
    {   
        $product_type = ($type == 'veg') ? 'veg' : ($type == 'non_veg' ? 'non_veg' : 'all');

        $products = Product::active()
        ->with(['branch_products', 'rating', 'group'])
            // ->with(['branch_product'])
            // ->whereHas('branch_product.branch', function ($query) {
            //     $query->where('status', 1);
            // })
            // ->branchProductAvailability()
            ->when($product_type != 'all', function ($query) use ($product_type) {
                return $query->where('product_type', $product_type);
            })
            ->when(isset($search), function ($q) use ($search) {
                $key = explode(' ', $search);
                foreach ($key as $value) {
                    $q->where('name', 'like', "%{$value}%");
                }
                $q->orWhereHas('tags',function($query) use ($key){
                    $query->where(function($q) use ($key){
                        foreach ($key as $value) {
                            $q->where('tag', 'like', "%{$value}%");
                        };
                    });
                });
            })
        ->get();


        $product_ids = [];
        foreach ($products as $product) {
            foreach (json_decode($product['category_ids'], true) as $category) {
                if ($category['id'] == $category_id) {
                    $product_ids[] = $product['id'];
                }
            }
        }

        return Product::with(['rating', 'branch_product'])
            ->with('group')
            ->whereIn('id', $product_ids)
        ->get();
    }

    public static function productsT($category_id, $type, $search)
    {
        $product_type = ($type == 'veg') ? 'veg' : ($type == 'non_veg' ? 'non_veg' : 'all');

        // Fetch all products that match the criteria
        $products = Product::active()
            ->with(['branch_products', 'rating', 'group'])
            // ->whereHas('branch_product.branch', function ($query) {
            //     $query->where('status', 1);
            // })
            // ->branchProductAvailability()
            ->when($product_type != 'all', function ($query) use ($product_type) {
                return $query->where('product_type', $product_type);
            })
            ->when(isset($search), function ($q) use ($search) {
                $key = explode(' ', $search);
                foreach ($key as $value) {
                    $q->where('name', 'like', "%{$value}%");
                }
                $q->orWhereHas('tags', function ($query) use ($key) {
                    $query->where(function ($q) use ($key) {
                        foreach ($key as $value) {
                            $q->where('tag', 'like', "%{$value}%");
                        }
                    });
                });
            })
            ->get();

        // Store products with their positions
        $products_with_positions = [];
        foreach ($products as $product) {
            foreach (json_decode($product['category_ids'], true) as $category) {
                if ($category['id'] == $category_id) {
                    $products_with_positions[] = [
                        'product' => $product,
                        'position' => $category['position'] ?? PHP_INT_MAX, // Default to a high value if position is missing
                    ];
                }
            }
        }

        // Sort products by position
        usort($products_with_positions, function ($a, $b) {
            return $a['position'] <=> $b['position'];
        });

        // Extract the sorted products
        $sorted_products = array_column($products_with_positions, 'product');

        return collect($sorted_products);
    }


    public static function all_products($id)
    {
        $cate_ids=[];
        array_push($cate_ids,(int)$id);
        foreach (CategoryLogic::child($id) as $ch1){
            array_push($cate_ids,$ch1['id']);
            foreach (CategoryLogic::child($ch1['id']) as $ch2){
                array_push($cate_ids,$ch2['id']);
            }
        }

        $products = Product::active()->branchProductAvailability()->get();
        $product_ids = [];
        foreach ($products as $product) {
            foreach (json_decode($product['category_ids'], true) as $category) {
                if (in_array($category['id'],$cate_ids)) {
                    array_push($product_ids, $product['id']);
                }
            }
        }

        return Product::with(['rating','branch_product'])->whereIn('id', $product_ids)->get();
    }
}
