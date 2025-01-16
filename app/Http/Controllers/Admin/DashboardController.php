<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Admin;
use App\Model\Branch;
use App\Model\Category;
use App\Model\Order;
use App\Model\OrderDetail;
use App\Model\Product;
use App\Model\Review;
use App\User;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\Support\Renderable;

class DashboardController extends Controller
{
    public function __construct(
        private Order       $order,
        private OrderDetail $order_detail,
        private Admin       $admin,
        private Review      $review,
        private User        $user,
        private Product     $product,
        private Category    $category,
        private Branch      $branch
    )
    {
    }

    /**
     * @param $id
     * @return string
     */
    public function fcm($id): string
    {
        $fcm_token = $this->admin->find(auth('admin')->id())->fcm_token;
        $data = [
            'title' => 'New auto generate message arrived from admin dashboard',
            'description' => $id,
            'order_id' => '',
            'image' => '',
            'type' => 'order_status',
        ];
        Helpers::send_push_notif_to_device($fcm_token, $data);

        return "Notification sent to admin";
    }

    /**
     * @return Renderable
     */
    public function dashboard()
    {
        // return auth('admin')->user();
        // return dd(config('addon_admin_routes'));

        //update daily stock
        Helpers::update_daily_product_stock();

        $top_sell = $this->order_detail->with(['product'])
            ->whereHas('order', function ($query) {
                $query->where('order_status', 'delivered');
            })
            ->select('product_id', DB::raw('SUM(quantity) as count'))
            ->groupBy('product_id')
            ->orderBy("count", 'desc')
            ->take(6)
        ->get();

        $most_rated_products = $this->review->with(['product'])
            ->select(['product_id',
                DB::raw('AVG(rating) as ratings_average'),
                DB::raw('COUNT(rating) as total'),
            ])
            ->groupBy('product_id')
            ->orderBy("total", 'desc')
            ->take(7)
        ->get();

        $top_customer = $this->order->with(['customer'])
            ->select('user_id', DB::raw('COUNT(user_id) as count'))
            ->groupBy('user_id')
            ->orderBy("count", 'desc')
            ->take(6)
        ->get();

        $data = self::order_stats_data();

        $data['customer'] = $this->user->count();
        $data['product'] = $this->product->count();
        $data['order'] = $this->order->count();
        $data['category'] = $this->category->where('parent_id', 0)->count();
        $data['branch'] = $this->branch->count();

        $data['top_sell'] = $top_sell;
        $data['most_rated_products'] = $most_rated_products;
        $data['top_customer'] = $top_customer;

        $from = Carbon::now()->startOfYear()->format('Y-m-d');
        $to = Carbon::now()->endOfYear()->format('Y-m-d');

        $earning = [];
        $earning_data = $this->order->where([
            'order_status' => 'delivered',
        ])->select(
            DB::raw('IFNULL(sum(order_amount),0) as sums'),
            DB::raw('YEAR(created_at) year, MONTH(created_at) month')
        )
            ->whereBetween('created_at', [Carbon::parse(now())->startOfYear(), Carbon::parse(now())->endOfYear()])
        ->groupby('year', 'month')->get()->toArray();
        
        for ($inc = 1; $inc <= 12; $inc++) {
            $earning[$inc] = 0;
            foreach ($earning_data as $match) {
                if ($match['month'] == $inc) {
                    $earning[$inc] = Helpers::set_price($match['sums']);
                }
            }
        }

        $order_statistics_chart = [];
        $order_statistics_chart_data = $this->order->where(['order_status' => 'delivered'])
            ->select(
                DB::raw('(count(id)) as total'),
                DB::raw('YEAR(created_at) year, MONTH(created_at) month')
            )
            // ->whereBetween('created_at', [$from, $to])
            // ->whereBetween('created_at', [Carbon::parse(now())->startOfYear(), Carbon::parse(now())->endOfYear()])
            ->whereBetween('created_at', [
                Carbon::now()->startOfYear(),
                Carbon::now()->endOfYear()
            ])
            ->groupby('year', 'month')->get()->toArray();

        for ($inc = 1; $inc <= 12; $inc++) {
            $order_statistics_chart[$inc] = 0;
            foreach ($order_statistics_chart_data as $match) {
                if ($match['month'] == $inc) {
                    $order_statistics_chart[$inc] = $match['total'];
                }
            }
        }

        $detailed_products_statistics_chart = [];
        $detailed_products_statistics_chart_data = $this->order->where(['order_status' => 'delivered'])
            ->select(
                DB::raw('(count(orders.id)) as total'),
                DB::raw('YEAR(orders.created_at) year, MONTH(orders.created_at) month')
            )
            ->join('order_details', 'orders.id', '=', 'order_details.order_id')
            ->whereBetween('orders.created_at', [
                Carbon::now()->startOfYear(),
                Carbon::now()->endOfYear()
            ])
        ->groupby('year', 'month')->get()->toArray();

        for ($inc = 1; $inc <= 12; $inc++) {
            $detailed_products_statistics_chart[$inc] = 0;
            foreach ($detailed_products_statistics_chart_data as $match) {
                if ($match['month'] == $inc) {
                    $detailed_products_statistics_chart[$inc] = $match['total'];
                }
            }
        }

        $donut = [];
        $donut_data = $this->order->all();
        $donut['pending'] = $donut_data->where('order_status', 'pending')->count();
        $donut['ongoing'] = $donut_data->whereIn('order_status', ['confirmed', 'processing', 'out_for_delivery'])->count();
        $donut['delivered'] = $donut_data->where('order_status', 'delivered')->count();
        $donut['canceled'] = $donut_data->where('order_status', 'canceled')->count();
        $donut['returned'] = $donut_data->where('order_status', 'returned')->count();
        $donut['failed'] = $donut_data->where('order_status', 'failed')->count();

        $data['recent_orders'] = $this->order->latest()->take(5)->get();

        return view('admin-views.dashboard', compact('data', 'earning', 'order_statistics_chart', 'donut', 'detailed_products_statistics_chart'));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function order_stats(Request $request): JsonResponse
    {
        session()->put('statistics_type', $request['statistics_type']);
        $data = self::order_stats_data();

        return response()->json([
            'view' => view('admin-views.partials._dashboard-order-stats', compact('data'))->render()
        ], 200);
    }

    /**
     * @return array
     */
    public function order_stats_data(): array
    {
        $today = session()->has('statistics_type') && session('statistics_type') == 'today' ? 1 : 0;
        $this_month = session()->has('statistics_type') && session('statistics_type') == 'this_month' ? 1 : 0;

        $pending = $this->order
            ->where(['order_status' => 'pending'])
            ->notSchedule()
            ->when($today, function ($query) {
                return $query->whereDate('created_at', \Carbon\Carbon::today());
            })
            ->when($this_month, function ($query) {
                return $query->whereMonth('created_at', Carbon::now());
            })
            ->count();
        $confirmed = $this->order
            ->where(['order_status' => 'confirmed'])
            ->when($today, function ($query) {
                return $query->whereDate('created_at', Carbon::today());
            })
            ->when($this_month, function ($query) {
                return $query->whereMonth('created_at', Carbon::now());
            })
            ->count();
        $processing = $this->order
            ->where(['order_status' => 'processing'])
            ->when($today, function ($query) {
                return $query->whereDate('created_at', Carbon::today());
            })
            ->when($this_month, function ($query) {
                return $query->whereMonth('created_at', Carbon::now());
            })
            ->count();
        $out_for_delivery = $this->order
            ->where(['order_status' => 'out_for_delivery'])
            ->when($today, function ($query) {
                return $query->whereDate('created_at', Carbon::today());
            })
            ->when($this_month, function ($query) {
                return $query->whereMonth('created_at', Carbon::now());
            })
            ->count();
        $canceled = $this->order
            ->where(['order_status' => 'canceled'])
            ->when($today, function ($query) {
                return $query->whereDate('created_at', Carbon::today());
            })
            ->when($this_month, function ($query) {
                return $query->whereMonth('created_at', Carbon::now());
            })
            ->count();
        $delivered = $this->order
            ->where(['order_status' => 'delivered'])
            ->when($today, function ($query) {
                return $query->whereDate('created_at', Carbon::today());
            })
            ->when($this_month, function ($query) {
                return $query->whereMonth('created_at', Carbon::now());
            })
            ->count();
        $all = $this->order
            ->when($today, function ($query) {
                return $query->whereDate('created_at', Carbon::today());
            })
            ->when($this_month, function ($query) {
                return $query->whereMonth('created_at', Carbon::now());
            })
            ->count();
        $returned = $this->order
            ->where(['order_status' => 'returned'])
            ->when($today, function ($query) {
                return $query->whereDate('created_at', Carbon::today());
            })
            ->when($this_month, function ($query) {
                return $query->whereMonth('created_at', Carbon::now());
            })
            ->count();
        $failed = $this->order
            ->where(['order_status' => 'failed'])
            ->when($today, function ($query) {
                return $query->whereDate('created_at', Carbon::today());
            })
            ->when($this_month, function ($query) {
                return $query->whereMonth('created_at', Carbon::now());
            })
            ->count();

        return [
            'pending' => $pending,
            'confirmed' => $confirmed,
            'processing' => $processing,
            'out_for_delivery' => $out_for_delivery,
            'canceled' => $canceled,
            'delivered' => $delivered,
            'all' => $all,
            'returned' => $returned,
            'failed' => $failed
        ];
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function order_statistics(Request $request): JsonResponse
    {
        $dateType = $request->type;

        $order_data = array();
        if ($dateType == 'yearOrder') {
            $number = 12;
            $from = Carbon::now()->startOfYear()->format('Y-m-d');
            $to = Carbon::now()->endOfYear()->format('Y-m-d');

            $orders = $this->order->where(['order_status' => 'delivered'])
                ->select(
                    DB::raw('(count(id)) as total'),
                    DB::raw('YEAR(created_at) year, MONTH(created_at) month')
                )
                // ->whereBetween('created_at', [$from, $to])
                ->whereBetween('created_at', [Carbon::parse(now())->startOfYear(), Carbon::parse(now())->endOfYear()])
            ->groupby('year', 'month')->get()->toArray();

            for ($inc = 1; $inc <= $number; $inc++) {
                $order_data[$inc] = 0;
                foreach ($orders as $match) {
                    if ($match['month'] == $inc) {
                        $order_data[$inc] = $match['total'];
                    }
                }
            }
            $key_range = array("Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");

        } elseif ($dateType == 'MonthOrder') {
            $from = date('Y-m-01');
            $to = date('Y-m-t');
            $number = date('d', strtotime($to));
            $key_range = range(1, $number);

            $orders = $this->order->where(['order_status' => 'delivered'])
                ->select(
                    DB::raw('(count(id)) as total'),
                    DB::raw('YEAR(created_at) year, MONTH(created_at) month, DAY(created_at) day')
                )
                // ->whereBetween('created_at', [$from, $to])
                ->whereBetween('created_at', [Carbon::parse(now())->startOfYear(), Carbon::parse(now())->endOfYear()])
                ->groupby('created_at')
            ->get()->toArray();

            for ($inc = 1; $inc <= $number; $inc++) {
                $order_data[$inc] = 0;
                foreach ($orders as $match) {
                    if ($match['day'] == $inc) {
                        $order_data[$inc] += $match['total'];
                    }
                }
            }
        } elseif ($dateType == 'WeekOrder') {
            Carbon::setWeekStartsAt(Carbon::SUNDAY);
            Carbon::setWeekEndsAt(Carbon::SATURDAY);

            $from = Carbon::now()->startOfWeek();
            $to = Carbon::now()->endOfWeek();
            $orders = $this->order->where(['order_status' => 'delivered'])
                ->whereBetween('created_at', [$from, $to])->get();

            $date_range = CarbonPeriod::create($from, $to)->toArray();
            $key_range = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
            $order_data = [];
            foreach ($date_range as $date) {

                $order_data[] = $orders->whereBetween('created_at', [$date, Carbon::parse($date)->endOfDay()])->count();
            }
        }

        $label = $key_range;
        $order_data_final = $order_data;

        $data = array(
            'orders_label' => $label,
            'orders' => array_values($order_data_final),
        );
        return response()->json($data);
    }

    public function order_statistics_new(Request $request): JsonResponse
    {
        $dateType = $request->type;
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Initialize variables
        $order_data = array();
        $key_range = [];

        // Check if custom date range is provided
        if ($startDate && $endDate) {
            $from = Carbon::parse($startDate)->startOfDay();
            $to = Carbon::parse($endDate)->endOfDay();
        } elseif ($dateType == 'yearOrder') {
            $from = Carbon::now()->startOfYear();
            $to = Carbon::now()->endOfYear();
        } elseif ($dateType == 'MonthOrder') {
            $from = Carbon::now()->startOfMonth();
            $to = Carbon::now()->endOfMonth();
        } elseif ($dateType == 'WeekOrder') {
            Carbon::setWeekStartsAt(Carbon::SUNDAY);
            Carbon::setWeekEndsAt(Carbon::SATURDAY);
            $from = Carbon::now()->startOfWeek();
            $to = Carbon::now()->endOfWeek();
        } else {
            return response()->json([
                'error' => 'Invalid date type or missing custom dates.'
            ], 400);
        }

        if ($dateType === 'yearOrder' || ($startDate && $endDate)) {
            $number = $from->diffInMonths($to) + 1;
            $orders = $this->order->where(['order_status' => 'delivered'])
                // ->join('order_details', 'orders.id', '=', 'order_details.order_id')
                ->select(
                    DB::raw('(count(orders.id)) as total'),
                    DB::raw('YEAR(orders.created_at) year, MONTH(orders.created_at) month')
                )
                ->whereBetween('orders.created_at', [$from, $to])
                ->groupBy('year', 'month')
                ->get()
            ->toArray();

            for ($inc = 1; $inc <= $number; $inc++) {
                $order_data[$inc] = 0;
                foreach ($orders as $match) {
                    if ($match['month'] == $inc) {
                        $order_data[$inc] = $match['total'];
                    }
                }
            }
            $key_range = array("Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");

        } elseif ($dateType === 'MonthOrder') {
            $number = $from->daysInMonth;
            $key_range = range(1, $number);

            $orders = $this->order->where(['order_status' => 'delivered'])
                ->select(
                    DB::raw('(count(id)) as total'),
                    DB::raw('YEAR(created_at) year, MONTH(created_at) month, DAY(created_at) day')
                )
                ->whereBetween('created_at', [$from, $to])
                ->groupBy('year', 'month', 'day')
                ->get()
                ->toArray();

            for ($inc = 1; $inc <= $number; $inc++) {
                $order_data[$inc] = 0;
                foreach ($orders as $match) {
                    if ($match['day'] == $inc) {
                        $order_data[$inc] += $match['total'];
                    }
                }
            }

        } elseif ($dateType === 'WeekOrder') {
            $date_range = CarbonPeriod::create($from, $to)->toArray();
            $key_range = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');

            $orders = $this->order->where(['order_status' => 'delivered'])
                ->whereBetween('created_at', [$from, $to])
                ->get();

            foreach ($date_range as $date) {
                $order_data[] = $orders->whereBetween('created_at', [$date, Carbon::parse($date)->endOfDay()])->count();
            }
        }

        $data = array(
            'orders_label' => $key_range,
            'orders' => array_values($order_data),
        );

        return response()->json($data);
    }

    public function order_statistics_new2(Request $request): JsonResponse
    {
        $dateType = $request->type;
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Initialize variables
        $order_data = array();
        $key_range = [];

        // Check if custom date range is provided
        if ($startDate && $endDate) {
            $from = Carbon::parse($startDate)->startOfDay();
            $to = Carbon::parse($endDate)->endOfDay();
        } elseif ($dateType == 'yearOrder') {
            $from = Carbon::now()->startOfYear();
            $to = Carbon::now()->endOfYear();
        } elseif ($dateType == 'MonthOrder') {
            $from = Carbon::now()->startOfMonth();
            $to = Carbon::now()->endOfMonth();
        } elseif ($dateType == 'WeekOrder') {
            Carbon::setWeekStartsAt(Carbon::SUNDAY);
            Carbon::setWeekEndsAt(Carbon::SATURDAY);
            $from = Carbon::now()->startOfWeek();
            $to = Carbon::now()->endOfWeek();
        } else {
            return response()->json([
                'error' => 'Invalid date type or missing custom dates - ' . $dateType
            ], 400);
        }

        if ($dateType === 'yearOrder' || ($startDate && $endDate)) {
            $number = $from->diffInMonths($to) + 1;
            $orders = $this->order->where(['order_status' => 'delivered'])
                ->join('order_details', 'orders.id', '=', 'order_details.order_id')
                ->select(
                    DB::raw('(count(orders.id)) as total'),
                    DB::raw('YEAR(orders.created_at) year, MONTH(orders.created_at) month')
                )
                ->whereBetween('orders.created_at', [$from, $to])
                ->groupBy('year', 'month')
                ->get()
            ->toArray();

            for ($inc = 1; $inc <= $number; $inc++) {
                $order_data[$inc] = 0;
                foreach ($orders as $match) {
                    if ($match['month'] == $inc) {
                        $order_data[$inc] = $match['total'];
                    }
                }
            }
            $key_range = array("Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");

        } elseif ($dateType === 'MonthOrder') {
            $number = $from->daysInMonth;
            $key_range = range(1, $number);

            $orders = $this->order->where(['order_status' => 'delivered'])
                ->join('order_details', 'orders.id', '=', 'order_details.order_id')
                ->select(
                    DB::raw('(count(id)) as total'),
                    DB::raw('YEAR(created_at) year, MONTH(created_at) month, DAY(created_at) day')
                )
                ->whereBetween('created_at', [$from, $to])
                ->groupBy('year', 'month', 'day')
            ->get()->toArray();

            for ($inc = 1; $inc <= $number; $inc++) {
                $order_data[$inc] = 0;
                foreach ($orders as $match) {
                    if ($match['day'] == $inc) {
                        $order_data[$inc] += $match['total'];
                    }
                }
            }

        } elseif ($dateType === 'WeekOrder') {
            $date_range = CarbonPeriod::create($from, $to)->toArray();
            $key_range = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');

            $orders = $this->order->where(['order_status' => 'delivered'])
                ->join('order_details', 'orders.id', '=', 'order_details.order_id')
                ->whereBetween('orders.created_at', [$from, $to])
            ->get();

            foreach ($date_range as $date) {
                $order_data[] = $orders->whereBetween('created_at', [$date, Carbon::parse($date)->endOfDay()])->count();
            }
        }

        $data = array(
            'orders_label' => $key_range,
            'orders' => array_values($order_data),
        );

        return response()->json($data);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function earning_statistics(Request $request): JsonResponse
    {
        $dateType = $request->type;

        $earning_data = array();
        if ($dateType == 'yearEarn') {
            $earning = [];
            $earning_data = $this->order->where([
                'order_status' => 'delivered',
            ])->select(
                DB::raw('IFNULL(sum(order_amount),0) as sums'),
                DB::raw('YEAR(created_at) year, MONTH(created_at) month')
            )
                ->whereBetween('created_at', [Carbon::parse(now())->startOfYear(), Carbon::parse(now())->endOfYear()])
                ->groupby('year', 'month')->get()->toArray();

            for ($inc = 1; $inc <= 12; $inc++) {
                $earning[$inc] = 0;
                foreach ($earning_data as $match) {
                    if ($match['month'] == $inc) {
                        $earning[$inc] = Helpers::set_price($match['sums']);
                    }
                }
            }
            $key_range = array("Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");
            $order_data = $earning;
        } elseif ($dateType == 'MonthEarn') {
            $from = date('Y-m-01');
            $to = date('Y-m-t');
            $number = date('d', strtotime($to));
            $key_range = range(1, $number);

            $earning = $this->order->where(['order_status' => 'delivered'])
                ->select(DB::raw('IFNULL(sum(order_amount),0) as sums'), DB::raw('YEAR(created_at) year, MONTH(created_at) month, DAY(created_at) day'))
                ->whereBetween('created_at', [Carbon::parse(now())->startOfMonth(), Carbon::parse(now())->endOfMonth()])
                ->groupby('created_at')
                ->get()
                ->toArray();

            for ($inc = 1; $inc <= $number; $inc++) {
                $earning_data[$inc] = 0;
                foreach ($earning as $match) {
                    if ($match['day'] == $inc) {
                        $earning_data[$inc] += $match['sums'];
                    }
                }
            }

            $order_data = $earning_data;
        } elseif ($dateType == 'WeekEarn') {

            Carbon::setWeekStartsAt(Carbon::SUNDAY);
            Carbon::setWeekEndsAt(Carbon::SATURDAY);

            $from = Carbon::now()->startOfWeek();
            $to = Carbon::now()->endOfWeek();
            $orders = $this->order->where(['order_status' => 'delivered'])->whereBetween('created_at', [$from, $to])->get();

            $date_range = CarbonPeriod::create($from, $to)->toArray();
            $key_range = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
            $order_data = [];
            foreach ($date_range as $date) {
                $order_data[] = $orders->whereBetween('created_at', [$date, Carbon::parse($date)->endOfDay()])->sum('order_amount');
            }
        }

        $label = $key_range;
        $earning_data_final = $order_data;

        $data = array(
            'earning_label' => $label,
            'earning' => array_values($earning_data_final),
        );
        return response()->json($data);
    }
}
