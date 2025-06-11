<?php

namespace App\Http\Controllers;

use App\Models\Product;
// use Illuminate\Support\Facades\Log;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Category;
use App\Models\ProductStore;
use App\Models\ProductSale;
use App\Models\Orderdetail;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{

    // Quản lý sản phẩm (Danh sách active)
    public function index()
    {
        $products = Product::where('product.status', '!=', 0)
            ->join('category', 'product.category_id', '=', 'category.id')
            ->join('brand', 'product.brand_id', '=', 'brand.id')
            ->with('images')
            ->orderBy('product.created_at', 'DESC')
            ->select("product.id", "product.name", "product.status", "category.name as catname", "brand.name as brandname", "product.price", "product.detail", "product.description", "product.created_at", "product.updated_at")
            ->get();
            // ->paginate(8);
            foreach ($products as $product) {
                if ($product->images) {
                    foreach ($product->images as $image) {
                        $image->thumbnail = asset('images/product/' . $image->thumbnail); // Generate  
                    }
                }
            }
        
         $result = [
            'status' => true,
            'message' => 'Tải dữ liệu thành công',
            'products' => $products,
        ];

        return response()->json($result);
    }
 

    // Quản lý sản phẩm (Danh sách trong trash)
    public function trash()
    {
        // Retrieve only trashed products (soft-deleted)
        $trashedProducts = Product::where('product.status', '=', 0)
            ->join('category', 'product.category_id', '=', 'category.id')
            ->join('brand', 'product.brand_id', '=', 'brand.id')
            ->with('images')
            ->orderBy('product.created_at', 'DESC')
            ->select(
                "product.id",
                "product.name",
                "product.status",
                "category.name as catname",
                "brand.name as brandname",
                "product.price",
                "product.detail",
                "product.description",
                "product.created_at",
                "product.updated_at"
            )
            ->get();
    
        // Process each product to generate full URLs for their images
        foreach ($trashedProducts as $product) {
            if ($product->images) {
                foreach ($product->images as $image) {
                    $image->thumbnail = asset('images/product/' . $image->thumbnail); // Generate full URL for image
                }
            }
        }
    
        // Prepare result response
        $result = [
            'status' => true,
            'message' => 'Danh sách sản phẩm trong thùng rác',
            'products' => $trashedProducts,
        ];
    
        // Return the result as JSON
        return response()->json($result);
    }
    

    // Thêm mới sản phẩm
    public function store(StoreProductRequest $request)
    {
 
        $product = new Product();
        $product->category_id = $request->category_id;
        $product->brand_id = $request->brand_id;
        $product->name = $request->name;
        $product->slug = Str::of($request->name)->slug('-');
        $product->detail = $request->detail;
        $product->price = $request->price;
        $product->description = $request->description;
        $product->created_by = 1;
        $product->created_at = date('Y-m-d H:i:s');
        $product->status = $request->status;
    
        if ($product->save()) {
            if ($request->thumbnail) {
                $index = 1;
                foreach ($request->thumbnail as $file) {
                    $productImage = new ProductImage();
                    $productImage->product_id = $product->id;
                    $exten = $file->extension();
                    $imageName = $product->slug . $index. "." . $exten;
                    $file->move(public_path('images/product'), $imageName);
                    $productImage->thumbnail = $imageName;
                    $productImage->save();
                    $index++;
                }
            }
    
            $result = [
                'status' => true,
                'message' => 'Them san pham thanh cong',
                'product' => $product,
            ];
        } else {
            $result = [
                'status' => false,
                'message' => 'Khong them them',
                'product' => null,
            ];
        } 
        return response()->json($result);
    }
    

    // Xem chi tiết sản phẩm
    public function show($id)
    {
        // Retrieve the product with associated category, brand, and images
        $product = Product::where('product.id', $id)
            ->join('category', 'product.category_id', '=', 'category.id')
            ->join('brand', 'product.brand_id', '=', 'brand.id')
            ->with('images') // Include related images
            ->select(
                "product.*",
                "category.name as catname",
                "brand.name as brandname"
            )
            ->first();
        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Sản phẩm không tồn tại',
            ], 404);
        }
        if ($product->images) {
            foreach ($product->images as $image) {
                $image->thumbnail = asset('images/product/' . $image->thumbnail); // Generate full URL
            }
        } 
        // Add the images to the product response
        return response()->json([
            'status' => true,
            'message' => 'Lấy dữ liệu thành công',
            'product' => $product,
        ]);
    }
    public function update(UpdateProductRequest $request, $id)
{
    Log::info('User update request data:', $request->all());
    $product = Product::find($id);

    if (!$product) {
        return response()->json([
            'status' => false,
            'message' => 'Sản phẩm không tồn tại',
        ], 404);
    }

    // Update product fields
    $product->category_id = $request->category_id;
    $product->brand_id = $request->brand_id;
    $product->name = $request->name;
    $product->slug = Str::of($request->name)->slug('-');
    $product->detail = $request->detail;
    $product->price = $request->price;
    $product->description = $request->description;
    $product->updated_at = date('Y-m-d H:i:s');
    $product->status = $request->status;

    if ($product->save()) {
        // Handle new images
        if ($request->thumbnail) {
            $index = 1;
            foreach ($request->thumbnail as $file) {
                $productImage = new ProductImage();
                $productImage->product_id = $product->id;
                $exten = $file->extension();
                $imageName = $product->slug . $index. "." . $exten;
                $file->move(public_path('images/product'), $imageName);
                $productImage->thumbnail = $imageName;
                $productImage->save();
                $index++;
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật sản phẩm thành công',
            'product' => $product,
        ]);
    } else {
        return response()->json([
            'status' => false,
            'message' => 'Cập nhật sản phẩm thất bại',
            'product' => null,
        ]);
    }
}

    // Cập nhật sản phẩm
    // public function update(UpdateProductRequest $request, $id)
    // {
    //     $product = Product::find($id);
    //     if (!$product) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Sản phẩm không tồn tại',
    //         ], 404);
    //     } 
    //     $product->category_id = $request->category_id;
    //     $product->brand_id = $request->brand_id;
    //     $product->name = $request->name;
    //     $product->slug = Str::of($request->name)->slug('-');
    //     $product->detail = $request->detail;
    //     $product->price = $request->price;
    //     $product->description = $request->description;
    //     $product->updated_at = date('Y-m-d H:i:s');
    //     $product->status = $request->status;
    //     if ($product->save()) {
    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Cập nhật sản phẩm thành công',
    //             'product' => $product,
    //         ]);
    //     } else {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Cập nhật sản phẩm thất bại',
    //             'product' => null,
    //         ]);
    //     }
    // }
    


    public function delete($id)
    {
        // Find the product by its ID
        $product = Product::find($id);
        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Sản phẩm không tồn tại',
            ], 404);
        }
        $product->status = 0;
        $product->updated_at = date('Y-m-d H:i:s');
        $product->save();

        return response()->json([
            'status' => true,
            'message' => 'Sản phẩm đã được di chuyển vào thùng rác',
        ]);
    }
    

    // Khôi phục sản phẩm (Restore from Trash)
    public function restore($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Sản phẩm không tồn tại trong thùng rác',
            ], 404);
        }
        $product->status = 2;  
        $product->updated_at = date('Y-m-d H:i:s');
        $product->save();
        return response()->json([
            'status' => true,
            'message' => 'Sản phẩm đã được khôi phục',
        ]);
    }


    // Xóa vĩnh viễn sản phẩm
    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Sản phẩm không tồn tại trong thùng rác',
            ], 404);
        }

        $product->delete();

        return response()->json([
            'status' => true,
            'message' => 'Sản phẩm đã được xóa vĩnh viễn',
        ]);
    }

    public function status(string $id)
    {
        $product = Product::find($id);
        if ($product == null) {
            return response()->json([
                'status' => false,
                'message' => 'The item does not exist.'
            ], 404);
        }
    
        $product->status = ($product->status == 2) ? 1 : 2;
        $product->updated_at = now(); // Use Laravel's helper for current time
        $product->save();
    
        return response()->json([
            'status' => true,
            'message' => 'Product status updated successfully.',
            'product' => $product
        ]);
    }
    public function product_new($limit)
    {
        $subproductstore = ProductStore::select('product_id', DB::raw('SUM(qty) as qty'))
            ->groupBy('product_id');
    
        $products = Product::where('product.status', '=', 1)
            ->joinSub($subproductstore, 'product_store', function ($join) {
                $join->on('product.id', '=', 'product_store.product_id');
            })
            ->leftJoin('product_sale', function ($join) {
                $today = Carbon::now()->format('Y-m-d H:i:s');
                $join->on('product.id', '=', 'product_sale.product_id')
                    ->where([
                        ['product_sale.date_begin', '<=', $today],
                        ['product_sale.date_end', '>=', $today],
                        ['product_sale.status', '=', 1],
                    ]);
            })
            ->with('images')
            ->orderBy('product.created_at', 'DESC')
            ->select("product.id", "product.name", "product.price", "product.slug", "product_sale.price_sale")
            ->limit($limit)
            ->get();
    
        // Generate the image thumbnails
        foreach ($products as $product) {
            if ($product->images) {
                foreach ($product->images as $image) {
                    $image->thumbnail = asset('images/product/' . $image->thumbnail);
                }
            }
        }
    
        $result = [
            'status' => true,
            'message' => 'Tải dữ liệu thành công',
            'products' => $products,
        ];
    
        return response()->json($result);
    }
    
    public function product_sale($limit)
    {
        $subproductstore = ProductStore::select('product_id', DB::raw('SUM(qty) as qty'))
            ->groupBy('product_id');
        
        $products = Product::where('product.status', '=', 1)
            ->joinSub($subproductstore, 'product_store', function ($join) {
                $join->on('product.id', '=', 'product_store.product_id');
            })
            ->join('product_sale', function ($join) {
                $today = Carbon::now()->format('Y-m-d H:i:s');
                $join->on('product.id', '=', 'product_sale.product_id')
                    ->where([
                        ['product_sale.date_begin', '<=', $today],
                        ['product_sale.date_end', '>=', $today],
                        ['product_sale.status', '=', 1],
                    ]);
            })
            ->with('images')  // Eager load images
            ->orderBy('product_sale.price_sale', 'DESC')
            ->select('product.id', 'product.name', 'product.price', 'product.slug', 'product_sale.price_sale')
            ->limit($limit)
            ->get();
    
        // Generate the image thumbnails
        foreach ($products as $product) {
            if ($product->images) {
                foreach ($product->images as $image) {
                    $image->thumbnail = asset('images/product/' . $image->thumbnail);  // Generate thumbnail URL
                }
            }
        }
    
        $result = [
            'status' => true,
            'message' => 'Tải dữ liệu thành công',
            'products' => $products,
        ];
    
        return response()->json($result);
    }
    
    public function product_bestseller($limit)
    {
        $subproductstore = ProductStore::select('product_id', DB::raw('SUM(qty) as qty'))
            ->groupBy('product_id');
        
        $suborderdetail = orderdetail::select('product_id', DB::raw('SUM(qty) as qty'))
            ->groupBy('product_id');
    
        $products = Product::where('product.status', '=', 1)
            ->joinSub($subproductstore, 'product_store', function ($join) {
                $join->on('product.id', '=', 'product_store.product_id');
            })
            ->joinSub($suborderdetail, 'orderdetail', function ($join) {
                $join->on('product.id', '=', 'orderdetail.product_id');
            })
            ->leftJoin('product_sale', function ($join) {
                $today = Carbon::now()->format('Y-m-d H:i:s');
                $join->on('product.id', '=', 'product_sale.product_id')
                    ->where([
                        ['product_sale.date_begin', '<=', $today],
                        ['product_sale.date_end', '>=', $today],
                        ['product_sale.status', '=', 1],
                    ]);
            })
            ->with('images')  // Eager load images
            ->orderBy('orderdetail.qty', 'DESC')
            ->select('product.id', 'product.name', 'product.price', 'product.slug', 'product_sale.price_sale', 'orderdetail.qty')
            ->limit($limit)
            ->get();
    
        // Generate the image thumbnails
        foreach ($products as $product) {
            if ($product->images) {
                foreach ($product->images as $image) {
                    $image->thumbnail = asset('images/product/' . $image->thumbnail);  // Generate thumbnail URL
                }
            }
        }
    
        $result = [
            'status' => true,
            'message' => 'Tải dữ liệu thành công',
            'products' => $products,
        ];
    
        return response()->json($result);
    }
    
    public function product_category_home($limit)
    {
        // Retrieve categories (limited by the parameter)
        $categories = Category::where('status', '=', 1)
            ->limit(3)
            ->get();
    
        if ($categories->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Danh mục không tồn tại',
            ], 404);
        }
    
        $productsByCategory = [];
    
        // For each category, retrieve the products
        foreach ($categories as $category) {
            $products = Product::where('status', '=', 1)
                ->where('category_id', $category->id)
                ->with('images')  // Eager load images
                ->limit($limit)   // Limit the number of products per category
                ->get();
    
            // Process the product images
            foreach ($products as $product) {
                if ($product->images) {
                    foreach ($product->images as $image) {
                        $image->thumbnail = asset('images/product/' . $image->thumbnail);
                    }
                }
            }
    
            // Add products to the array with the category name
            $productsByCategory[] = [
                'category' => $category->name,
                'products' => $products,
            ];
        }
    
        // Return the result as JSON
        return response()->json([
            'status' => true,
            'message' => 'Tải dữ liệu thành công',
            'categories' => $productsByCategory,
        ]);
    }
    
    // public function product_all(Request $request)
    public function product_all(Request $request )
    {
        // Parse multiple category and brand IDs from the request as arrays
        $category_ids = $request->query('category_id') ? explode(',', $request->query('category_id')) : [];
        $brand_ids = $request->query('brand_id') ? explode(',', $request->query('brand_id')) : [];
        $price_min = $request->query('price_min', 0);
        $price_max = $request->query('price_max', 9999999999);
        $sort = $request->query('sort', 'price_asc'); 
 
        $where_arg = [['product.status', '=', 1]];

        // Get list of categories if category_ids are provided
        $list_category_ids = [];
        if (!empty($category_ids)) {
            foreach ($category_ids as $category_id) {
                $list_category_ids = array_merge($list_category_ids, $this->getListCategoryId($category_id));
            }
        }

        // Define subquery for product stock quantities
        $subproductstore = ProductStore::select('product_id', DB::raw('SUM(qty) as qty'))
            ->groupBy('product_id');

        $today = Carbon::now()->format('Y-m-d H:i:s');

        // Main product query with where conditions and joins
        $products_tmp = Product::where($where_arg)
            ->whereBetween('product.price', [(float)$price_min, (float)$price_max])
            ->joinSub($subproductstore, 'product_store', function ($join) {
                $join->on('product.id', '=', 'product_store.product_id');
            })
            ->leftJoin('product_sale', function ($join) use ($today) {
                $join->on('product.id', '=', 'product_sale.product_id')
                    ->where('product_sale.date_begin', '<=', $today)
                    ->where('product_sale.date_end', '>=', $today)
                    ->where('product_sale.status', '=', 1);
            })
            ->with('images')
            ->select(
                'product.id',
                'product.name',
                'product.brand_id',
                'product.category_id',
                'product.price',
                'product.slug',
                'product_sale.price_sale'
            );

        // Apply category filter with whereIn for multiple categories
        if (!empty($list_category_ids)) {
            $products_tmp->whereIn('product.category_id', $list_category_ids);
        }
        if (!empty($brand_ids)) {
            $products_tmp->whereIn('product.brand_id', $brand_ids);
        }
        if ($sort === 'newest') {
            $products_tmp->orderBy('product.created_at', 'DESC');
        } elseif ($sort === 'price_desc') {
            $products_tmp->orderBy('product.price', 'DESC');
        } elseif ($sort === 'price_asc') {
            $products_tmp->orderBy('product.price', 'ASC');
        } elseif ($sort === 'bestseller') {
            // Use subquery to get total quantity sold from orderdetail table and order by it
            $suborderdetail = orderdetail::select('product_id', DB::raw('SUM(qty) as total_sold'))
            ->groupBy('product_id'); 
            // Join the subquery and order by total_sold for bestsellers
            $products_tmp->leftJoinSub($suborderdetail, 'orderdetail', function ($join) {
                $join->on('product.id', '=', 'orderdetail.product_id');
            })
            ->orderBy('orderdetail.total_sold', 'DESC');
        }

        // Paginate results with 8 items per page
        $products = $products_tmp->paginate(6);
        // Process images for each product
        foreach ($products as $product) {
            if ($product->images) {
                foreach ($product->images as $image) {
                    $image->thumbnail = asset('images/product/' . $image->thumbnail);
                }
            }
        }
        // Prepare the response
        $result = [
            'status' => true,
            'message' => 'Data fetched successfully',
            'parameters_received' => [
                'category_ids' => $category_ids,
                'brand_ids' => $brand_ids,
                'price_min' => $price_min,
                'price_max' => $price_max,
                'sort' => $sort,
                'query' => $products_tmp->toSql(),
                'page' => $request->query('page', 1)  
            ],
            'products' => $products // Includes pagination data
        ];

        return response()->json($result);
    }

    function getListCategoryId($category_id)
    {
        // Initialize list with the main category ID
        $list = [$category_id];
    
        // Fetch first level of child categories
        $list_cat1 = Category::where([
            ['status', '=', 1],
            ['parent_id', '=', $category_id]
        ])->get();
    
        // Check if first level categories are found and process each
        if ($list_cat1->isNotEmpty()) {
            foreach ($list_cat1 as $row_cat1) {
                $list[] = $row_cat1->id;
    
                // Fetch second level of child categories
                $list_cat2 = Category::where([
                    ['status', '=', 1],
                    ['parent_id', '=', $row_cat1->id]
                ])->get();
    
                // Check if second level categories are found and add to list
                if ($list_cat2->isNotEmpty()) {
                    foreach ($list_cat2 as $row_cat2) {
                        $list[] = $row_cat2->id;
                    }
                }
            }
        }
    
        // Return the complete list of categories including parent and children
        return $list;
    }

    public function productDetail($slug)
    {
        // Fetch the main product by ID
        $product = Product::with(['images'])
            ->where('slug', $slug)
            ->where('status', 1) // Ensure product is active
            ->first();

        // If product doesn't exist, return an error
        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not foundd.'
            ], 404);
        }

        // Fetch relevant products from the same category
        $relevantProducts = Product::with('images')
            ->where('category_id', $product->category_id)
            ->where('slug', '!=', $slug) // Exclude the main product
            ->where('status', 1) // Only active products
            ->orderBy('created_at', 'DESC')
            ->take(4)  
            ->get();

        // Add thumbnail URLs to each image
        foreach ($product->images as $image) {
            $image->thumbnail = asset('images/product/' . $image->thumbnail);
        }
        
        foreach ($relevantProducts as $relatedProduct) {
            foreach ($relatedProduct->images as $image) {
                $image->thumbnail = asset('images/product/' . $image->thumbnail);
            }
        }

        // Return the main product and relevant products
        return response()->json([
            'status' => true,
            'message' => 'Product details fetched successfully.',
            'product' => $product,
            'relevant_products' => $relevantProducts,
        ]);


    
    } 
    public function getProductsByCategory($slug)
    {
        $category = Category::where([['slug', $slug], ['status', '=', 1]])
        ->select('id', 'name', 'slug')
        ->first();
        $listcatid = [];
        if (!$category) {
            return response()->json(['error' => 'Category not found'], 404);
        }
        if ($category) {
            // Get all category IDs (including subcategories)
            $listcatid = $this->getListCategoryId($category->id);
        }
        $perPage = 6;

        $products = Product::where('status', '!=', 0)
        ->whereIn('category_id', $listcatid)
        ->with('images')
        ->paginate($perPage);

        foreach ($products as $product) {
            if ($product->images) {
                foreach ($product->images as $image) {
                    $image->thumbnail = asset('images/product/' . $image->thumbnail); //   
                }
            }
        }
        return response()->json([
            'category' => $category,
            'products' => $products
        ]);
    }
}
